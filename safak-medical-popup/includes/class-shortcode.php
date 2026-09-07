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
        add_shortcode( 'safak_banner_form',        [ __CLASS__, 'render_banner_form' ] );
        add_action( 'wp_footer', [ __CLASS__, 'render_modal_in_footer' ] );
    }

    /**
     * Renders the modal HTML and dynamic translation arrays globally in the footer.
     */
    public static function render_modal_in_footer(): void {
        self::maybe_inline_i18n();
        
        $ajax_url = admin_url( 'admin-ajax.php' );
        $nonce    = wp_create_nonce( 'safak_popup_nonce' );
        
        echo "<!-- SAFAK POPUP MODAL FOOTER START -->\n";
        echo self::get_modal_html();
        ?>
        <script>
        (function() {
            var COUNTRIES = [
                { name: 'Algeria', code: '+213', flag: '🇩🇿' },
                { name: 'Afghanistan', code: '+93', flag: '🇦🇫' },
                { name: 'Albania', code: '+355', flag: '🇦🇱' },
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
                { name: 'Zambia', code: '+260', flag: '🇿🇲' }
            ];

            function flagEmojiToISO(emoji) {
                if (!emoji) return 'dz';
                try {
                    return Array.from(emoji)
                        .map(function(char) { return String.fromCharCode(char.codePointAt(0) - 127397); })
                        .join('')
                        .toLowerCase();
                } catch (e) {
                    return 'dz';
                }
            }

            function applyModalLanguage(lang) {
                var i18n = (window.SafakI18n || {})[lang];
                if (!i18n) return;
                
                var modal = document.getElementById('safak-modal');
                if (!modal) return;
                
                modal.querySelectorAll('[data-i18n]').forEach(function(el) {
                    var key = el.dataset.i18n;
                    if (i18n[key] !== undefined) {
                        el.textContent = i18n[key];
                    }
                });

                modal.querySelectorAll('[data-i18n-placeholder]').forEach(function(el) {
                    var key = el.dataset.i18nPlaceholder;
                    if (i18n[key] !== undefined) {
                        el.placeholder = i18n[key];
                    }
                });

                var isRTL = i18n.dir === 'rtl';
                modal.setAttribute('data-dir', isRTL ? 'rtl' : 'ltr');
                modal.setAttribute('dir', isRTL ? 'rtl' : 'ltr');

                modal.querySelectorAll('.safak-lang-btn').forEach(function(btn) {
                    if (btn.dataset.lang === lang) {
                        btn.classList.add('active');
                    } else {
                        btn.classList.remove('active');
                    }
                });

                var popupWrapper = modal.querySelector('.safak-form-wrapper-container');
                if (popupWrapper) {
                    popupWrapper.dataset.lang = lang;
                    var hiddenLangInput = popupWrapper.querySelector('[name="language"]');
                    if (hiddenLangInput) hiddenLangInput.value = lang;
                    
                    popupWrapper.querySelectorAll('[placeholder]').forEach(function(el) {
                        if (el.name === 'first_name' && i18n.placeholder_first_name) el.placeholder = i18n.placeholder_first_name;
                        if (el.name === 'last_name' && i18n.placeholder_last_name) el.placeholder = i18n.placeholder_last_name;
                        if (el.name === 'message' && i18n.placeholder_message) el.placeholder = i18n.placeholder_message;
                    });

                    popupWrapper.querySelectorAll('.safak-form__label').forEach(function(el) {
                        var nextInput = el.nextElementSibling;
                        if (nextInput) {
                            if (nextInput.name === 'first_name' && i18n.label_first_name) el.textContent = i18n.label_first_name;
                            if (nextInput.name === 'last_name' && i18n.label_last_name) el.textContent = i18n.label_last_name;
                            if (nextInput.name === 'message' && i18n.label_message) el.textContent = i18n.label_message;
                        }
                        if (el.nextElementSibling && el.nextElementSibling.classList.contains('safak-phone-wrapper') && i18n.label_phone) {
                            el.textContent = i18n.label_phone;
                        }
                    });
                }
            }

            function closeModal() {
                var overlay = document.getElementById('safak-popup-overlay');
                if (!overlay) return;
                overlay.classList.remove('is-visible');
                setTimeout(function() {
                    overlay.hidden = true;
                    document.body.style.overflow = '';
                }, 250);
            }

            function filterCountries(listContainer, query) {
                var normalized = query.toLowerCase().trim();
                listContainer.querySelectorAll('.safak-country-item').forEach(function(item) {
                    var name = (item.dataset.name || '').toLowerCase();
                    var code = (item.dataset.code || '').toLowerCase();
                    if (name.includes(normalized) || code.includes(normalized)) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
            }

            function initFormInstance(container, lang) {
                var form = container.querySelector('.safak-consultation-form');
                if (!form) return;

                var submitBtn = container.querySelector('.safak-form__submit');
                var successPanel = container.querySelector('.safak-feedback--success');
                var errorPanel = container.querySelector('.safak-feedback--error');

                var countryToggleBtn = container.querySelector('.safak-country-btn');
                var countryMenu = container.querySelector('.safak-country-dropdown');
                var countrySearchInput = container.querySelector('.safak-country-search');
                var countryListContainer = container.querySelector('.safak-country-list');

                var state = {
                    selectedCountryCode: '+213',
                    selectedCountryName: 'Algeria',
                    selectedCountryFlag: '🇩🇿',
                    isSubmitting: false,
                    lang: lang
                };

                // Populate country dropdown list
                if (countryListContainer) {
                    countryListContainer.innerHTML = '';
                    COUNTRIES.forEach(function(c) {
                        var item = document.createElement('div');
                        item.className = 'safak-country-item';
                        item.dataset.code = c.code;
                        item.dataset.name = c.name;
                        if (state.selectedCountryCode === c.code && state.selectedCountryName === c.name) {
                            item.classList.add('active');
                        }
                        var iso = flagEmojiToISO(c.flag);
                        item.innerHTML = '<span class="safak-country-item-flag"><img src="https://flagcdn.com/20x15/' + iso + '.png" width="20" height="15" alt="" /></span><span class="safak-country-item-text">' + c.name + ' (' + c.code + ')</span>';
                        item.addEventListener('click', function() {
                            state.selectedCountryCode = c.code;
                            state.selectedCountryName = c.name;
                            state.selectedCountryFlag = c.flag;
                            
                            if (countryToggleBtn) {
                                var flagEl = countryToggleBtn.querySelector('.safak-country-selected-flag');
                                if (flagEl) {
                                    flagEl.innerHTML = '<img src="https://flagcdn.com/20x15/' + iso + '.png" width="20" height="15" alt="" />';
                                }
                            }
                            var phoneInput = form.querySelector('[name="phone"]');
                            if (phoneInput) phoneInput.placeholder = c.code;
                            
                            countryListContainer.querySelectorAll('.safak-country-item').forEach(function(el) {
                                el.classList.remove('active');
                            });
                            item.classList.add('active');
                            
                            if (countryMenu) {
                                countryMenu.hidden = true;
                                var wrapper = countryToggleBtn.closest('.safak-phone-wrapper');
                                if (wrapper) wrapper.classList.remove('is-open');
                            }
                        });
                        countryListContainer.appendChild(item);
                    });
                }

                // Toggle country dropdown
                if (countryToggleBtn && countryMenu) {
                    countryToggleBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        var isHidden = countryMenu.hidden;
                        countryMenu.hidden = !isHidden;
                        var wrapper = countryToggleBtn.closest('.safak-phone-wrapper');
                        if (wrapper) {
                            if (isHidden) wrapper.classList.add('is-open');
                            else wrapper.classList.remove('is-open');
                        }
                        if (isHidden && countrySearchInput) {
                            countrySearchInput.value = '';
                            countrySearchInput.focus();
                            filterCountries(countryListContainer, '');
                        }
                    });
                }

                // Filter country list
                if (countrySearchInput && countryListContainer) {
                    countrySearchInput.addEventListener('input', function() {
                        filterCountries(countryListContainer, this.value);
                    });
                    countrySearchInput.addEventListener('click', function(e) {
                        e.stopPropagation();
                    });
                }

                // Hide country list on outside click
                document.addEventListener('click', function(e) {
                    if (countryMenu && !countryMenu.hidden) {
                        if (!countryMenu.contains(e.target) && !countryToggleBtn.contains(e.target)) {
                            countryMenu.hidden = true;
                            var wrapper = countryToggleBtn.closest('.safak-phone-wrapper');
                            if (wrapper) wrapper.classList.remove('is-open');
                        }
                    }
                });

                // Validation helpers
                function showFieldError(inputEl, message) {
                    inputEl.classList.add('has-error');
                    var errorEl = form.querySelector('.safak-form__error[data-field="' + inputEl.name + '"]');
                    if (errorEl) {
                        errorEl.textContent = message;
                        errorEl.classList.add('is-visible');
                    }
                }

                function clearFieldError(inputEl) {
                    inputEl.classList.remove('has-error');
                    var errorEl = form.querySelector('.safak-form__error[data-field="' + inputEl.name + '"]');
                    if (errorEl) {
                        errorEl.textContent = '';
                        errorEl.classList.remove('is-visible');
                    }
                }

                form.querySelectorAll('.safak-form__input, .safak-form__textarea').forEach(function(el) {
                    el.addEventListener('input', function() {
                        clearFieldError(this);
                    });
                });

                // Form Submit
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    if (state.isSubmitting) return;

                    form.querySelectorAll('.has-error').forEach(function(el) { el.classList.remove('has-error'); });
                    form.querySelectorAll('.safak-form__error').forEach(function(el) { el.textContent = ''; el.classList.remove('is-visible'); });

                    var i18n = (window.SafakI18n || {})[state.lang] || {};
                    var isValid = true;

                    var fName = form.querySelector('[name="first_name"]');
                    if (fName && fName.value.trim() === '') {
                        showFieldError(fName, i18n.error_required || 'This field is required.');
                        isValid = false;
                    }
                    var lName = form.querySelector('[name="last_name"]');
                    if (lName && lName.value.trim() === '') {
                        showFieldError(lName, i18n.error_required || 'This field is required.');
                        isValid = false;
                    }
                    var phone = form.querySelector('[name="phone"]');
                    if (phone) {
                        var pVal = phone.value.trim();
                        if (pVal === '') {
                            showFieldError(phone, i18n.error_required || 'This field is required.');
                            isValid = false;
                        } else if (pVal.length < 6) {
                            showFieldError(phone, i18n.error_min_phone || 'Minimum 6 digits required.');
                            isValid = false;
                        }
                    }
                    var msg = form.querySelector('[name="message"]');
                    if (msg && msg.value.trim() === '') {
                        showFieldError(msg, i18n.error_required || 'This field is required.');
                        isValid = false;
                    }

                    if (!isValid) return;

                    state.isSubmitting = true;
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.classList.add('is-loading');
                    }

                    var formData = new FormData();
                    formData.append('action', 'safak_submit_form');
                    formData.append('nonce', '<?php echo esc_js($nonce); ?>');
                    formData.append('first_name', fName.value.trim());
                    formData.append('last_name', lName.value.trim());
                    formData.append('phone', phone.value.trim());
                    formData.append('country_name', state.selectedCountryName);
                    formData.append('country_code', state.selectedCountryCode);
                    formData.append('country_flag', state.selectedCountryFlag);
                    formData.append('country_flag_iso', flagEmojiToISO(state.selectedCountryFlag));
                    formData.append('message', msg.value.trim());
                    formData.append('language', state.lang);

                    var hp = form.querySelector('[name="safak_honeypot"]');
                    if (hp) formData.append('safak_honeypot', hp.value);

                    try {
                        var res = await fetch('<?php echo esc_url($ajax_url); ?>', {
                            method: 'POST',
                            credentials: 'same-origin',
                            body: formData
                        });
                        var responseText = await res.text();
                        var data;
                        try {
                            data = JSON.parse(responseText);
                        } catch (jsonErr) {
                            console.error('[Safak Popup] Failed to parse JSON. Raw response from server:', responseText);
                            throw jsonErr;
                        }
                        
                        if (data.success) {
                            form.hidden = true;
                            if (successPanel) successPanel.hidden = false;
                            
                            var modalContainer = form.closest('.safak-modal');
                            if (modalContainer) {
                                var branding = modalContainer.querySelector('.safak-modal__branding');
                                if (branding) branding.style.setProperty('display', 'none', 'important');
                                var controls = modalContainer.querySelector('.safak-modal__controls');
                                if (controls) controls.style.setProperty('display', 'none', 'important');
                            }
                        } else {
                            if (errorPanel) errorPanel.hidden = false;
                        }
                    } catch (err) {
                        console.error(err);
                        if (errorPanel) errorPanel.hidden = false;
                    } finally {
                        state.isSubmitting = false;
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.classList.remove('is-loading');
                        }
                    }
                });

                container.resetFormInstance = function() {
                    form.hidden = false;
                    if (successPanel) successPanel.hidden = true;
                    if (errorPanel) errorPanel.hidden = true;
                    form.reset();
                    state.isSubmitting = false;
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.classList.remove('is-loading');
                    }
                    state.selectedCountryCode = '+213';
                    state.selectedCountryName = 'Algeria';
                    state.selectedCountryFlag = '🇩🇿';
                    var iso = flagEmojiToISO('🇩🇿');
                    if (countryToggleBtn) {
                        var flagEl = countryToggleBtn.querySelector('.safak-country-selected-flag');
                        if (flagEl) {
                            flagEl.innerHTML = '<img src="https://flagcdn.com/20x15/' + iso + '.png" width="20" height="15" alt="" />';
                        }
                    }
                    var phoneInput = form.querySelector('[name="phone"]');
                    if (phoneInput) phoneInput.placeholder = '+213';
                    if (countryListContainer) {
                        countryListContainer.querySelectorAll('.safak-country-item').forEach(function(el) {
                            if (el.dataset.code === '+213') el.classList.add('active');
                            else el.classList.remove('active');
                        });
                    }
                    
                    var modalContainer = form.closest('.safak-modal');
                    if (modalContainer) {
                        var branding = modalContainer.querySelector('.safak-modal__branding');
                        if (branding) branding.style.removeProperty('display');
                        var controls = modalContainer.querySelector('.safak-modal__controls');
                        if (controls) controls.style.removeProperty('display');
                    }
                };
            }

            document.addEventListener('click', function(e) {
                // Trigger button click
                var trigger = e.target.closest('.safak-popup-trigger, #safak-open-popup, a');
                if (trigger) {
                    var href = trigger.getAttribute('href') || '';
                    if (href === '#safak-popup' || href.indexOf('#safak-popup') !== -1) {
                        e.preventDefault();
                        var overlay = document.getElementById('safak-popup-overlay');
                        if (overlay) {
                            overlay.hidden = false;
                            void overlay.offsetWidth;
                            overlay.classList.add('is-visible');
                            document.body.style.overflow = 'hidden';
                            var closeBtn = document.getElementById('safak-close-btn');
                            if (closeBtn) {
                                closeBtn.focus();
                            }

                            var popupContainer = overlay.querySelector('.safak-form-wrapper-container');
                            if (popupContainer && popupContainer.resetFormInstance) {
                                popupContainer.resetFormInstance();
                            }
                            
                            var htmlLang = document.documentElement.lang || 'en';
                            var defaultLang = 'en';
                            if (htmlLang.indexOf('ar') === 0 || window.location.pathname.indexOf('/ar') !== -1) {
                                defaultLang = 'ar';
                            } else if (htmlLang.indexOf('fr') === 0 || window.location.pathname.indexOf('/fr') !== -1) {
                                defaultLang = 'fr';
                            }
                            applyModalLanguage(defaultLang);
                        }
                        return;
                    }
                }

                // Close button click
                if (e.target.closest('#safak-close-btn')) {
                    e.preventDefault();
                    closeModal();
                    return;
                }

                // Backdrop click
                var overlay = document.getElementById('safak-popup-overlay');
                if (overlay && e.target === overlay) {
                    closeModal();
                    return;
                }

                // Language switcher click
                var langBtn = e.target.closest('.safak-lang-btn');
                if (langBtn) {
                    e.preventDefault();
                    var lang = langBtn.dataset.lang;
                    if (lang) {
                        applyModalLanguage(lang);
                    }
                }
            }, true);

            // Initialize all forms on DOMContentLoaded or immediately
            function initAll() {
                document.querySelectorAll('.safak-form-wrapper-container').forEach(function(container) {
                    var lang = container.dataset.lang || 'en';
                    initFormInstance(container, lang);
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initAll);
            } else {
                initAll();
            }
        })();
        </script>
        <?php
        echo "<!-- SAFAK POPUP MODAL FOOTER END -->\n";
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
        $current_lang = self::detect_site_language();
        return self::get_form_card_html( $current_lang, true );
    }

    /**
     * Detects site language from Polylang, WPML, TranslatePress, URL, or WP locale.
     * Returns 'ar', 'fr', or 'en'.
     */
    public static function detect_site_language( string $override = '' ): string {
        if ( ! empty( $override ) && in_array( strtolower( $override ), [ 'ar', 'fr', 'en' ], true ) ) {
            return strtolower( $override );
        }

        // 1. Polylang
        if ( function_exists( 'pll_current_language' ) ) {
            $pll_lang = pll_current_language( 'slug' );
            if ( ! empty( $pll_lang ) && in_array( strtolower( $pll_lang ), [ 'ar', 'fr', 'en' ], true ) ) {
                return strtolower( $pll_lang );
            }
        }

        // 2. WPML
        if ( defined( 'ICL_LANGUAGE_CODE' ) && in_array( strtolower( ICL_LANGUAGE_CODE ), [ 'ar', 'fr', 'en' ], true ) ) {
            return strtolower( ICL_LANGUAGE_CODE );
        }

        // 3. TranslatePress
        if ( class_exists( 'TRP_Translate_Press' ) ) {
            global $TRP_LANGUAGE;
            if ( ! empty( $TRP_LANGUAGE ) ) {
                $sub = substr( strtolower( $TRP_LANGUAGE ), 0, 2 );
                if ( in_array( $sub, [ 'ar', 'fr', 'en' ], true ) ) {
                    return $sub;
                }
            }
        }

        // 4. URL path inspection (e.g. safakmedical.com/ar/... or ?lang=ar)
        if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
            $uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
            if ( preg_match( '#/(ar)(/|\?|\#|$)#i', $uri ) || preg_match( '#[?&]lang=ar#i', $uri ) ) {
                return 'ar';
            }
            if ( preg_match( '#/(fr)(/|\?|\#|$)#i', $uri ) || preg_match( '#[?&]lang=fr#i', $uri ) ) {
                return 'fr';
            }
            if ( preg_match( '#/(en)(/|\?|\#|$)#i', $uri ) || preg_match( '#[?&]lang=en#i', $uri ) ) {
                return 'en';
            }
        }

        // 5. WordPress site locale
        $locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
        if ( strpos( $locale, 'ar' ) === 0 ) {
            return 'ar';
        }
        if ( strpos( $locale, 'fr' ) === 0 ) {
            return 'fr';
        }

        return 'en';
    }

    /**
     * Renders the banner appointment form (third display style).
     * Usage: [safak_banner_form phone="+90-xxx" emergency_text="..." description="..." lang="ar|fr|en"]
     */
    public static function render_banner_form( $atts = [] ): string {
        $atts = is_array( $atts ) ? $atts : [];
        $req_lang = isset( $atts['lang'] ) ? sanitize_text_field( $atts['lang'] ) : '';
        $current_lang = self::detect_site_language( $req_lang );

        $banner_i18n = [
            'en' => [
                'emergency_text'    => 'Emergency Cases',
                'description'       => 'For urgent medical consultations and appointments, reach out to our team. We are here to help you 24/7.',
                'contact_btn'       => 'Contact Us',
                'title'             => 'Book Appointment Today!',
                'select_dept'       => 'Select Department',
                'select_doctor'     => 'Select Doctor',
                'placeholder_name'  => 'Your Name',
                'placeholder_phone' => 'Phone Number',
                'placeholder_email' => 'Email Address',
                'placeholder_date'  => 'Date',
                'placeholder_time'  => 'Time',
                'btn_submit'        => 'Make Appointment',
                'success_msg'       => 'Your appointment request has been received! Our medical team will contact you shortly.',
                'error_msg'         => 'Something went wrong. Please try again or call us directly.',
                'dir'               => 'ltr',
                'default_depts'     => [
                    'General Medicine',
                    'Cardiology',
                    'Dental Care',
                    'Plastic Surgery',
                    'Hair Transplant',
                    'Orthopedics',
                    'Ophthalmology',
                    'Bariatric Surgery',
                ],
            ],
            'fr' => [
                'emergency_text'    => 'Urgences Médicales',
                'description'       => 'Pour des consultations médicales urgentes et des rendez-vous, contactez notre équipe disponible 24/7.',
                'contact_btn'       => 'Contactez-nous',
                'title'             => "Prenez Rendez-vous Aujourd'hui !",
                'select_dept'       => 'Sélectionner le Département',
                'select_doctor'     => 'Sélectionner le Médecin',
                'placeholder_name'  => 'Votre Nom',
                'placeholder_phone' => 'Numéro de Téléphone',
                'placeholder_email' => 'Adresse Email',
                'placeholder_date'  => 'Date',
                'placeholder_time'  => 'Heure',
                'btn_submit'        => 'Prendre Rendez-vous',
                'success_msg'       => 'Votre demande de rendez-vous a bien été reçue ! Nous vous contacterons sous peu.',
                'error_msg'         => 'Une erreur est survenue. Veuillez réessayer ou nous appeler directement.',
                'dir'               => 'ltr',
                'default_depts'     => [
                    'Médecine Générale',
                    'Cardiologie',
                    'Soins Dentaires',
                    'Chirurgie Plastique',
                    'Greffe de Cheveux',
                    'Orthopédie',
                    'Ophtalmologie',
                    'Chirurgie Bariatrique',
                ],
            ],
            'ar' => [
                'emergency_text'    => 'حالات الطوارئ',
                'description'       => 'للاستشارات الطبية العاجلة وحجز المواعيد السريعة، تواصل مع فريقنا الطبي المتاح على مدار الساعة.',
                'contact_btn'       => 'تواصل معنا',
                'title'             => 'احجز موعدك اليوم!',
                'select_dept'       => 'اختر القسم',
                'select_doctor'     => 'اختر الطبيب',
                'placeholder_name'  => 'الاسم الكامل',
                'placeholder_phone' => 'رقم الهاتف',
                'placeholder_email' => 'البريد الإلكتروني',
                'placeholder_date'  => 'التاريخ',
                'placeholder_time'  => 'الوقت',
                'btn_submit'        => 'تأكيد الحجز',
                'success_msg'       => 'تم استلام طلب موعدك بنجاح! سيتواصل معك فريقنا الطبي قريباً.',
                'error_msg'         => 'حدث خطأ ما. يرجى المحاولة مرة أخرى أو الاتصال بنا مباشرة.',
                'dir'               => 'rtl',
                'default_depts'     => [
                    'الطب العام',
                    'أمراض القلب والشرايين',
                    'طب وجراحة الأسنان',
                    'جراحة التجميل',
                    'زراعة الشعر',
                    'طب وجراحة العظام',
                    'طب العيون',
                    'جراحة السمنة والتخسيس',
                ],
            ],
        ];

        $t = $banner_i18n[ $current_lang ] ?? $banner_i18n['en'];
        $dir = $t['dir'];
        $is_rtl = ( $dir === 'rtl' );
        $font_family = 'inherit';
        $text_align = $is_rtl ? 'right' : 'left';

        $parsed_atts = shortcode_atts(
            [
                'phone'          => '+90 537 691 76 95',
                'emergency_text' => $t['emergency_text'],
                'description'    => $t['description'],
                'lang'           => $current_lang,
            ],
            $atts,
            'safak_banner_form'
        );

        $phone          = esc_html( $parsed_atts['phone'] );
        $tel_url        = 'tel:' . preg_replace( '/[^0-9+]/', '', $phone );
        $emergency_text = esc_html( $parsed_atts['emergency_text'] );
        $description    = esc_html( $parsed_atts['description'] );
        $contact_btn    = esc_html( $t['contact_btn'] );
        $title          = esc_html( $t['title'] );
        $select_dept    = esc_attr( $t['select_dept'] );
        $select_doctor  = esc_attr( $t['select_doctor'] );
        $ph_name        = esc_attr( $t['placeholder_name'] );
        $ph_phone       = esc_attr( $t['placeholder_phone'] );
        $ph_email       = esc_attr( $t['placeholder_email'] );
        $btn_submit     = esc_html( $t['btn_submit'] );
        $success_msg    = esc_html( $t['success_msg'] );
        $error_msg      = esc_html( $t['error_msg'] );

        // Get departments and doctors from admin (language-aware).
        $departments = [];
        $doctors     = [];
        if ( class_exists( 'Safak_Admin' ) ) {
            $departments = Safak_Admin::get_departments_for_lang( $current_lang );
            $doctors     = Safak_Admin::get_doctors_for_lang( $current_lang );
        }

        // Fallback to localized default departments if none configured yet
        if ( empty( $departments ) && ! empty( $t['default_depts'] ) ) {
            $departments = $t['default_depts'];
        }

        $dept_options = '<option value="">' . $select_dept . '</option>';
        foreach ( $departments as $dept ) {
            $dept_options .= '<option value="' . esc_attr( $dept ) . '">' . esc_html( $dept ) . '</option>';
        }

        // Fallback default doctors if none configured
        if ( empty( $doctors ) ) {
            $doctors = [
                [ 'name' => 'Dr. Ahmet Yılmaz',   'department' => $departments[0] ?? 'Cardiology' ],
                [ 'name' => 'Dr. Mehmet Kaya',    'department' => $departments[1] ?? 'Ophthalmology' ],
                [ 'name' => 'Dr. Ayşe Demir',     'department' => $departments[2] ?? 'Dental Care' ],
                [ 'name' => 'Dr. Mustafa Çelik',  'department' => $departments[3] ?? 'Plastic & Aesthetic Surgery' ],
                [ 'name' => 'Dr. Fatma Şahin',    'department' => $departments[4] ?? 'Hair Transplant' ],
                [ 'name' => 'Dr. Emre Aydın',     'department' => $departments[5] ?? 'Orthopedics & Traumatology' ],
                [ 'name' => 'Dr. Zeynep Arslan',  'department' => $departments[6] ?? 'General Surgery' ],
                [ 'name' => 'Dr. Burak Öztürk',   'department' => $departments[7] ?? 'Bariatric Surgery' ],
            ];
        }

        $doctor_options = '<option value="">' . $select_doctor . '</option>';
        foreach ( $doctors as $doc ) {
            $doc_name = is_array( $doc ) ? ( $doc['name'] ?? '' ) : (string) $doc;
            $doc_dept = is_array( $doc ) ? ( $doc['department'] ?? '' ) : '';
            if ( ! empty( $doc_name ) ) {
                $doctor_options .= '<option value="' . esc_attr( $doc_name ) . '" data-department="' . esc_attr( $doc_dept ) . '">' . esc_html( $doc_name ) . '</option>';
            }
        }

        $doctors_json      = wp_json_encode( $doctors, JSON_UNESCAPED_UNICODE );
        $select_doctor_js  = esc_js( $t['select_doctor'] );
        $ajax_url          = admin_url( 'admin-ajax.php' );
        $nonce             = wp_create_nonce( 'safak_popup_nonce' );
        $unique_id         = 'safak-banner-' . wp_rand( 1000, 9999 );

        // Select chevron position based on text direction
        $chevron_svg = "%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='7' viewBox='0 0 12 7'%3E%3Cpath d='M1 1l5 5 5-5' fill='none' stroke='%239CA3AF' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E";
        $select_bg_pos = $is_rtl ? "left 14px center" : "right 14px center";
        $select_padding = $is_rtl ? "11px 14px 11px 36px" : "11px 36px 11px 14px";

        // Shared input style string (inherits theme font, allows custom CSS override)
        $input_style = "display:block !important;width:100% !important;padding:11px 14px !important;font-family:inherit;font-size:13.5px !important;color:#111827 !important;background:#ffffff !important;border:1.5px solid #d1d5db !important;border-radius:8px !important;outline:none !important;box-sizing:border-box !important;height:48px !important;line-height:normal !important;box-shadow:none !important;text-align:{$text_align} !important;margin:0 !important;";
        $select_style = "display:block !important;width:100% !important;padding:{$select_padding} !important;font-family:inherit;font-size:13.5px !important;color:#111827 !important;background:#ffffff url(\"data:image/svg+xml,{$chevron_svg}\") no-repeat {$select_bg_pos} !important;border:1.5px solid #d1d5db !important;border-radius:8px !important;outline:none !important;box-sizing:border-box !important;-webkit-appearance:none !important;-moz-appearance:none !important;appearance:none !important;cursor:pointer !important;height:48px !important;line-height:normal !important;box-shadow:none !important;text-align:{$text_align} !important;margin:0 !important;";

        ob_start();
        ?>
<style>
/* ── Safak Typography (Inherits from website theme, allows custom CSS on h2, p, a, inputs) ── */
#<?php echo $unique_id; ?>.safak-banner {
    font-family: inherit;
}
#<?php echo $unique_id; ?> input,
#<?php echo $unique_id; ?> select,
#<?php echo $unique_id; ?> button,
#<?php echo $unique_id; ?> textarea {
    font-family: inherit;
}

