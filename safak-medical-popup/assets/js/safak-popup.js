/**
 * safak-popup.js
 * Safak Medical – Popup Consultation Form
 *
 * Responsibilities:
 *  1. Open / close modal with keyboard & click-outside support.
 *  2. Language switcher – applies i18n strings & RTL layout instantly.
 *  3. Client-side form validation (mirrors server-side rules).
 *  4. Async AJAX submission via Fetch API (no jQuery).
 *  5. Show inline field errors, loading state, and success/error panels.
 *
 * Depends on:
 *  – window.SafakPopup  – localised by wp_localize_script (nonce, ajaxUrl…)
 *  – window.SafakI18n   – inlined by Safak_Shortcode::maybe_inline_i18n()
 *
 * @package Safak_Medical_Popup
 */

( function () {
    'use strict';

    // ── Guard: wait for DOM ──────────────────────────────────────────────────
    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', init );
    } else {
        init();
    }

    // ── State ────────────────────────────────────────────────────────────────
    let currentLang   = ( window.SafakPopup && SafakPopup.currentLang ) ? SafakPopup.currentLang : 'en';
    let isSubmitting  = false;
    let triggerButton = null;   // Element that opened the modal (for focus return).

    // Country Picker State
    let selectedCountryCode = '+213';
    let selectedCountryName = 'Algeria';
    let selectedCountryFlag = '🇩🇿';
    let hasManuallySelectedCountry = false;

    // Standard list of world countries (excl. Israel +972 / IL)
    const COUNTRIES = [
        { name: 'Afghanistan', code: '+93', flag: '🇦🇫' },
        { name: 'Albania', code: '+355', flag: '🇦🇱' },
        { name: 'Algeria', code: '+213', flag: '🇩🇿' },
        { name: 'Andorra', code: '+376', flag: '🇦🇩' },
        { name: 'Angola', code: '+244', flag: '🇦🇴' },
        { name: 'Antigua and Barbuda', code: '+1-268', flag: '🇦🇬' },
        { name: 'Argentina', code: '+54', flag: '🇦🇷' },
        { name: 'Armenia', code: '+374', flag: '🇦🇲' },
        { name: 'Australia', code: '+61', flag: '🇦🇺' },
        { name: 'Austria', code: '+43', flag: '🇦🇹' },
        { name: 'Azerbaijan', code: '+994', flag: '🇦🇿' },
        { name: 'Bahamas', code: '+1-242', flag: '🇧🇸' },
        { name: 'Bahrain', code: '+973', flag: '🇧🇭' },
        { name: 'Bangladesh', code: '+880', flag: '🇧🇩' },
        { name: 'Barbados', code: '+1-246', flag: '🇧🇧' },
        { name: 'Belarus', code: '+375', flag: '🇧🇾' },
        { name: 'Belgium', code: '+32', flag: '🇧🇪' },
        { name: 'Belize', code: '+501', flag: '🇧🇿' },
        { name: 'Bermuda', code: '+1-441', flag: '🇧🇲' },
        { name: 'Benin', code: '+229', flag: '🇧🇯' },
        { name: 'Bhutan', code: '+975', flag: '🇧🇹' },
        { name: 'Bolivia', code: '+591', flag: '🇧🇴' },
        { name: 'Bosnia and Herzegovina', code: '+387', flag: '🇧🇦' },
        { name: 'Botswana', code: '+267', flag: '🇧🇼' },
        { name: 'Brazil', code: '+55', flag: '🇧🇷' },
        { name: 'Brunei', code: '+673', flag: '🇧🇳' },
        { name: 'Bulgaria', code: '+359', flag: '🇧🇬' },
        { name: 'Burkina Faso', code: '+226', flag: '🇧🇫' },
        { name: 'Burundi', code: '+257', flag: '🇧🇮' },
        { name: 'Cabo Verde', code: '+238', flag: '🇨🇻' },
        { name: 'Cambodia', code: '+855', flag: '🇰🇭' },
        { name: 'Cameroon', code: '+237', flag: '🇨🇲' },
        { name: 'Canada', code: '+1', flag: '🇨🇦' },
        { name: 'Central African Republic', code: '+236', flag: '🇨🇫' },
        { name: 'Chad', code: '+235', flag: '🇹🇩' },
        { name: 'Chile', code: '+56', flag: '🇨🇱' },
        { name: 'China', code: '+86', flag: '🇨🇳' },
        { name: 'Colombia', code: '+57', flag: '🇨🇴' },
        { name: 'Comoros', code: '+269', flag: '🇰🇲' },
        { name: 'Congo (Republic)', code: '+242', flag: '🇨🇬' },
        { name: 'Congo (DRC)', code: '+243', flag: '🇨🇩' },
        { name: 'Costa Rica', code: '+506', flag: '🇨🇷' },
        { name: 'Croatia', code: '+385', flag: '🇭🇷' },
        { name: 'Cuba', code: '+53', flag: '🇨🇺' },
        { name: 'Cyprus', code: '+357', flag: '🇨🇾' },
        { name: 'Czechia', code: '+420', flag: '🇨🇿' },
        { name: 'Denmark', code: '+45', flag: '🇩🇰' },
        { name: 'Djibouti', code: '+253', flag: '🇩🇯' },
        { name: 'Dominica', code: '+1-767', flag: '🇩🇲' },
        { name: 'Dominican Republic', code: '+1', flag: '🇩🇴' },
        { name: 'Ecuador', code: '+593', flag: '🇪🇨' },
        { name: 'Egypt', code: '+20', flag: '🇪🇬' },
        { name: 'El Salvador', code: '+503', flag: '🇸🇻' },
        { name: 'Equatorial Guinea', code: '+240', flag: '🇬🇶' },
        { name: 'Eritrea', code: '+291', flag: '🇪🇷' },
        { name: 'Estonia', code: '+372', flag: '🇪🇪' },
        { name: 'Eswatini', code: '+268', flag: '🇸🇿' },
        { name: 'Ethiopia', code: '+251', flag: '🇪🇹' },
        { name: 'Fiji', code: '+679', flag: '🇫🇯' },
        { name: 'Finland', code: '+358', flag: '🇫🇮' },
        { name: 'France', code: '+33', flag: '🇫🇷' },
        { name: 'French Guiana', code: '+594', flag: '🇬🇫' },
        { name: 'French Polynesia', code: '+689', flag: '🇵🇫' },
        { name: 'Gabon', code: '+241', flag: '🇬🇦' },
        { name: 'Gambia', code: '+220', flag: '🇬🇲' },
        { name: 'Georgia', code: '+995', flag: '🇬🇪' },
        { name: 'Germany', code: '+49', flag: '🇩🇪' },
        { name: 'Ghana', code: '+233', flag: '🇬🇭' },
        { name: 'Greece', code: '+30', flag: '🇬🇷' },
        { name: 'Greenland', code: '+299', flag: '🇬🇱' },
        { name: 'Grenada', code: '+1-473', flag: '🇬🇩' },
        { name: 'Guadeloupe', code: '+590', flag: '🇬🇵' },
        { name: 'Guatemala', code: '+502', flag: '🇬🇹' },
        { name: 'Guinea', code: '+224', flag: '🇬🇳' },
        { name: 'Guinea-Bissau', code: '+245', flag: '🇬🇼' },
        { name: 'Guyana', code: '+592', flag: '🇬🇾' },
        { name: 'Haiti', code: '+509', flag: '🇭🇹' },
        { name: 'Honduras', code: '+504', flag: '🇭🇳' },
        { name: 'Hong Kong', code: '+852', flag: '🇭🇰' },
        { name: 'Hungary', code: '+36', flag: '🇭🇺' },
        { name: 'Iceland', code: '+354', flag: '🇮🇸' },
        { name: 'India', code: '+91', flag: '🇮🇳' },
        { name: 'Indonesia', code: '+62', flag: '🇮🇩' },
        { name: 'Iran', code: '+98', flag: '🇮🇷' },
        { name: 'Iraq', code: '+964', flag: '🇮🇶' },
        { name: 'Ireland', code: '+353', flag: '🇮🇪' },
        { name: 'Italy', code: '+39', flag: '🇮🇹' },
        { name: 'Ivory Coast', code: '+225', flag: '🇨🇮' },
        { name: 'Jamaica', code: '+1-876', flag: '🇯🇲' },
        { name: 'Japan', code: '+81', flag: '🇯🇵' },
        { name: 'Jordan', code: '+962', flag: '🇯🇴' },
        { name: 'Kazakhstan', code: '+7', flag: '🇰🇿' },
        { name: 'Kenya', code: '+254', flag: '🇰🇪' },
        { name: 'Kiribati', code: '+686', flag: '🇰🇮' },
        { name: 'Kuwait', code: '+965', flag: '🇰🇼' },
        { name: 'Kyrgyzstan', code: '+996', flag: '🇰🇬' },
        { name: 'Laos', code: '+856', flag: '🇱🇦' },
        { name: 'Latvia', code: '+371', flag: '🇱🇻' },
        { name: 'Lebanon', code: '+961', flag: '🇱🇧' },
        { name: 'Lesotho', code: '+266', flag: '🇱🇸' },
        { name: 'Liberia', code: '+231', flag: '🇱🇷' },
        { name: 'Libya', code: '+218', flag: '🇱🇾' },
        { name: 'Liechtenstein', code: '+423', flag: '🇱🇮' },
        { name: 'Lithuania', code: '+370', flag: '🇱🇹' },
        { name: 'Luxembourg', code: '+352', flag: '🇱🇺' },
        { name: 'Macau', code: '+853', flag: '🇲🇴' },
        { name: 'Madagascar', code: '+261', flag: '🇲🇬' },
        { name: 'Malawi', code: '+265', flag: '🇲🇼' },
        { name: 'Malaysia', code: '+60', flag: '🇲🇾' },
        { name: 'Maldives', code: '+960', flag: '🇲🇻' },
        { name: 'Mali', code: '+223', flag: '🇲🇱' },
        { name: 'Malta', code: '+356', flag: '🇲🇹' },
        { name: 'Marshall Islands', code: '+692', flag: '🇲🇭' },
        { name: 'Martinique', code: '+596', flag: '🇲🇶' },
        { name: 'Mauritania', code: '+222', flag: '🇲🇷' },
        { name: 'Mauritius', code: '+230', flag: '🇲🇺' },
        { name: 'Mexico', code: '+52', flag: '🇲🇽' },
        { name: 'Micronesia', code: '+691', flag: '🇫🇲' },
        { name: 'Moldova', code: '+373', flag: '🇲🇩' },
        { name: 'Monaco', code: '+377', flag: '🇲🇨' },
        { name: 'Mongolia', code: '+976', flag: '🇲🇳' },
        { name: 'Montenegro', code: '+382', flag: '🇲🇪' },
        { name: 'Morocco', code: '+212', flag: '🇲🇦' },
        { name: 'Mozambique', code: '+258', flag: '🇲🇿' },
        { name: 'Myanmar', code: '+95', flag: '🇲🇲' },
        { name: 'Namibia', code: '+264', flag: '🇳🇦' },
        { name: 'Nauru', code: '+674', flag: '🇳🇷' },
        { name: 'Nepal', code: '+977', flag: '🇳🇵' },
        { name: 'Netherlands', code: '+31', flag: '🇳🇱' },
        { name: 'New Caledonia', code: '+687', flag: '🇳🇨' },
        { name: 'New Zealand', code: '+64', flag: '🇳🇿' },
        { name: 'Nicaragua', code: '+505', flag: '🇳🇮' },
        { name: 'Niger', code: '+227', flag: '🇳🇪' },
        { name: 'Nigeria', code: '+234', flag: '🇳🇬' },
        { name: 'North Korea', code: '+850', flag: '🇰🇵' },
        { name: 'North Macedonia', code: '+389', flag: '🇲🇰' },
        { name: 'Norway', code: '+47', flag: '🇳🇴' },
        { name: 'Oman', code: '+968', flag: '🇴🇲' },
        { name: 'Pakistan', code: '+92', flag: '🇵🇰' },
        { name: 'Palau', code: '+680', flag: '🇵🇼' },
        { name: 'Palestine', code: '+970', flag: '🇵🇸' },
        { name: 'Panama', code: '+507', flag: '🇵🇦' },
        { name: 'Papua New Guinea', code: '+675', flag: '🇵🇬' },
        { name: 'Paraguay', code: '+595', flag: '🇵🇾' },
        { name: 'Peru', code: '+51', flag: '🇵🇪' },
        { name: 'Philippines', code: '+63', flag: '🇵🇭' },
        { name: 'Poland', code: '+48', flag: '🇵🇱' },
        { name: 'Portugal', code: '+351', flag: '🇵🇹' },
        { name: 'Puerto Rico', code: '+1-787', flag: '🇵🇷' },
        { name: 'Qatar', code: '+974', flag: '🇶🇦' },
        { name: 'Reunion', code: '+262', flag: '🇷🇪' },
        { name: 'Romania', code: '+40', flag: '🇷🇴' },
        { name: 'Russia', code: '+7', flag: '🇷🇺' },
        { name: 'Rwanda', code: '+250', flag: '🇷🇼' },
        { name: 'Saint Kitts and Nevis', code: '+1-869', flag: '🇰🇳' },
        { name: 'Saint Lucia', code: '+1-758', flag: '🇱🇨' },
        { name: 'Saint Vincent and Grenadines', code: '+1-784', flag: '🇻🇨' },
        { name: 'Samoa', code: '+685', flag: '🇼🇸' },
        { name: 'San Marino', code: '+378', flag: '🇸🇲' },
        { name: 'Sao Tome and Principe', code: '+239', flag: '🇸🇹' },
        { name: 'Saudi Arabia', code: '+966', flag: '🇸🇦' },
        { name: 'Senegal', code: '+221', flag: '🇸🇳' },
        { name: 'Serbia', code: '+381', flag: '🇷🇸' },
        { name: 'Seychelles', code: '+248', flag: '🇸🇨' },
        { name: 'Sierra Leone', code: '+232', flag: '🇸🇱' },
        { name: 'Singapore', code: '+65', flag: '🇸🇬' },
        { name: 'Slovakia', code: '+421', flag: '🇸🇰' },
        { name: 'Slovenia', code: '+386', flag: '🇸🇮' },
        { name: 'Solomon Islands', code: '+677', flag: '🇸🇧' },
        { name: 'Somalia', code: '+252', flag: '🇸🇴' },
        { name: 'South Africa', code: '+27', flag: '🇿🇦' },
        { name: 'South Korea', code: '+82', flag: '🇰🇷' },
        { name: 'South Sudan', code: '+211', flag: '🇸🇸' },
        { name: 'Spain', code: '+34', flag: '🇪🇸' },
        { name: 'Sri Lanka', code: '+94', flag: '🇱🇰' },
        { name: 'Sudan', code: '+249', flag: '🇸🇩' },
        { name: 'Suriname', code: '+597', flag: '🇸🇷' },
        { name: 'Sweden', code: '+46', flag: '🇸🇪' },
        { name: 'Switzerland', code: '+41', flag: '🇨🇭' },
        { name: 'Syria', code: '+963', flag: '🇸🇾' },
        { name: 'Taiwan', code: '+886', flag: '🇹🇼' },
        { name: 'Tajikistan', code: '+992', flag: '🇹🇯' },
        { name: 'Tanzania', code: '+255', flag: '🇹🇿' },
        { name: 'Thailand', code: '+66', flag: '🇹🇭' },
        { name: 'Timor-Leste', code: '+670', flag: '🇹🇱' },
        { name: 'Togo', code: '+228', flag: '🇹🇬' },
        { name: 'Tonga', code: '+676', flag: '🇹🇴' },
        { name: 'Trinidad and Tobago', code: '+1-868', flag: '🇹🇹' },
        { name: 'Tunisia', code: '+216', flag: '🇹🇳' },
        { name: 'Turkey', code: '+90', flag: '🇹🇷' },
        { name: 'Turkmenistan', code: '+993', flag: '🇹🇲' },
        { name: 'Tuvalu', code: '+688', flag: '🇹🇻' },
        { name: 'Uganda', code: '+256', flag: '🇺🇬' },
        { name: 'Ukraine', code: '+380', flag: '🇺🇦' },
        { name: 'United Arab Emirates', code: '+971', flag: '🇦🇪' },
        { name: 'United Kingdom', code: '+44', flag: '🇬🇧' },
        { name: 'United States', code: '+1', flag: '🇺🇸' },
        { name: 'Uruguay', code: '+598', flag: '🇺🇾' },
        { name: 'Uzbekistan', code: '+998', flag: '🇺🇿' },
        { name: 'Vanuatu', code: '+678', flag: '🇻🇺' },
        { name: 'Vatican City', code: '+39', flag: '🇻🇦' },
        { name: 'Venezuela', code: '+58', flag: '🇻🇪' },
        { name: 'Vietnam', code: '+84', flag: '🇻🇳' },
        { name: 'Yemen', code: '+967', flag: '🇾🇪' },
        { name: 'Zambia', code: '+260', flag: '🇿🇲' },
        { name: 'Zimbabwe', code: '+263', flag: '🇿🇼' }
    ];

    // ── DOM References (resolved after init) ─────────────────────────────────
    let overlay, modal, form, closeBtn, submitBtn, langBtns,
        logoEl, successPanel, errorPanel, hiddenLangInput;

    // Country DOM References
    let countryToggleBtn, countryMenu, countrySearchInput, countryListContainer;

    // ────────────────────────────────────────────────────────────────────────
    function init() {

        overlay          = document.getElementById( 'safak-popup-overlay' );
        modal            = document.getElementById( 'safak-modal' );
        form             = document.getElementById( 'safak-consultation-form' );
        closeBtn         = document.getElementById( 'safak-close-btn' );
        submitBtn        = document.getElementById( 'safak-submit-btn' );
        logoEl           = document.getElementById( 'safak-logo' );
        successPanel     = document.getElementById( 'safak-form-success' );
        errorPanel       = document.getElementById( 'safak-form-error' );
        hiddenLangInput  = document.getElementById( 'safak-language' );
        langBtns         = document.querySelectorAll( '.safak-lang-btn' );

        countryToggleBtn     = document.getElementById( 'safak-country-toggle' );
        countryMenu          = document.getElementById( 'safak-country-menu' );
        countrySearchInput   = document.getElementById( 'safak-country-search' );
        countryListContainer = document.getElementById( 'safak-country-list' );

        if ( ! overlay || ! modal || ! form ) {
            // Modal HTML not on this page – nothing to initialise.
            return;
        }

        // Set logo src from PHP-passed URL.
        if ( logoEl && window.SafakPopup && SafakPopup.logoUrl ) {
            logoEl.src = SafakPopup.logoUrl;
        }

        // Populate country dropdown options
        populateCountries();

        // Set default selected country dynamically based on active language
        setDefaultCountryForLang( currentLang );

        // Apply default language strings.
        applyLanguage( currentLang );

        // ── Country Picker Listeners ─────────────────────────────────────────
        if ( countryToggleBtn ) {
            countryToggleBtn.addEventListener( 'click', function ( e ) {
                e.stopPropagation();
                toggleCountryMenu();
            } );
        }

        if ( countrySearchInput ) {
            countrySearchInput.addEventListener( 'input', function () {
                filterCountries( this.value );
            } );
            countrySearchInput.addEventListener( 'click', function ( e ) {
                e.stopPropagation();
            } );
        }

        // Hide dropdown on outside click
        document.addEventListener( 'click', function ( e ) {
            if ( countryMenu && ! countryMenu.hidden ) {
                if ( ! countryMenu.contains( e.target ) && ! countryToggleBtn.contains( e.target ) ) {
                    closeCountryMenu();
                }
            }
        } );

        // ── Event Listeners ──────────────────────────────────────────────────

        // Robust Event Delegation: Listen for clicks on any element with .safak-popup-trigger,
        // ID safak-open-popup, or anchor tags linking to #safak-popup (supporting subfolders).
        document.addEventListener( 'click', function ( e ) {
            const trigger = e.target.closest( '.safak-popup-trigger, #safak-open-popup, a[href$="#safak-popup"]' );
            if ( trigger ) {
                e.preventDefault();
                openModal( trigger );
            }
        } );

        // Close via X button.
        if ( closeBtn ) {
            closeBtn.addEventListener( 'click', closeModal );
        }

        // Close on backdrop click (not modal itself).
        overlay.addEventListener( 'click', function ( e ) {
            if ( e.target === overlay ) closeModal();
        } );

        // Close on Escape key.
        document.addEventListener( 'keydown', function ( e ) {
            if ( e.key === 'Escape' && ! overlay.hidden ) closeModal();
        } );

        // Trap Tab focus inside modal when open.
        modal.addEventListener( 'keydown', trapFocus );

        // Language switcher.
        langBtns.forEach( btn => {
            btn.addEventListener( 'click', function () {
                const lang = this.dataset.lang;
                if ( lang && lang !== currentLang ) {
                    applyLanguage( lang );
                }
            } );
        } );

        // Form submission.
        form.addEventListener( 'submit', handleSubmit );

        // Real-time error clearing on input.
        form.querySelectorAll( '.safak-form__input, .safak-form__textarea' ).forEach( el => {
            el.addEventListener( 'input', function () {
                clearFieldError( this );
            } );
        } );

        // Expose a public developer API globally
        window.SafakPopupAPI = {
            open: function () {
                openModal( null );
            },
            close: function () {
                closeModal();
            },
            setLanguage: function ( lang ) {
                if ( [ 'en', 'fr', 'ar' ].includes( lang ) ) {
                    applyLanguage( lang );
                }
            }
        };

        // ── Hash-based trigger ────────────────────────────────────────────────
        // If the page loaded with #safak-popup in the URL (e.g. from a
        // multilingual menu link like /fr/accueil/#safak-popup), open the modal
        // immediately and silently clean the hash from the address bar.
        function checkAndOpenFromHash() {
            if ( window.location.hash === '#safak-popup' ) {
                // Clean the URL without reloading the page.
                if ( window.history && window.history.replaceState ) {
                    window.history.replaceState(
                        null,
                        document.title,
                        window.location.pathname + window.location.search
                    );
                }
                openModal( null );
            }
        }

        // Check on initial load.
        checkAndOpenFromHash();

        // Also listen for hash changes (SPA / Elementor smooth scroll scenarios).
        window.addEventListener( 'hashchange', checkAndOpenFromHash );
    }

    // ── Modal Open / Close ───────────────────────────────────────────────────

    function openModal( trigger ) {
        if ( trigger instanceof HTMLElement ) {
            triggerButton = trigger;
        } else if ( trigger && trigger.currentTarget ) {
            triggerButton = trigger.currentTarget;
        }

        // Reset state: hide feedback, show form.
        showForm();

        // Remove hidden attribute then trigger CSS transition.
        overlay.hidden = false;
        // Force reflow so transition fires.
        void overlay.offsetWidth;
        overlay.classList.add( 'is-visible' );

        // Move focus to close button (accessible).
        if ( closeBtn ) {
            closeBtn.focus();
        }

        // Prevent page scroll.
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        overlay.classList.remove( 'is-visible' );

        // Safe cleanup function to run once transition is done or as a fallback
        let cleanedUp = false;
        const cleanup = () => {
            if ( cleanedUp ) return;
            cleanedUp = true;
            overlay.hidden = true;
            document.body.style.overflow = '';
            if ( triggerButton ) {
                triggerButton.focus();
                triggerButton = null;
            }
        };

        // Wait for transition to finish, with a 250ms fallback safety timer
        overlay.addEventListener( 'transitionend', cleanup, { once: true } );
        setTimeout( cleanup, 250 );
    }

    // ── Language Switching ───────────────────────────────────────────────────

    /**
     * Apply the selected language to all data-i18n elements and placeholders.
     * Also sets dir attribute for RTL support.
     *
     * @param {string} lang  'en' | 'fr' | 'ar'
     */
    function applyLanguage( lang ) {
        const i18n = ( window.SafakI18n || {} )[ lang ];
        if ( ! i18n ) return;

        currentLang = lang;

        // Update hidden language field.
        if ( hiddenLangInput ) hiddenLangInput.value = lang;

        // ── Text content ──────────────────────────────────────────────────
        modal.querySelectorAll( '[data-i18n]' ).forEach( el => {
            const key = el.dataset.i18n;
            if ( i18n[ key ] !== undefined ) {
                el.textContent = i18n[ key ];
            }
        } );

        // ── Placeholders ──────────────────────────────────────────────────
        modal.querySelectorAll( '[data-i18n-placeholder]' ).forEach( el => {
            const key = el.dataset.i18nPlaceholder;
            if ( i18n[ key ] !== undefined ) {
                el.placeholder = i18n[ key ];
            }
        } );

        // ── RTL / LTR layout ──────────────────────────────────────────────
        const isRTL = i18n.dir === 'rtl';
        modal.setAttribute( 'data-dir', isRTL ? 'rtl' : 'ltr' );
        modal.setAttribute( 'dir', isRTL ? 'rtl' : 'ltr' );

        // Apply dir to individual inputs for correct cursor behaviour.
        form.querySelectorAll( 'input, textarea' ).forEach( el => {
            if ( el.id === 'safak-phone' ) {
                el.dir = 'ltr';
            } else {
                el.dir = isRTL ? 'rtl' : 'ltr';
            }
        } );

        // Update default country selector when language switches (only if user hasn't manually selected yet)
        if ( ! hasManuallySelectedCountry ) {
            setDefaultCountryForLang( lang );
        }

        // ── Active button styling ─────────────────────────────────────────
        langBtns.forEach( btn => {
            btn.classList.toggle( 'active', btn.dataset.lang === lang );
        } );

        // ── aria-label for close (accessibility, translated) ──────────────
        if ( closeBtn ) {
            const closeLabels = { en: 'Close', fr: 'Fermer', ar: 'إغلاق' };
            closeBtn.setAttribute( 'aria-label', closeLabels[ lang ] || 'Close' );
        }

        // Re-apply any visible error messages in the new language.
        refreshVisibleErrors( i18n );
    }

    /**
     * After a language switch, refresh error text on fields that are
     * already showing errors so they display in the new language.
     */
    function refreshVisibleErrors( i18n ) {
        modal.querySelectorAll( '.safak-form__error.is-visible' ).forEach( el => {
            const key = el.dataset.i18n;
            if ( key && i18n[ key ] !== undefined ) {
                el.textContent = i18n[ key ];
            }
        } );
    }

    // ── Validation ───────────────────────────────────────────────────────────

    /**
     * Validate all required fields.
     * Returns true if valid, false otherwise (and shows inline errors).
     */
    function validateForm() {
        const i18n    = ( window.SafakI18n || {} )[ currentLang ] || {};
        let   isValid = true;

        // 1. First Name Validation (Required, Min 2 chars)
        const firstNameInput = document.getElementById( 'safak-first-name' );
        if ( firstNameInput ) {
            const val = firstNameInput.value.trim();
            if ( val === '' ) {
                showFieldError( firstNameInput, i18n.error_required || 'This field is required.' );
                isValid = false;
            } else if ( val.length < 2 ) {
                showFieldError( firstNameInput, i18n.error_min_name || 'Minimum 2 characters required.' );
                isValid = false;
            }
        }

        // 2. Last Name Validation (Required, Min 2 chars)
        const lastNameInput = document.getElementById( 'safak-last-name' );
        if ( lastNameInput ) {
            const val = lastNameInput.value.trim();
            if ( val === '' ) {
                showFieldError( lastNameInput, i18n.error_required || 'This field is required.' );
                isValid = false;
            } else if ( val.length < 2 ) {
                showFieldError( lastNameInput, i18n.error_min_name || 'Minimum 2 characters required.' );
                isValid = false;
            }
        }

        // 3. Phone Number Validation (Required, Min 6 digits, Regex check)
        const phoneInput = document.getElementById( 'safak-phone' );
        if ( phoneInput ) {
            const val = phoneInput.value.trim();
            if ( val === '' ) {
                showFieldError( phoneInput, i18n.error_required || 'This field is required.' );
                isValid = false;
            } else if ( val.length < 6 ) {
                showFieldError( phoneInput, i18n.error_min_phone || 'Minimum 6 digits required.' );
                isValid = false;
            } else {
                const phoneRegex = /^[0-9\+\-\s\(\)]{6,25}$/;
                if ( ! phoneRegex.test( val ) ) {
                    showFieldError( phoneInput, i18n.error_phone_format || 'Please enter a valid phone number.' );
                    isValid = false;
                }
            }
        }

        // 4. Message Validation (Required, Min 5 chars)
        const messageInput = document.getElementById( 'safak-message' );
        if ( messageInput ) {
            const val = messageInput.value.trim();
            if ( val === '' ) {
                showFieldError( messageInput, i18n.error_required || 'This field is required.' );
                isValid = false;
            } else if ( val.length < 5 ) {
                showFieldError( messageInput, i18n.error_min_message || 'Minimum 5 characters required.' );
                isValid = false;
            }
        }

        return isValid;
    }

    function showFieldError( inputEl, message ) {
        inputEl.classList.add( 'has-error' );
        const errorEl = form.querySelector(
            `.safak-form__error[data-field="${ inputEl.name }"]`
        );
        if ( errorEl ) {
            errorEl.textContent = message;
            errorEl.classList.add( 'is-visible' );
        }
    }

    function clearFieldError( inputEl ) {
        inputEl.classList.remove( 'has-error' );
        const errorEl = form.querySelector(
            `.safak-form__error[data-field="${ inputEl.name }"]`
        );
        if ( errorEl ) {
            errorEl.textContent = '';
            errorEl.classList.remove( 'is-visible' );
        }
    }

    function clearAllErrors() {
        form.querySelectorAll( '.has-error' ).forEach( el => el.classList.remove( 'has-error' ) );
        form.querySelectorAll( '.safak-form__error' ).forEach( el => {
            el.textContent = '';
            el.classList.remove( 'is-visible' );
        } );
    }

    // ── AJAX Submission ──────────────────────────────────────────────────────

    async function handleSubmit( e ) {
        e.preventDefault();

        if ( isSubmitting ) return;

        clearAllErrors();

        if ( ! validateForm() ) {
            // Scroll to first error.
            const firstError = form.querySelector( '.has-error' );
            if ( firstError ) firstError.focus();
            return;
        }

        // Check WP config is available.
        if ( ! window.SafakPopup ) {
            console.error( '[Safak Popup] SafakPopup config missing.' );
            return;
        }

        isSubmitting = true;
        setLoadingState( true );

        const formData = new FormData();
        formData.append( 'action',     SafakPopup.action );
        formData.append( 'nonce',      SafakPopup.nonce );
        formData.append( 'first_name', form.querySelector( '[name="first_name"]' ).value.trim() );
        formData.append( 'last_name',  form.querySelector( '[name="last_name"]' ).value.trim() );
        formData.append( 'phone',      form.querySelector( '[name="phone"]' ).value.trim() );
        formData.append( 'country_name', selectedCountryName );
        formData.append( 'country_code', selectedCountryCode );
        formData.append( 'country_flag', selectedCountryFlag );
        formData.append( 'country_flag_iso', flagEmojiToISO( selectedCountryFlag ) );
        formData.append( 'message',    form.querySelector( '[name="message"]' ).value.trim() );
        formData.append( 'language',   currentLang );

        try {
            const response = await fetch( SafakPopup.ajaxUrl, {
                method:      'POST',
                credentials: 'same-origin',
                body:        formData,
            } );

            const data = await response.json();

            if ( data.success ) {
                showSuccess();
            } else {
                // Server-side field errors.
                if ( data.data && data.data.fields ) {
                    const i18n = ( window.SafakI18n || {} )[ currentLang ] || {};
                    data.data.fields.forEach( field => {
                        const input = form.querySelector( `[name="${ field }"]` );
                        if ( input ) {
                            showFieldError( input, i18n.error_required || 'This field is required.' );
                        }
                    } );
                } else {
                    showError();
                }
            }
        } catch ( err ) {
            console.error( '[Safak Popup] Submission error:', err );
            showError();
        } finally {
            isSubmitting = false;
            setLoadingState( false );
        }
    }

    // ── UI State Helpers ──────────────────────────────────────────────────────

    function setLoadingState( loading ) {
        submitBtn.disabled = loading;
        submitBtn.classList.toggle( 'is-loading', loading );
    }

    function showSuccess() {
        form.hidden         = true;
        successPanel.hidden = false;
        errorPanel.hidden   = true;
        successPanel.focus();
    }

    function showError() {
        errorPanel.hidden = false;
        errorPanel.focus();
    }

    function showForm() {
        if ( form )          form.hidden         = false;
        if ( successPanel )  successPanel.hidden = true;
        if ( errorPanel )    errorPanel.hidden   = true;
        if ( form )          form.reset();
        clearAllErrors();
        isSubmitting = false;
        setLoadingState( false );
        // Re-apply language so placeholders/labels are fresh.
        applyLanguage( currentLang );
    }

    // ── Country Dropdown Helpers ──────────────────────────────────────────────

    function flagEmojiToISO( emoji ) {
        if ( ! emoji ) return 'dz';
        if ( /^[a-z]{2}$/i.test( emoji ) ) {
            return emoji.toLowerCase();
        }
        try {
            return Array.from( emoji )
                .map( char => String.fromCharCode( char.codePointAt( 0 ) - 127397 ) )
                .join( '' )
                .toLowerCase();
        } catch ( e ) {
            return 'dz';
        }
    }

    function populateCountries() {
        if ( ! countryListContainer ) return;
        countryListContainer.innerHTML = '';
        COUNTRIES.forEach( c => {
            const item = document.createElement( 'div' );
            item.className = 'safak-country-item';
            item.dataset.code = c.code;
            item.dataset.name = c.name;
            item.dataset.flag = c.flag;
            item.role = 'option';
            
            if ( selectedCountryCode === c.code && selectedCountryName === c.name ) {
                item.classList.add( 'active' );
            }
            
            const iso = flagEmojiToISO( c.flag );
            item.innerHTML = `
                <span class="safak-country-item-flag">
                    <img src="https://flagcdn.com/20x15/${iso}.png" width="20" height="15" alt="" style="display:inline-block;vertical-align:middle;border-radius:2px;box-shadow:0 1px 2px rgba(0,0,0,0.15);" />
                </span>
                <span class="safak-country-item-name">${c.name}</span>
                <span class="safak-country-item-code">${c.code}</span>
            `;
            
            item.addEventListener( 'click', function () {
                selectCountry( c.code, c.name, c.flag, true );
                closeCountryMenu();
            } );
            
            countryListContainer.appendChild( item );
        } );
    }

    function selectCountry( code, name, flag, isManual = false ) {
        selectedCountryCode = code;
        selectedCountryName = name;
        selectedCountryFlag = flag;
        
        if ( isManual ) {
            hasManuallySelectedCountry = true;
        }
        
        if ( countryToggleBtn ) {
            const flagEl = countryToggleBtn.querySelector( '.safak-country-selected-flag' );
            const codeEl = countryToggleBtn.querySelector( '.safak-country-selected-code' );
            if ( flagEl ) {
                const iso = flagEmojiToISO( flag );
                flagEl.innerHTML = `<img src="https://flagcdn.com/20x15/${iso}.png" width="20" height="15" alt="" style="display:inline-block;vertical-align:middle;border-radius:2px;box-shadow:0 1px 2px rgba(0,0,0,0.15);" />`;
            }
            if ( codeEl ) codeEl.textContent = code;
        }
        
        if ( countryListContainer ) {
            countryListContainer.querySelectorAll( '.safak-country-item' ).forEach( item => {
                if ( item.dataset.code === code && item.dataset.name === name ) {
                    item.classList.add( 'active' );
                } else {
                    item.classList.remove( 'active' );
                }
            } );
        }
    }

    function setDefaultCountryForLang( lang ) {
        let defCode = '+213', defName = 'Algeria', defFlag = '🇩🇿';
        if ( lang === 'ar' ) {
            defCode = '+213'; defName = 'Algeria'; defFlag = '🇩🇿';
        } else if ( lang === 'fr' ) {
            defCode = '+33'; defName = 'France'; defFlag = '🇫🇷';
        } else if ( lang === 'en' ) {
            defCode = '+44'; defName = 'United Kingdom'; defFlag = '🇬🇧';
        }
        selectCountry( defCode, defName, defFlag, false );
    }

    function toggleCountryMenu() {
        if ( ! countryMenu ) return;
        if ( countryMenu.hidden ) {
            openCountryMenu();
        } else {
            closeCountryMenu();
        }
    }

    function openCountryMenu() {
        if ( ! countryMenu ) return;
        countryMenu.hidden = false;
        countryToggleBtn.setAttribute( 'aria-expanded', 'true' );
        if ( countrySearchInput ) {
            countrySearchInput.value = '';
            countrySearchInput.focus();
        }
        filterCountries( '' );
    }

    function closeCountryMenu() {
        if ( ! countryMenu ) return;
        countryMenu.hidden = true;
        countryToggleBtn.setAttribute( 'aria-expanded', 'false' );
    }

    function filterCountries( query ) {
        if ( ! countryListContainer ) return;
        const items = countryListContainer.querySelectorAll( '.safak-country-item' );
        const normalizedQuery = query.toLowerCase().trim();
        items.forEach( item => {
            const name = (item.dataset.name || '').toLowerCase();
            const code = (item.dataset.code || '').toLowerCase();
            if ( name.includes( normalizedQuery ) || code.includes( normalizedQuery ) ) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        } );
    }

    // ── Focus Trap (Accessibility) ────────────────────────────────────────────

    /**
     * Trap Tab/Shift+Tab focus within the modal while it is open.
     * This meets WCAG 2.1 Success Criterion 2.1.2 (No Keyboard Trap... wait, we
     * intentionally trap but provide Escape as an exit – that's correct per ARIA).
     */
    function trapFocus( e ) {
        if ( e.key !== 'Tab' ) return;

        const focusableSelectors = [
            'a[href]',
            'button:not([disabled])',
            'input:not([disabled]):not([type="hidden"])',
            'textarea:not([disabled])',
            'select:not([disabled])',
            '[tabindex]:not([tabindex="-1"])',
        ].join( ', ' );

        const focusable = Array.from( modal.querySelectorAll( focusableSelectors ) )
            .filter( el => ! el.closest( '[hidden]' ) );

        if ( focusable.length === 0 ) return;

        const first = focusable[ 0 ];
        const last  = focusable[ focusable.length - 1 ];

        if ( e.shiftKey ) {
            if ( document.activeElement === first ) {
                e.preventDefault();
                last.focus();
            }
        } else {
            if ( document.activeElement === last ) {
                e.preventDefault();
                first.focus();
            }
        }
    }

} )();
