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
    let triggerButton = null;   // Element that opened the modal (for focus return).

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

    // ── Global DOM References (Modal specific) ──────────────────────────────
    let overlay, modal, closeBtn, langBtns, logoEl;

    // ────────────────────────────────────────────────────────────────────────
    function init() {
        overlay          = document.getElementById( 'safak-popup-overlay' );
        modal            = document.getElementById( 'safak-modal' );
        closeBtn         = document.getElementById( 'safak-close-btn' );
        logoEl           = document.getElementById( 'safak-logo' );
        langBtns         = document.querySelectorAll( '.safak-lang-btn' );

        // ── 1. Initialize all Form instances on the page ──────────────────────
        document.querySelectorAll( '.safak-form-wrapper-container' ).forEach( container => {
            initFormInstance( container );
        } );

        // Set logo src from PHP-passed URL.
        if ( logoEl && window.SafakPopup && SafakPopup.logoUrl ) {
            logoEl.src = SafakPopup.logoUrl;
        }

        // Apply default language strings to the modal content.
        if ( modal ) {
            applyModalLanguage( currentLang );
        }

        // ── Event Listeners (Global popup triggers) ───────────────────────────
        document.addEventListener( 'click', function ( e ) {
            const trigger = e.target.closest( '.safak-popup-trigger, #safak-open-popup, a' );
            if ( trigger ) {
                const href = trigger.getAttribute( 'href' ) || '';
                const isPopupTrigger = trigger.classList.contains( 'safak-popup-trigger' ) || 
                                      trigger.id === 'safak-open-popup' || 
                                      href.includes( '#safak-popup' ) || 
                                      ( trigger.hash && trigger.hash === '#safak-popup' );
                if ( isPopupTrigger ) {
                    e.preventDefault();
                    openModal( trigger );
                }
            }
        } );

        // Close via X button.
        if ( closeBtn ) {
            closeBtn.addEventListener( 'click', closeModal );
        }

        // Close on backdrop click.
        if ( overlay ) {
            overlay.addEventListener( 'click', function ( e ) {
                if ( e.target === overlay ) closeModal();
            } );
        }

        // Close on Escape key.
        document.addEventListener( 'keydown', function ( e ) {
            if ( e.key === 'Escape' && overlay && ! overlay.hidden ) closeModal();
        } );

        // Trap Tab focus inside modal when open.
        if ( modal ) {
            modal.addEventListener( 'keydown', trapFocus );
        }

        // Language switcher for modal.
        langBtns.forEach( btn => {
            btn.addEventListener( 'click', function () {
                const lang = this.dataset.lang;
                if ( lang && lang !== currentLang ) {
                    applyModalLanguage( lang );
                }
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
                    applyModalLanguage( lang );
                }
            }
        };

        // ── Hash-based trigger ────────────────────────────────────────────────
        function checkAndOpenFromHash() {
            if ( window.location.hash && window.location.hash.includes( '#safak-popup' ) ) {
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

        checkAndOpenFromHash();
        window.addEventListener( 'hashchange', checkAndOpenFromHash );
    }

    // ── Form Instance Initializer ────────────────────────────────────────────
    function initFormInstance( container ) {
        const form             = container.querySelector( '.safak-consultation-form' );
        if ( ! form ) return;

        const submitBtn        = container.querySelector( '.safak-form__submit' );
        const successPanel     = container.querySelector( '.safak-feedback--success' );
        const errorPanel       = container.querySelector( '.safak-feedback--error' );

        const countryToggleBtn     = container.querySelector( '.safak-country-btn' );
        const countryMenu          = container.querySelector( '.safak-country-dropdown' );
        const countrySearchInput   = container.querySelector( '.safak-country-search' );
        const countryListContainer = container.querySelector( '.safak-country-list' );

        // Local Form instance state
        let instanceState = {
            selectedCountryCode: '+213',
            selectedCountryName: 'Algeria',
            selectedCountryFlag: '🇩🇿',
            hasManuallySelectedCountry: false,
            isSubmitting: false,
            lang: container.dataset.lang || 'en'
        };

        // Populate country list for this instance
        populateCountries( countryListContainer, instanceState, onCountrySelect );

        // Always default to Algeria as requested
        setDefaultCountryForLang( instanceState, instanceState.lang );

        // ── Event Listeners relative to this form ────────────────────────────
        if ( countryToggleBtn && countryMenu ) {
            countryToggleBtn.addEventListener( 'click', function ( e ) {
                e.stopPropagation();
                toggleCountryMenu( countryMenu, countryToggleBtn );
            } );
        }

        if ( countrySearchInput ) {
            countrySearchInput.addEventListener( 'input', function () {
                filterCountries( countryListContainer, this.value );
            } );
            countrySearchInput.addEventListener( 'click', function ( e ) {
                e.stopPropagation();
            } );
        }

        // Hide dropdown on outside click
        document.addEventListener( 'click', function ( e ) {
            if ( countryMenu && ! countryMenu.hidden ) {
                if ( ! countryMenu.contains( e.target ) && ! countryToggleBtn.contains( e.target ) ) {
                    closeCountryMenu( countryMenu, countryToggleBtn );
                }
            }
        } );

        // Form submission
        form.addEventListener( 'submit', function ( e ) {
            handleSubmit( e, form, container, instanceState, successPanel, errorPanel, submitBtn );
        } );

        // Clear error on input
        form.querySelectorAll( '.safak-form__input, .safak-form__textarea' ).forEach( el => {
            el.addEventListener( 'input', function () {
                clearFieldError( form, this );
            } );
        } );

        // Expose public showForm method on container for external resets
        container.resetFormInstance = function() {
            form.hidden         = false;
            successPanel.hidden = true;
            errorPanel.hidden   = true;
            form.reset();
            clearAllErrors( form );
            instanceState.isSubmitting = false;
            setLoadingState( submitBtn, false );
            setDefaultCountryForLang( instanceState, instanceState.lang );

            // Restore branding and controls in the modal
            const modalContainer = form.closest( '.safak-modal' );
            if ( modalContainer ) {
                const branding = modalContainer.querySelector( '.safak-modal__branding' );
                if ( branding ) branding.style.removeProperty( 'display' );
                const controls = modalContainer.querySelector( '.safak-modal__controls' );
                if ( controls ) controls.style.removeProperty( 'display' );
            }
        };

        // When country is manually selected
        function onCountrySelect( code, name, flag ) {
            instanceState.selectedCountryCode = code;
            instanceState.selectedCountryName = name;
            instanceState.selectedCountryFlag = flag;
            instanceState.hasManuallySelectedCountry = true;
            
            // Update toggle button flag
            if ( countryToggleBtn ) {
                const flagEl = countryToggleBtn.querySelector( '.safak-country-selected-flag' );
                if ( flagEl ) {
                    const iso = flagEmojiToISO( flag );
                    flagEl.innerHTML = `<img src="https://flagcdn.com/20x15/${iso}.png" width="20" height="15" alt="" style="display:inline-block;vertical-align:middle;" />`;
                }
            }

            // Update phone input placeholder
            const phoneInput = form.querySelector( '[name="phone"]' );
            if ( phoneInput ) {
                phoneInput.placeholder = code;
            }

            // Update active styling in item list
            if ( countryListContainer ) {
                countryListContainer.querySelectorAll( '.safak-country-item' ).forEach( item => {
                    if ( item.dataset.code === code && item.dataset.name === name ) {
                        item.classList.add( 'active' );
                    } else {
                        item.classList.remove( 'active' );
                    }
                } );
            }

            // Auto-close menu on selection
            if ( countryMenu && countryToggleBtn ) {
                closeCountryMenu( countryMenu, countryToggleBtn );
            }
        }
    }

    // ── Reusable Helper Functions ────────────────────────────────────────────

    function populateCountries( container, state, onSelect ) {
        if ( ! container ) return;
        container.innerHTML = '';
        COUNTRIES.forEach( c => {
            const item = document.createElement( 'div' );
            item.className = 'safak-country-item';
            item.dataset.code = c.code;
            item.dataset.name = c.name;
            item.dataset.flag = c.flag;
            item.role = 'option';
            
            if ( state.selectedCountryCode === c.code && state.selectedCountryName === c.name ) {
                item.classList.add( 'active' );
            }
            
            const iso = flagEmojiToISO( c.flag );
            item.innerHTML = `
                <span class="safak-country-item-flag">
                    <img src="https://flagcdn.com/20x15/${iso}.png" width="20" height="15" alt="" style="display:inline-block;vertical-align:middle;" />
                </span>
                <span class="safak-country-item-text">${c.name} (${c.code})</span>
            `;
            
            item.addEventListener( 'click', function () {
                onSelect( c.code, c.name, c.flag );
            } );
            
            container.appendChild( item );
        } );
    }

    function setDefaultCountryForLang( state, lang ) {
        // As requested: the default flag is strictly the Algerian one (+213) in all 3 languages
        let defCode = '+213', defName = 'Algeria', defFlag = '🇩🇿';
        
        state.selectedCountryCode = defCode;
        state.selectedCountryName = defName;
        state.selectedCountryFlag = defFlag;
    }

    function toggleCountryMenu( menu, btn ) {
        if ( menu.hidden ) {
            openCountryMenu( menu, btn );
        } else {
            closeCountryMenu( menu, btn );
        }
    }

    function openCountryMenu( menu, btn ) {
        menu.hidden = false;
        btn.setAttribute( 'aria-expanded', 'true' );
        const wrapper = btn.closest( '.safak-phone-wrapper' );
        if ( wrapper ) {
            wrapper.classList.add( 'is-open' );
        }
        const searchInput = menu.querySelector( '.safak-country-search' );
        if ( searchInput ) {
            searchInput.value = '';
            searchInput.focus();
        }
        filterCountries( menu.querySelector( '.safak-country-list' ), '' );
    }

    function closeCountryMenu( menu, btn ) {
        menu.hidden = true;
        btn.setAttribute( 'aria-expanded', 'false' );
        const wrapper = btn.closest( '.safak-phone-wrapper' );
        if ( wrapper ) {
            wrapper.classList.remove( 'is-open' );
        }
    }

    function filterCountries( listContainer, query ) {
        if ( ! listContainer ) return;
        const items = listContainer.querySelectorAll( '.safak-country-item' );
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

    // ── Language Switching for Modal (Global modal translations) ─────────────

    function applyModalLanguage( lang ) {
        const i18n = ( window.SafakI18n || {} )[ lang ];
        if ( ! i18n ) return;

        currentLang = lang;

        // Apply translations inside the modal container only to avoid interfering with inline forms
        if ( modal ) {
            modal.querySelectorAll( '[data-i18n]' ).forEach( el => {
                const key = el.dataset.i18n;
                if ( i18n[ key ] !== undefined ) {
                    el.textContent = i18n[ key ];
                }
            } );

            modal.querySelectorAll( '[data-i18n-placeholder]' ).forEach( el => {
                const key = el.dataset.i18nPlaceholder;
                if ( i18n[ key ] !== undefined ) {
                    el.placeholder = i18n[ key ];
                }
            } );

            const isRTL = i18n.dir === 'rtl';
            modal.setAttribute( 'data-dir', isRTL ? 'rtl' : 'ltr' );
            modal.setAttribute( 'dir', isRTL ? 'rtl' : 'ltr' );

            // Sync the active class in the language switcher buttons inside the modal
            langBtns.forEach( btn => {
                if ( btn.dataset.lang === lang ) {
                    btn.classList.add( 'active' );
                } else {
                    btn.classList.remove( 'active' );
                }
            } );

            // Sync the popup form instance's language dataset & reset if needed
            const popupWrapper = modal.querySelector( '.safak-form-wrapper-container' );
            if ( popupWrapper ) {
                popupWrapper.dataset.lang = lang;
                const hiddenLangInput = popupWrapper.querySelector( '[name="language"]' );
                if ( hiddenLangInput ) hiddenLangInput.value = lang;
                
                // Re-apply placeholders to form inputs (excluding the phone country code placeholder)
                popupWrapper.querySelectorAll( '[placeholder]' ).forEach( el => {
                    if ( el.name === 'first_name' && i18n.placeholder_first_name ) el.placeholder = i18n.placeholder_first_name;
                    if ( el.name === 'last_name' && i18n.placeholder_last_name ) el.placeholder = i18n.placeholder_last_name;
                    if ( el.name === 'message' && i18n.placeholder_message ) el.placeholder = i18n.placeholder_message;
                } );

                popupWrapper.querySelectorAll( '.safak-form__label' ).forEach( el => {
                    const nextInput = el.nextElementSibling;
                    if ( nextInput ) {
                        if ( nextInput.name === 'first_name' && i18n.label_first_name ) el.textContent = i18n.label_first_name;
                        if ( nextInput.name === 'last_name' && i18n.label_last_name ) el.textContent = i18n.label_last_name;
                        if ( nextInput.name === 'message' && i18n.label_message ) el.textContent = i18n.label_message;
                    }
                    if ( el.nextElementSibling && el.nextElementSibling.classList.contains('safak-phone-wrapper') && i18n.label_phone ) {
                        el.textContent = i18n.label_phone;
                    }
                } );
            }
        }

        if ( closeBtn ) {
            const closeLabels = { en: 'Close', fr: 'Fermer', ar: 'إغلاق' };
            closeBtn.setAttribute( 'aria-label', closeLabels[ lang ] || 'Close' );
        }
    }

    // ── Form Validation ──────────────────────────────────────────────────────

    function validateForm( form, lang ) {
        const i18n    = ( window.SafakI18n || {} )[ lang ] || {};
        let   isValid = true;

        const firstNameInput = form.querySelector( '[name="first_name"]' );
        if ( firstNameInput ) {
            const val = firstNameInput.value.trim();
            if ( val === '' ) {
                showFieldError( form, firstNameInput, i18n.error_required || 'This field is required.' );
                isValid = false;
            } else if ( val.length < 2 ) {
                showFieldError( form, firstNameInput, i18n.error_min_name || 'Minimum 2 characters required.' );
                isValid = false;
            }
        }

        const lastNameInput = form.querySelector( '[name="last_name"]' );
        if ( lastNameInput ) {
            const val = lastNameInput.value.trim();
            if ( val === '' ) {
                showFieldError( form, lastNameInput, i18n.error_required || 'This field is required.' );
                isValid = false;
            } else if ( val.length < 2 ) {
                showFieldError( form, lastNameInput, i18n.error_min_name || 'Minimum 2 characters required.' );
                isValid = false;
            }
        }

        const phoneInput = form.querySelector( '[name="phone"]' );
        if ( phoneInput ) {
            const val = phoneInput.value.trim();
            if ( val === '' ) {
                showFieldError( form, phoneInput, i18n.error_required || 'This field is required.' );
                isValid = false;
            } else if ( val.length < 6 ) {
                showFieldError( form, phoneInput, i18n.error_min_phone || 'Minimum 6 digits required.' );
                isValid = false;
            } else {
                const phoneRegex = /^[0-9\+\-\s\(\)]{6,25}$/;
                if ( ! phoneRegex.test( val ) ) {
                    showFieldError( form, phoneInput, i18n.error_phone_format || 'Please enter a valid phone number.' );
                    isValid = false;
                }
            }
        }

        const messageInput = form.querySelector( '[name="message"]' );
        if ( messageInput ) {
            const val = messageInput.value.trim();
            if ( val === '' ) {
                showFieldError( form, messageInput, i18n.error_required || 'This field is required.' );
                isValid = false;
            } else if ( val.length < 5 ) {
                showFieldError( form, messageInput, i18n.error_min_message || 'Minimum 5 characters required.' );
                isValid = false;
            }
        }

        return isValid;
    }

    function showFieldError( form, inputEl, message ) {
        inputEl.classList.add( 'has-error' );
        const errorEl = form.querySelector(
            `.safak-form__error[data-field="${ inputEl.name }"]`
        );
        if ( errorEl ) {
            errorEl.textContent = message;
            errorEl.classList.add( 'is-visible' );
        }
    }

    function clearFieldError( form, inputEl ) {
        inputEl.classList.remove( 'has-error' );
        const errorEl = form.querySelector(
            `.safak-form__error[data-field="${ inputEl.name }"]`
        );
        if ( errorEl ) {
            errorEl.textContent = '';
            errorEl.classList.remove( 'is-visible' );
        }
    }

    function clearAllErrors( form ) {
        form.querySelectorAll( '.has-error' ).forEach( el => el.classList.remove( 'has-error' ) );
        form.querySelectorAll( '.safak-form__error' ).forEach( el => {
            el.textContent = '';
            el.classList.remove( 'is-visible' );
        } );
    }

    // ── AJAX Submission ──────────────────────────────────────────────────────

    async function handleSubmit( e, form, container, state, successPanel, errorPanel, submitBtn ) {
        e.preventDefault();

        if ( state.isSubmitting ) return;

        clearAllErrors( form );

        if ( ! validateForm( form, state.lang ) ) {
            const firstError = form.querySelector( '.has-error' );
            if ( firstError ) firstError.focus();
            return;
        }

        if ( ! window.SafakPopup ) {
            console.error( '[Safak Popup] SafakPopup config missing.' );
            return;
        }

        state.isSubmitting = true;
        setLoadingState( submitBtn, true );

        const formData = new FormData();
        formData.append( 'action',       SafakPopup.action );
        formData.append( 'nonce',        SafakPopup.nonce );
        formData.append( 'first_name',   form.querySelector( '[name="first_name"]' ).value.trim() );
        formData.append( 'last_name',    form.querySelector( '[name="last_name"]' ).value.trim() );
        formData.append( 'phone',        form.querySelector( '[name="phone"]' ).value.trim() );
        formData.append( 'country_name', state.selectedCountryName );
        formData.append( 'country_code', state.selectedCountryCode );
        formData.append( 'country_flag', state.selectedCountryFlag );
        formData.append( 'country_flag_iso', flagEmojiToISO( state.selectedCountryFlag ) );
        formData.append( 'message',      form.querySelector( '[name="message"]' ).value.trim() );
        formData.append( 'language',     state.lang );

        const honeypotInput = form.querySelector( '[name="safak_honeypot"]' );
        if ( honeypotInput ) {
            formData.append( 'safak_honeypot', honeypotInput.value );
        }

        try {
            const response = await fetch( SafakPopup.ajaxUrl, {
                method:      'POST',
                credentials: 'same-origin',
                body:        formData,
            } );

            const data = await response.json();

            if ( data.success ) {
                form.hidden         = true;
                successPanel.hidden = false;
                errorPanel.hidden   = true;
                successPanel.focus();

                // Hide branding and controls in the modal to avoid overlap on success screen
                const modalContainer = form.closest( '.safak-modal' );
                if ( modalContainer ) {
                    const branding = modalContainer.querySelector( '.safak-modal__branding' );
                    if ( branding ) branding.style.setProperty( 'display', 'none', 'important' );
                    const controls = modalContainer.querySelector( '.safak-modal__controls' );
                    if ( controls ) controls.style.setProperty( 'display', 'none', 'important' );
                }
            } else {
                if ( data.data && data.data.fields ) {
                    const i18n = ( window.SafakI18n || {} )[ state.lang ] || {};
                    data.data.fields.forEach( field => {
                        const input = form.querySelector( `[name="${ field }"]` );
                        if ( input ) {
                            showFieldError( form, input, i18n.error_required || 'This field is required.' );
                        }
                    } );
                } else {
                    errorPanel.hidden = false;
                    errorPanel.focus();
                }
            }
        } catch ( err ) {
            console.error( '[Safak Popup] Submission error:', err );
            errorPanel.hidden = false;
            errorPanel.focus();
        } finally {
            state.isSubmitting = false;
            setLoadingState( submitBtn, false );
        }
    }

    function setLoadingState( btn, loading ) {
        if ( ! btn ) return;
        btn.disabled = loading;
        btn.classList.toggle( 'is-loading', loading );
    }

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

    // ── Global Modal Open / Close ────────────────────────────────────────────

    function openModal( trigger ) {
        if ( trigger instanceof HTMLElement ) {
            triggerButton = trigger;
        } else if ( trigger && trigger.currentTarget ) {
            triggerButton = trigger.currentTarget;
        }

        if ( ! overlay ) {
            console.warn( '[Safak Popup] Modal overlay "#safak-popup-overlay" not found on this page.' );
            return;
        }

        const popupContainer = modal ? modal.querySelector( '.safak-form-wrapper-container' ) : null;
        if ( popupContainer && popupContainer.resetFormInstance ) {
            popupContainer.resetFormInstance();
        }

        overlay.hidden = false;
        void overlay.offsetWidth;
        overlay.classList.add( 'is-visible' );

        if ( closeBtn ) {
            closeBtn.focus();
        }

        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        overlay.classList.remove( 'is-visible' );

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

        overlay.addEventListener( 'transitionend', cleanup, { once: true } );
        setTimeout( cleanup, 250 );
    }

    // ── Focus Trap (Accessibility) ────────────────────────────────────────────

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