/* ── Safak Banner Layout (Desktop & Fluid Responsiveness) ── */
#<?php echo $unique_id; ?>.safak-banner {
    display: flex !important;
    flex-direction: row !important;
    width: 100% !important;
    max-width: 1180px !important;
    min-height: 280px !important;
    margin: 24px auto !important;
    padding: 0 !important;
    background: #ffffff !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 16px !important;
    box-shadow: 0 12px 35px rgba(0,0,0,0.08) !important;
    overflow: hidden !important;
    box-sizing: border-box !important;
    position: relative !important;
    z-index: 2 !important;
}

#<?php echo $unique_id; ?> .safak-banner__sidebar {
    flex: 0 0 320px !important;
    width: 320px !important;
    max-width: 320px !important;
    background: #1A4A72 !important;
    color: #ffffff !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: center !important;
    padding: 36px 26px !important;
    box-sizing: border-box !important;
    position: relative !important;
    overflow: hidden !important;
    border: none !important;
}

#<?php echo $unique_id; ?> .safak-banner-phone {
    white-space: nowrap !important;
    word-break: keep-all !important;
    font-size: 20px !important;
}

#<?php echo $unique_id; ?> .safak-banner__content {
    flex: 1 1 auto !important;
    min-width: 0 !important;
    background: #ffffff !important;
    padding: 36px 40px !important;
    box-sizing: border-box !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: center !important;
    position: relative !important;
}

