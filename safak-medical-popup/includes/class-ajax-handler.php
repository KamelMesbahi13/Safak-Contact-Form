<?php
/**
 * Safak_Ajax_Handler
 *
 * Handles the secure WP-AJAX form submission:
 *  1. Nonce verification.
 *  2. Input sanitisation & validation.
 *  3. Database logging via Safak_Database.
 *  4. Styled HTML email notification to the business.
 *
 * @package Safak_Medical_Popup
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Safak_Ajax_Handler {

    /** Recipient email for all consultation notifications. */
    const NOTIFY_EMAIL = 'kmlmes13@gmail.com';

    /** Register both logged-in and guest AJAX actions. */
    public static function init(): void {
        add_action( 'wp_ajax_safak_submit_form',        [ __CLASS__, 'handle_submission' ] );
        add_action( 'wp_ajax_nopriv_safak_submit_form', [ __CLASS__, 'handle_submission' ] );
    }

    /**
     * Main handler – called by WordPress on AJAX request.
     * Sends JSON response and exits.
     */
    public static function handle_submission(): void {

        // ── 1. Nonce Verification ────────────────────────────────────────────
        if ( ! isset( $_POST['nonce'] ) ||
             ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'safak_popup_nonce' )
        ) {
            wp_send_json_error( [ 'message' => 'Security check failed.' ], 403 );
        }

        // ── 1b. Honeypot Check (Spam Prevention) ──────────────────────────────
        if ( ! empty( $_POST['safak_honeypot'] ) ) {
            wp_send_json_error( [ 'message' => 'Spam detected.' ], 400 );
        }

        // ── 1c. IP-Based Rate Limiting (Transients sliding window) ───────────
        $client_ip     = self::get_client_ip();
        $ip_hash       = md5( $client_ip );
        $transient_key = 'safak_limit_' . $ip_hash;

        $submissions = get_transient( $transient_key );
        if ( ! is_array( $submissions ) ) {
            $submissions = [];
        }

        $current_time = time();
        // Keep only submissions within the last 1 hour
        $submissions = array_filter( $submissions, function( $timestamp ) use ( $current_time ) {
            return $timestamp > ( $current_time - HOUR_IN_SECONDS );
        } );

        if ( count( $submissions ) >= 3 ) {
            wp_send_json_error( [ 'message' => 'Too many requests. Please try again later.' ], 429 );
        }

        // Log the current request timestamp
        $submissions[] = $current_time;
        set_transient( $transient_key, $submissions, HOUR_IN_SECONDS );

        // ── 2. Sanitise & Validate ───────────────────────────────────────────
        $first_name       = sanitize_text_field( wp_unslash( $_POST['first_name']       ?? '' ) );
        $last_name        = sanitize_text_field( wp_unslash( $_POST['last_name']        ?? '' ) );
        $phone            = sanitize_text_field( wp_unslash( $_POST['phone']            ?? '' ) );
        $country_name     = sanitize_text_field( wp_unslash( $_POST['country_name']     ?? '' ) );
        $country_code     = sanitize_text_field( wp_unslash( $_POST['country_code']     ?? '' ) );
        $country_flag     = sanitize_text_field( wp_unslash( $_POST['country_flag']     ?? '' ) );
        $country_flag_iso = sanitize_text_field( wp_unslash( $_POST['country_flag_iso'] ?? '' ) );
        $message          = sanitize_textarea_field( wp_unslash( $_POST['message']      ?? '' ) );
        $language         = sanitize_text_field( wp_unslash( $_POST['language']         ?? 'en' ) );

        // Whitelist language values.
        if ( ! in_array( $language, [ 'en', 'fr', 'ar' ], true ) ) {
            $language = 'en';
        }

        // Required & min character validation to prevent spam (First/Last name: min 2, Phone: min 6, Message: min 5).
        $errors = [];
        if ( empty( $first_name ) || mb_strlen( trim( $first_name ) ) < 2 ) { $errors[] = 'first_name'; }
        if ( empty( $last_name )  || mb_strlen( trim( $last_name ) ) < 2 )  { $errors[] = 'last_name'; }
        if ( empty( $phone )      || mb_strlen( trim( $phone ) ) < 6 )      { $errors[] = 'phone'; }
        if ( empty( $message )    || mb_strlen( trim( $message ) ) < 5 )    { $errors[] = 'message'; }

        // Basic phone sanity check – allow digits, +, -, spaces, parentheses (6 to 25 characters).
        if ( ! empty( $phone ) && ! preg_match( '/^[0-9\+\-\s\(\)]{6,25}$/', $phone ) ) {
            $errors[] = 'phone_format';
        }

        if ( ! empty( $errors ) ) {
            wp_send_json_error( [ 'message' => 'Validation failed.', 'fields' => $errors ], 422 );
        }

        // ── 3. Collect visitor IP (privacy-conscious – last octet masked). ──
        $raw_ip    = $client_ip;
        $ip_masked = self::mask_ip( $raw_ip );

        // Construct full combined phone number for database and default logging
        $full_phone = trim( $country_code . ' ' . $phone );

        // ── 4. Log to Database ───────────────────────────────────────────────
        $row_id = Safak_Database::insert_submission( [
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'phone'      => $full_phone,
            'message'    => $message,
            'language'   => $language,
            'ip_address' => $ip_masked,
        ] );

        // ── 5. Send Email Notification ───────────────────────────────────────
        $email_sent = self::send_notification_email(
            $first_name,
            $last_name,
            $phone,
            $country_name,
            $country_code,
            $country_flag,
            $country_flag_iso,
            $message,
            $language,
            $ip_masked
        );

        // Respond with success even if email fails – DB log is the fail-safe.
        wp_send_json_success( [
            'message'    => 'Submission received.',
            'db_id'      => $row_id,
            'email_sent' => $email_sent,
        ] );
    }

    // ── Private Helpers ──────────────────────────────────────────────────────

    /**
     * Build and send a beautifully styled HTML email to the business inbox.
     */
    private static function send_notification_email(
        string $first_name,
        string $last_name,
        string $phone,
        string $country_name,
        string $country_code,
        string $country_flag,
        string $country_flag_iso,
        string $message,
        string $language,
        string $ip
    ): bool {

        $lang_labels = [
            'en' => 'English',
            'fr' => 'French / Français',
            'ar' => 'Arabic / العربية',
        ];
        $lang_display = $lang_labels[ $language ] ?? 'Unknown';

        // ── 4. Prevent Email Header Injection ────────────────────────────────
        // Explicitly strip carriage return (\r) and newline (\n) characters.
        $first_name_clean = str_replace( [ "\r", "\n" ], '', $first_name );
        $last_name_clean  = str_replace( [ "\r", "\n" ], '', $last_name );

        $subject = sprintf(
            '[Safak Medical] New Consultation Request from %s %s',
            $first_name_clean,
            $last_name_clean
        );

        // ── 3. Defense-in-Depth against XSS (Late Escaping) ──────────────────
        // Wrap dynamic user variables in esc_html() right before outputting.
        $esc_first_name       = esc_html( $first_name );
        $esc_last_name        = esc_html( $last_name );
        $esc_phone            = esc_html( $phone );
        $esc_country_name     = esc_html( $country_name );
        $esc_country_code     = esc_html( $country_code );
        $esc_country_flag_iso = esc_html( $country_flag_iso );
        $esc_message          = nl2br( esc_html( $message ) );
        $esc_ip               = esc_html( $ip );
        $submitted_at         = esc_html( current_time( 'F j, Y – H:i' ) . ' (server time)' );

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>New Consultation Request</title>
</head>
<body style="margin:0;padding:0;background:#F0F4F8;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#F0F4F8;padding:40px 20px;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#FFFFFF;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,85,143,0.12);">

          <!-- Header -->
          <tr>
            <td style="background:linear-gradient(135deg,#00558F 0%,#003d6b 100%);padding:36px 40px;text-align:center;">
              <p style="margin:0 0 8px;color:rgba(255,255,255,0.75);font-size:12px;letter-spacing:2px;text-transform:uppercase;">New Request via safakmedical.com</p>
              <h1 style="margin:0;color:#FFFFFF;font-size:24px;font-weight:700;letter-spacing:-0.5px;">
                Consultation Request
              </h1>
            </td>
          </tr>

          <!-- Alert Banner -->
          <tr>
            <td style="background:#D60A17;padding:10px 40px;text-align:center;">
              <p style="margin:0;color:#FFFFFF;font-size:13px;font-weight:600;letter-spacing:0.5px;">
                ⚡ Action Required — A patient is waiting for your response.
              </p>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:40px;">

              <!-- Patient Details -->
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding-bottom:24px;">
                    <p style="margin:0 0 16px;font-size:13px;font-weight:700;color:#00558F;letter-spacing:1.5px;text-transform:uppercase;border-bottom:2px solid #E8EFF6;padding-bottom:8px;">
                      Patient Information
                    </p>

                    <!-- Details Table -->
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:12px;">
                      <tr>
                        <td width="140" style="font-size:13px;color:#6B7A8D;font-weight:600;vertical-align:top;padding:10px 0;">Full Name</td>
                        <td style="font-size:15px;color:#1A2B3C;font-weight:700;vertical-align:top;padding:10px 0;">
                          {$esc_first_name} {$esc_last_name}
                        </td>
                      </tr>
                      <tr>
                        <td colspan="2" style="height:1px;background:#F0F4F8;"></td>
                      </tr>
                      <tr>
                        <td width="140" style="font-size:13px;color:#6B7A8D;font-weight:600;vertical-align:top;padding:10px 0;">Country</td>
                        <td style="font-size:15px;color:#1A2B3C;font-weight:700;vertical-align:top;padding:10px 0;">
                          <img src="https://flagcdn.com/20x15/{$esc_country_flag_iso}.png" width="20" height="15" alt="" style="display:inline-block;vertical-align:middle;margin-right:6px;border-radius:2px;box-shadow:0 1px 2px rgba(0,0,0,0.15);" /> {$esc_country_name}
                        </td>
                      </tr>
                      <tr>
                        <td colspan="2" style="height:1px;background:#F0F4F8;"></td>
                      </tr>
                      <tr>
                        <td width="140" style="font-size:13px;color:#6B7A8D;font-weight:600;vertical-align:top;padding:10px 0;">Phone Number</td>
                        <td style="font-size:15px;color:#1A2B3C;font-weight:700;vertical-align:top;padding:10px 0;">
                          <a href="tel:{$esc_country_code}{$esc_phone}" style="color:#00558F;text-decoration:none;">{$esc_country_code} {$esc_phone}</a>
                        </td>
                      </tr>
                      <tr>
                        <td colspan="2" style="height:1px;background:#F0F4F8;"></td>
                      </tr>
                      <tr>
                        <td width="140" style="font-size:13px;color:#6B7A8D;font-weight:600;vertical-align:top;padding:10px 0;">Submitted At</td>
                        <td style="font-size:13px;color:#6B7A8D;vertical-align:top;padding:10px 0;">{$submitted_at}</td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <!-- Message -->
                <tr>
                  <td>
                    <p style="margin:0 0 10px;font-size:13px;font-weight:700;color:#00558F;letter-spacing:1.5px;text-transform:uppercase;border-bottom:2px solid #E8EFF6;padding-bottom:8px;">
                      Message / Comment
                    </p>
                    <div style="background:#F8FAFD;border-left:3px solid #00558F;border-radius:0 8px 8px 0;padding:16px 20px;font-size:14px;color:#2C3E50;line-height:1.7;min-height:48px;">
                      {$esc_message}
                    </div>
                  </td>
                </tr>
              </table>

            </td>
          </tr>

          <!-- CTA -->
          <tr>
            <td style="padding:0 40px 40px;text-align:center;">
              <a href="tel:{$esc_country_code}{$esc_phone}"
                 style="display:inline-block;background:#00558F;color:#FFFFFF;text-decoration:none;font-size:14px;font-weight:700;padding:14px 36px;border-radius:8px;letter-spacing:0.5px;">
                Call Patient
              </a>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#F8FAFD;border-top:1px solid #E8EFF6;padding:20px 40px;text-align:center;">
              <p style="margin:0;font-size:12px;color:#9AABBF;">
                This notification was generated automatically by the Safak Medical consultation form plugin.<br>
                IP (masked): {$esc_ip}
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: Safak Medical Website <no-reply@safakmedical.com>',
            'X-Mailer: Safak-Medical-Popup/' . SAFAK_POPUP_VERSION,
        ];

        return wp_mail( self::NOTIFY_EMAIL, $subject, $html, $headers );
    }

    /** Retrieve the client IP from available server variables. */
    private static function get_client_ip(): string {
        // Unless we are strictly verifying Cloudflare headers in a known secure environment,
        // we solely rely on REMOTE_ADDR to prevent attackers from spoofing headers like HTTP_X_FORWARDED_FOR.
        return ! empty( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
    }

    /**
     * Mask the last octet of IPv4 (or last group of IPv6) for privacy.
     * e.g. 192.168.1.45 → 192.168.1.xxx
     */
    private static function mask_ip( string $ip ): string {
        if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
            return preg_replace( '/\.\d+$/', '.xxx', $ip );
        }
        if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
            return preg_replace( '/:[^:]+$/', ':xxxx', $ip );
        }
        return $ip;
    }
}
