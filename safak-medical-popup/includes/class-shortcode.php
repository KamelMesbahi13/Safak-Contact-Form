<?php
/**
 * Safak_Shortcode
 *
 * Registers [safak_popup_form_button] shortcode.
 *
 * Usage:
 *   [safak_popup_form_button text="Get a Free Consultation"]
 *
 * The shortcode renders:
 *  – A trigger button.
 *  – The full modal HTML (injected once per page, no duplicates).
 *
 * All user-facing strings are stored in a PHP array here and passed to JS
 * via wp_localize_script (see class-shortcode.php → enqueue step in main plugin).
 * This keeps translations in one place and avoids JS file edits.
 *
 * @package Safak_Medical_Popup
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Safak_Shortcode {

    /** Prevents duplicate modal HTML on pages with multiple shortcodes. */
    private static bool $modal_rendered = false;

    /** Register the shortcode and footer hook. */
    public static function init(): void {
        add_shortcode( 'safak_popup_form_button', [ __CLASS__, 'render' ] );
        add_action( 'wp_footer', [ __CLASS__, 'render_modal_in_footer' ] );
    }

    /**
     * Renders the modal HTML and dynamic translation arrays globally in the footer
     * so that any button or menu link with "#safak-popup" can trigger the modal.
     */
    public static function render_modal_in_footer(): void {
        self::maybe_inline_i18n();
        echo self::get_modal_html();
    }

    /**
     * Shortcode callback.
     * Renders only the custom trigger button.
     *
     * @param array  $atts    Shortcode attributes.
     * @param string $content Inner content (unused).
     * @return string         HTML output.
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
     * Returns the full modal HTML structure.
     * All visible text is handled by JavaScript using data-i18n attributes.
     */
    private static function get_modal_html(): string {
        $assets_url = SAFAK_POPUP_ASSETS;
        $medicalpark_url = esc_url( $assets_url . 'images/Medicalpark.png' );
        $florance_url    = esc_url( $assets_url . 'images/florance.webp' );
        $hospital_url    = esc_url( $assets_url . 'images/hospital.png' );
        $medipol_url     = esc_url( $assets_url . 'images/medipol.png' );
        $memorial_url    = esc_url( $assets_url . 'images/memorial.png' );

        return <<<HTML
<div id="safak-popup-overlay" class="safak-overlay" role="dialog" aria-modal="true" aria-labelledby="safak-modal-title" hidden>
  <div class="safak-modal" id="safak-modal">

    <!-- Floating Close Button -->
    <button class="safak-modal__close" id="safak-close-btn" type="button" aria-label="Close modal">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>

    <div class="safak-modal__container">

      <!-- ── Left Column (Form Card) ────────────────────── -->
      <div class="safak-modal__left">

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
              <svg class="safak-flag-icon" viewBox="0 0 60 30" width="16" height="12" fill="none"><path d="M0 0h60v30H0z" fill="#012169"/><path d="M0 0l60 30M60 0L0 30" stroke="#fff" stroke-width="6"/><path d="M0 0l60 30M60 0L0 30" stroke="#C8102E" stroke-width="4"/><path d="M30 0v30M0 15h60" stroke="#fff" stroke-width="10"/><path d="M30 0v30M0 15h60" stroke="#C8102E" stroke-width="6"/></svg>
              <span>EN</span>
            </button>
            <span class="safak-lang-divider" aria-hidden="true">|</span>
            <button class="safak-lang-btn" data-lang="fr" type="button">
              <svg class="safak-flag-icon" viewBox="0 0 3 2" width="16" height="12"><rect width="1" height="2" fill="#002395"/><rect x="1" width="1" height="2" fill="#FFF"/><rect x="2" width="1" height="2" fill="#ED2939"/></svg>
              <span>FR</span>
            </button>
            <span class="safak-lang-divider" aria-hidden="true">|</span>
            <button class="safak-lang-btn" data-lang="ar" type="button">
              <svg class="safak-flag-icon" viewBox="0 0 24 16" width="16" height="12"><rect width="24" height="16" fill="#006C35" rx="2"/><path d="M5 12h14" stroke="#fff" stroke-width="1.2" stroke-linecap="round"/><path d="M6 6c1 0 1-2 2-2s1 2 2 2 1-2 2-2 1 2 2 2 1-2 2-2 1 2 2 2" stroke="#fff" stroke-width="1" fill="none"/></svg>
              <span>ع</span>
            </button>
          </div>
        </div>

        <form id="safak-consultation-form" novalidate autocomplete="off">

          <div class="safak-form__row safak-form__row--dual">
            <div class="safak-form__group">
              <label class="safak-form__label" for="safak-first-name" data-i18n="label_first_name">First Name</label>
              <input
                type="text"
                id="safak-first-name"
                name="first_name"
                class="safak-form__input"
                data-i18n-placeholder="placeholder_first_name"
                required
                autocomplete="given-name"
              />
              <span class="safak-form__error" data-field="first_name" data-i18n="error_required" aria-live="polite"></span>
            </div>

            <div class="safak-form__group">
              <label class="safak-form__label" for="safak-last-name" data-i18n="label_last_name">Last Name</label>
              <input
                type="text"
                id="safak-last-name"
                name="last_name"
                class="safak-form__input"
                data-i18n-placeholder="placeholder_last_name"
                required
                autocomplete="family-name"
              />
              <span class="safak-form__error" data-field="last_name" data-i18n="error_required" aria-live="polite"></span>
            </div>
          </div>

          <div class="safak-form__group">
            <label class="safak-form__label" for="safak-phone" data-i18n="label_phone">Phone Number</label>
            <input
              type="tel"
              id="safak-phone"
              name="phone"
              class="safak-form__input"
              data-i18n-placeholder="placeholder_phone"
              required
              autocomplete="tel"
            />
            <span class="safak-form__error" data-field="phone" data-i18n="error_required" aria-live="polite"></span>
          </div>

          <div class="safak-form__group">
            <label class="safak-form__label" for="safak-message" data-i18n="label_message">Your Message</label>
            <textarea
              id="safak-message"
              name="message"
              class="safak-form__textarea"
              rows="4"
              data-i18n-placeholder="placeholder_message"
            ></textarea>
          </div>

          <!-- Hidden language field – updated by JS -->
          <input type="hidden" id="safak-language" name="language" value="en" />

          <!-- Submit -->
          <button type="submit" class="safak-form__submit" id="safak-submit-btn">
            <span class="safak-submit__label" data-i18n="btn_submit">Send Message</span>
            <span class="safak-submit__spinner" aria-hidden="true"></span>
          </button>

          <!-- WhatsApp Chat Option -->
          <div class="safak-whatsapp-cta">
            <p class="safak-whatsapp-cta__prompt" data-i18n="whatsapp_prompt">Have any questions? Connect with us directly on WhatsApp:</p>
            <a href="https://wa.me/905376917695" target="_blank" rel="noopener noreferrer" class="safak-whatsapp-cta__btn">
              <span class="safak-whatsapp-cta__icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.724-1.457L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.825 1.451 5.436 0 9.86-4.42 9.864-9.864.002-2.637-1.03-5.114-2.905-6.989-1.873-1.873-4.351-2.905-6.986-2.907-5.435 0-9.86 4.42-9.864 9.864-.001 1.716.463 3.39 1.34 4.874l-.994 3.634 3.72-.962zm10.907-7.408c-.29-.146-1.72-.849-1.986-.945-.266-.096-.459-.144-.652.146-.193.29-.748.945-.917 1.139-.169.193-.339.217-.629.072-.29-.146-1.226-.452-2.334-1.441-.862-.77-1.444-1.72-1.613-2.012-.17-.29-.018-.447.127-.592.13-.13.29-.339.435-.509.145-.17.193-.29.29-.483.097-.193.048-.363-.024-.509-.072-.146-.652-1.573-.894-2.152-.236-.569-.475-.492-.652-.501-.17-.008-.363-.01-.556-.01-.193 0-.507.072-.772.363-.266.29-1.014.992-1.014 2.42 0 1.427 1.039 2.807 1.184 3.002.145.193 2.044 3.122 4.952 4.378.692.299 1.232.478 1.653.612.695.221 1.329.19 1.829.115.557-.084 1.72-.702 1.962-1.38.242-.678.242-1.258.17-1.38-.072-.122-.266-.193-.556-.339z"/></svg>
              </span>
              <span data-i18n="btn_whatsapp">Start WhatsApp Chat</span>
            </a>
          </div>

          <!-- Disclaimer related to Safak Medical's website -->
          <p class="safak-form__disclaimer" data-i18n="privacy_disclaimer">
            By submitting this form, you agree to Safak Medical's Privacy Policy and consent to be contacted about your inquiry via phone or WhatsApp. You can unsubscribe anytime.
          </p>

        </form>

        <!-- Success / Error feedback (relative within Form card) -->
        <div id="safak-form-success" class="safak-feedback safak-feedback--success" hidden role="status">
          <div class="safak-feedback__icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <h3 class="safak-feedback__title" data-i18n="success_title">Request Received!</h3>
          <p class="safak-feedback__msg" data-i18n="success_msg">Thank you. Our team will contact you shortly.</p>
        </div>

        <div id="safak-form-error" class="safak-feedback safak-feedback--error" hidden role="alert">
          <div class="safak-feedback__icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
          </div>
          <h3 class="safak-feedback__title" data-i18n="error_title">Something went wrong</h3>
          <p class="safak-feedback__msg" data-i18n="error_msg">Please try again or call us directly.</p>
        </div>

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
     * Inline the i18n JS object once per page load.
     * Keeps all translations centralized and easily editable.
     */
    private static function maybe_inline_i18n(): void {
        static $printed = false;
        if ( $printed ) {
            return;
        }
        $printed = true;

        $translations = [

            'en' => [
                'tagline'              => 'Medical Consultation & Tourism',
                'label_first_name'     => 'First Name',
                'label_last_name'      => 'Last Name',
                'label_phone'          => 'Phone Number',
                'label_message'        => 'Your Message',
                'placeholder_first_name' => 'First Name',
                'placeholder_last_name'  => 'Last Name',
                'placeholder_phone'      => 'Enter Your Number..',
                'placeholder_message'    => 'Type your message...',
                'btn_submit'           => 'Send Message',
                'btn_whatsapp'         => 'Start WhatsApp Chat',
                'whatsapp_prompt'      => 'Have a question or request? Connect with us directly:',
                'error_required'       => 'This field is required.',
                'error_phone_format'   => 'Please enter a valid phone number.',
                'success_title'        => 'Request Received!',
                'success_msg'          => 'Thank you. Our medical team will contact you within 24 hours.',
                'error_title'          => 'Something went wrong',
                'error_msg'            => 'Please try again or call us directly.',
                'dir'                  => 'ltr',
                
                // Custom screenshot text fields (English)
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
                'placeholder_message'    => 'Tapez votre message...',
                'btn_submit'           => 'Envoyer le Message',
                'btn_whatsapp'         => 'Discuter sur WhatsApp',
                'whatsapp_prompt'      => 'Vous avez des questions ou des demandes ? Contactez-nous directly :',
                'error_required'       => 'Ce champ est obligatoire.',
                'error_phone_format'   => 'Veuillez entrer un numéro de téléphone valide.',
                'success_title'        => 'Demande reçue !',
                'success_msg'          => 'Merci. Notre équipe médicale vous contactera sous 24 heures.',
                'error_title'          => 'Une erreur est survenue',
                'error_msg'            => 'Veuillez réessayer ou appelez-nous directement.',
                'dir'                  => 'ltr',
                
                // Custom screenshot text fields (French)
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
                'placeholder_message'    => 'اكتب رسالتك هنا...',
                'btn_submit'           => 'إرسال الرسالة',
                'btn_whatsapp'         => 'تواصل عبر واتساب',
                'whatsapp_prompt'      => 'هل لديك أي استفسار أو طلب خاص؟ تواصل معنا مباشرة:',
                'error_required'       => 'هذا الحقل مطلوب.',
                'error_phone_format'   => 'يرجى إدخال رقم هاتف صحيح.',
                'success_title'        => 'تم استلام طلبك!',
                'success_msg'          => 'شكراً لك. سيتواصل معك فريقنا الطبي الاستشاري خلال 24 ساعة.',
                'error_title'          => 'حدث خطأ ما',
                'error_msg'            => 'يرجى المحاولة مرة أخرى أو الاتصال بنا مباشرة.',
                'dir'                  => 'rtl',
                
                // Custom screenshot text fields (Arabic)
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

        $json = wp_json_encode( $translations, JSON_UNESCAPED_UNICODE );
        // phpcs:ignore WordPress.WP.EnqueuedResources
        echo "<script>window.SafakI18n=" . $json . ";</script>\n";
    }
}