#<?php echo $unique_id; ?> .safak-banner-heading {
    font-size: 26px !important;
    font-weight: 800 !important;
    color: #1A4A72 !important;
    margin: 0 0 20px !important;
    letter-spacing: -0.5px !important;
    line-height: 1.25 !important;
    text-align: <?php echo $text_align; ?> !important;
    unicode-bidi: isolate !important;
}

#<?php echo $unique_id; ?> .safak-banner__row {
    display: flex !important;
    gap: 12px !important;
    flex-wrap: wrap !important;
    width: 100% !important;
    box-sizing: border-box !important;
}

#<?php echo $unique_id; ?> .safak-banner__field {
    box-sizing: border-box !important;
}

#<?php echo $unique_id; ?> .safak-field-dept,
#<?php echo $unique_id; ?> .safak-field-doctor {
    flex: 1.4 1 180px !important;
    min-width: 140px !important;
}

#<?php echo $unique_id; ?> .safak-field-date,
#<?php echo $unique_id; ?> .safak-field-time {
    flex: 0.8 1 110px !important;
    min-width: 100px !important;
}

#<?php echo $unique_id; ?> .safak-field-name,
#<?php echo $unique_id; ?> .safak-field-phone,
#<?php echo $unique_id; ?> .safak-field-email {
    flex: 1 1 150px !important;
    min-width: 130px !important;
}

