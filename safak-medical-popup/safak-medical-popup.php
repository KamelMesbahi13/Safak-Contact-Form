<?php
/**
 * Plugin Name:       Safak Medical – Popup Consultation Form
 * Plugin URI:        https://safakmedical.com
 * Description:       A lightweight, multilingual (EN/FR/AR) popup consultation form with AJAX submission, email notification, and database logging for Safak Medical.
 * Version:           1.2.2
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
define( 'SAFAK_POPUP_VERSION',   '1.2.2' );
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
require_once SAFAK_POPUP_INCLUDES . 'class-admin.php';

// ── Activation / Deactivation Hooks ─────────────────────────────────────────
register_activation_hook(   __FILE__, [ 'Safak_Medical_Popup', 'activate' ] );
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
        Safak_Admin::init();

        // Register daily database cleanup cron action.
        add_action( 'safak_daily_cleanup_submissions', [ 'Safak_Database', 'delete_old_submissions' ] );
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
        $css_ver = SAFAK_POPUP_VERSION;
        $css_path = SAFAK_POPUP_DIR . 'assets/css/safak-popup.css';
        if ( file_exists( $css_path ) ) {
            $css_ver = (string) filemtime( $css_path );
        }

        wp_enqueue_style(
            'safak-popup-style',
            SAFAK_POPUP_ASSETS . 'css/safak-popup.css',
            [],
            $css_ver
        );

        // External JS enqueuing is disabled because all form/modal JavaScript logic has been 
        // compiled into a self-contained inline script in the footer to bypass caching and defer issues.
    }

    /** Activation – create database table, schedule daily cleanup cron event. */
    public static function activate(): void {
        Safak_Database::create_table();

        if ( ! wp_next_scheduled( 'safak_daily_cleanup_submissions' ) ) {
            wp_schedule_event( time(), 'daily', 'safak_daily_cleanup_submissions' );
        }
    }

    /** Deactivation – flush rewrite rules, etc. (table kept intentionally). */
    public static function deactivate(): void {
        wp_clear_scheduled_hook( 'safak_daily_cleanup_submissions' );
        flush_rewrite_rules();
    }
}

// ── Bootstrap ────────────────────────────────────────────────────────────────
add_action( 'plugins_loaded', [ 'Safak_Medical_Popup', 'get_instance' ] );
