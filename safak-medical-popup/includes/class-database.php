<?php
/**
 * Safak_Database
 *
 * Handles creation and management of the custom submissions table
 * `wp_safak_form_submissions`. Called on plugin activation.
 *
 * @package Safak_Medical_Popup
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Safak_Database {

    /**
     * Create the custom database table on plugin activation.
     * Uses dbDelta() so it is safe to run multiple times (upgrade-safe).
     */
    public static function create_table(): void {
        global $wpdb;

        $table_name      = $wpdb->prefix . SAFAK_POPUP_TABLE;
        $charset_collate = $wpdb->get_charset_collate();

        /*
         * IMPORTANT: dbDelta() is strict about SQL formatting:
         * – Two spaces before field definitions.
         * – PRIMARY KEY on its own line.
         * – No trailing commas.
         */
        $sql = "CREATE TABLE {$table_name} (
  id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  first_name    VARCHAR(100)        NOT NULL,
  last_name     VARCHAR(100)        NOT NULL,
  phone         VARCHAR(50)         NOT NULL,
  message       TEXT                         DEFAULT '',
  language      VARCHAR(5)          NOT NULL DEFAULT 'en',
  ip_address    VARCHAR(45)                  DEFAULT '',
  submitted_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (id)
) {$charset_collate};";

        // dbDelta() is the WordPress-approved way to create/update tables.
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        // Store the DB version for future migrations.
        update_option( 'safak_popup_db_version', '1.0.0' );
    }

    /**
     * Insert a sanitised submission record.
     *
     * @param array $data {
     *     @type string $first_name
     *     @type string $last_name
     *     @type string $phone
     *     @type string $message
     *     @type string $language   'en' | 'fr' | 'ar'
     *     @type string $ip_address
     * }
     * @return int|false  Inserted row ID, or false on failure.
     */
    public static function insert_submission( array $data ): int|false {
        global $wpdb;

        $table_name = $wpdb->prefix . SAFAK_POPUP_TABLE;

        $inserted = $wpdb->insert(
            $table_name,
            [
                'first_name'   => sanitize_text_field( $data['first_name']   ?? '' ),
                'last_name'    => sanitize_text_field( $data['last_name']    ?? '' ),
                'phone'        => sanitize_text_field( $data['phone']        ?? '' ),
                'message'      => sanitize_textarea_field( $data['message']  ?? '' ),
                'language'     => sanitize_text_field( $data['language']     ?? 'en' ),
                'ip_address'   => sanitize_text_field( $data['ip_address']   ?? '' ),
                'submitted_at' => current_time( 'mysql' ),
            ],
            [ '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]   // Data format.
        );

        return $inserted ? (int) $wpdb->insert_id : false;
    }

    /**
     * Retrieve all submissions (for admin use / future admin page).
     *
     * @param int $limit  Number of rows to fetch. Default 50.
     * @return array
     */
    public static function get_submissions( int $limit = 50 ): array {
        global $wpdb;

        $table_name = $wpdb->prefix . SAFAK_POPUP_TABLE;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} ORDER BY submitted_at DESC LIMIT %d",
                $limit
            ),
            ARRAY_A
        );
    }
}