#<?php echo $unique_id; ?> .safak-banner-submit-wrap {
    margin-top: 8px !important;
    text-align: <?php echo $text_align; ?> !important;
}

#<?php echo $unique_id; ?> .safak-banner__submit,
#<?php echo $unique_id; ?> .safak-banner__submit:hover,
#<?php echo $unique_id; ?> .safak-banner__submit:focus,
#<?php echo $unique_id; ?> .safak-banner__submit:active,
#<?php echo $unique_id; ?> .safak-banner-contact-btn,
#<?php echo $unique_id; ?> .safak-banner-contact-btn:hover,
#<?php echo $unique_id; ?> .safak-banner-contact-btn:focus,
#<?php echo $unique_id; ?> .safak-banner-contact-btn:active {
    box-shadow: none !important;
    -webkit-box-shadow: none !important;
    text-shadow: none !important;
    outline: none !important;
    filter: none !important;
}

/* ── Tablet Screens (<= 991px) ── */
@media screen and (max-width: 991px) {
    #<?php echo $unique_id; ?>.safak-banner {
        flex-direction: column !important;
        width: 100% !important;
        max-width: 100% !important;
        min-height: auto !important;
        margin: 18px auto !important;
        border-radius: 14px !important;
    }
    #<?php echo $unique_id; ?> .safak-banner__sidebar {
        flex: none !important;
        width: 100% !important;
        max-width: 100% !important;
        padding: 26px 24px !important;
    }
    #<?php echo $unique_id; ?> .safak-banner__content {
        flex: none !important;
        width: 100% !important;
        max-width: 100% !important;
        padding: 28px 24px !important;
    }
    #<?php echo $unique_id; ?> .safak-banner-heading {
        font-size: 22px !important;
        margin-bottom: 16px !important;
    }
    #<?php echo $unique_id; ?> .safak-field-dept,
    #<?php echo $unique_id; ?> .safak-field-doctor {
        flex: 1 1 calc(50% - 6px) !important;
        min-width: 140px !important;
    }
    #<?php echo $unique_id; ?> .safak-field-date,
    #<?php echo $unique_id; ?> .safak-field-time {
        flex: 1 1 calc(50% - 6px) !important;
        min-width: 100px !important;
    }
    #<?php echo $unique_id; ?> .safak-field-name,
    #<?php echo $unique_id; ?> .safak-field-phone {
        flex: 1 1 calc(50% - 6px) !important;
        min-width: 130px !important;
    }
    #<?php echo $unique_id; ?> .safak-field-email {
        flex: 1 1 100% !important;
        width: 100% !important;
    }
}

