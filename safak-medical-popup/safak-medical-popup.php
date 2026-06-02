<?php
/**
 * Plugin Name:       Safak Medical – Popup Consultation Form
 * Plugin URI:        https://safakmedical.com
 * Description:       A lightweight, multilingual (EN/FR/AR) popup consultation form with AJAX submission, email notification, and database logging for Safak Medical.
 * Version:           1.0.0
 * Author:            Safak Medical Dev Team
 * Author URI:        https://safakmedical.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       safak-medical-popup
 * Domain Path:       /languages
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Plugin Constants ────────────────────────────────────────────────────────
define( 'SAFAK_POPUP_VERSION',   '1.0.0' );
define( 'SAFAK_POPUP_FILE',      __FILE__ );
define( 'SAFAK_POPUP_DIR',       plugin_dir_path( __FILE__ ) );
define( 'SAFAK_POPUP_URL',       plugin_dir_url( __FILE__ ) );
define( 'SAFAK_POPUP_ASSETS',    SAFAK_POPUP_URL  . 'assets/' );
define( 'SAFAK_POPUP_INCLUDES',  SAFAK_POPUP_DIR  . 'includes/' );
define( 'SAFAK_POPUP_TABLE',     'safak_form_submissions' );

// ── Autoload includes ───────────────────────────────────────────────────────
require_once SAFAK_POPUP_INCLUDES . 'class-database.php';
require_once SAFAK_POPUP_INCLUDES . 'class-ajax-handler.php';
require_once SAFAK_POPUP_INCLUDES . 'class-shortcode.php';

// ── Activation / Deactivation Hooks ─────────────────────────────────────────
register_activation_hook(   __FILE__, [ 'Safak_Database', 'create_table' ] );
register_deactivation_hook( __FILE__, [ 'Safak_Medical_Popup', 'deactivate' ] );

/**
 * Main plugin class – orchestrates all components.
 */
final class Safak_Medical_Popup {

    /** Singleton instance. */
    private static ?self $instance = null;

    /** Boot the plugin once. */
    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** Private constructor – register hooks. */
    private function __construct() {
        add_action( 'init',             [ $this, 'load_textdomain' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );

        // Initialise sub-components.
        Safak_Ajax_Handler::init();
        Safak_Shortcode::init();
    }

    /** Load plugin text domain for future server-side i18n. */
    public function load_textdomain(): void {
        load_plugin_textdomain(
            'safak-medical-popup',
            false,
            dirname( plugin_basename( __FILE__ ) ) . '/languages'
        );
    }

    /**
     * Enqueue front-end CSS & JS.
     * Scripts are only loaded when the shortcode is actually present on a page
     * OR unconditionally (simpler, reliable approach chosen here for SPA compat).
     */
    public function enqueue_assets(): void {
        wp_enqueue_style(
            'safak-popup-style',
            SAFAK_POPUP_ASSETS . 'css/safak-popup.css',
            [],
            SAFAK_POPUP_VERSION
        );

        wp_enqueue_script(
            'safak-popup-script',
            SAFAK_POPUP_ASSETS . 'js/safak-popup.js',
            [],                    // No jQuery dependency – pure vanilla JS.
            SAFAK_POPUP_VERSION,
            true                   // Load in footer.
        );

        /**
         * Pass PHP-side configuration to JavaScript via wp_localize_script.
         * This keeps the nonce and AJAX URL server-generated and secure.
         */
        wp_localize_script(
            'safak-popup-script',
            'SafakPopup',          // Global JS object name.
            [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'safak_popup_nonce' ),
                'logoUrl' => SAFAK_POPUP_ASSETS . 'images/logo-white-1.webp',
                'action'  => 'safak_submit_form',
            ]
        );
    }

    /** Deactivation – flush rewrite rules, etc. (table kept intentionally). */
    public static function deactivate(): void {
        flush_rewrite_rules();
    }
}

// ── Bootstrap ────────────────────────────────────────────────────────────────
add_action( 'plugins_loaded', [ 'Safak_Medical_Popup', 'get_instance' ] );
