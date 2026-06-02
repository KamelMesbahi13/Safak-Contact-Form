<?php
/**
 * Safak_Shortcode
 *
 * Registers [safak_popup_form_button] and [safak_inline_form] shortcodes.
 *
 * @package Safak_Medical_Popup
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Safak_Shortcode {

    /** Prevents duplicate modal HTML on pages with multiple shortcodes. */
    private static bool $modal_rendered = false;

    /** Register shortcodes and footer hook. */
    public static function init(): void {
        add_shortcode( 'safak_popup_form_button', [ __CLASS__, 'render' ] );
        add_shortcode( 'safak_inline_form',        [ __CLASS__, 'render_inline_form' ] );
        add_action( 'wp_footer', [ __CLASS__, 'render_modal_in_footer' ] );
    }

    /**
     * Renders the modal HTML and dynamic translation arrays globally in the footer.
     */
    public static function render_modal_in_footer(): void {
        self::maybe_inline_i18n();
        echo self::get_modal_html();
    }

    /**
     * Shortcode callback for trigger button.
     */
    public static function render( $atts, string $content = '' ): string {
        $atts = shortcode_atts(
            [
                'text'  => 'Get a Free Consultation',
                'class' => '',
                'id'    => 'safak-open-popup',
            ],
            $atts,
            'safak_popup_form_button'
        );

        $button_text  = esc_html( $atts['text'] );
        $extra_class  = sanitize_html_class( $atts['class'] );
        $button_id    = sanitize_html_class( $atts['id'] );

        return <<<HTML
<button
  id="{$button_id}"
  class="safak-popup-trigger {$extra_class}"
  type="button"
  aria-haspopup="dialog"
  aria-controls="safak-popup-overlay"
>
  <span class="safak-btn-icon">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 10.8 19.79 19.79 0 01.22 2.18 2 2 0 012.18 0h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.91 7.91a16 16 0 006.27 6.27l1.27-.5a2 2 0 012.11.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
  </span>
  {$button_text}
</button>
HTML;
    }

    /**
     * Renders the inline form directly on a page.
     * Usage: [safak_inline_form]
     */
    public static function render_inline_form(): string {
        self::maybe_inline_i18n();

        $current_lang = 'en';
        $locale       = get_locale();
        if ( strpos( $locale, 'ar' ) === 0 ) {
            $current_lang = 'ar';
        } elseif ( strpos( $locale, 'fr' ) === 0 ) {
            $current_lang = 'fr';
        }

        return self::get_form_card_html( $current_lang, true );
    }

    /**
     * Returns the reusable form card HTML with inline translations pre-rendered.
     */
    public static function get_form_card_html( string $lang, bool $is_inline = false ): string {
        $translations = self::get_translations();
        $t = $translations[ $lang ] ?? $translations['en'];
        $dir = $t['dir'] ?? 'ltr';

        $first_name_label       = esc_html( $t['label_first_name'] );
        $first_name_placeholder = esc_attr( $t['placeholder_first_name'] );
        $last_name_label        = esc_html( $t['label_last_name'] );
        $last_name_placeholder  = esc_attr( $t['placeholder_last_name'] );
        $phone_label            = esc_html( $t['label_phone'] );
        $message_label          = esc_html( $t['label_message'] );
        $message_placeholder    = esc_attr( $t['placeholder_message'] );
        $btn_submit             = esc_html( $t['btn_submit'] );
        $whatsapp_prompt        = esc_html( $t['whatsapp_prompt'] );
        $btn_whatsapp           = esc_html( $t['btn_whatsapp'] );
        $privacy_disclaimer     = esc_html( $t['privacy_disclaimer'] );
        $success_title          = esc_html( $t['success_title'] );
        $success_msg            = esc_html( $t['success_msg'] );
        $error_title            = esc_html( $t['error_title'] );
        $error_msg              = esc_html( $t['error_msg'] );
        $error_required         = esc_html( $t['error_required'] );
        $placeholder_search     = esc_attr( $t['placeholder_search'] );

        $container_class = $is_inline ? 'safak-form-wrapper-container safak-inline-container' : 'safak-form-wrapper-container';
        $data_dir_attr   = $is_inline ? "data-dir=\"{$dir}\" dir=\"{$dir}\"" : '';

        return <<<HTML
<div class="{$container_class}" data-lang="{$lang}" {$data_dir_attr}>
  <div class="safak-modal__left" style="border:none;padding:0;background:transparent;width:100%;flex:none;position:relative;">
        <form class="safak-consultation-form" novalidate autocomplete="off">

          <!-- Honeypot field to catch automated spam bots -->
          <div style="display:none !important;">
            <input type="text" name="safak_honeypot" value="" autocomplete="off" tabindex="-1" />
          </div>

          <div class="safak-form__row safak-form__row--dual">
            <div class="safak-form__group">
              <label class="safak-form__label">{$first_name_label}</label>
              <input
                type="text"
                name="first_name"
                class="safak-form__input"
                placeholder="{$first_name_placeholder}"
                required
                autocomplete="given-name"
              />
              <span class="safak-form__error" data-field="first_name" data-i18n="error_required" aria-live="polite">{$error_required}</span>
            </div>

            <div class="safak-form__group">
              <label class="safak-form__label">{$last_name_label}</label>
              <input
                type="text"
                name="last_name"
                class="safak-form__input"
                placeholder="{$last_name_placeholder}"
                required
                autocomplete="family-name"
              />
              <span class="safak-form__error" data-field="last_name" data-i18n="error_required" aria-live="polite">{$error_required}</span>
            </div>
          </div>

          <div class="safak-form__group">
            <label class="safak-form__label">{$phone_label}</label>
            <div class="safak-phone-wrapper">
              <div class="safak-country-dropdown-container">
                <button type="button" class="safak-country-btn" aria-haspopup="listbox" aria-expanded="false">
                  <span class="safak-country-selected-flag">
                    <img src="https://flagcdn.com/20x15/dz.png" width="20" height="15" alt="" style="display:inline-block;vertical-align:middle;" />
                  </span>
                  <span class="safak-country-btn-arrow">
                    <svg width="8" height="5" viewBox="0 0 8 5" fill="currentColor" style="display:inline-block;vertical-align:middle;margin-left:4px;"><path d="M0 0h8L4 5z"/></svg>
                  </span>
                </button>
                <div class="safak-country-dropdown" role="listbox" hidden>
                  <div class="safak-country-search-wrapper">
                    <span class="safak-country-search-icon">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;color:#9ca3af;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </span>
                    <input type="text" class="safak-country-search" placeholder="{$placeholder_search}" autocomplete="off" />
                  </div>
                  <div class="safak-country-list">
                    <!-- JS populated -->
                  </div>
                </div>
              </div>
              <input
                type="tel"
                name="phone"
                class="safak-form__input"
                placeholder="+213"
                required
                autocomplete="tel"
              />
            </div>
            <span class="safak-form__error" data-field="phone" data-i18n="error_required" aria-live="polite">{$error_required}</span>
          </div>

          <div class="safak-form__group">
            <label class="safak-form__label">{$message_label}</label>
            <textarea
              name="message"
              class="safak-form__textarea"
              rows="4"
              placeholder="{$message_placeholder}"
              required
            ></textarea>
            <span class="safak-form__error" data-field="message" data-i18n="error_required" aria-live="polite">{$error_required}</span>
          </div>

          <!-- Hidden language field – updated by JS -->
          <input type="hidden" name="language" value="{$lang}" />

          <!-- Submit -->
          <button type="submit" class="safak-form__submit">
            <span class="safak-submit__label" data-i18n="btn_submit">{$btn_submit}</span>
            <span class="safak-submit__spinner" aria-hidden="true"></span>
          </button>

          <!-- WhatsApp Chat Option -->
          <div class="safak-whatsapp-cta">
            <p class="safak-whatsapp-cta__prompt" data-i18n="whatsapp_prompt">{$whatsapp_prompt}</p>
            <a href="https://wa.me/905376917695" target="_blank" rel="noopener noreferrer" class="safak-whatsapp-cta__btn">
              <span class="safak-whatsapp-cta__icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.724-1.457L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.825 1.451 5.436 0 9.86-4.42 9.864-9.864.002-2.637-1.03-5.114-2.905-6.989-1.873-1.873-4.351-2.905-6.986-2.907-5.435 0-9.86 4.42-9.864 9.864-.001 1.716.463 3.39 1.34 4.874l-.994 3.634 3.72-.962zm10.907-7.408c-.29-.146-1.72-.849-1.986-.945-.266-.096-.459-.144-.652.146-.193.29-.748.945-.917 1.139-.169.193-.339.217-.629.072-.29-.146-1.226-.452-2.334-1.441-.862-.77-1.444-1.72-1.613-2.012-.17-.29-.018-.447.127-.592.13-.13.29-.339.435-.509.145-.17.193-.29.29-.483.097-.193.048-.363-.024-.509-.072-.146-.652-1.573-.894-2.152-.236-.569-.475-.492-.652-.501-.17-.008-.363-.01-.556-.01-.193 0-.507.072-.772.363-.266.29-1.014.992-1.014 2.42 0 1.427 1.039 2.807 1.184 3.002.145.193 2.044 3.122 4.952 4.378.692.299 1.232.478 1.653.612.695.221 1.329.19 1.829.115.557-.084 1.72-.702 1.962-1.38.242-.678.242-1.258.17-1.38-.072-.122-.266-.193-.556-.339z"/></svg>
              </span>
              <span>{$btn_whatsapp}</span>
            </a>
          </div>

          <!-- Disclaimer related to Safak Medical's website -->
          <p class="safak-form__disclaimer" data-i18n="privacy_disclaimer">{$privacy_disclaimer}</p>

        </form>

        <!-- Success / Error feedback (relative within Form card) -->
        <div class="safak-feedback safak-feedback--success" hidden role="status">
          <div class="safak-feedback__icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <h3 class="safak-feedback__title" data-i18n="success_title">{$success_title}</h3>
          <p class="safak-feedback__msg" data-i18n="success_msg">{$success_msg}</p>
        </div>

        <div class="safak-feedback safak-feedback--error" hidden role="alert">
          <div class="safak-feedback__icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
          </div>
          <h3 class="safak-feedback__title" data-i18n="error_title">{$error_title}</h3>
          <p class="safak-feedback__msg" data-i18n="error_msg">{$error_msg}</p>
        </div>
  </div>
</div>
HTML;
    }

    /**
     * Returns the full modal HTML structure.
     */
    private static function get_modal_html(): string {
        $assets_url = SAFAK_POPUP_ASSETS;
        $medicalpark_url = esc_url( $assets_url . 'images/Medicalpark.png' );
        $florance_url    = esc_url( $assets_url . 'images/florance.webp' );
        $hospital_url    = esc_url( $assets_url . 'images/hospital.png' );
        $medipol_url     = esc_url( $assets_url . 'images/medipol.png' );
        $memorial_url    = esc_url( $assets_url . 'images/memorial.png' );

        $current_lang = 'en';
        $locale       = get_locale();
        if ( strpos( $locale, 'ar' ) === 0 ) {
            $current_lang = 'ar';
        } elseif ( strpos( $locale, 'fr' ) === 0 ) {
            $current_lang = 'fr';
        }

        $form_card_html = self::get_form_card_html( $current_lang, false );

        return <<<HTML
<div id="safak-popup-overlay" class="safak-overlay" role="dialog" aria-modal="true" aria-labelledby="safak-modal-title" hidden>
  <div class="safak-modal" id="safak-modal">

    <!-- Floating Close Button -->
    <button class="safak-modal__close" id="safak-close-btn" type="button" aria-label="Close modal">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>

    <div class="safak-modal__container">

      <!-- ── Left Column (Form Card) ────────────────────── -->
      <div class="safak-modal__left" style="padding: 44px 40px; border-right: 1px solid var(--safak-border); background: var(--safak-form-bg); flex: 1 1 50%; box-sizing: border-box; display: flex; flex-direction: column; justify-content: center; position: relative;">

        <div class="safak-modal__branding">
          <div class="safak-modal__brand-text">
            <span class="safak-modal__brand-name">Safak Medical</span>
            <span class="safak-modal__brand-tagline" data-i18n="tagline">Medical Consultation & Tourism</span>
          </div>
        </div>

        <div class="safak-modal__controls">
          <!-- Language switcher -->
          <div class="safak-lang-switcher" role="group" aria-label="Language selector">
            <button class="safak-lang-btn active" data-lang="en" type="button">
              <img class="safak-flag-icon" src="https://flagcdn.com/16x12/gb.png" alt="EN" />
              <span>EN</span>
            </button>
            <span class="safak-lang-divider" aria-hidden="true">|</span>
            <button class="safak-lang-btn" data-lang="fr" type="button">
              <img class="safak-flag-icon" src="https://flagcdn.com/16x12/fr.png" alt="FR" />
              <span>FR</span>
            </button>
            <span class="safak-lang-divider" aria-hidden="true">|</span>
            <button class="safak-lang-btn" data-lang="ar" type="button">
              <img class="safak-flag-icon" src="https://flagcdn.com/16x12/sa.png" alt="ع" />
              <span>ع</span>
            </button>
          </div>
        </div>

        {$form_card_html}

      </div>

      <!-- ── Right Column (Medical Trust Info) ─────────── -->
      <div class="safak-modal__right">

        <div class="safak-right__content">
          <span class="safak-badge-pill" data-i18n="pill_text">Safe Treatment in Turkey</span>
          
          <h2 class="safak-right__heading" id="safak-modal-title" data-i18n="main_heading">Let's Start Your Healing Journey in Turkey Together</h2>
          
          <p class="safak-right__subtext" data-i18n="main_subtext">
            Safak Medical is a trusted medical tourism facilitator connecting you with premier A+ accredited hospitals, top-tier Turkish specialists, and dedicated travel support for your complete recovery.
          </p>

          <ul class="safak-right__list">
            <li>
              <span class="safak-list__check" aria-hidden="true">✓</span>
              <span data-i18n="bullet_1">Treatment in JCI-Accredited A+ hospitals in Istanbul.</span>
            </li>
            <li>
              <span class="safak-list__check" aria-hidden="true">✓</span>
              <span data-i18n="bullet_2">Complete coordination: VIP Transfer, luxury hotel, and 24/7 interpreter.</span>
            </li>
            <li>
              <span class="safak-list__check" aria-hidden="true">✓</span>
              <span data-i18n="bullet_3">Free medical file evaluation by specialized Turkish surgeons.</span>
            </li>
          </ul>

          <div class="safak-right__trusted">
            <span class="safak-trusted__title" data-i18n="trusted_by">TRUSTED HOSPITALS & PARTNERS</span>
            <div class="safak-trusted__logos">
              <img src="{$medicalpark_url}" class="safak-trusted__logo-img" alt="Medical Park" />
              <img src="{$florance_url}" class="safak-trusted__logo-img" alt="Florence Nightingale" />
              <img src="{$hospital_url}" class="safak-trusted__logo-img" alt="Hospital Partner" />
              <img src="{$medipol_url}" class="safak-trusted__logo-img" alt="Medipol" />
              <img src="{$memorial_url}" class="safak-trusted__logo-img" alt="Memorial Hospital" />
            </div>
          </div>
        </div>

      </div>

    </div>

  </div>
</div>
HTML;
    }

    /**
     * Shared translation maps.
     */
    public static function get_translations(): array {
        return [
            'en' => [
                'tagline'              => 'Medical Consultation & Tourism',
                'label_first_name'     => 'First Name',
                'label_last_name'      => 'Last Name',
                'label_phone'          => 'Phone Number',
                'label_message'        => 'Your Message',
                'placeholder_first_name' => 'First Name',
                'placeholder_last_name'  => 'Last Name',
                'placeholder_phone'      => 'Enter Your Number..',
                'placeholder_search'     => 'Search country...',
                'placeholder_message'    => 'Type your message...',
                'btn_submit'           => 'Send Message',
                'btn_whatsapp'         => 'Start WhatsApp Chat',
                'whatsapp_prompt'      => 'Have a question or request? Connect with us directly:',
                'error_required'       => 'This field is required.',
                'error_phone_format'   => 'Please enter a valid phone number.',
                'error_min_name'       => 'Minimum 2 characters required.',
                'error_min_phone'      => 'Minimum 6 digits required.',
                'error_min_message'    => 'Minimum 5 characters required.',
                'success_title'        => 'Request Received!',
                'success_msg'          => 'Thank you. Our medical team will contact you within 24 hours.',
                'error_title'          => 'Something went wrong',
                'error_msg'            => 'Please try again or call us directly.',
                'dir'                  => 'ltr',
                'pill_text'            => 'Safe Treatment in Turkey',
                'main_heading'         => 'Let\'s Start Your Healing Journey in Turkey Together',
                'main_subtext'         => 'Safak Medical is a trusted medical tourism facilitator connecting you with premier A+ accredited hospitals, top-tier Turkish specialists, and dedicated travel support for your complete recovery.',
                'bullet_1'             => 'Treatment in JCI-Accredited A+ hospitals in Istanbul.',
                'bullet_2'             => 'Complete coordination: VIP Transfer, luxury hotel, and 24/7 interpreter.',
                'bullet_3'             => 'Free medical file evaluation by specialized Turkish surgeons.',
                'trusted_by'           => 'TRUSTED HOSPITALS & PARTNERS',
                'privacy_disclaimer'   => 'By submitting this form, you agree to Safak Medical\'s Privacy Policy and consent to be contacted about your inquiry via phone or WhatsApp. You can unsubscribe anytime.',
            ],
            'fr' => [
                'tagline'              => 'Consultation Médicale & Tourisme',
                'label_first_name'     => 'Prénom',
                'label_last_name'      => 'Nom',
                'label_phone'          => 'Numéro de Téléphone',
                'label_message'        => 'Votre Message',
                'placeholder_first_name' => 'Prénom',
                'placeholder_last_name'  => 'Nom',
                'placeholder_phone'      => 'Entrez Votre Numéro..',
                'placeholder_search'     => 'Rechercher un pays...',
                'placeholder_message'    => 'Tapez votre message...',
                'btn_submit'           => 'Envoyer le Message',
                'btn_whatsapp'         => 'Discuter sur WhatsApp',
                'whatsapp_prompt'      => 'Vous avez des questions ou des demandes ? Contactez-nous directly :',
                'error_required'       => 'Ce champ est obligatoire.',
                'error_phone_format'   => 'Veuillez entrer un numéro de téléphone valide.',
                'error_min_name'       => 'Minimum 2 caractères requis.',
                'error_min_phone'      => 'Minimum 6 chiffres requis.',
                'error_min_message'    => 'Minimum 5 caractères requis.',
                'success_title'        => 'Demande reçue !',
                'success_msg'          => 'Merci. Notre équipe médicale vous contactera sous 24 heures.',
                'error_title'          => 'Une erreur est survenue',
                'error_msg'            => 'Veuillez réessayer ou appelez-nous directement.',
                'dir'                  => 'ltr',
                'pill_text'            => 'Soins médicaux en Turquie',
                'main_heading'         => 'Commençons votre voyage de guérison en Turquie ensemble',
                'main_subtext'         => 'Safak Medical est un facilitateur de confiance pour votre parcours médical, vous connectant aux meilleurs hôpitaux de classe A+, aux spécialistes renommés et assurant toute la logistique de voyage.',
                'bullet_1'             => 'Soins dans des cliniques accréditées JCI à Istanbul.',
                'bullet_2'             => 'Prise en charge totale : Transfert VIP, hôtel 5* et interprète dédié.',
                'bullet_3'             => 'Analyse et pré-diagnostic gratuits de votre dossier par nos chirurgiens.',
                'trusted_by'           => 'CLINIQUES ET PARTENAIRES AGRÉÉS',
                'privacy_disclaimer'   => 'En soumettant ce formulaire, vous acceptez la politique de confidentialité de Safak Medical et consentez à être contacté au sujet de votre demande par téléphone ou WhatsApp.',
            ],
            'ar' => [
                'tagline'              => 'الاستشارة الطبية والسياحة العلاجية',
                'label_first_name'     => 'الاسم الأول',
                'label_last_name'      => 'اسم العائلة',
                'label_phone'          => 'رقم الهاتف',
                'label_message'        => 'رسالتك',
                'placeholder_first_name' => 'الاسم الأول',
                'placeholder_last_name'  => 'اسم العائلة',
                'placeholder_phone'      => 'أدخل رقم هاتفك..',
                'placeholder_search'     => 'ابحث عن بلد...',
                'placeholder_message'    => 'اكتب رسالتك هنا...',
                'btn_submit'           => 'إرسال الرسالة',
                'btn_whatsapp'         => 'تواصل عبر واتساب',
                'whatsapp_prompt'      => 'هل لديك أي استفسار أو طلب خاص؟ تواصل معنا مباشرة:',
                'error_required'       => 'هذا الحقل مطلوب.',
                'error_phone_format'   => 'يرجى إدخال رقم هاتف صحيح.',
                'error_min_name'       => 'يجب إدخال حرفين على الأقل.',
                'error_min_phone'      => 'يجب إدخال 6 أرقام على الأقل.',
                'error_min_message'    => 'يجب إدخال 5 أحرف على الأقل.',
                'success_title'        => 'تم استلام طلبك!',
                'success_msg'          => 'شكراً لك. سيتواصل معك فريقنا الطبي الاستشاري خلال 24 ساعة.',
                'error_title'          => 'حدث خطأ ما',
                'error_msg'            => 'يرجى المحاولة مرة أخرى أو الاتصال بنا مباشرة.',
                'dir'                  => 'rtl',
                'pill_text'            => 'علاج آمن في تركيا',
                'main_heading'         => 'لنبدأ رحلتك العلاجية في تركيا معاً',
                'main_subtext'         => 'شفق ميديكال هي منصتك المثالية وحليفك الموثوق لتسهيل العلاج في الخارج، حيث نربطك بأفضل مستشفيات تركيا المعتمدة A+ وأمهر الأطباء مع تغطية كاملة لرحلتك.',
                'bullet_1'             => 'العلاج في مستشفيات تركية معتمدة عالمياً (JCI) في إسطنبول.',
                'bullet_2'             => 'باقة متكاملة مريحة: نقل VIP، إقامة فندقية فاخرة، ومترجم خاص 24/7.',
                'bullet_3'             => 'استشارة مجانية وتقييم فوري لملفك الطبي من كبار الجراحين والاستشاريين.',
                'trusted_by'           => 'المستشفيات الشريكة المعتمدة',
                'privacy_disclaimer'   => 'بإرسال هذا النموذج، فإنك توافق على سياسة الخصوصية الخاصة بـ شفق ميديكال وتوافق على التواصل معك بشأن استفسارك عبر الهاتف أو الواتساب.',
            ],
        ];
    }

    /**
     * Inline the i18n JS object once per page load.
     */
    private static function maybe_inline_i18n(): void {
        static $printed = false;
        if ( $printed ) {
            return;
        }
        $printed = true;

        $translations = self::get_translations();
        $json = wp_json_encode( $translations, JSON_UNESCAPED_UNICODE );
        echo "<script>window.SafakI18n=" . $json . ";</script>\n";
    }
}