/* ── Mobile Screens (<= 640px) ── */
@media screen and (max-width: 640px) {
    #<?php echo $unique_id; ?>.safak-banner {
        margin: 12px 0 !important;
        border-radius: 12px !important;
        box-shadow: 0 4px 18px rgba(0,0,0,0.06) !important;
    }
    #<?php echo $unique_id; ?> .safak-banner__sidebar {
        padding: 22px 18px !important;
    }
    #<?php echo $unique_id; ?> .safak-banner-phone {
        font-size: 19px !important;
    }
    #<?php echo $unique_id; ?> .safak-banner-desc {
        font-size: 12.5px !important;
        margin: 0 0 16px !important;
        line-height: 1.5 !important;
    }
    #<?php echo $unique_id; ?> .safak-banner-contact-btn {
        display: block !important;
        width: 100% !important;
        padding: 11px 16px !important;
        box-sizing: border-box !important;
        text-align: center !important;
    }
    #<?php echo $unique_id; ?> .safak-banner__content {
        padding: 22px 16px !important;
    }
    #<?php echo $unique_id; ?> .safak-banner-heading {
        font-size: 20px !important;
        margin-bottom: 14px !important;
        text-align: center !important;
    }
    #<?php echo $unique_id; ?> .safak-banner__row {
        flex-direction: column !important;
        gap: 10px !important;
    }
    #<?php echo $unique_id; ?> .safak-banner__field,
    #<?php echo $unique_id; ?> .safak-field-dept,
    #<?php echo $unique_id; ?> .safak-field-doctor,
    #<?php echo $unique_id; ?> .safak-field-date,
    #<?php echo $unique_id; ?> .safak-field-time,
    #<?php echo $unique_id; ?> .safak-field-name,
    #<?php echo $unique_id; ?> .safak-field-phone,
    #<?php echo $unique_id; ?> .safak-field-email {
        flex: 1 1 100% !important;
        width: 100% !important;
        min-width: 100% !important;
    }
    #<?php echo $unique_id; ?> .safak-banner-submit-wrap {
        text-align: center !important;
        margin-top: 6px !important;
    }
    #<?php echo $unique_id; ?> .safak-banner__submit {
        display: flex !important;
        width: 100% !important;
        justify-content: center !important;
        align-items: center !important;
        padding: 13px 20px !important;
        font-size: 15px !important;
        box-sizing: border-box !important;
    }
}
</style>
<div class="safak-banner" id="<?php echo $unique_id; ?>" dir="<?php echo $dir; ?>" data-dir="<?php echo $dir; ?>" data-lang="<?php echo $current_lang; ?>">

    <!-- Sidebar (Plain Blue Emergency Cases Box, no gradient) -->
    <div class="safak-banner__sidebar">
        <div style="position:relative;z-index:1;text-align:<?php echo $text_align; ?>;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.08);display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid rgba(255,255,255,0.15);">
                    <svg style="color:#ffffff;display:block;" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 10.8 19.79 19.79 0 01.22 2.18 2 2 0 012.18 0h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.91 7.91a16 16 0 006.27 6.27l1.27-.5a2 2 0 012.11.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/>
                    </svg>
                </div>
                <div style="display:flex;flex-direction:column;min-width:0;flex:1;">
                    <span style="font-size:12px;font-weight:600;color:rgba(255,255,255,0.75);letter-spacing:0.3px;text-transform:uppercase;white-space:nowrap;"><?php echo $emergency_text; ?></span>
                    <span class="safak-banner-phone" dir="ltr" style="font-size:20px;font-weight:800;color:#ffffff;line-height:1.2;margin-top:2px;direction:ltr;unicode-bidi:isolate;display:inline-block;white-space:nowrap !important;word-break:keep-all !important;"><?php echo $phone; ?></span>
                </div>
            </div>

            <p class="safak-banner-desc" style="font-size:13px;color:rgba(255,255,255,0.75);line-height:1.6;margin:0 0 24px;"><?php echo $description; ?></p>
            <a href="<?php echo esc_attr( $tel_url ); ?>" class="safak-banner-contact-btn" style="display:inline-block;padding:10px 24px;font-size:13px;font-weight:700;color:#ffffff;background:transparent;border:1.5px solid rgba(255,255,255,0.4);border-radius:50px;text-decoration:none;text-align:center;cursor:pointer;transition:all 0.25s ease;"><?php echo $contact_btn; ?></a>
        </div>
    </div>

    <!-- Main Content Area (Form matching Safak Popup) -->
    <div class="safak-banner__content">
        <h2 class="safak-banner-heading"><?php echo $title; ?></h2>

        <form id="<?php echo $unique_id; ?>-form" novalidate autocomplete="off" style="display:flex !important;flex-direction:column !important;gap:14px !important;position:relative !important;z-index:1 !important;margin:0 !important;padding:0 !important;">
            <!-- Anti-spam Honeypot -->
            <div style="display:none !important;"><input type="text" name="safak_honeypot" value="" autocomplete="off" tabindex="-1" /></div>

            <!-- Row 1: Department, Doctor, Date, Time -->
            <div class="safak-banner__row">
                <div class="safak-banner__field safak-field-dept">
                    <select name="department" id="<?php echo $unique_id; ?>-dept" style="<?php echo $select_style; ?>"><?php echo $dept_options; ?></select>
                </div>
                <div class="safak-banner__field safak-field-doctor">
                    <select name="doctor" id="<?php echo $unique_id; ?>-doctor" style="<?php echo $select_style; ?>"><?php echo $doctor_options; ?></select>
                </div>
                <div class="safak-banner__field safak-field-date">
                    <input type="date" name="appointment_date" style="<?php echo $input_style; ?>" />
                </div>
                <div class="safak-banner__field safak-field-time">
                    <input type="time" name="appointment_time" style="<?php echo $input_style; ?>" />
                </div>
            </div>

            <!-- Row 2: Name, Phone, Email -->
            <div class="safak-banner__row">
                <div class="safak-banner__field safak-field-name">
                    <input type="text" name="first_name" placeholder="<?php echo $ph_name; ?>" required autocomplete="given-name" style="<?php echo $input_style; ?>" />
                </div>
                <div class="safak-banner__field safak-field-phone">
                    <input type="tel" name="phone" placeholder="<?php echo $ph_phone; ?>" required autocomplete="tel" style="<?php echo $input_style; ?>" />
                </div>
                <div class="safak-banner__field safak-field-email">
                    <input type="email" name="email" placeholder="<?php echo $ph_email; ?>" autocomplete="email" style="<?php echo $input_style; ?>" />
                </div>
            </div>

            <input type="hidden" name="language" value="<?php echo $current_lang; ?>" />
            <input type="hidden" name="form_type" value="banner" />

            <!-- Submit Button (inherits style from WordPress / theme) -->
            <div class="safak-banner-submit-wrap">
                <button type="submit" class="safak-banner__submit button elementor-button">
                    <span><?php echo $btn_submit; ?></span>
                    <span class="safak-banner-spinner" style="display:none;width:14px;height:14px;border:2px solid currentColor;border-top-color:transparent;border-radius:50%;animation:safak-spin 0.6s linear infinite;margin-inline-start:8px;vertical-align:middle;" aria-hidden="true"></span>
                </button>
            </div>
        </form>

        <!-- Feedback Messages -->
        <div id="<?php echo $unique_id; ?>-success" hidden style="display:none;align-items:center;gap:12px;padding:16px 20px;border-radius:8px;font-size:14px;font-weight:600;margin-top:14px;background:rgba(16,185,129,0.08);color:#059669;border:1px solid rgba(16,185,129,0.25);font-family:inherit;text-align:<?php echo $text_align; ?>;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="flex-shrink:0;"><polyline points="20 6 9 17 4 12"/></svg>
            <span><?php echo $success_msg; ?></span>
        </div>
        <div id="<?php echo $unique_id; ?>-error" hidden style="display:none;align-items:center;gap:12px;padding:16px 20px;border-radius:8px;font-size:14px;font-weight:600;margin-top:14px;background:rgba(214,10,23,0.06);color:#DC2626;border:1px solid rgba(214,10,23,0.2);font-family:inherit;text-align:<?php echo $text_align; ?>;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            <span><?php echo $error_msg; ?></span>
        </div>
    </div>
