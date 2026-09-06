<?php
/**
 * Safak_Admin
 *
 * Provides a WordPress admin settings page for managing
 * departments and doctors used in the banner appointment form.
 * Automatically synchronizes existing WordPress departments, taxonomies,
 * procedures, and doctor custom post types.
 *
 * @package Safak_Medical_Popup
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Safak_Admin {

    /** Option keys. */
    const OPT_DEPARTMENTS = 'safak_departments';
    const OPT_DOCTORS     = 'safak_doctors';

    /** Register hooks. */
    public static function init(): void {
        add_action( 'admin_menu',            [ __CLASS__, 'add_menu_page' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );

        // AJAX handlers for departments.
        add_action( 'wp_ajax_safak_add_department',    [ __CLASS__, 'ajax_add_department' ] );
        add_action( 'wp_ajax_safak_remove_department', [ __CLASS__, 'ajax_remove_department' ] );

        // AJAX handlers for doctors.
        add_action( 'wp_ajax_safak_add_doctor',        [ __CLASS__, 'ajax_add_doctor' ] );
        add_action( 'wp_ajax_safak_remove_doctor',     [ __CLASS__, 'ajax_remove_doctor' ] );

        // AJAX handler for WordPress auto-sync.
        add_action( 'wp_ajax_safak_sync_wp_data',      [ __CLASS__, 'ajax_sync_wp_data' ] );
    }

    // ── Menu Registration ───────────────────────────────────────────────────

    /** Add top-level menu page on WordPress admin left sidebar. */
    public static function add_menu_page(): void {
        add_menu_page(
            __( 'Inscription Form', 'safak-medical-popup' ),
            __( 'Inscription Form', 'safak-medical-popup' ),
            'manage_options',
            'safak-medical-settings',
            [ __CLASS__, 'render_settings_page' ],
            'dashicons-clipboard',
            30
        );
    }

    /** Enqueue admin CSS/JS only on our settings page. */
    public static function enqueue_admin_assets( string $hook ): void {
        if ( strpos( $hook, 'safak-medical-settings' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'safak-admin-style',
            SAFAK_POPUP_ASSETS . 'css/safak-admin.css',
            [],
            SAFAK_POPUP_VERSION
        );

        wp_enqueue_script(
            'safak-admin-script',
            SAFAK_POPUP_ASSETS . 'js/safak-admin.js',
            [],
            SAFAK_POPUP_VERSION,
            true
        );

        wp_localize_script( 'safak-admin-script', 'SafakAdmin', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'safak_admin_nonce' ),
        ] );
    }

    // ── WordPress Auto-Detection Helpers ─────────────────────────────────────

    /**
     * Auto-detect existing departments from WordPress taxonomies and post types.
     * Scans taxonomies (department, specialty, procedure_category, etc.) and
     * custom post types (Procedures, Doctors).
     */
    public static function auto_detect_wp_departments(): array {
        $departments = [];

        // 1. Common candidate taxonomies in clinic and medical themes
        $candidate_taxonomies = [
            'department',
            'departments',
            'doctor_department',
            'doctor-department',
            'doctor_departments',
            'specialty',
            'specialties',
            'speciality',
            'specialities',
            'doctor_specialty',
            'doctor_category',
            'doctor-category',
            'doctor_cat',
            'procedure_category',
            'procedure-category',
            'procedure_cat',
            'procedure_department',
            'procedure_type',
            'medical_department',
            'clinic_department',
        ];

        // 2. Discover all taxonomies attached to relevant CPTs (doctor, procedure, hospital)
        $relevant_cpts = [ 'doctor', 'doctors', 'procedure', 'procedures', 'hospital', 'hospitals' ];
        foreach ( $relevant_cpts as $cpt ) {
            if ( post_type_exists( $cpt ) ) {
                $cpt_taxes = get_object_taxonomies( $cpt, 'names' );
                if ( ! empty( $cpt_taxes ) ) {
                    $candidate_taxonomies = array_merge( $candidate_taxonomies, $cpt_taxes );
                }
            }
        }

        // 3. Search public taxonomies matching medical keywords
        if ( function_exists( 'get_taxonomies' ) ) {
            $all_taxes = get_taxonomies( [ 'public' => true ], 'names' );
            foreach ( $all_taxes as $tax_name ) {
                if ( preg_match( '/(dept|specialt|doctor|procedure)/i', $tax_name ) ) {
                    $candidate_taxonomies[] = $tax_name;
                }
            }
        }

        $candidate_taxonomies = array_unique( $candidate_taxonomies );

        // 4. Fetch terms from all matching taxonomies
        foreach ( $candidate_taxonomies as $tax ) {
            if ( taxonomy_exists( $tax ) ) {
                $terms = get_terms( [
                    'taxonomy'   => $tax,
                    'hide_empty' => false,
                ] );
                if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                    foreach ( $terms as $term ) {
                        if ( is_object( $term ) && ! empty( $term->name ) ) {
                            $name = trim( $term->name );
                            if ( strtolower( $name ) !== 'uncategorized' && ! in_array( $name, $departments, true ) ) {
                                $departments[] = $name;
                            }
                        }
                    }
                }
            }
        }

        // 5. If Procedure post type exists, scan procedure titles as departments if needed
        if ( count( $departments ) < 3 ) {
            foreach ( [ 'procedure', 'procedures' ] as $proc_cpt ) {
                if ( post_type_exists( $proc_cpt ) ) {
                    $proc_posts = get_posts( [
                        'post_type'      => $proc_cpt,
                        'posts_per_page' => 50,
                        'post_status'    => 'publish',
                    ] );
                    if ( ! empty( $proc_posts ) ) {
                        foreach ( $proc_posts as $post ) {
                            $p_title = trim( get_the_title( $post ) );
                            if ( ! empty( $p_title ) && ! in_array( $p_title, $departments, true ) ) {
                                $departments[] = $p_title;
                            }
                        }
                    }
                }
            }
        }

        // 6. Fallback clinic departments so the site is never blank
        if ( empty( $departments ) ) {
            $departments = [
                'Cardiology',
                'Ophthalmology',
                'Dental Care',
                'Plastic & Aesthetic Surgery',
                'Hair Transplant',
                'Orthopedics & Traumatology',
                'General Surgery',
                'Bariatric Surgery',
                'Dermatology',
                'Neurology & Neurosurgery',
                'Urology',
                'Gynecology & Obstetrics',
                'Pediatrics',
                'Physical Therapy & Rehabilitation',
            ];
        }

        return array_values( array_unique( $departments ) );
    }

    /**
     * Auto-detect existing doctors from WordPress post types.
     */
    public static function auto_detect_wp_doctors( array $available_depts = [] ): array {
        $doctors = [];
        $doctor_cpts = [ 'doctor', 'doctors' ];

        if ( function_exists( 'get_post_types' ) ) {
            $all_cpts = get_post_types( [], 'objects' );
            foreach ( $all_cpts as $slug => $cpt_obj ) {
                $slug_l  = strtolower( $slug );
                $label_l = strtolower( $cpt_obj->label ?? '' );
                if ( strpos( $slug_l, 'doctor' ) !== false || strpos( $label_l, 'doctor' ) !== false || strpos( $slug_l, 'physician' ) !== false ) {
                    $doctor_cpts[] = $slug;
                }
            }
        }
        $doctor_cpts = array_unique( $doctor_cpts );
        $found_posts = [];

        foreach ( $doctor_cpts as $cpt ) {
            if ( post_type_exists( $cpt ) ) {
                $posts = get_posts( [
                    'post_type'      => $cpt,
                    'posts_per_page' => 100,
                    'post_status'    => 'publish',
                ] );
                if ( ! empty( $posts ) ) {
                    $found_posts = array_merge( $found_posts, $posts );
                }
            }
        }

        if ( ! empty( $found_posts ) ) {
            foreach ( $found_posts as $doc_post ) {
                $name = trim( get_the_title( $doc_post ) );
                if ( empty( $name ) ) {
                    continue;
                }

                $doc_dept = '';
                $post_taxes = get_object_taxonomies( $doc_post->post_type, 'names' );
                foreach ( $post_taxes as $tax ) {
                    $terms = wp_get_post_terms( $doc_post->ID, $tax );
                    if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                        foreach ( $terms as $term ) {
                            if ( strtolower( $term->name ) !== 'uncategorized' ) {
                                $doc_dept = $term->name;
                                break 2;
                            }
                        }
                    }
                }

                if ( empty( $doc_dept ) ) {
                    $meta_keys = [ 'department', 'doctor_department', 'specialty', '_department' ];
                    foreach ( $meta_keys as $mk ) {
                        $mv = get_post_meta( $doc_post->ID, $mk, true );
                        if ( ! empty( $mv ) && is_string( $mv ) ) {
                            $doc_dept = trim( $mv );
                            break;
                        }
                    }
                }

                if ( empty( $doc_dept ) ) {
                    $doc_dept = ! empty( $available_depts[0] ) ? $available_depts[0] : 'General Medicine';
                }

                $doctors[] = [
                    'name'       => $name,
                    'department' => $doc_dept,
                ];
            }
        }

        // Fallback default doctors if none exist in WordPress yet
        if ( empty( $doctors ) ) {
            $doctors = [
                [ 'name' => 'Dr. Ahmet Yılmaz',   'department' => $available_depts[0] ?? 'Cardiology' ],
                [ 'name' => 'Dr. Mehmet Kaya',    'department' => $available_depts[1] ?? 'Ophthalmology' ],
                [ 'name' => 'Dr. Ayşe Demir',     'department' => $available_depts[2] ?? 'Dental Care' ],
                [ 'name' => 'Dr. Mustafa Çelik',  'department' => $available_depts[3] ?? 'Plastic & Aesthetic Surgery' ],
                [ 'name' => 'Dr. Fatma Şahin',    'department' => $available_depts[4] ?? 'Hair Transplant' ],
                [ 'name' => 'Dr. Emre Aydın',     'department' => $available_depts[5] ?? 'Orthopedics & Traumatology' ],
                [ 'name' => 'Dr. Zeynep Arslan',  'department' => $available_depts[6] ?? 'General Surgery' ],
                [ 'name' => 'Dr. Burak Öztürk',   'department' => $available_depts[7] ?? 'Bariatric Surgery' ],
            ];
        }

        return $doctors;
    }

    // ── Data Helpers ────────────────────────────────────────────────────────

    /** Get all departments (auto-populates from WP if empty). */
    public static function get_departments(): array {
        $deps = get_option( self::OPT_DEPARTMENTS, null );
        if ( ! is_array( $deps ) || empty( $deps ) ) {
            $deps = self::auto_detect_wp_departments();
            update_option( self::OPT_DEPARTMENTS, $deps );
        }
        return $deps;
    }

    /** Get all doctors (auto-detects from WP if empty). */
    public static function get_doctors(): array {
        $docs = get_option( self::OPT_DOCTORS, null );
        if ( ! is_array( $docs ) || empty( $docs ) ) {
            $deps = self::get_departments();
            $docs = self::auto_detect_wp_doctors( $deps );
            if ( ! empty( $docs ) ) {
                update_option( self::OPT_DOCTORS, $docs );
            }
        }
        return is_array( $docs ) ? $docs : [];
    }

    // ── AJAX: WordPress Sync ────────────────────────────────────────────────

    public static function ajax_sync_wp_data(): void {
        check_ajax_referer( 'safak_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Unauthorized.' ], 403 );
        }

        // Force re-scan
        $detected_deps = self::auto_detect_wp_departments();
        $existing_deps = get_option( self::OPT_DEPARTMENTS, [] );
        if ( ! is_array( $existing_deps ) ) {
            $existing_deps = [];
        }
        $merged_deps = array_values( array_unique( array_merge( $existing_deps, $detected_deps ) ) );
        update_option( self::OPT_DEPARTMENTS, $merged_deps );

        $detected_docs = self::auto_detect_wp_doctors( $merged_deps );
        $existing_docs = get_option( self::OPT_DOCTORS, [] );
        if ( ! is_array( $existing_docs ) ) {
            $existing_docs = [];
        }

        $merged_docs = $existing_docs;
        foreach ( $detected_docs as $d_doc ) {
            $exists = false;
            foreach ( $merged_docs as $m_doc ) {
                if ( $m_doc['name'] === $d_doc['name'] ) {
                    $exists = true;
                    break;
                }
            }
            if ( ! $exists ) {
                $merged_docs[] = $d_doc;
            }
        }
        update_option( self::OPT_DOCTORS, $merged_docs );

        wp_send_json_success( [
            'departments' => $merged_deps,
            'doctors'     => $merged_docs,
            'message'     => sprintf( 'Synced %1$d departments and %2$d doctors from WordPress.', count( $merged_deps ), count( $merged_docs ) ),
        ] );
    }

    // ── AJAX: Departments ───────────────────────────────────────────────────

    public static function ajax_add_department(): void {
        check_ajax_referer( 'safak_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Unauthorized.' ], 403 );
        }

        $name = sanitize_text_field( wp_unslash( $_POST['department_name'] ?? '' ) );
        if ( empty( $name ) ) {
            wp_send_json_error( [ 'message' => 'Department name is required.' ] );
        }

        $departments = self::get_departments();

        if ( in_array( $name, $departments, true ) ) {
            wp_send_json_error( [ 'message' => 'Department already exists.' ] );
        }

        $departments[] = $name;
        update_option( self::OPT_DEPARTMENTS, $departments );

        wp_send_json_success( [ 'departments' => $departments ] );
    }

    public static function ajax_remove_department(): void {
        check_ajax_referer( 'safak_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Unauthorized.' ], 403 );
        }

        $name = sanitize_text_field( wp_unslash( $_POST['department_name'] ?? '' ) );
        $departments = self::get_departments();
        $departments = array_values( array_filter( $departments, fn( $d ) => $d !== $name ) );
        update_option( self::OPT_DEPARTMENTS, $departments );

        $doctors = self::get_doctors();
        $doctors = array_values( array_filter( $doctors, fn( $doc ) => ( $doc['department'] ?? '' ) !== $name ) );
        update_option( self::OPT_DOCTORS, $doctors );

        wp_send_json_success( [ 'departments' => $departments, 'doctors' => $doctors ] );
    }

    // ── AJAX: Doctors ───────────────────────────────────────────────────────

    public static function ajax_add_doctor(): void {
        check_ajax_referer( 'safak_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Unauthorized.' ], 403 );
        }

        $name       = sanitize_text_field( wp_unslash( $_POST['doctor_name'] ?? '' ) );
        $department = sanitize_text_field( wp_unslash( $_POST['doctor_department'] ?? '' ) );

        if ( empty( $name ) ) {
            wp_send_json_error( [ 'message' => 'Doctor name is required.' ] );
        }
        if ( empty( $department ) ) {
            wp_send_json_error( [ 'message' => 'Please select a department.' ] );
        }

        $doctors = self::get_doctors();

        foreach ( $doctors as $doc ) {
            if ( $doc['name'] === $name && $doc['department'] === $department ) {
                wp_send_json_error( [ 'message' => 'This doctor already exists in that department.' ] );
            }
        }

        $doctors[] = [
            'name'       => $name,
            'department' => $department,
        ];
        update_option( self::OPT_DOCTORS, $doctors );

        wp_send_json_success( [ 'doctors' => $doctors ] );
    }

    public static function ajax_remove_doctor(): void {
        check_ajax_referer( 'safak_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Unauthorized.' ], 403 );
        }

        $index = intval( $_POST['doctor_index'] ?? -1 );
        $doctors = self::get_doctors();

        if ( $index < 0 || $index >= count( $doctors ) ) {
            wp_send_json_error( [ 'message' => 'Invalid doctor index.' ] );
        }

        array_splice( $doctors, $index, 1 );
        update_option( self::OPT_DOCTORS, $doctors );

        wp_send_json_success( [ 'doctors' => $doctors ] );
    }

    // ── Settings Page Renderer ──────────────────────────────────────────────

    public static function render_settings_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $departments = self::get_departments();
        $doctors     = self::get_doctors();
        ?>
        <div class="wrap safak-admin-wrap">
            <div class="safak-admin-header">
                <div>
                    <h1 class="safak-admin-title">Inscription Form</h1>
                    <p class="safak-admin-subtitle">Manage departments and doctors for the inscription form.</p>
                </div>
                <div class="safak-admin-header-actions">
                    <button type="button" id="safak-sync-wp-btn" class="safak-admin-btn safak-admin-btn--sync">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;vertical-align:middle;"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/></svg>
                        <span>Sync with WordPress</span>
                    </button>
                </div>
            </div>

            <div id="safak-admin-notice" class="safak-admin-notice" style="display:none;"></div>

            <div class="safak-admin-grid">

                <!-- ── Departments Panel ──────────────────────── -->
                <div class="safak-admin-card">
                    <div class="safak-admin-card__header">
                        <h2>Departments</h2>
                    </div>
                    <div class="safak-admin-card__body">
                        <div class="safak-admin-add-row">
                            <input type="text" id="safak-dept-input" class="safak-admin-input" placeholder="e.g. Cardiology" />
                            <button type="button" id="safak-add-dept-btn" class="safak-admin-btn safak-admin-btn--primary">+ Add</button>
                        </div>
                        <div id="safak-dept-list" class="safak-admin-list">
                            <?php if ( empty( $departments ) ) : ?>
                                <p class="safak-admin-empty">No departments added yet.</p>
                            <?php else : ?>
                                <?php foreach ( $departments as $dept ) : ?>
                                    <div class="safak-admin-list-item" data-name="<?php echo esc_attr( $dept ); ?>">
                                        <span class="safak-admin-list-item__name"><?php echo esc_html( $dept ); ?></span>
                                        <button type="button" class="safak-admin-btn safak-admin-btn--danger safak-remove-dept" data-name="<?php echo esc_attr( $dept ); ?>">✕</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ── Doctors Panel ──────────────────────────── -->
                <div class="safak-admin-card">
                    <div class="safak-admin-card__header">
                        <h2>Doctors</h2>
                    </div>
                    <div class="safak-admin-card__body">
                        <div class="safak-admin-add-row safak-admin-add-row--doctor">
                            <input type="text" id="safak-doctor-input" class="safak-admin-input" placeholder="Dr. Name" />
                            <select id="safak-doctor-dept-select" class="safak-admin-select">
                                <option value="">— Select Department —</option>
                                <?php foreach ( $departments as $dept ) : ?>
                                    <option value="<?php echo esc_attr( $dept ); ?>"><?php echo esc_html( $dept ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" id="safak-add-doctor-btn" class="safak-admin-btn safak-admin-btn--primary">+ Add</button>
                        </div>
                        <div id="safak-doctor-list" class="safak-admin-list">
                            <?php if ( empty( $doctors ) ) : ?>
                                <p class="safak-admin-empty">No doctors added yet.</p>
                            <?php else : ?>
                                <?php foreach ( $doctors as $idx => $doc ) : ?>
                                    <div class="safak-admin-list-item" data-index="<?php echo $idx; ?>">
                                        <span class="safak-admin-list-item__name"><?php echo esc_html( $doc['name'] ); ?></span>
                                        <span class="safak-admin-list-item__badge"><?php echo esc_html( $doc['department'] ); ?></span>
                                        <button type="button" class="safak-admin-btn safak-admin-btn--danger safak-remove-doctor" data-index="<?php echo $idx; ?>">✕</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Shortcode Reference -->
            <div class="safak-admin-card safak-admin-card--full" style="margin-top:24px;">
                <div class="safak-admin-card__header">
                    <h2>Shortcode Reference</h2>
                </div>
                <div class="safak-admin-card__body">
                    <table class="safak-admin-shortcode-table">
                        <thead>
                            <tr>
                                <th>Shortcode</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>[safak_popup_form_button]</code></td>
                                <td>Renders a trigger button that opens the popup modal form.</td>
                            </tr>
                            <tr>
                                <td><code>[safak_inline_form]</code></td>
                                <td>Renders the consultation form directly inline on the page.</td>
                            </tr>
                            <tr>
                                <td><code>[safak_banner_form]</code></td>
                                <td>
                                    Renders the horizontal banner appointment form with department & doctor dropdowns, styled to match the Safak medical popup.
                                    <br><small style="color:#64748b;">• Automatically detects website language (<strong>Arabic</strong>, <strong>French</strong>, <strong>English</strong>) with full RTL support.</small>
                                    <br><small style="color:#64748b;">• Optional attributes: <code>phone="+90..."</code>, <code>emergency_text="..."</code>, <code>description="..."</code>, <code>lang="ar|fr|en"</code>.</small>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
}
