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

    // ── DOM References (resolved after init) ─────────────────────────────────
    let overlay, modal, form, closeBtn, submitBtn, langBtns,
        logoEl, successPanel, errorPanel, hiddenLangInput;

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

        if ( ! overlay || ! modal || ! form ) {
            // Modal HTML not on this page – nothing to initialise.
            return;
        }

        // Set logo src from PHP-passed URL.
        if ( logoEl && window.SafakPopup && SafakPopup.logoUrl ) {
            logoEl.src = SafakPopup.logoUrl;
        }

        // Apply default language strings.
        applyLanguage( currentLang );

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
            el.dir = isRTL ? 'rtl' : 'ltr';
        } );

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

        // Required text fields.
        [ 'safak-first-name', 'safak-last-name', 'safak-phone' ].forEach( id => {
            const input = document.getElementById( id );
            if ( ! input ) return;

            if ( input.value.trim() === '' ) {
                showFieldError( input, i18n.error_required || 'This field is required.' );
                isValid = false;
            }
        } );

        // Phone format check.
        const phoneInput = document.getElementById( 'safak-phone' );
        if ( phoneInput && phoneInput.value.trim() !== '' ) {
            const phoneRegex = /^[0-9\+\-\s\(\)]{6,25}$/;
            if ( ! phoneRegex.test( phoneInput.value.trim() ) ) {
                showFieldError( phoneInput, i18n.error_phone_format || 'Please enter a valid phone number.' );
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