</div>

<script>
(function() {
    var bannerId = '<?php echo $unique_id; ?>';
    var allDoctors = <?php echo $doctors_json; ?>;
    var ajaxUrl = '<?php echo esc_url( $ajax_url ); ?>';
    var nonce = '<?php echo esc_js( $nonce ); ?>';
    var selectDoctorText = '<?php echo $select_doctor_js; ?>';
    var currentLang = '<?php echo $current_lang; ?>';

    var banner = document.getElementById(bannerId);
    if (!banner) return;

    var deptSelect   = document.getElementById(bannerId + '-dept');
    var doctorSelect = document.getElementById(bannerId + '-doctor');
    var form         = document.getElementById(bannerId + '-form');
    var successEl    = document.getElementById(bannerId + '-success');
    var errorEl      = document.getElementById(bannerId + '-error');
    var submitBtn    = banner.querySelector('.safak-banner__submit');
    var contactBtn   = banner.querySelector('.safak-banner-contact-btn');

    // Populate and filter doctors
    function populateDoctors(selectedDept) {
        if (!doctorSelect) return;
        var currentSelected = doctorSelect.value;
        doctorSelect.innerHTML = '<option value="">' + selectDoctorText + '</option>';
        if (!allDoctors || !allDoctors.length) return;

        var cleanSelected = (selectedDept || '').trim().toLowerCase();

        allDoctors.forEach(function(doc) {
            var docName = doc.name || '';
            var docDept = (doc.department || '').trim().toLowerCase();

            var isMatch = !cleanSelected ||
                          (docDept === cleanSelected) ||
                          (cleanSelected && docDept.indexOf(cleanSelected) !== -1) ||
                          (docDept && cleanSelected.indexOf(docDept) !== -1);

            if (isMatch) {
                var opt = document.createElement('option');
                opt.value = docName;
                opt.textContent = docName;
                opt.dataset.department = doc.department || '';
                if (docName === currentSelected) {
                    opt.selected = true;
                }
                doctorSelect.appendChild(opt);
            }
        });

        // Fallback: If no doctors matched the selected department, show all doctors
        if (doctorSelect.options.length <= 1 && allDoctors.length > 0) {
            allDoctors.forEach(function(doc) {
                var opt = document.createElement('option');
                opt.value = doc.name || '';
                opt.textContent = doc.name || '';
                opt.dataset.department = doc.department || '';
                if (doc.name === currentSelected) {
                    opt.selected = true;
                }
                doctorSelect.appendChild(opt);
            });
        }
    }

    if (deptSelect) {
        deptSelect.addEventListener('change', function() {
            populateDoctors(this.value);
        });
    }

    if (doctorSelect) {
        doctorSelect.addEventListener('change', function() {
            var selOpt = this.options[this.selectedIndex];
            var docDept = selOpt ? selOpt.dataset.department : '';
            if (docDept && deptSelect && (!deptSelect.value || deptSelect.value === '')) {
                for (var i = 0; i < deptSelect.options.length; i++) {
                    if (deptSelect.options[i].value === docDept ||
                        (deptSelect.options[i].textContent && deptSelect.options[i].textContent.trim().toLowerCase() === docDept.trim().toLowerCase())) {
                        deptSelect.selectedIndex = i;
                        break;
                    }
                }
            }
        });
    }

    // Populate on initial load
    populateDoctors(deptSelect ? deptSelect.value : '');

    if (contactBtn) {
        contactBtn.addEventListener('mouseenter', function() {
            this.style.setProperty('background', '#E30213', 'important');
            this.style.setProperty('border-color', '#E30213', 'important');
        });
        contactBtn.addEventListener('mouseleave', function() {
            this.style.setProperty('background', 'transparent', 'important');
            this.style.setProperty('border-color', 'rgba(255,255,255,0.4)', 'important');
        });
    }

    // Input focus ring styles
    banner.querySelectorAll('input, select').forEach(function(el) {
        if (el.type === 'hidden' || el.name === 'safak_honeypot') return;
        el.addEventListener('focus', function() {
            this.style.setProperty('border-color', '#1A4A72', 'important');
            this.style.setProperty('box-shadow', '0 0 0 3px rgba(26,74,114,0.12)', 'important');
        });
        el.addEventListener('blur', function() {
            this.style.setProperty('border-color', '#d1d5db', 'important');
            this.style.setProperty('box-shadow', 'none', 'important');
        });
    });

    // Form submission
    if (form) {
        var isSubmitting = false;
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (isSubmitting) return;

            var firstName = form.querySelector('[name="first_name"]');
            var phone     = form.querySelector('[name="phone"]');

            if (firstName && firstName.value.trim() === '') {
                firstName.focus();
                firstName.style.setProperty('border-color', '#E30213', 'important');
                return;
            }
            if (phone && phone.value.trim() === '') {
                phone.focus();
                phone.style.setProperty('border-color', '#E30213', 'important');
                return;
            }

            isSubmitting = true;
            if (submitBtn) {
                submitBtn.disabled = true;
                var sp = submitBtn.querySelector('.safak-banner-spinner');
                if (sp) sp.style.display = 'inline-block';
            }

            var formData = new FormData();
            formData.append('action', 'safak_submit_form');
            formData.append('nonce', nonce);
            formData.append('form_type', 'banner');
            formData.append('first_name', firstName.value.trim());
            formData.append('last_name', '\u2014');
            formData.append('phone', phone.value.trim());
            formData.append('country_name', '');
            formData.append('country_code', '');
            formData.append('country_flag', '');
            formData.append('country_flag_iso', '');
            formData.append('message', 'Appointment booking via banner form');
            formData.append('language', currentLang);

            var dept = form.querySelector('[name="department"]');
            if (dept && dept.value) formData.append('department', dept.value);
            var doc = form.querySelector('[name="doctor"]');
            if (doc && doc.value) formData.append('doctor', doc.value);
            var emailF = form.querySelector('[name="email"]');
            if (emailF && emailF.value) formData.append('email', emailF.value.trim());
            var dateF = form.querySelector('[name="appointment_date"]');
            if (dateF && dateF.value) formData.append('appointment_date', dateF.value);
            var timeF = form.querySelector('[name="appointment_time"]');
            if (timeF && timeF.value) formData.append('appointment_time', timeF.value);
            var hp = form.querySelector('[name="safak_honeypot"]');
            if (hp) formData.append('safak_honeypot', hp.value);

            fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: formData })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        form.style.display = 'none';
                        if (successEl) {
                            successEl.hidden = false;
                            successEl.style.display = 'flex';
                        }
                    } else {
                        if (errorEl) {
                            errorEl.hidden = false;
                            errorEl.style.display = 'flex';
                        }
                    }
                })
                .catch(function() {
                    if (errorEl) {
                        errorEl.hidden = false;
                        errorEl.style.display = 'flex';
                    }
                })
                .finally(function() {
                    isSubmitting = false;
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        var sp = submitBtn.querySelector('.safak-banner-spinner');
                        if (sp) sp.style.display = 'none';
                    }
                });
        });
    }
})();
</script>
        <?php
        return ob_get_clean();
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

        $current_lang = self::detect_site_language();

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
              <img class="safak-flag-icon" src="https://flagcdn.com/16x12/dz.png" alt="ع" />
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
                'label_first_name'     => 'Patient Name',
                'label_last_name'      => 'Patient Last Name',
                'label_phone'          => 'Phone Number',
                'label_message'        => 'Tell Us Your Condition',
                'placeholder_first_name' => 'Patient Name',
                'placeholder_last_name'  => 'Patient Last Name',
                'placeholder_phone'      => 'Enter Your Number..',
                'placeholder_search'     => 'Search country...',
                'placeholder_message'    => 'Describe your condition or symptoms...',
                'btn_submit'           => 'Send Request',
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
                'label_first_name'     => 'Prénom du patient',
                'label_last_name'      => 'Nom du patient',
                'label_phone'          => 'Numéro de Téléphone',
                'label_message'        => 'Décrivez votre état de santé',
                'placeholder_first_name' => 'Prénom du patient',
                'placeholder_last_name'  => 'Nom du patient',
                'placeholder_phone'      => 'Entrez Votre Numéro..',
                'placeholder_search'     => 'Rechercher un pays...',
                'placeholder_message'    => 'Décrivez votre état ou vos symptômes...',
                'btn_submit'           => 'Envoyer la demande',
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
                'label_first_name'     => 'اسم المريض',
                'label_last_name'      => 'لقب المريض',
                'label_phone'          => 'رقم الهاتف',
                'label_message'        => 'أخبرنا عن حالتك الطبية',
                'placeholder_first_name' => 'اسم المريض',
                'placeholder_last_name'  => 'لقب المريض',
                'placeholder_phone'      => 'أدخل رقم هاتفك..',
                'placeholder_search'     => 'ابحث عن بلد...',
                'placeholder_message'    => 'صف حالتك أو الأعراض...',
                'btn_submit'           => 'إرسال الطلب',
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
