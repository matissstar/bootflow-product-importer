<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Bfpi
 * @subpackage Bfpi/includes/admin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * The admin-specific functionality of the plugin.
 */
class Bfpi_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'bfpi-import') === false) {
            return;
        }
        
        wp_enqueue_style(
            $this->plugin_name . '-admin',
            BFPI_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'bfpi-import') === false) {
            return;
        }
        
        wp_enqueue_script(
            $this->plugin_name . '-admin',
            BFPI_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-util'),
            $this->version . '.' . time(),
            true
        );

        // Localize script for AJAX
        wp_localize_script(
            $this->plugin_name . '-admin',
            'bfpi_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bfpi_nonce'),
                'strings' => array(
                    'uploading' => __('Uploading file...', 'bootflow-product-xml-csv-importer'),
                    'parsing' => __('Parsing file structure...', 'bootflow-product-xml-csv-importer'),
                    'importing' => __('Importing products...', 'bootflow-product-xml-csv-importer'),
                    'complete' => __('Import complete!', 'bootflow-product-xml-csv-importer'),
                    'error' => __('An error occurred:', 'bootflow-product-xml-csv-importer'),
                    'confirm_import' => __('Are you sure you want to start the import?', 'bootflow-product-xml-csv-importer'),
                    'test_ai' => __('Testing AI provider...', 'bootflow-product-xml-csv-importer')
                )
            )
        );
        
        // Also localize as wcAiImportData for consistency across all pages
        wp_localize_script(
            $this->plugin_name . '-admin',
            'wcAiImportData',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bfpi_nonce'),
                'i18n' => array(
                    'deleting_products' => __('Deleting Products', 'bootflow-product-xml-csv-importer'),
                    'products_deleted' => __('products deleted', 'bootflow-product-xml-csv-importer'),
                    'cancel' => __('Cancel', 'bootflow-product-xml-csv-importer'),
                    'close' => __('Close', 'bootflow-product-xml-csv-importer'),
                    'confirm_delete_products' => __('Are you sure you want to delete all products from this import?', 'bootflow-product-xml-csv-importer'),
                    'counting_products' => __('Counting products...', 'bootflow-product-xml-csv-importer'),
                    'deleting' => __('Deleting...', 'bootflow-product-xml-csv-importer'),
                    'no_products_found' => __('No products found to delete.', 'bootflow-product-xml-csv-importer'),
                    // translators: %d is the number of products deleted
                    'all_products_deleted' => __('All %d products deleted successfully!', 'bootflow-product-xml-csv-importer'),
                    // File preview navigation
                    'prev' => __('← Prev', 'bootflow-product-xml-csv-importer'),
                    'next' => __('Next →', 'bootflow-product-xml-csv-importer'),
                    'go' => __('Go', 'bootflow-product-xml-csv-importer'),
                    'go_to_product' => __('Go to product:', 'bootflow-product-xml-csv-importer'),
                    // translators: %1$d is the current product number, %2$d is the total number of products
                    'product_x_of_y' => __('Product %1$d of %2$d', 'bootflow-product-xml-csv-importer'),
                    // File preview group headers
                    'basic_info' => __('Basic Info', 'bootflow-product-xml-csv-importer'),
                    'pricing' => __('Pricing', 'bootflow-product-xml-csv-importer'),
                    'inventory' => __('Inventory', 'bootflow-product-xml-csv-importer'),
                    'shipping' => __('Shipping', 'bootflow-product-xml-csv-importer'),
                    'identifiers' => __('Identifiers', 'bootflow-product-xml-csv-importer'),
                    'other_fields' => __('Other Fields', 'bootflow-product-xml-csv-importer'),
                    // Expandable fields
                    'items' => __('items', 'bootflow-product-xml-csv-importer'),
                    'click_to_expand' => __('(click to expand)', 'bootflow-product-xml-csv-importer'),
                    'click' => __('(click)', 'bootflow-product-xml-csv-importer'),
                    'object_fields' => __('Object (%d fields)', 'bootflow-product-xml-csv-importer'),
                    'all_items' => __('All %d items', 'bootflow-product-xml-csv-importer'),
                    'nested_fields' => __('+ %d nested/complex fields (attributes, variations, etc.) available in dropdown', 'bootflow-product-xml-csv-importer'),
                    'preview' => __('Preview:', 'bootflow-product-xml-csv-importer'),
                    'variable_product_detected' => __('Variable Product Detected', 'bootflow-product-xml-csv-importer'),
                )
            )
        );
    }

    /**
     * Redirect old/incorrect page slugs to correct ones.
     *
     * @since    1.0.0
     */
    public function redirect_old_slugs() {
        if (!isset($_GET['page'])) {
            return;
        }
        
        // Only redirect OLD slugs to NEW ones (don't include same->same mappings!)
        $old_slugs = array(
            'bfpi_import_logs' => 'bfpi-import-logs',
        );
        
        $current_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        
        if (isset($old_slugs[$current_page])) {
            $redirect_url = add_query_arg(array('page' => $old_slugs[$current_page]), admin_url('admin.php'));
            
            // Preserve specific known GET parameters only
            $allowed_params = array( 'import_id', 'step' );
            foreach ( $allowed_params as $param ) {
                if ( isset( $_GET[ $param ] ) ) {
                    $redirect_url = add_query_arg( sanitize_key( $param ), absint( $_GET[ $param ] ), $redirect_url );
                }
            }
            
            wp_safe_redirect($redirect_url);
            exit;
        }
    }
    
    /**
     * Render language switcher dropdown for admin pages.
     *
     * @since    1.0.0
     */
    private function render_language_switcher() {
        $locales = Bfpi_i18n::get_supported_locales();
        $current = Bfpi_i18n::get_admin_locale();
        $user_override = get_user_meta(get_current_user_id(), 'bootflow_admin_language', true);
        $is_auto = empty($user_override) || $user_override === 'auto';
        
        // Flag emoji map
        $flags = array(
            'en_US' => '🇬🇧', 'lv' => '🇱🇻', 'es_ES' => '🇪🇸', 'de_DE' => '🇩🇪',
            'fr_FR' => '🇫🇷', 'pt_BR' => '🇧🇷', 'ja' => '🇯🇵', 'it_IT' => '🇮🇹',
            'nl_NL' => '🇳🇱', 'ru_RU' => '🇷🇺', 'zh_CN' => '🇨🇳', 'pl_PL' => '🇵🇱',
            'tr_TR' => '🇹🇷', 'sv_SE' => '🇸🇪', 'id_ID' => '🇮🇩', 'ar' => '🇸🇦',
        );
        
        $current_flag = isset($flags[$current]) ? $flags[$current] : '🌐';
        $current_name = isset($locales[$current]) ? $locales[$current] : 'English';
        
        echo '<div class="bootflow-lang-switcher">';
        echo '<button type="button" class="bootflow-lang-btn" id="bootflow-lang-toggle">';
        echo '<span class="bootflow-lang-flag">' . wp_kses_post($current_flag) . '</span>';
        echo '<span class="bootflow-lang-name">' . esc_html($current_name) . '</span>';
        echo '<span class="dashicons dashicons-arrow-down-alt2"></span>';
        echo '</button>';
        echo '<div class="bootflow-lang-dropdown" id="bootflow-lang-dropdown" style="display:none;">';
        
        // Auto option
        $auto_class = $is_auto ? ' active' : '';
        echo '<a href="#" class="bootflow-lang-option' . esc_attr($auto_class) . '" data-locale="auto">';
        echo '<span class="bootflow-lang-flag">🌐</span>';
        echo '<span class="bootflow-lang-name">Auto (WordPress)</span>';
        echo '</a>';
        
        foreach ($locales as $locale => $name) {
            $flag = isset($flags[$locale]) ? $flags[$locale] : '🌐';
            $active_class = (!$is_auto && $current === $locale) ? ' active' : '';
            echo '<a href="#" class="bootflow-lang-option' . esc_attr($active_class) . '" data-locale="' . esc_attr($locale) . '">';
            echo '<span class="bootflow-lang-flag">' . wp_kses_post($flag) . '</span>';
            echo '<span class="bootflow-lang-name">' . esc_html($name) . '</span>';
            echo '</a>';
        }
        
        echo '</div></div>';
        echo '<input type="hidden" id="bootflow-lang-nonce" value="' . esc_attr(wp_create_nonce('bootflow_switch_language')) . '" />';
    }
    
    /**
     * Add admin menu items.
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {
        // Add top-level menu with icon
        add_menu_page(
            __('Bootflow Importer', 'bootflow-product-xml-csv-importer'),
            __('Bootflow Import', 'bootflow-product-xml-csv-importer'),
            'manage_options',
            'bfpi-import',
            array($this, 'display_import_page'),
            'dashicons-upload',
            56 // Position (after WooCommerce at 55)
        );

        // Add submenu pages
        add_submenu_page(
            'bfpi-import',
            __('New Import', 'bootflow-product-xml-csv-importer'),
            __('New Import', 'bootflow-product-xml-csv-importer'),
            'manage_options',
            'bfpi-import',
            array($this, 'display_import_page')
        );

        add_submenu_page(
            'bfpi-import',
            __('Import History', 'bootflow-product-xml-csv-importer'),
            __('History', 'bootflow-product-xml-csv-importer'),
            'manage_options',
            'bfpi-import-history',
            array($this, 'display_history_page')
        );

        add_submenu_page(
            'bfpi-import',
            __('Import Settings', 'bootflow-product-xml-csv-importer'),
            __('Settings', 'bootflow-product-xml-csv-importer'),
            'manage_options',
            'bfpi-import-settings',
            array($this, 'display_settings_page')
        );

        add_submenu_page(
            'bfpi-import',
            __('Import Logs', 'bootflow-product-xml-csv-importer'),
            __('Logs', 'bootflow-product-xml-csv-importer'),
            'manage_options',
            'bfpi-import-logs',
            array($this, 'display_logs_page')
        );
    }

    /**
     * Display main import page.
     *
     * @since    1.0.0
     */
    public function display_import_page() {
        // Handle Re-run action with resume dialog
        if (isset($_GET['action']) && $_GET['action'] === 'rerun' && isset($_GET['import_id'])) {
            $import_id = intval($_GET['import_id']);
            
            // Check if this is a confirmed action (resume or restart)
            if (isset($_GET['resume_action'])) {
                $resume_action = sanitize_text_field(wp_unslash($_GET['resume_action']));
                $this->rerun_import($import_id, $resume_action === 'resume');
                return;
            }
            
            // Check if import has progress
            global $wpdb;
            $import = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}bfpi_imports WHERE id = %d",
                $import_id
            ), ARRAY_A);
            
            if ($import && $import['processed_products'] > 0 && $import['processed_products'] < $import['total_products']) {
                // Show resume dialog
                $this->display_resume_dialog($import);
                return;
            }
            
            // No progress or completed - just restart
            $this->rerun_import($import_id, false);
            return;
        }
        
        $step = isset($_GET['step']) ? intval($_GET['step']) : 1;
        
        echo '<div class="wrap bfpi-import-wrap">';
        echo '<div class="bootflow-header-row">';
        echo '<h1>' . esc_html__('Bootflow – WooCommerce XML & CSV Importer', 'bootflow-product-xml-csv-importer') . '</h1>';
        $this->render_language_switcher();
        echo '</div>';
        
        // Progress indicator
        $this->display_progress_indicator($step);
        
        switch ($step) {
            case 1:
                $this->display_step_1_upload();
                break;
            case 2:
                $this->display_step_2_mapping();
                break;
            case 3:
                $this->display_step_3_progress();
                break;
            default:
                $this->display_step_1_upload();
                break;
        }
        
        echo '</div>';
    }

    /**
     * Display progress indicator.
     *
     * @since    1.0.0
     * @param    int $current_step Current step
     */
    private function display_progress_indicator($current_step) {
        $steps = array(
            1 => __('Upload File', 'bootflow-product-xml-csv-importer'),
            2 => __('Map Fields', 'bootflow-product-xml-csv-importer'),
            3 => __('Import Progress', 'bootflow-product-xml-csv-importer')
        );
        
        echo '<div class="wc-ai-import-progress-indicator">';
        echo '<ul class="wc-ai-import-steps">';
        
        foreach ($steps as $step_num => $step_name) {
            $class = 'step';
            if ($step_num < $current_step) {
                $class .= ' completed';
            } elseif ($step_num == $current_step) {
                $class .= ' active';
            }
            
            echo '<li class="' . esc_attr($class) . '">';
            echo '<span class="step-number">' . esc_html($step_num) . '</span>';
            echo '<span class="step-name">' . esc_html($step_name) . '</span>';
            echo '</li>';
        }
        
        echo '</ul>';
        echo '</div>';
    }

    /**
     * Display step 1: File upload.
     *
     * @since    1.0.0
     */
    private function display_step_1_upload() {
        include_once BFPI_PLUGIN_DIR . 'includes/admin/partials/step-1-upload.php';
    }

    /**
     * Display step 2: Field mapping.
     *
     * @since    1.0.0
     */
    private function display_step_2_mapping() {
        global $wpdb;
        
        // Check if Edit mode (import_id in URL)
        $import_id = isset($_GET['import_id']) ? intval($_GET['import_id']) : 0;
        
        // HANDLE POST SUBMISSION FIRST (before any output)
        if ($import_id > 0 && isset($_POST['update_import'])) {
            // Redirect to display_import_details for POST handling
            $this->display_import_details($import_id);
            return;
        }
        
        // Get parameters from URL OR from database
        if ($import_id > 0) {
            // Edit mode - load from database
            $import = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}bfpi_imports WHERE id = %d",
                $import_id
            ), ARRAY_A);
            
            if ($import) {
                $file_path = $import['file_path'];
                $file_type = $import['file_type'];
                $import_name = $import['name'];
                $schedule_type = $import['schedule_type'];
                $product_wrapper = $import['product_wrapper'];
                $update_existing = $import['update_existing'];
                $skip_unchanged = $import['skip_unchanged'];
                $batch_size = $import['batch_size'] ?? 50;
            } else {
                $file_path = '';
                $file_type = '';
                $import_name = '';
                $schedule_type = '';
                $product_wrapper = 'product';
                $update_existing = '0';
                $skip_unchanged = '0';
                $batch_size = 50;
            }
        } else {
            // New import mode - get from URL parameters
            $file_path = isset($_GET['file_path']) ? sanitize_text_field(wp_unslash($_GET['file_path'])) : '';
            $file_type = isset($_GET['file_type']) ? sanitize_text_field(wp_unslash($_GET['file_type'])) : '';
            $import_name = isset($_GET['import_name']) ? sanitize_text_field(wp_unslash($_GET['import_name'])) : '';
            $schedule_type = isset($_GET['schedule_type']) ? sanitize_text_field(wp_unslash($_GET['schedule_type'])) : '';
            $product_wrapper = isset($_GET['product_wrapper']) ? sanitize_text_field(wp_unslash($_GET['product_wrapper'])) : 'product';
            $update_existing = isset($_GET['update_existing']) ? sanitize_text_field(wp_unslash($_GET['update_existing'])) : '0';
            $skip_unchanged = isset($_GET['skip_unchanged']) ? sanitize_text_field(wp_unslash($_GET['skip_unchanged'])) : '0';
        }
        
        // Pass step-2 data to JavaScript via wp_add_inline_script
        if (!empty($file_path)) {
            $step2_data = array(
                'file_path'       => $file_path,
                'file_type'       => $file_type,
                'import_name'     => $import_name,
                'schedule_type'   => $schedule_type,
                'product_wrapper' => $product_wrapper,
                'update_existing' => $update_existing,
                'skip_unchanged'  => $skip_unchanged,
                'batch_size'      => intval($batch_size ?? 50),
                'ajax_url'        => admin_url('admin-ajax.php'),
                'nonce'           => wp_create_nonce('bfpi_nonce'),
            );
            wp_add_inline_script(
                $this->plugin_name . '-admin',
                'var wcAiImportData = ' . wp_json_encode($step2_data) . ';',
                'before'
            );
        }
        
        include_once BFPI_PLUGIN_DIR . 'includes/admin/partials/step-2-mapping.php';
    }

    /**
     * Display step 3: Import progress.
     *
     * @since    1.0.0
     */
    private function display_step_3_progress() {
        include_once BFPI_PLUGIN_DIR . 'includes/admin/partials/step-3-progress.php';
    }

    /**
     * Display import history page.
     *
     * @since    1.0.0
     */
    public function display_history_page() {
        global $wpdb;
        
        // Handle edit action - redirect to Step 2 with import data
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['import_id'])) {
            $this->display_step_2_mapping();
            return;
        }
        
        // Handle view action
        if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['import_id'])) {
            $this->display_import_details(intval($_GET['import_id']));
            return;
        }
        
        // Handle STOP action - stop the import immediately
        if (isset($_GET['action']) && $_GET['action'] === 'stop' && isset($_GET['import_id'])) {
            $import_id = intval($_GET['import_id']);
            $table = $wpdb->prefix . 'bfpi_imports';
            
            // Update status to stopped/failed
            $wpdb->update($table, array('status' => 'failed'), array('id' => $import_id), array('%s'), array('%d'));
            
            // Clear ALL scheduled cron jobs for this import
            $hooks = array('bfpi_process_chunk', 'bfpi_retry_chunk', 'bfpi_single_chunk');
            foreach ($hooks as $hook) {
                // Clear with import_id as first argument
                wp_clear_scheduled_hook($hook, array($import_id));
                // Also try clearing with just import_id
                $crons = _get_cron_array();
                if (!empty($crons)) {
                    foreach ($crons as $timestamp => $cron) {
                        if (isset($cron[$hook])) {
                            foreach ($cron[$hook] as $key => $data) {
                                if (!empty($data['args']) && isset($data['args'][0]) && intval($data['args'][0]) === $import_id) {
                                    wp_unschedule_event($timestamp, $hook, $data['args']);
                                }
                            }
                        }
                    }
                }
            }
            
            // Clear transient locks to stop any running batch immediately
            delete_transient('bfpi_import_lock_' . $import_id);
            delete_transient('bfpi_import_lock_time_' . $import_id);
            
            // Set kill flag transient to stop any currently running process
            set_transient('bfpi_import_killed_' . $import_id, time(), HOUR_IN_SECONDS);
            
            // Also set global kill flag transient
            set_transient('bfpi_import_killed_global', $import_id . ':' . time(), HOUR_IN_SECONDS);
            
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Import stopped successfully.', 'bootflow-product-xml-csv-importer') . '</p></div>';
        }
        
        // Handle delete import action
        if (isset($_GET['action']) && $_GET['action'] === 'delete_import' && isset($_GET['import_id'])) {
            $import_id = intval($_GET['import_id']);
            
            // Verify nonce
            if (isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'delete_import_' . $import_id)) {
                // Get import data to access file_path
                $import = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}bfpi_imports WHERE id = %d",
                    $import_id
                ), ARRAY_A);
                
                // Delete the file if it exists
                if ($import && !empty($import['file_path']) && file_exists($import['file_path'])) {
                    @wp_delete_file($import['file_path']);
                }
                
                // Delete database record
                $deleted = $wpdb->delete(
                    $wpdb->prefix . 'bfpi_imports',
                    array('id' => $import_id),
                    array('%d')
                );
                
                if ($deleted) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Import and file deleted successfully.', 'bootflow-product-xml-csv-importer') . '</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Failed to delete import.', 'bootflow-product-xml-csv-importer') . '</p></div>';
                }
            }
        }
        
        // Handle delete products action
        if (isset($_GET['action']) && $_GET['action'] === 'delete_products' && isset($_GET['import_id'])) {
            $import_id = intval($_GET['import_id']);
            
            // Verify nonce
            if (isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'delete_products_' . $import_id)) {
                // Get all products associated with this import
                $product_ids = $wpdb->get_col($wpdb->prepare(
                    "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '_wc_import_id' AND meta_value = %d",
                    $import_id
                ));
                
                $deleted_count = 0;
                foreach ($product_ids as $product_id) {
                    if (wp_delete_post($product_id, true)) {
                        $deleted_count++;
                    }
                }
                
                // Update import's processed_products count to 0
                $wpdb->update(
                    $wpdb->prefix . 'bfpi_imports',
                    array('processed_products' => 0),
                    array('id' => $import_id),
                    array('%d'),
                    array('%d')
                );
                
                if ($deleted_count > 0) {
                    // translators: %d is the number of deleted products
                    echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(esc_html__('%d products deleted successfully.', 'bootflow-product-xml-csv-importer'), intval($deleted_count)) . '</p></div>';
                } else {
                    echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__('No products found to delete.', 'bootflow-product-xml-csv-importer') . '</p></div>';
                }
            }
        }
        
        echo '<div class="wrap">';
        echo '<div class="bootflow-header-row">';
        echo '<h1>' . esc_html__('Import History', 'bootflow-product-xml-csv-importer') . '</h1>';
        $this->render_language_switcher();
        echo '</div>';
        
        // Get imports
        $imports = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder -- %i requires WP 6.2+
            $wpdb->prepare( "SELECT * FROM %i ORDER BY created_at DESC", $wpdb->prefix . 'bfpi_imports' ),
            ARRAY_A
        );
        
        if (empty($imports)) {
            echo '<p>' . esc_html__('No imports found.', 'bootflow-product-xml-csv-importer') . '</p>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>' . esc_html__('Name', 'bootflow-product-xml-csv-importer') . '</th>';
            echo '<th>' . esc_html__('File Type', 'bootflow-product-xml-csv-importer') . '</th>';
            echo '<th>' . esc_html__('Products', 'bootflow-product-xml-csv-importer') . '</th>';
            echo '<th>' . esc_html__('Status', 'bootflow-product-xml-csv-importer') . '</th>';
            echo '<th>' . esc_html__('Schedule', 'bootflow-product-xml-csv-importer') . '</th>';
            echo '<th>' . esc_html__('Created', 'bootflow-product-xml-csv-importer') . '</th>';
            echo '<th>' . esc_html__('Last Run', 'bootflow-product-xml-csv-importer') . '</th>';
            echo '<th>' . esc_html__('Actions', 'bootflow-product-xml-csv-importer') . '</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            
            foreach ($imports as $import) {
                // Get actual product count from database (products with this import_id meta)
                $actual_product_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}postmeta WHERE meta_key = '_wc_import_id' AND meta_value = %d",
                    $import['id']
                ));
                
                $schedule_label = 'Disabled';
                if (!empty($import['schedule_type']) && $import['schedule_type'] !== 'none' && $import['schedule_type'] !== 'disabled') {
                    $schedule_labels = array(
                        '15min' => __('Every 15 min', 'bootflow-product-xml-csv-importer'),
                        'hourly' => __('Hourly', 'bootflow-product-xml-csv-importer'),
                        '6hours' => __('Every 6h', 'bootflow-product-xml-csv-importer'),
                        'daily' => __('Daily', 'bootflow-product-xml-csv-importer'),
                        'weekly' => __('Weekly', 'bootflow-product-xml-csv-importer'),
                        'monthly' => __('Monthly', 'bootflow-product-xml-csv-importer')
                    );
                    $schedule_label = $schedule_labels[$import['schedule_type']] ?? $import['schedule_type'];
                }
                
                echo '<tr>';
                echo '<td>' . esc_html($import['name']) . '</td>';
                echo '<td>' . esc_html(strtoupper($import['file_type'])) . '</td>';
                // translators: %1$d is the database count, %2$d is processed count, %3$d is total in file - Show actual products in DB / processed from file / total in file
                echo '<td title="' . esc_attr(sprintf(__('In database: %1$d, Processed: %2$d, In file: %3$d', 'bootflow-product-xml-csv-importer'), $actual_product_count, $import['processed_products'], $import['total_products'])) . '">' . esc_html($actual_product_count) . ' <small style="color:#666;">(' . esc_html($import['processed_products']) . '/' . esc_html($import['total_products']) . ')</small></td>';
                echo '<td>' . esc_html(ucfirst($import['status'])) . '</td>';
                echo '<td>' . esc_html($schedule_label) . '</td>';
                echo '<td>' . esc_html(Bfpi_i18n::localize_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($import['created_at']))) . '</td>';
                echo '<td>';
                if ($import['last_run']) {
                    $last_run_ts = strtotime($import['last_run']);
                    $ago_seconds = current_time('timestamp') - $last_run_ts;
                    if ($ago_seconds < 60) {
                        $ago_text = __('just now', 'bootflow-product-xml-csv-importer');
                    } elseif ($ago_seconds < 3600) {
                        $ago_text = sprintf(__('%d min ago', 'bootflow-product-xml-csv-importer'), intval($ago_seconds / 60));
                    } elseif ($ago_seconds < 86400) {
                        $hours = intval($ago_seconds / 3600);
                        $mins = intval(($ago_seconds % 3600) / 60);
                        $ago_text = sprintf(__('%dh %dm ago', 'bootflow-product-xml-csv-importer'), $hours, $mins);
                    } else {
                        $ago_text = sprintf(__('%d days ago', 'bootflow-product-xml-csv-importer'), intval($ago_seconds / 86400));
                    }
                    echo esc_html(Bfpi_i18n::localize_date('d.m.Y H:i:s', $last_run_ts));
                    echo '<br><small style="color:#888;">(' . esc_html($ago_text) . ')</small>';
                    // Show next scheduled run
                    if (!empty($import['schedule_type']) && $import['schedule_type'] !== 'none' && $import['schedule_type'] !== 'disabled') {
                        $intervals = array('15min'=>900, 'hourly'=>3600, '6hours'=>21600, 'daily'=>86400, 'weekly'=>604800, 'monthly'=>2592000);
                        $interval = $intervals[$import['schedule_type']] ?? 0;
                        if ($interval > 0) {
                            $next_run_ts = $last_run_ts + $interval;
                            $until_seconds = $next_run_ts - current_time('timestamp');
                            if ($until_seconds <= 0) {
                                $next_text = __('⏳ due now', 'bootflow-product-xml-csv-importer');
                            } elseif ($until_seconds < 60) {
                                $next_text = __('⏳ <1 min', 'bootflow-product-xml-csv-importer');
                            } elseif ($until_seconds < 3600) {
                                $next_text = sprintf(__('⏳ in %d min', 'bootflow-product-xml-csv-importer'), intval($until_seconds / 60));
                            } else {
                                $next_text = sprintf(__('⏳ in %dh %dm', 'bootflow-product-xml-csv-importer'), intval($until_seconds / 3600), intval(($until_seconds % 3600) / 60));
                            }
                            echo '<br><small style="color:#0073aa;">' . esc_html($next_text) . '</small>';
                        }
                    }
                } else {
                    echo esc_html__('Never', 'bootflow-product-xml-csv-importer');
                }
                echo '</td>';
                echo '<td>';
                
                // Edit button
                echo '<a href="' . esc_url(admin_url('admin.php?page=bfpi-import-history&action=edit&import_id=' . $import['id'])) . '" class="button button-small button-primary">' . esc_html__('Edit', 'bootflow-product-xml-csv-importer') . '</a> ';
                
                // Stop button - only show if import is processing
                if ($import['status'] === 'processing') {
                    echo '<a href="' . esc_url(admin_url('admin.php?page=bfpi-import-history&action=stop&import_id=' . $import['id'])) . '" class="button button-small">' . esc_html__('Stop', 'bootflow-product-xml-csv-importer') . '</a> ';
                }
                
                // Re-run button
                echo '<a href="' . esc_url(admin_url('admin.php?page=bfpi-import&action=rerun&import_id=' . $import['id'])) . '" class="button button-small">' . esc_html__('Re-run', 'bootflow-product-xml-csv-importer') . '</a> ';
                
                // Delete import button
                $delete_import_url = wp_nonce_url(
                    admin_url('admin.php?page=bfpi-import-history&action=delete_import&import_id=' . $import['id']),
                    'delete_import_' . $import['id']
                );
                echo '<a href="' . esc_url($delete_import_url) . '" class="button button-small button-link-delete" onclick="return confirm(\'' . esc_js(__('Are you sure you want to delete this import and its file?', 'bootflow-product-xml-csv-importer')) . '\')">' . esc_html__('Delete import', 'bootflow-product-xml-csv-importer') . '</a> ';
                
                // Delete products button (AJAX with progress)
                echo '<button type="button" class="button button-small button-link-delete delete-products-ajax" data-import-id="' . esc_attr($import['id']) . '" data-nonce="' . esc_attr(wp_create_nonce('bfpi_nonce')) . '">' . esc_html__('Delete products', 'bootflow-product-xml-csv-importer') . '</button>';
                
                echo '</td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
        }
        
        echo '</div>';
    }

    /**
     * Display import details with full editing capability.
     */
    private function display_import_details($import_id) {
        global $wpdb;
        
        if (defined('WP_DEBUG') && WP_DEBUG) { error_log('=== display_import_details() called for import_id: ' . $import_id); }
        

        $import = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bfpi_imports WHERE id = %d", $import_id), ARRAY_A);

        if (!$import) {
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('Import not found in database for ID: ' . $import_id); }
            echo '<div class="wrap"><h1>' . esc_html__('Import Not Found', 'bootflow-product-xml-csv-importer') . '</h1>';
            echo '<p><a href="' . esc_url(admin_url('admin.php?page=bfpi-import-history')) . '" class="button">Back</a></p></div>';
            return;
        }

        if (defined('WP_DEBUG') && WP_DEBUG) { error_log('Import found: ' . $import['name'] . ', has field_mappings: ' . (empty($import['field_mappings']) ? 'NO' : 'YES')); }

        // Patch: If file_path is empty, try to auto-fill from plugin upload dir
        if (empty($import['file_path'])) {
            $upload_dir = wp_upload_dir();
            $plugin_upload_dir = $upload_dir['basedir'] . '/bootflow-product-xml-csv-importer/';
            if (is_dir($plugin_upload_dir)) {
                $files = glob($plugin_upload_dir . '*');
                if ($files && count($files) > 0) {
                    // Try to find a file that matches import name or type
                    $found = false;
                    foreach ($files as $f) {
                        if (stripos(basename($f), $import['name']) !== false || stripos(basename($f), $import['file_type']) !== false) {
                            $import['file_path'] = $f;
                            $found = true;
                            break;
                        }
                    }
                    // If not found, just use the first file
                    if (!$found) {
                        $import['file_path'] = $files[0];
                    }
                }
            }
        }
        
        // Debug: Log request method
        if (defined('WP_DEBUG') && WP_DEBUG) { error_log('=== EDIT PAGE LOAD ==='); }
        if (defined('WP_DEBUG') && WP_DEBUG) { error_log('POST data exists: ' . (empty($_POST) ? 'NO' : 'YES')); }
        if (defined('WP_DEBUG') && WP_DEBUG) { error_log('update_import in POST: ' . (isset($_POST['update_import']) ? 'YES' : 'NO')); }
        if (defined('WP_DEBUG') && WP_DEBUG) { error_log('run_import_now in POST: ' . (isset($_POST['run_import_now']) ? 'YES' : 'NO')); }
        
        // Handle "Run Import Now" button - saves mapping AND starts import
        if (isset($_POST['run_import_now'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('=== RUN IMPORT NOW CLICKED ==='); }
            
            // Verify nonce
            if (!check_admin_referer('update_import_' . $import_id, '_wpnonce', false)) {
                if (defined('WP_DEBUG') && WP_DEBUG) { error_log('Nonce check FAILED for RUN IMPORT NOW'); }
                wp_die(esc_html__('Security check failed. Please try again.', 'bootflow-product-xml-csv-importer'));
            }
            
            // First, save the mappings (same as update_import)
            $_POST['update_import'] = true; // Trigger save logic below
            // Don't return - let it fall through to save logic, then redirect to step 3
        }
        
        // Handle form submission (only validate nonce on POST, not on GET/view)
        if (isset($_POST['update_import'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('POST data present: YES'); }
            
            // Verify nonce only for POST submissions
            if (!check_admin_referer('update_import_' . $import_id, '_wpnonce', false)) {
                if (defined('WP_DEBUG') && WP_DEBUG) { error_log('Nonce check FAILED for POST submission'); }
                wp_die(esc_html__('Security check failed. Please try again.', 'bootflow-product-xml-csv-importer'));
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('Nonce check: VALID'); }
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('=== SAVING IMPORT MAPPINGS ==='); }
            $field_mapping = isset($_POST['field_mapping']) ? wp_unslash( $_POST['field_mapping'] ) : array();
            $custom_fields = isset($_POST['custom_fields']) ? wp_unslash( $_POST['custom_fields'] ) : array();
            
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('Raw field_mapping count: ' . count($field_mapping)); }
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('Raw field_mapping sample: ' . print_r(array_slice($field_mapping, 0, 2, true), true)); }
            
            // Merge mappings - save ALL fields that have processing_mode or source
            $all_mappings = array();
            foreach ($field_mapping as $wc_field => $mapping_data) {
                // Special handling for shipping_class_formula (uses [formula] instead of [processing_mode])
                // IMPORTANT: Save even if formula is empty (user might want to clear it)
                if ($wc_field === 'shipping_class_formula') {
                    $all_mappings[$wc_field] = $mapping_data;
                    if (!empty($mapping_data['formula'])) {
                    } else {
                    }
                }
                // Save field if it has processing_mode OR source OR update_on_sync flag
                // This ensures update_on_sync checkbox state is always saved
                elseif (!empty($mapping_data['processing_mode']) || !empty($mapping_data['source']) || isset($mapping_data['update_on_sync'])) {
                    $all_mappings[$wc_field] = $mapping_data;
                    if (defined('WP_DEBUG') && WP_DEBUG) { error_log("Saving field: {$wc_field} - mode=" . ($mapping_data['processing_mode'] ?? 'none') . " source=" . ($mapping_data['source'] ?? 'none') . " update_on_sync=" . ($mapping_data['update_on_sync'] ?? 'not set')); }
                } else {
                    if (defined('WP_DEBUG') && WP_DEBUG) { error_log("Skipping empty field: {$wc_field}"); }
                }
            }
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('Merged mappings count: ' . count($all_mappings)); }
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('Merged mappings sample: ' . print_r(array_slice($all_mappings, 0, 2, true), true)); }
            
            // CRITICAL: If no mappings, don't overwrite existing ones!
            if (empty($all_mappings) && !empty($import['field_mappings'])) {
                if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WARNING: No new mappings provided, keeping existing mappings'); }
                $all_mappings = json_decode($import['field_mappings'], true);
                if (!is_array($all_mappings)) {
                    $all_mappings = array();
                }
            }
            
            // Add custom fields
            foreach ($custom_fields as $cf) {
                if (!empty($cf['name']) && !empty($cf['source'])) {
                    $all_mappings['_custom_' . sanitize_key($cf['name'])] = $cf;
                }
            }
            
            // Collect import filters
            $import_filters = array();
            $filter_logic = isset($_POST['filter_logic']) ? sanitize_text_field(wp_unslash($_POST['filter_logic'])) : 'AND';
            $draft_non_matching = isset($_POST['draft_non_matching']) ? 1 : 0;
            
            if (isset($_POST['import_filters']) && is_array($_POST['import_filters'])) {
                $raw_filters = map_deep( wp_unslash( $_POST['import_filters'] ), 'sanitize_text_field' );
                foreach ($raw_filters as $filter) {
                    if (!empty($filter['field']) && !empty($filter['operator'])) {
                        // Validate operator is in allowed list
                        $allowed_operators = array('=', '!=', '>', '<', '>=', '<=', 'contains', 'not_contains', 'empty', 'not_empty');
                        $operator = in_array($filter['operator'], $allowed_operators) ? $filter['operator'] : '=';
                        
                        $filter_data = array(
                            'field' => sanitize_text_field($filter['field']),
                            'operator' => $operator,  // Don't sanitize - use validated value
                            'value' => sanitize_text_field($filter['value'] ?? '')
                        );
                        
                        // Add logic if present (for chaining with next filter)
                        if (isset($filter['logic'])) {
                            $filter_data['logic'] = in_array($filter['logic'], array('AND', 'OR')) ? $filter['logic'] : 'AND';
                        }
                        
                        $import_filters[] = $filter_data;
                    }
                }
            }
            
            // IMPORTANT: Preserve file_path and file_url from existing record
            $existing_import = $wpdb->get_row($wpdb->prepare(
                "SELECT file_path, file_url FROM {$wpdb->prefix}bfpi_imports WHERE id = %d", 
                $import_id
            ), ARRAY_A);
            
            // Prepare custom_fields array with full data (including ai_prompt, ai_provider, php_formula etc.)
            $custom_fields_to_save = array();
            
            foreach ($custom_fields as $cf) {
                if (!empty($cf['name']) && !empty($cf['source'])) {
                    $custom_fields_to_save[] = $cf;
                }
            }
            
            
            $update_data = array(
                'file_path' => $existing_import['file_path'],  // Preserve file path
                'file_url' => $existing_import['file_url'],    // Preserve file URL
                'field_mappings' => wp_json_encode($all_mappings),
                'custom_fields' => wp_json_encode($custom_fields_to_save),  // Save custom fields separately too
                'import_filters' => wp_json_encode($import_filters),
                'filter_logic' => $filter_logic,
                'draft_non_matching' => $draft_non_matching,
                'schedule_type' => sanitize_text_field(wp_unslash($_POST['schedule_type'] ?? $_POST['schedule_type_hidden'] ?? 'none')),
                'schedule_method' => sanitize_text_field(wp_unslash($_POST['schedule_method'] ?? $_POST['schedule_method_hidden'] ?? 'action_scheduler')),
                'update_existing' => isset($_POST['update_existing']) ? '1' : '0',
                'skip_unchanged' => isset($_POST['skip_unchanged']) ? '1' : '0',
                'handle_missing' => isset($_POST['handle_missing']) ? '1' : '0',
                'missing_action' => sanitize_text_field(wp_unslash($_POST['missing_action'] ?? 'draft')),
                'delete_variations' => isset($_POST['delete_variations']) ? '1' : '0',
                'batch_size' => isset($_POST['batch_size']) ? absint(wp_unslash($_POST['batch_size'])) : 50
            );
            
            // DEBUG: Log schedule fields            
            // DEBUG: Log batch_size specifically
            
            // DEBUG: Show shipping_class_formula in JSON before saving
            if (isset($all_mappings['shipping_class_formula'])) {
            } else {
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('JSON to save: ' . substr(wp_json_encode($all_mappings), 0, 500)); }
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('Filters to save: ' . wp_json_encode($import_filters)); }
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('Filter logic: ' . $filter_logic); }
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('Updating import ID: ' . $import_id); }
            
            // DEBUG: Show what we're about to save
            /*
            echo '<div style="background: #fff; padding: 20px; margin: 20px 0; border: 2px solid #0073aa;">';
            echo '<h2>DEBUG: Data being saved to database</h2>';
            echo '<h3>Images field:</h3><pre>' . print_r($all_mappings['images'] ?? 'NOT SET', true) . '</pre>';
            echo '<h3>Featured Image field:</h3><pre>' . print_r($all_mappings['featured_image'] ?? 'NOT SET', true) . '</pre>';
            echo '<h3>Import Filters (' . count($import_filters) . '):</h3><pre>' . print_r($import_filters, true) . '</pre>';
            echo '<h3>Filter Logic: ' . $filter_logic . '</h3>';
            echo '<h3>All mappings (first 5):</h3><pre>' . print_r(array_slice($all_mappings, 0, 5, true), true) . '</pre>';
            echo '<h3>Total fields: ' . count($all_mappings) . '</h3>';
            echo '</div>';
            */
            
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('Import ID: ' . $import_id); }
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('Total mappings: ' . count($all_mappings)); }
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('Mappings JSON length: ' . strlen(wp_json_encode($all_mappings))); }
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('File path to save: ' . ($update_data['file_path'] ?? 'NULL')); }
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('schedule_type to save: ' . $update_data['schedule_type']); }
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('schedule_method to save: ' . $update_data['schedule_method']); }
            
            $result = $wpdb->update(
                $wpdb->prefix . 'bfpi_imports', 
                $update_data, 
                array('id' => $import_id), 
                array('%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%d'),  // 14 formats for 14 fields
                array('%d')
            );
            
            // DEBUG: Verify what was actually saved
            if ($result !== false) {
                $saved_import = $wpdb->get_row($wpdb->prepare("SELECT field_mappings, file_path FROM {$wpdb->prefix}bfpi_imports WHERE id = %d", $import_id), ARRAY_A);
                if ($saved_import) {
                    $saved_mappings = json_decode($saved_import['field_mappings'], true);
                    if (isset($saved_mappings['sku'])) {
                    }
                    $saved_mappings = json_decode($saved_import['field_mappings'], true);
                    if (isset($saved_mappings['shipping_class_formula'])) {
                    } else {
                    }
                }
            }
            
            // Check if "Run Import Now" was clicked
            $should_run_import = isset($_POST['run_import_now']);
            
            if ($should_run_import) {
                
                // Set import status to processing and reset processed count
                $wpdb->update(
                    $wpdb->prefix . 'bfpi_imports',
                    array(
                        'status' => 'pending',  // Set to pending - progress page will kickstart
                        'processed_products' => 0  // Reset processed count
                    ),
                    array('id' => $import_id),
                    array('%s', '%d'),
                    array('%d')
                );
                
                // DON'T trigger import here - let progress page kickstart handle it
                // This prevents double processing
                
                // Redirect to progress page (step 3)
                $redirect_url = admin_url('admin.php?page=bfpi-import&step=3&import_id=' . $import_id);
                wp_safe_redirect($redirect_url);
                exit;
            }
            
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Import updated successfully.', 'bootflow-product-xml-csv-importer') . '</p></div>';
            $import = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bfpi_imports WHERE id = %d", $import_id), ARRAY_A);
            
            // DEBUG: Log reloaded batch_size
        }
        
        // Get existing mappings from field_mappings column
        $existing_mappings = array();
        $mapping_source = 'field_mappings';
        
        if (!empty($import['field_mappings'])) {
            $existing_mappings = json_decode($import['field_mappings'], true);
            if (!is_array($existing_mappings)) {
                $existing_mappings = array();
            }
        }
        
        if (!is_array($existing_mappings)) {
            $existing_mappings = array();
        }
        
        // Load saved custom fields from BOTH sources:
        // 1. From field_mappings with '_custom_' prefix (new format)
        // 2. From dedicated custom_fields column (old format)
        $saved_custom_fields = array();
        
        // FORCE DEBUG - always log
        
        // First, check field_mappings for _custom_ prefixed keys
        foreach ($existing_mappings as $key => $mapping) {
            if (strpos($key, '_custom_') === 0 && is_array($mapping)) {
                $saved_custom_fields[] = $mapping;
            }
        }
        
        // If no custom fields found in field_mappings, check custom_fields column
        if (empty($saved_custom_fields) && !empty($import['custom_fields'])) {
            $legacy_custom_fields = json_decode($import['custom_fields'], true);
            if (is_array($legacy_custom_fields)) {
                $saved_custom_fields = $legacy_custom_fields;
            }
        }
        
        
        if (!empty($existing_mappings)) {        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('Import Edit - No field_mappings data found in import record'); }
        }
        
        if (!empty($saved_custom_fields)) {        }
        
        // Generate secret key
        $import_secret = get_option('bfpi_secret_' . $import_id);
        if (empty($import_secret)) {
            $import_secret = wp_generate_password(32, false);
            update_option('bfpi_secret_' . $import_id, $import_secret);
        }
        
        $cron_url = admin_url('admin-ajax.php?action=bfpi_single_cron&import_id=' . $import_id . '&secret=' . $import_secret);
        
        // Load file structure for dropdowns - use XML Parser for proper nested field support
        $file_path = $import['file_path'];
        $file_fields = array();
        if (file_exists($file_path)) {
            if ($import['file_type'] === 'xml') {
                
                // Use XML Parser class to get proper structure with nested fields
                $xml_parser = new Bfpi_XML_Parser();
                $structure_result = $xml_parser->parse_structure($file_path, $import['product_wrapper'] ?: 'product', 1, 1);
                
                if (!empty($structure_result['structure'])) {
                    // Extract field paths from structure (filter out object/array types, only keep text fields)
                    foreach ($structure_result['structure'] as $field) {
                        if ($field['type'] !== 'object' && $field['type'] !== 'array') {
                            $file_fields[] = $field['path'];
                        }
                    }
                } else {
                }
            }
        } else {
        }
        
        // WooCommerce fields structure
        $woocommerce_fields = array(
            'basic' => array(
                'title' => __('Basic Product Fields', 'bootflow-product-xml-csv-importer'),
                'fields' => array(
                    'sku' => array('label' => __('Product Code (SKU)', 'bootflow-product-xml-csv-importer'), 'required' => true),
                    'name' => array('label' => __('Product Name', 'bootflow-product-xml-csv-importer'), 'required' => false),
                    'description' => array('label' => __('Description', 'bootflow-product-xml-csv-importer'), 'required' => false),
                    'short_description' => array('label' => __('Short Description', 'bootflow-product-xml-csv-importer'), 'required' => false),
                    'status' => array('label' => __('Product Status', 'bootflow-product-xml-csv-importer'), 'required' => false),
                )
            ),
            'pricing' => array(
                'title' => __('Pricing Fields', 'bootflow-product-xml-csv-importer'),
                'fields' => array(
                    'regular_price' => array('label' => __('Regular Price', 'bootflow-product-xml-csv-importer'), 'required' => false),
                    'sale_price' => array('label' => __('Sale Price', 'bootflow-product-xml-csv-importer'), 'required' => false),
                    'tax_status' => array('label' => __('Tax Status', 'bootflow-product-xml-csv-importer'), 'required' => false),
                    'tax_class' => array('label' => __('Tax Class', 'bootflow-product-xml-csv-importer'), 'required' => false),
                )
            ),
            'inventory' => array(
                'title' => __('Inventory Fields', 'bootflow-product-xml-csv-importer'),
                'fields' => array(
                    'manage_stock' => array('label' => __('Manage Stock', 'bootflow-product-xml-csv-importer'), 'required' => false),
                    'stock_quantity' => array('label' => __('Stock Quantity', 'bootflow-product-xml-csv-importer'), 'required' => false),
                    'stock_status' => array('label' => __('Stock Status', 'bootflow-product-xml-csv-importer'), 'required' => false),
                    'backorders' => array('label' => __('Allow Backorders', 'bootflow-product-xml-csv-importer'), 'required' => false),
                )
            ),
            'physical' => array(
                'title' => __('Physical Properties', 'bootflow-product-xml-csv-importer'),
                'fields' => array(
                    'weight' => array('label' => __('Weight', 'bootflow-product-xml-csv-importer'), 'required' => false),
                    'length' => array('label' => __('Length', 'bootflow-product-xml-csv-importer'), 'required' => false),
                    'width' => array('label' => __('Width', 'bootflow-product-xml-csv-importer'), 'required' => false),
                    'height' => array('label' => __('Height', 'bootflow-product-xml-csv-importer'), 'required' => false),
                )
            ),
            'shipping_class_engine' => array(
                'title' => __('Shipping Class Rules', 'bootflow-product-xml-csv-importer'),
                'fields' => array()
            ),
            'media' => array(
                'title' => __('Media Fields', 'bootflow-product-xml-csv-importer'),
                'fields' => array(
                    'images' => array(
                        'label' => __('Product Images', 'bootflow-product-xml-csv-importer'), 
                        'required' => false,
                        'type' => 'textarea',
                        'description' => __('Enter image URLs or use placeholders: {image} = first image, {image[1]} = first, {image[2]} = second, {image*} = all images. Separate multiple values with commas.', 'bootflow-product-xml-csv-importer')
                    ),
                    'featured_image' => array('label' => __('Featured Image', 'bootflow-product-xml-csv-importer'), 'required' => false),
                )
            ),
            'taxonomy' => array(
                'title' => __('Categories & Tags', 'bootflow-product-xml-csv-importer'),
                'fields' => array(
                    'categories' => array('label' => __('Product Categories', 'bootflow-product-xml-csv-importer'), 'required' => false),
                    'tags' => array('label' => __('Product Tags', 'bootflow-product-xml-csv-importer'), 'required' => false),
                    'brand' => array('label' => __('Brand', 'bootflow-product-xml-csv-importer'), 'required' => false),
                )
            ),
            'seo' => array(
                'title' => __('SEO Fields', 'bootflow-product-xml-csv-importer'),
                'fields' => array(
                    'meta_title' => array('label' => __('Meta Title', 'bootflow-product-xml-csv-importer'), 'required' => false),
                    'meta_description' => array('label' => __('Meta Description', 'bootflow-product-xml-csv-importer'), 'required' => false),
                    'meta_keywords' => array('label' => __('Meta Keywords', 'bootflow-product-xml-csv-importer'), 'required' => false),
                )
            )
        );
        
        $settings = get_option('bfpi_settings', array());
        $ai_providers = array('openai' => 'OpenAI GPT', 'gemini' => 'Google Gemini', 'claude' => 'Anthropic Claude', 'grok' => 'xAI Grok', 'copilot' => 'Microsoft Copilot');
        
        // Output HTML
        include_once BFPI_PLUGIN_DIR . 'includes/admin/partials/import-edit.php';
    }

    /**
     * Display resume dialog for partially completed imports.
     *
     * @since    1.0.0
     * @param    array $import Import data
     */
    private function display_resume_dialog($import) {
        $percentage = round(($import['processed_products'] / $import['total_products']) * 100, 1);
        $remaining = $import['total_products'] - $import['processed_products'];
        
        echo '<div class="wrap bfpi-import-wrap">';
        echo '<h1>' . esc_html__('Resume Import?', 'bootflow-product-xml-csv-importer') . '</h1>';
        
        echo '<div class="card" style="max-width: 600px; padding: 20px; margin: 20px 0;">';
        echo '<h2 style="margin-top: 0;">' . esc_html($import['name']) . '</h2>';
        
        echo '<div class="import-progress-summary" style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px;">';
        echo '<p style="font-size: 16px; margin: 0 0 10px 0;">';
        echo '<strong>' . esc_html__('Current Progress:', 'bootflow-product-xml-csv-importer') . '</strong> ';
        echo '<span style="color: #0073aa; font-size: 20px;">' . esc_html($percentage) . '%</span>';
        echo '</p>';
        // translators: %1$d is processed products count, %2$d is total products count
        echo '<p style="margin: 5px 0;">' . sprintf(esc_html__('%1$d of %2$d products processed', 'bootflow-product-xml-csv-importer'), 
            intval($import['processed_products']), intval($import['total_products'])) . '</p>';
        // translators: %d is remaining products count
        echo '<p style="margin: 5px 0; color: #666;">' . sprintf(esc_html__('%d products remaining', 'bootflow-product-xml-csv-importer'), intval($remaining)) . '</p>';
        echo '</div>';
        
        echo '<p style="font-size: 14px; color: #555;">' . esc_html__('This import was previously started. Would you like to:', 'bootflow-product-xml-csv-importer') . '</p>';
        
        echo '<div class="resume-actions" style="display: flex; gap: 15px; margin-top: 20px;">';
        
        // Resume button
        $resume_url = admin_url('admin.php?page=bfpi-import&action=rerun&import_id=' . $import['id'] . '&resume_action=resume');
        echo '<a href="' . esc_url($resume_url) . '" class="button button-primary button-hero" style="display: flex; align-items: center; gap: 8px;">';
        echo '<span class="dashicons dashicons-controls-play" style="margin-top: 5px;"></span>';
        echo '<span>';
        echo '<strong>' . esc_html__('Continue Import', 'bootflow-product-xml-csv-importer') . '</strong><br>';
        // translators: %d is the product number to resume from
        echo '<small style="font-weight: normal;">' . sprintf(esc_html__('Resume from product %d', 'bootflow-product-xml-csv-importer'), intval($import['processed_products']) + 1) . '</small>';
        echo '</span>';
        echo '</a>';
        
        // Start Over button
        $restart_url = admin_url('admin.php?page=bfpi-import&action=rerun&import_id=' . $import['id'] . '&resume_action=restart');
        echo '<a href="' . esc_url($restart_url) . '" class="button button-secondary button-hero" style="display: flex; align-items: center; gap: 8px;" onclick="return confirm(\'' . esc_js(__('Are you sure? This will reset progress and start from the beginning.', 'bootflow-product-xml-csv-importer')) . '\')">';
        echo '<span class="dashicons dashicons-update" style="margin-top: 5px;"></span>';
        echo '<span>';
        echo '<strong>' . esc_html__('Start Over', 'bootflow-product-xml-csv-importer') . '</strong><br>';
        echo '<small style="font-weight: normal;">' . esc_html__('Reset and import all products', 'bootflow-product-xml-csv-importer') . '</small>';
        echo '</span>';
        echo '</a>';
        
        echo '</div>';
        
        // Cancel link
        echo '<p style="margin-top: 20px;">';
        echo '<a href="' . esc_url(admin_url('admin.php?page=bfpi-import-history')) . '">' . esc_html__('← Back to Import History', 'bootflow-product-xml-csv-importer') . '</a>';
        echo '</p>';
        
        echo '</div>'; // .card
        echo '</div>'; // .wrap
    }

    /**
     * Re-run an existing import.
     *
     * @since    1.0.0
     * @param    int $import_id Import ID to re-run
     * @param    bool $resume Whether to resume from current position (true) or restart (false)
     */
    private function rerun_import($import_id, $resume = false) {
        global $wpdb;
        
        $upload_dir = wp_upload_dir();
        $log_file = $upload_dir['basedir'] . '/bootflow-product-xml-csv-importer/logs/import_debug.log';
        
        $import = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bfpi_imports WHERE id = %d", $import_id), ARRAY_A);
        
        if (!$import) {
            wp_die(esc_html__('Import not found.', 'bootflow-product-xml-csv-importer'));
        }
        
        // CRITICAL: Clear any kill flag transients that might have been set by Stop action
        delete_transient('bfpi_import_killed_' . $import_id);
        delete_transient('bfpi_import_killed_global');
        
        // Clear transient locks from previous run
        delete_transient('bfpi_import_lock_' . $import_id);
        delete_transient('bfpi_import_lock_time_' . $import_id);
        
        // Prepare update data
        $update_data = array(
            'status' => 'pending'  // Use pending - kickstart will set to processing
        );
        $update_formats = array('%s');
        
        // Only reset processed count if NOT resuming
        if (!$resume) {
            $update_data['processed_products'] = 0;
            $update_formats[] = '%d';
        } else {
        }
        
        // Update import status
        $result = $wpdb->update(
            $wpdb->prefix . 'bfpi_imports',
            $update_data,
            array('id' => $import_id),
            $update_formats,
            array('%d')
        );
        
        if (defined('WP_DEBUG') && WP_DEBUG) { error_log("Status reset result: " . ($result !== false ? "SUCCESS" : "FAILED")); }
        
        // DON'T trigger import here - let progress page kickstart handle it
        // This prevents double processing when both this function and kickstart run
        
        // Just redirect to progress page - kickstart will start the import
        $redirect_url = admin_url('admin.php?page=bfpi-import&step=3&import_id=' . $import_id);
        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Display settings page.
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        // Handle form submission
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        
        // Load settings
        $settings = get_option('bfpi_settings', array());
        
        // Generate secret key if not exists
        if (empty($settings['cron_secret_key'])) {
            $settings['cron_secret_key'] = wp_generate_password(32, false);
            update_option('bfpi_settings', $settings);
        }
        
        // Include the settings page partial (with all tabs)
        include_once BFPI_PLUGIN_DIR . 'includes/admin/partials/settings-page.php';
    }

    /**
     * Normalize PHP formula to fix common user mistakes.
     * Makes formulas more forgiving and user-friendly.
     *
     * @since    1.0.0
     * @param    string $formula Raw formula from user
     * @return   string Normalized formula ready for execution
     */
    private function normalize_php_formula($formula) {
        $formula = trim($formula);
        
        // Handle simple expressions without any control structures
        $has_control = preg_match('/\b(if|else|elseif|switch|for|foreach|while|do)\b/i', $formula);
        
        if (!$has_control) {
            // Simple expression - just add return
            $formula = rtrim($formula, ';');
            return 'return ' . $formula . ';';
        }
        
        // For complex formulas with control structures, keep the original formatting
        // Only do minimal normalization to preserve multi-line code blocks
        
        // If formula already ends with return statement, use as-is
        if (preg_match('/return\s+[^;]+;\s*$/i', $formula)) {
            return $formula;
        }
        
        // If formula has else block covering all cases, use as-is
        if (stripos($formula, 'else {') !== false || stripos($formula, 'else{') !== false) {
            return $formula;
        }
        
        // Pattern: condition ? true : false (ternary without return)
        if (preg_match('/^\$?\w+.*\?.*:.*$/i', $formula) && stripos($formula, 'return') === false) {
            $formula = rtrim($formula, ';');
            return 'return ' . $formula . ';';
        }
        
        // For simple single-line if without braces, normalize
        $single_line = preg_replace('/\s+/', ' ', $formula);
        if (preg_match('/^if\s*\((.+?)\)\s*return\s+(.+?)(?:;?\s*)?$/i', $single_line, $matches)) {
            $condition = trim($matches[1]);
            $return_value = rtrim(trim($matches[2]), ';');
            return "if ({$condition}) { return {$return_value}; } return \$value;";
        }
        
        return $formula;
    }

    /**
     * Detect file type from URL path patterns.
     *
     * @since    1.0.0
     * @param    string $url The URL to analyze
     * @return   string 'xml', 'csv', or empty string if unknown
     */
    private function detect_file_type_from_url($url) {
        // First check file extension
        $path = wp_parse_url($url, PHP_URL_PATH);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        if (in_array($extension, array('xml', 'csv'))) {
            return $extension;
        }
        
        // Check URL path patterns (e.g., /xml/ or /csv/)
        $url_lower = strtolower($url);
        
        if (strpos($url_lower, '/xml/') !== false || strpos($url_lower, '/xml?') !== false) {
            return 'xml';
        }
        
        if (strpos($url_lower, '/csv/') !== false || strpos($url_lower, '/csv?') !== false) {
            return 'csv';
        }
        
        // Check query parameters
        $query = wp_parse_url($url, PHP_URL_QUERY);
        if ($query) {
            parse_str($query, $params);
            foreach ($params as $key => $value) {
                $key_lower = strtolower($key);
                $value_lower = strtolower($value);
                
                if (in_array($key_lower, array('format', 'type', 'output', 'export'))) {
                    if (in_array($value_lower, array('xml', 'csv'))) {
                        return $value_lower;
                    }
                }
            }
        }
        
        return '';
    }

    /**
     * Detect file type from file content.
     *
     * @since    1.0.0
     * @param    string $file_path Path to the file
     * @return   string 'xml' or 'csv' (defaults to xml if uncertain)
     */
    private function detect_file_type_from_content($file_path) {
        if (!file_exists($file_path)) {
            return 'xml'; // Default fallback
        }
        
        // Read first 4KB of file
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Required for binary file type detection
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            return 'xml';
        }
        
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
        $sample = fread($handle, 4096);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
        fclose($handle);
        
        if (empty($sample)) {
            return 'xml';
        }
        
        // Trim BOM and whitespace
        $sample = ltrim($sample, "\xEF\xBB\xBF\xFE\xFF\xFF\xFE\x00"); // UTF-8, UTF-16 BOMs
        $sample = ltrim($sample);
        
        // Check for XML declaration or root element
        if (strpos($sample, '<?xml') === 0) {
            return 'xml';
        }
        
        // Check if starts with < (likely XML element)
        if (strpos($sample, '<') === 0) {
            return 'xml';
        }
        
        // Check for common CSV patterns
        // CSV typically has commas or semicolons as delimiters
        $first_line_end = strpos($sample, "\n");
        $first_line = $first_line_end !== false ? substr($sample, 0, $first_line_end) : $sample;
        
        // Count potential delimiters in first line
        $comma_count = substr_count($first_line, ',');
        $semicolon_count = substr_count($first_line, ';');
        $tab_count = substr_count($first_line, "\t");
        
        // If we have multiple delimiters, likely CSV
        if ($comma_count >= 2 || $semicolon_count >= 2 || $tab_count >= 2) {
            // Additional check: CSV shouldn't have XML-like content
            if (strpos($sample, '</') === false && strpos($sample, '/>') === false) {
                return 'csv';
            }
        }
        
        // Check Content-Type from response headers if available in meta
        // This is a fallback for downloaded files
        
        // Default to XML if uncertain
        return 'xml';
    }

    /**
     * Save plugin settings.
     *
     * @since    1.0.0
     */
    private function save_settings() {
        if ( ! isset( $_POST['bfpi_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bfpi_settings_nonce'] ) ), 'bfpi_settings' ) ) {
            return;
        }
        
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }
        
        $settings = get_option('bfpi_settings', array());
        
        $settings['chunk_size'] = isset( $_POST['chunk_size'] ) ? intval( $_POST['chunk_size'] ) : 50;
        $settings['max_file_size'] = isset( $_POST['max_file_size'] ) ? intval( $_POST['max_file_size'] ) : 104857600;
        $settings['default_ai_provider'] = isset( $_POST['default_ai_provider'] ) ? sanitize_text_field( wp_unslash( $_POST['default_ai_provider'] ) ) : '';
        $settings['ai_api_keys'] = array();
        
        if (isset($_POST['ai_api_keys']) && is_array($_POST['ai_api_keys'])) {
            foreach ( wp_unslash( $_POST['ai_api_keys'] ) as $provider => $key ) {
                $settings['ai_api_keys'][ sanitize_text_field( $provider ) ] = sanitize_text_field( $key );
            }
        }
        
        update_option('bfpi_settings', $settings);
        
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p>' . esc_html__('Settings saved successfully.', 'bootflow-product-xml-csv-importer') . '</p>';
        echo '</div>';
    }

    /**
     * Handle file upload AJAX request.
     *
     * @since    1.0.0
     */
    public function handle_file_upload() {
        // Log every invocation to detect duplicates
        
        // Verify nonce - support both standard POST and FormData
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : (isset($_REQUEST['nonce']) ? sanitize_text_field(wp_unslash($_REQUEST['nonce'])) : '');
        if (!wp_verify_nonce($nonce, 'bfpi_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions to access this page.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        try {
            // Debug: Log received data (remove this in production)
            
            $upload_method = sanitize_text_field(wp_unslash($_POST['upload_method'] ?? ''));
            $import_name = sanitize_text_field(wp_unslash($_POST['import_name'] ?? ''));
            $schedule_type = sanitize_text_field(wp_unslash($_POST['schedule_type'] ?? 'once'));
            $product_wrapper = sanitize_text_field(wp_unslash($_POST['product_wrapper'] ?? 'product'));
            $update_existing = isset($_POST['update_existing']) ? '1' : '0';
            $skip_unchanged = isset($_POST['skip_unchanged']) ? '1' : '0';
            $force_file_type = isset($_POST['force_file_type']) ? sanitize_text_field(wp_unslash($_POST['force_file_type'])) : 'auto';
            $handle_missing = isset($_POST['handle_missing']) ? '1' : '0';
            $missing_action = isset($_POST['missing_action']) ? sanitize_text_field(wp_unslash($_POST['missing_action'])) : 'draft';
            $delete_variations = isset($_POST['delete_variations']) ? '1' : '0';
            
            // Validate required fields
            if (empty($import_name)) {
                throw new Exception(__('Import name is required.', 'bootflow-product-xml-csv-importer'));
            }
            
            if (empty($upload_method)) {
                throw new Exception(__('Upload method is required.', 'bootflow-product-xml-csv-importer'));
            }
            
            $file_path = '';
            $file_type = '';
            
            if ($upload_method === 'file' && isset($_FILES['file'])) {
                // Handle file upload
                $uploaded_file = $_FILES['file'];
                
                if ($uploaded_file['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception(__('File upload error.', 'bootflow-product-xml-csv-importer'));
                }
                
                $file_type = strtolower(pathinfo($uploaded_file['name'], PATHINFO_EXTENSION));
                
                // Allow files without extension (from URLs) or with xml/csv extension
                if (!empty($file_type) && !in_array($file_type, array('xml', 'csv'))) {
                    throw new Exception(__('Invalid file type. Only XML and CSV files are allowed.', 'bootflow-product-xml-csv-importer'));
                }
                
                // Apply force file type if set
                if ($force_file_type !== 'auto') {
                    $file_type = $force_file_type;
                } elseif (empty($file_type)) {
                    // Auto-detect from content if no extension
                    $file_type = $this->detect_file_type_from_content($uploaded_file['tmp_name']);
                }
                
                $upload_dir = wp_upload_dir();
                $basedir = $upload_dir['basedir'];
                $plugin_upload_dir = $basedir . '/bootflow-product-xml-csv-importer/';
                
                // Create directory if it doesn't exist
                if (!is_dir($plugin_upload_dir)) {
                    wp_mkdir_p($plugin_upload_dir);
                }
                
                // Use wp_handle_upload for secure file handling
                // test_type => false: skip WP MIME check — extension already validated above
                $upload_overrides = array(
                    'test_form' => false,
                    'test_type' => false,
                    'unique_filename_callback' => function( $dir, $name, $ext ) {
                        return time() . '_' . sanitize_file_name( $name );
                    },
                );
                // Override upload dir to our custom directory
                add_filter( 'upload_dir', function( $dirs ) use ( $plugin_upload_dir ) {
                    $dirs['path']    = rtrim( $plugin_upload_dir, '/' );
                    $dirs['url']     = '';
                    $dirs['subdir']  = '';
                    $dirs['basedir'] = rtrim( $plugin_upload_dir, '/' );
                    $dirs['baseurl'] = '';
                    return $dirs;
                });
                $uploaded = wp_handle_upload( $uploaded_file, $upload_overrides );
                remove_all_filters( 'upload_dir' );
                
                if ( isset( $uploaded['error'] ) ) {
                    throw new Exception( esc_html( $uploaded['error'] ) );
                }
                $file_path = $uploaded['file'];
                
            } elseif ($upload_method === 'url') {
                // Handle URL upload
                $file_url = esc_url_raw(wp_unslash($_POST['file_url'] ?? ''));
                
                if (empty($file_url)) {
                    throw new Exception(__('File URL is required.', 'bootflow-product-xml-csv-importer'));
                }

                // WP.org compliance: validate URL for SSRF protection
                $url_validation = Bfpi_Security::validate_remote_url($file_url);
                if (!$url_validation['valid']) {
                    throw new Exception($url_validation['error']);
                }

                // Download file
                $upload_dir = wp_upload_dir();
                // Fix uppercase /Var issue
                $basedir = $upload_dir['basedir'];
                $plugin_upload_dir = $basedir . '/bootflow-product-xml-csv-importer/';

                // Create directory if it doesn't exist
                if (!is_dir($plugin_upload_dir)) {
                    wp_mkdir_p($plugin_upload_dir);
                }

                $base_filename = sanitize_file_name(basename(wp_parse_url($file_url, PHP_URL_PATH)));
                if (empty($base_filename)) {
                    $base_filename = 'download';
                }
                
                // Save WITHOUT extension - same as Browse upload
                $file_path = $plugin_upload_dir . time() . '_' . $base_filename;

                // Download with streaming for large files
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Required for streaming large file downloads
                $temp_file = fopen($file_path, 'w');
                if (!$temp_file) {
                    throw new Exception(esc_html__('Failed to create temporary file.', 'bootflow-product-xml-csv-importer'));
                }
                
                // WP.org compliance: use wp_safe_remote_get with proper timeout and user-agent
                $response = wp_safe_remote_get($file_url, array(
                    'timeout' => 300, // WP.org compliance: reasonable timeout
                    'redirection' => 5, // WP.org compliance: limit redirects
                    'sslverify' => true, // WP.org compliance: verify SSL by default
                    'stream' => true,
                    'filename' => $file_path,
                    'user-agent' => 'Bootflow-WooCommerce-Importer/' . BFPI_VERSION
                ));
                
                if (is_wp_error($response)) {
                    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
                    fclose($temp_file);
                    if (file_exists($file_path)) wp_delete_file($file_path);
                    throw new Exception(esc_html__('Failed to download file from URL: ', 'bootflow-product-xml-csv-importer') . esc_html($response->get_error_message()));
                }

                $response_code = wp_remote_retrieve_response_code($response);
                if ($response_code !== 200) {
                    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
                    fclose($temp_file);
                    if (file_exists($file_path)) wp_delete_file($file_path);
                    throw new Exception(esc_html__('Failed to download file from URL. HTTP Status: ', 'bootflow-product-xml-csv-importer') . esc_html($response_code));
                }
                
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
                fclose($temp_file);
                
                if (!file_exists($file_path) || filesize($file_path) === 0) {
                    throw new Exception(esc_html__('Downloaded file is empty or failed to save.', 'bootflow-product-xml-csv-importer'));
                }
                
                // Wait for file to fully download - check file size stability
                $prev_size = 0;
                $stable_count = 0;
                $max_wait = 60; // 60 seconds max
                $waited = 0;
                
                while ($waited < $max_wait) {
                    clearstatcache(true, $file_path);
                    $current_size = filesize($file_path);
                    
                    if ($current_size === $prev_size) {
                        $stable_count++;
                        if ($stable_count >= 3) {
                            // File size stable for 3 checks (1.5 seconds) - download complete
                            break;
                        }
                    } else {
                        $stable_count = 0;
                        $prev_size = $current_size;
                    }
                    
                    usleep(500000); // 0.5 seconds
                    $waited++;
                }
                
                // Determine file type
                if ($force_file_type !== 'auto') {
                    $file_type = $force_file_type;
                } else {
                    // Try to detect from URL path
                    $file_type = $this->detect_file_type_from_url($file_url);
                    
                    // If still unknown, detect from downloaded content
                    if (empty($file_type)) {
                        $file_type = $this->detect_file_type_from_content($file_path);
                    }
                }
                
            } else {
                throw new Exception(__('No file provided.', 'bootflow-product-xml-csv-importer'));
            }
            
            // Validate file exists
            if (!file_exists($file_path)) {
                throw new Exception(__('File upload failed - file does not exist.', 'bootflow-product-xml-csv-importer'));
            }
            
            // Validate file size
            $file_size = filesize($file_path);
            if ($file_size === 0) {
                wp_delete_file($file_path);
                throw new Exception(__('File is empty.', 'bootflow-product-xml-csv-importer'));
            }
            
            // Load parser classes if not already loaded
            if ($file_type === 'xml') {
                if (!class_exists('Bfpi_XML_Parser')) {
                    require_once BFPI_PLUGIN_DIR . 'includes/class-bfpi-xml-parser.php';
                }
                $parser = new Bfpi_XML_Parser();
                $validation = $parser->validate_xml_file($file_path);
            } else {
                if (!class_exists('Bfpi_CSV_Parser')) {
                    require_once BFPI_PLUGIN_DIR . 'includes/class-bfpi-csv-parser.php';
                }
                $parser = new Bfpi_CSV_Parser();
                $validation = $parser->validate_csv_file($file_path);
            }
            
            if (!$validation['valid']) {
                if (file_exists($file_path)) {
                    wp_delete_file($file_path);
                }
                throw new Exception($validation['message']);
            }
            
            // Count products before redirect
            $total_products = 0;
            if ($file_type === 'xml') {
                $count_result = $parser->count_products_and_extract_structure($file_path, $product_wrapper);
                if ($count_result['success']) {
                    $total_products = $count_result['total_products'];
                }
            } else {
                $count_result = $parser->count_rows_and_extract_structure($file_path);
                if ($count_result['success']) {
                    $total_products = $count_result['total_rows'];
                }
            }
            
            // Store total products in a transient (avoid PHP sessions)
            set_transient( 'bfpi_import_total_products_' . get_current_user_id(), $total_products, HOUR_IN_SECONDS );
            
            // Create import record in database
            global $wpdb;
            $table_name = $wpdb->prefix . 'bfpi_imports';
            
            $wpdb->insert(
                $table_name,
                array(
                    'name' => $import_name,
                    'file_path' => $file_path,
                    'file_url' => $file_path, // Store same path for backward compatibility
                    'file_type' => $file_type,
                    'product_wrapper' => $product_wrapper,
                    'schedule_type' => $schedule_type,
                    'update_existing' => $update_existing,
                    'skip_unchanged' => $skip_unchanged,
                    'handle_missing' => $handle_missing,
                    'missing_action' => $missing_action,
                    'delete_variations' => $delete_variations,
                    'total_products' => $total_products,
                    'status' => 'pending',
                    'created_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%d', '%d', '%s', '%s')
            );
            
            $import_id = $wpdb->insert_id;
            
            wp_send_json_success(array(
                'message' => __('File uploaded successfully.', 'bootflow-product-xml-csv-importer'),
                'total_products' => $total_products,
                'import_id' => $import_id,
                'redirect_url' => admin_url('admin.php?page=bfpi-import&step=2&import_id=' . $import_id)
            ));
            
        } catch (Exception $e) {
            // Debug: Log the error (remove this in production)
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import - Upload error: ' . $e->getMessage()); }
            
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Handle parse structure AJAX request.
     *
     * @since    1.0.0
     */
    public function handle_parse_structure() {
        // Verify nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')), 'bfpi_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions to access this page.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        try {
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import - Parse structure started'); }
            
            $file_path = sanitize_text_field(wp_unslash($_POST['file_path']));
            $file_type = sanitize_text_field(wp_unslash($_POST['file_type']));
            $page = intval(wp_unslash($_POST['page'] ?? 1));
            $per_page = intval(wp_unslash($_POST['per_page'] ?? 5));
            
            if (defined('WP_DEBUG') && WP_DEBUG) { 
                error_log('WC XML CSV AI Import - Parse structure params: ' . wp_json_encode([
                    'file_path' => $file_path,
                    'file_type' => $file_type,
                    'page' => $page,
                    'per_page' => $per_page
                ])); 
            }
            
            // Wait for file to be fully written - retry up to 5 times
            $max_retries = 5;
            $retry_delay = 200000; // 200ms in microseconds
            $file_ready = false;
            
            for ($i = 0; $i < $max_retries; $i++) {
                if (file_exists($file_path)) {
                    $file_size = filesize($file_path);
                    if ($file_size > 0) {
                        // Try to open and read file to ensure it's not locked
                        clearstatcache(true, $file_path);
                        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Checking file lock status
                        $handle = @fopen($file_path, 'r');
                        if ($handle) {
                            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
                            fclose($handle);
                            $file_ready = true;
                            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import - File ready after ' . ($i + 1) . ' attempts'); }
                            break;
                        }
                    }
                }
                if ($i < $max_retries - 1) {
                    usleep($retry_delay);
                }
            }
            
            if (!$file_ready) {
                throw new Exception(__('File is not ready yet. Please refresh the page and try again.', 'bootflow-product-xml-csv-importer'));
            }
            
            if ($file_type === 'xml') {
                $product_wrapper = sanitize_text_field(wp_unslash($_POST['product_wrapper']));
                if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import - Using XML parser with wrapper: ' . $product_wrapper); }
                
                $xml_parser = new Bfpi_XML_Parser();
                $result = $xml_parser->parse_structure($file_path, $product_wrapper, $page, $per_page);
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import - Using CSV parser'); }
                $csv_parser = new Bfpi_CSV_Parser();
                $result = $csv_parser->parse_structure($file_path, $page, $per_page);
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import - Parse structure completed successfully'); }
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import - Parse structure error: ' . $e->getMessage()); }
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import - Parse structure trace: ' . $e->getTraceAsString()); }
            
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Handle start import AJAX request.
     *
     * @since    1.0.0
     */
    public function handle_start_import() {
        
        // Verify nonce - support both standard POST and FormData
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : (isset($_REQUEST['nonce']) ? sanitize_text_field(wp_unslash($_REQUEST['nonce'])) : '');
        if (defined('WP_DEBUG') && WP_DEBUG) { error_log('Nonce check: ' . ($nonce ? 'exists' : 'missing')); }
        
        if (!wp_verify_nonce($nonce, 'bfpi_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        if (defined('WP_DEBUG') && WP_DEBUG) { error_log('Nonce verified successfully'); }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions to access this page.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        try {
            // Debug: Log received data (remove this in production)
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import - Start import data received'); }
            
            // Decode JSON strings from FormData
            $field_mapping = array();
            $custom_fields = array();
            $import_filters = array();
            
            if (isset($_POST['field_mapping_json'])) {
                $field_mapping = json_decode(wp_unslash($_POST['field_mapping_json']), true);
                if (is_array($field_mapping)) {
                    $field_mapping = map_deep($field_mapping, 'sanitize_text_field');
                }
                if (defined('WP_DEBUG') && WP_DEBUG) { error_log('field_mapping from JSON: ' . print_r($field_mapping, true)); }
            }
            
            if (isset($_POST['custom_fields_json'])) {
                $custom_fields = json_decode(wp_unslash($_POST['custom_fields_json']), true);
                if (is_array($custom_fields)) {
                    $custom_fields = map_deep($custom_fields, 'sanitize_text_field');
                }
                if (defined('WP_DEBUG') && WP_DEBUG) { error_log('custom_fields from JSON: ' . print_r($custom_fields, true)); }
            }
            
            if (isset($_POST['import_filters_json'])) {
                $import_filters = json_decode(wp_unslash($_POST['import_filters_json']), true);
                if (is_array($import_filters)) {
                    $import_filters = map_deep($import_filters, 'sanitize_text_field');
                }
                if (defined('WP_DEBUG') && WP_DEBUG) { error_log('import_filters from JSON: ' . print_r($import_filters, true)); }
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('After decode - field_mapping count: ' . count($field_mapping)); }
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('Attributes check: ' . print_r($field_mapping['attributes_variations'] ?? 'NOT SET', true)); }
            
            // Check if this is updating existing import
            $import_id = isset($_POST['import_id']) ? intval(wp_unslash($_POST['import_id'])) : 0;
            
            // Collect import data from form fields
            $import_data = array(
                'import_id' => $import_id,  // Pass import_id to importer
                'import_name' => sanitize_text_field(wp_unslash($_POST['import_name'] ?? '')),
                'file_path' => sanitize_text_field(wp_unslash($_POST['file_path'] ?? '')),
                'file_type' => sanitize_text_field(wp_unslash($_POST['file_type'] ?? '')),
                'schedule_type' => sanitize_text_field(wp_unslash($_POST['schedule_type'] ?? 'once')),
                'product_wrapper' => sanitize_text_field(wp_unslash($_POST['product_wrapper'] ?? 'product')),
                'update_existing' => isset($_POST['update_existing']) ? sanitize_text_field(wp_unslash($_POST['update_existing'])) : '0',
                'skip_unchanged' => isset($_POST['skip_unchanged']) ? sanitize_text_field(wp_unslash($_POST['skip_unchanged'])) : '0',
                'field_mapping' => $field_mapping,
                'processing_modes' => isset($_POST['processing_modes']) ? map_deep(wp_unslash($_POST['processing_modes']), 'sanitize_text_field') : array(),
                'processing_configs' => isset($_POST['processing_configs']) ? map_deep(wp_unslash($_POST['processing_configs']), 'sanitize_text_field') : array(),
                'ai_settings' => isset($_POST['ai_settings']) ? map_deep(wp_unslash($_POST['ai_settings']), 'sanitize_text_field') : array(),
                'custom_fields' => $custom_fields,
                'import_filters' => $import_filters,
                'filter_logic' => sanitize_text_field(wp_unslash($_POST['filter_logic'] ?? 'AND')),
                'draft_non_matching' => isset($_POST['draft_non_matching']) ? 1 : 0
            );
            
            // Load importer class if not loaded
            if (!class_exists('Bfpi_Importer')) {
                require_once BFPI_PLUGIN_DIR . 'includes/class-bfpi-importer.php';
            }
            
            $importer = new Bfpi_Importer();
            $import_id = $importer->start_import($import_data);
            
            wp_send_json_success(array(
                'import_id' => $import_id,
                'message' => __('Import started successfully.', 'bootflow-product-xml-csv-importer'),
                'debug' => 'Import ID: ' . $import_id . ', File: ' . $import_data['file_path']
            ));
            
        } catch (Exception $e) {
            // Debug: Log the error (remove this in production)
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import - Start import error: ' . $e->getMessage()); }
            
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Handle kickstart import AJAX request.
     * Triggers import processing for stuck imports at 0%.
     *
     * @since    1.0.0
     */
    public function handle_kickstart_import() {
        // Verify nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')), 'bfpi_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions to access this page.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        $import_id = intval(wp_unslash($_POST['import_id']));
        
        
        try {
            global $wpdb;
            
            // Check if import is already in progress or completed
            $import = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}bfpi_imports WHERE id = %d",
                $import_id
            ));
            
            if (!$import) {
                wp_send_json_error(array('message' => __('Import not found.', 'bootflow-product-xml-csv-importer')));
                return;
            }
            
            // Don't kickstart if already completed
            if ($import->status === 'completed') {
                wp_send_json_success(array(
                    'message' => __('Import already completed.', 'bootflow-product-xml-csv-importer'),
                    'skipped' => true
                ));
                return;
            }
            
            // For processing status with products already done, check if actively processing
            // (Don't skip for pending status - that means we need to resume/restart)
            if ($import->status === 'processing' && intval($import->processed_products) > 0) {
                // Check if there's a lock (meaning it's actively running)
                $lock = get_transient('bfpi_import_lock_' . $import_id);
                if ($lock !== false) {
                    wp_send_json_success(array(
                        'message' => __('Import already in progress.', 'bootflow-product-xml-csv-importer'),
                        'skipped' => true
                    ));
                    return;
                }
            }
            
            // Set status to processing before triggering
            $wpdb->update(
                $wpdb->prefix . 'bfpi_imports',
                array('status' => 'processing'),
                array('id' => $import_id),
                array('%s'),
                array('%d')
            );
            
            // Determine correct offset - for Resume, start from where we left off
            $offset = intval($import->processed_products);
            $batch_size = intval($import->batch_size) ?: 5;
            
            // Trigger import chunk processing directly with correct offset
            do_action('bfpi_process_chunk', $import_id, $offset, $batch_size);
            
            
            wp_send_json_success(array(
                'message' => __('Import processing started.', 'bootflow-product-xml-csv-importer'),
                'offset' => $offset
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * Handle cron ping AJAX request - triggers WP-Cron to run.
     * 
     * This is called every 2 seconds from progress page to ensure
     * WP-Cron continues processing import chunks.
     * Also checks for stuck imports and reschedules them.
     *
     * @since    1.0.0
     */
    public function handle_ping_cron() {
        // Security: verify nonce and capability
        check_ajax_referer( 'bfpi_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }
        
        $import_id = isset($_POST['import_id']) ? intval(wp_unslash($_POST['import_id'])) : 0;
        
        // Trigger WP-Cron
        spawn_cron();
        
        // Check if import is stuck (has processing status but no scheduled cron event)
        if ($import_id > 0) {
            global $wpdb;
            $import = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}bfpi_imports WHERE id = %d",
                $import_id
            ));
            
            if ($import && $import->status === 'processing') {
                // Check if there's a scheduled cron event for this import
                $has_scheduled_event = false;
                $cron_array = _get_cron_array();
                if (is_array($cron_array)) {
                    foreach ($cron_array as $timestamp => $crons) {
                        if (isset($crons['bfpi_process_chunk'])) {
                            foreach ($crons['bfpi_process_chunk'] as $key => $event) {
                                if (isset($event['args'][0]) && $event['args'][0] == $import_id) {
                                    $has_scheduled_event = true;
                                    break 2;
                                }
                            }
                        }
                    }
                }
                
                // Check for stale lock (lock exists but is older than 3 minutes)
                $lock_key = 'bfpi_import_lock_' . $import_id;
                $lock_time_key = 'bfpi_import_lock_time_' . $import_id;
                $lock_exists = get_transient($lock_key) !== false;
                $lock_time = get_transient($lock_time_key);
                $lock_age = $lock_time ? (time() - intval($lock_time)) : 999;
                $lock_is_stale = $lock_exists && $lock_age > 180;
                
                // If no scheduled event and no active lock (or stale lock), reschedule
                if (!$has_scheduled_event && (!$lock_exists || $lock_is_stale)) {
                    // Clear stale lock if exists
                    if ($lock_is_stale) {
                        delete_transient($lock_key);
                        delete_transient($lock_time_key);
                    }
                    
                    // Calculate next offset based on already processed products
                    $processed = intval($import->processed_products);
                    $chunk_size = 5; // Match the chunk size used elsewhere
                    
                    // Schedule next chunk
                    wp_schedule_single_event(time(), 'bfpi_process_chunk', array($import_id, $processed, $chunk_size));
                    
                }
            }
        }
        
        // Return minimal response
        wp_send_json_success(array('pinged' => true));
    }

    /**
     * Handle test URL AJAX request.
     * Tests if a URL is accessible via wp_safe_remote_get
     * WP.org compliance: SSRF protection and proper validation
     *
     * @since    1.0.0
     */
    public function handle_test_url() {
        // WP.org compliance: sanitize nonce
        $nonce = isset($_POST['nonce']) ? sanitize_key(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'bfpi_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        // WP.org compliance: sanitize URL input
        $url = esc_url_raw(wp_unslash($_POST['url'] ?? ''));
        
        if (empty($url)) {
            wp_send_json_error(array('message' => __('URL is required.', 'bootflow-product-xml-csv-importer')));
            return;
        }

        // WP.org compliance: validate URL for SSRF protection
        $url_validation = Bfpi_Security::validate_remote_url($url);
        if (!$url_validation['valid']) {
            wp_send_json_error(array('message' => $url_validation['error']));
            return;
        }
        
        try {
            // WP.org compliance: use wp_safe_remote_get
            $response = wp_safe_remote_get($url, array(
                'timeout' => 10,
                'redirection' => 5,
                'sslverify' => true,
                'user-agent' => 'Bootflow-WooCommerce-Importer/' . BFPI_VERSION
            ));
            
            if (is_wp_error($response)) {
                wp_send_json_error(array('message' => 'Connection failed: ' . $response->get_error_message()));
                return;
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            
            if ($response_code === 200) {
                wp_send_json_success(array(
                    // translators: placeholder values
                    'message' => sprintf(__('URL is accessible (HTTP %d).', 'bootflow-product-xml-csv-importer'), $response_code)
                ));
            } else {
                wp_send_json_error(array(
                    // translators: placeholder values
                    'message' => sprintf(__('URL returned HTTP %d. Expected 200.', 'bootflow-product-xml-csv-importer'), $response_code)
                ));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        }
    }

    /**
     * Handle get progress AJAX request.
     *
     * @since    1.0.0
     */
    public function handle_get_progress() {
        // Verify nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')), 'bfpi_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions to access this page.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        global $wpdb;
        
        $import_id = intval(wp_unslash($_POST['import_id']));
        
        $import = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}bfpi_imports WHERE id = %d", $import_id),
            ARRAY_A
        );
        
        if (!$import) {
            wp_send_json_error(array('message' => __('Import not found.', 'bootflow-product-xml-csv-importer')));
        }
        
        // Get only the 50 most recent logs, ordered by ID (more reliable than timestamp)
        // Exclude progress logs - only product-related logs
        $chunk_pattern = $wpdb->esc_like('Chunk ') . '%';
        $processing_pattern = $wpdb->esc_like('Processing chunk ') . '%';
        $processed_pattern = $wpdb->esc_like('Processed ') . '%/%';
        $logs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}bfpi_import_logs 
                 WHERE import_id = %d 
                 AND message NOT LIKE %s 
                 AND message NOT LIKE %s
                 AND message NOT LIKE %s
                 ORDER BY id DESC LIMIT 50",
                $import_id,
                $chunk_pattern,
                $processing_pattern,
                $processed_pattern
            ),
            ARRAY_A
        );
        
        $percentage = $import['total_products'] > 0 ? round(($import['processed_products'] / $import['total_products']) * 100) : 0;
        
        // Calculate current chunk and total chunks
        $batch_size = intval($import['batch_size'] ?? 50);
        $total_chunks = $import['total_products'] > 0 ? ceil($import['total_products'] / $batch_size) : 1;
        $current_chunk = $import['processed_products'] > 0 ? ceil($import['processed_products'] / $batch_size) : 1;
        
        // Get import start time
        $start_time = strtotime($import['created_at']);
        
        wp_send_json_success(array(
            'status' => $import['status'],
            'total_products' => $import['total_products'],
            'processed_products' => $import['processed_products'],
            'percentage' => $percentage,
            'start_time' => $start_time,
            'current_chunk' => $current_chunk,
            'total_chunks' => $total_chunks,
            'logs' => $logs
        ));
    }

    /**
     * Handle AI auto-mapping AJAX request.
     * ADVANCED tier only feature.
     *
     * @since    1.0.0
     */
    public function handle_ai_auto_mapping() {
        // Verify nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')), 'bfpi_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        // Check if AI mapping is available (ADVANCED tier)
        // For now, allow if any AI API key is configured
        $ai_settings = get_option('bfpi_ai_settings', array());
        $has_ai_key = !empty($ai_settings['openai_api_key']) || 
                      !empty($ai_settings['claude_api_key']) || 
                      !empty($ai_settings['gemini_api_key']) ||
                      !empty($ai_settings['grok_api_key']);
        
        if (!$has_ai_key) {
            wp_send_json_error(array(
                'message' => __('AI auto-mapping requires an AI API key. Please configure one in Settings.', 'bootflow-product-xml-csv-importer')
            ));
            return;
        }
        
        try {
            $source_fields = isset($_POST['source_fields']) ? array_map('sanitize_text_field', $_POST['source_fields']) : array();
            $provider = sanitize_text_field(wp_unslash($_POST['provider'] ?? 'openai'));
            $file_type = sanitize_text_field(wp_unslash($_POST['file_type'] ?? 'xml'));
            $sample_data = isset($_POST['sample_data']) ? $_POST['sample_data'] : array();
            
            if (empty($source_fields)) {
                wp_send_json_error(array('message' => __('No source fields provided.', 'bootflow-product-xml-csv-importer')));
                return;
            }
            
            // Sanitize sample data
            if (is_array($sample_data)) {
                array_walk_recursive($sample_data, function(&$value) {
                    if (is_string($value)) {
                        $value = wp_kses_post($value);
                    }
                });
            }
            
            // Check if AI Providers class exists
            if (!class_exists('Bfpi_AI_Providers')) {
                wp_send_json_error(array('message' => __('AI Providers module is not available.', 'bootflow-product-xml-csv-importer')));
                return;
            }
            
            $ai_providers = new Bfpi_AI_Providers();
            $result = $ai_providers->auto_map_fields($source_fields, $provider, $file_type, $sample_data);
            
            // Get stats
            $stats = isset($result['stats']) ? $result['stats'] : array(
                'total_fields' => count($source_fields),
                'ai_mapped' => count($result['mappings']),
                'auto_filled' => 0,
                'unmapped' => count($result['unmapped'] ?? array())
            );
            
            // Build response array
            $response = array(
                'mappings' => $result['mappings'],
                'confidence' => $result['confidence'],
                'unmapped' => $result['unmapped'],
                'auto_filled' => $result['auto_filled'] ?? array(),
                'mapped_count' => count($result['mappings']),
                'provider' => $result['provider'],
                'stats' => $stats,
                'message' => sprintf(
                    // translators: %1$d is AI mapped count, %2$d is auto-filled count, %3$d is total mapped, %4$d is total fields
                    __('AI mapped %1$d fields, auto-filled %2$d fields. Total: %3$d of %4$d fields mapped.', 'bootflow-product-xml-csv-importer'),
                    $stats['ai_mapped'],
                    $stats['auto_filled'],
                    count($result['mappings']),
                    $stats['total_fields']
                )
            );
            
            // Add warning if some fields are still unmapped
            if ($stats['unmapped'] > 0) {
                $response['message'] .= ' ' . sprintf(
                    // translators: %d is the number of unmapped fields
                    __('Warning: %d fields could not be mapped automatically.', 'bootflow-product-xml-csv-importer'),
                    $stats['unmapped']
                );
            }
            
            // Add product structure info if available
            if (!empty($result['product_structure'])) {
                $response['product_structure'] = $result['product_structure'];
                
                // Update message if variable product detected
                if (!empty($result['product_structure']['has_variations'])) {
                    $response['message'] .= ' ' . esc_html__('Variable product structure detected.', 'bootflow-product-xml-csv-importer');
                    
                    if (!empty($result['product_structure']['detected_attributes'])) {
                        $attr_count = count($result['product_structure']['detected_attributes']);
                        $response['message'] .= ' ' . sprintf(
                            // translators: %d is the number of attributes found
                            _n('%d attribute found.', '%d attributes found.', $attr_count, 'bootflow-product-xml-csv-importer'),
                            $attr_count
                        );
                    }
                }
            }
            
            wp_send_json_success($response);
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Handle license activation AJAX request.
     *
     * @since    1.0.0
     */
    public function handle_activate_license() {
        // Verify nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')), 'bfpi_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        $license_key = sanitize_text_field(wp_unslash($_POST['license_key'] ?? ''));
        
        if (empty($license_key)) {
            wp_send_json_error(array('message' => __('Please enter a license key.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        // Try to activate the license
        $result = Bfpi_License::activate_license($license_key);
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => $result['message'],
                'tier' => $result['tier']
            ));
        } else {
            wp_send_json_error(array(
                'message' => $result['message']
            ));
        }
    }

    /**
     * Handle license deactivation AJAX request.
     *
     * @since    1.0.0
     */
    public function handle_deactivate_license() {
        // Verify nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')), 'bfpi_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        // Deactivate the license
        $result = Bfpi_License::deactivate_license();
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => $result['message']
            ));
        } else {
            wp_send_json_error(array(
                'message' => $result['message']
            ));
        }
    }

    /**
     * Handle test AI AJAX request.
     *
     * @since    1.0.0
     */
    public function handle_test_ai() {
        // Verify nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')), 'bfpi_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions to access this page.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        try {
            $provider = sanitize_text_field(wp_unslash($_POST['provider'] ?? ''));
            
            // Check if AI Providers class exists
            if (!class_exists('Bfpi_AI_Providers')) {
                wp_send_json_error(array('message' => __('AI Providers module is not available.', 'bootflow-product-xml-csv-importer')));
                return;
            }
            
            // Simple connection test mode (from Settings page)
            if (!empty($_POST['api_key']) && !empty($provider)) {
                // Temporarily set API key for test
                $ai_settings = get_option('bfpi_ai_settings', array());
                $original_key = $ai_settings[$provider . '_api_key'] ?? '';
                $ai_settings[$provider . '_api_key'] = sanitize_text_field(wp_unslash($_POST['api_key']));
                if (!empty($_POST['model'])) {
                    $ai_settings[$provider . '_model'] = sanitize_text_field(wp_unslash($_POST['model']));
                }
                update_option('bfpi_ai_settings', $ai_settings);
                
                $ai_providers = new Bfpi_AI_Providers();
                $result = $ai_providers->test_provider($provider);
                
                // Restore original key if it was different
                if ($original_key !== $ai_settings[$provider . '_api_key']) {
                    $ai_settings[$provider . '_api_key'] = $original_key;
                    update_option('bfpi_ai_settings', $ai_settings);
                }
                
                if ($result['success']) {
                    wp_send_json_success(array('message' => $result['message']));
                } else {
                    wp_send_json_error(array('message' => $result['message']));
                }
                return;
            }
            
            // Full field test mode (from Mapping page)
            $test_prompt = wp_unslash($_POST['test_prompt'] ?? ''); // Don't sanitize - may contain HTML
            $test_value = wp_unslash($_POST['test_value'] ?? ''); // Don't sanitize - may contain HTML
            
            // Build test context from sample data if available
            $context = array();
            if (!empty($_POST['sample_data'])) {
                $sample_data = map_deep( wp_unslash( $_POST['sample_data'] ), 'sanitize_text_field' );
                if (!empty($sample_data['name'])) {
                    $context['name'] = $sample_data['name'];
                }
                if (!empty($sample_data['price'])) {
                    $context['price'] = $sample_data['price'];
                }
                if (!empty($sample_data['ean'])) {
                    $context['ean'] = $sample_data['ean'];
                }
                if (!empty($sample_data['brand'])) {
                    $context['brand'] = $sample_data['brand'];
                }
                if (!empty($sample_data['category'])) {
                    $context['category'] = $sample_data['category'];
                }
            }
            
            // Check if AI Providers class exists
            if (!class_exists('Bfpi_AI_Providers')) {
                wp_send_json_error(array('message' => __('AI Providers module is not available.', 'bootflow-product-xml-csv-importer')));
                return;
            }
            
            $ai_providers = new Bfpi_AI_Providers();
            $result = $ai_providers->process_field($test_value, $test_prompt, array('provider' => $provider), $context);
            
            wp_send_json_success(array(
                'result' => $result,
                'message' => __('AI test completed successfully.', 'bootflow-product-xml-csv-importer')
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Handle test PHP formula AJAX request.
     *
     * @since    1.0.0
     */
    public function handle_test_php() {
        // Verify nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')), 'bfpi_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions to access this page.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        try {
            $formula = wp_unslash($_POST['formula'] ?? '');
            $test_value = sanitize_text_field(wp_unslash($_POST['test_value'] ?? ''));
            $sample_data = isset($_POST['sample_data']) ? map_deep(wp_unslash($_POST['sample_data']), 'sanitize_text_field') : array();
            
            // Validate formula safety
            $validation = Bfpi_Processor::validate_formula($formula);
            if (is_wp_error($validation)) {
                wp_send_json_error(array('message' => $validation->get_error_message()));
                return;
            }

            // Prepare variables for formula evaluation
            $value = $test_value;
            $name = $sample_data['product_name'] ?? ($sample_data['name'] ?? '');
            $price = $sample_data['price'] ?? ($sample_data['regular_price'] ?? 0);
            $sku = $sample_data['id'] ?? ($sample_data['sku'] ?? '');
            $category = $sample_data['category'] ?? '';
            $brand = $sample_data['brand'] ?? '';
            $weight = $sample_data['gross_weight'] ?? ($sample_data['weight'] ?? 0);
            $length = $sample_data['package_dimensions.length'] ?? ($sample_data['length'] ?? 0);
            $width = $sample_data['package_dimensions.width'] ?? ($sample_data['width'] ?? 0);
            $height = $sample_data['package_dimensions.height'] ?? ($sample_data['height'] ?? 0);
            $ean = $sample_data['eans.ean'] ?? ($sample_data['ean'] ?? '');
            $gtin = $sample_data['gtin'] ?? '';
            
            // Smart formula normalization
            $formula = $this->normalize_php_formula($formula);
            
            // Execute formula - wrap in anonymous function to allow complex code
            $wrapped_formula = "
                \$func = function() use (\$value, \$name, \$price, \$sku, \$category, \$brand, \$weight, \$length, \$width, \$height, \$ean, \$gtin) {
                    {$formula}
                };
                return \$func();
            ";
            
            $result = eval($wrapped_formula);
            
            // Format result for display
            $formatted_result = is_array($result) ? wp_json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $result;
            
            wp_send_json_success(array(
                'result' => $formatted_result,
                'raw_result' => $result,
                'message' => __('PHP formula test completed successfully.', 'bootflow-product-xml-csv-importer')
            ));
            
        } catch (ParseError $e) {
            wp_send_json_error(array(
                'message' => 'Syntax error: ' . $e->getMessage()
            ));
        } catch (Throwable $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Handle save mapping AJAX request.
     *
     * @since    1.0.0
     */
    public function handle_save_mapping() {
        global $wpdb;
        
        // DEBUG: Log that handler was called
        
        // Verify nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')), 'bfpi_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions to access this page.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        try {
            $import_id = intval(wp_unslash($_POST['import_id']));
            $mapping_data_json = wp_unslash( $_POST['mapping_data'] ?? '' );
            $mapping_data = json_decode( $mapping_data_json, true );
            
            if (!$import_id || !$mapping_data) {
                wp_send_json_error(array('message' => __('Invalid import ID or mapping data.', 'bootflow-product-xml-csv-importer')));
                return;
            }
            
            // Sanitize mapping data recursively
            $mapping_data = map_deep( $mapping_data, 'sanitize_text_field' );
            
            // Prepare field mappings for database
            $field_mappings = array();
            
            // Process standard field mappings
            if (isset($mapping_data['field_mapping'])) {
                $field_mappings = $mapping_data['field_mapping'];
            }
            
            // Process custom fields - save as array, not individual fields
            if (isset($mapping_data['custom_fields']) && is_array($mapping_data['custom_fields'])) {
                // Add each custom field with a unique key (use _custom_ prefix for consistency)
                $cf_index = 0;
                foreach ($mapping_data['custom_fields'] as $field_config) {
                    if (is_array($field_config) && !empty($field_config['name'])) {
                        $field_mappings['_custom_' . $cf_index] = $field_config;
                        $cf_index++;
                    }
                }
            }
            
            // Also save custom_fields in separate column for backward compatibility
            $custom_fields_array = isset($mapping_data['custom_fields']) ? $mapping_data['custom_fields'] : array();
            
            // Prepare update data
            $update_data = array(
                'field_mappings' => wp_json_encode($field_mappings, JSON_UNESCAPED_UNICODE),
                'custom_fields' => wp_json_encode($custom_fields_array, JSON_UNESCAPED_UNICODE)
            );
            
            // Add filters if present
            if (isset($mapping_data['import_filters'])) {
                $update_data['import_filters'] = wp_json_encode($mapping_data['import_filters'], JSON_UNESCAPED_UNICODE);
            }
            
            // Add filter logic if present
            if (isset($mapping_data['filter_logic'])) {
                $update_data['filter_logic'] = sanitize_text_field($mapping_data['filter_logic']);
            }
            
            // Add draft_non_matching if present
            if (isset($mapping_data['draft_non_matching'])) {
                $update_data['draft_non_matching'] = intval($mapping_data['draft_non_matching']);
            }
            
            // Add update_existing if present (CRITICAL FIX)
            if (isset($mapping_data['update_existing'])) {
                $update_data['update_existing'] = $mapping_data['update_existing'] === '1' ? '1' : '0';
            }
            
            // Add skip_unchanged if present (CRITICAL FIX)
            if (isset($mapping_data['skip_unchanged'])) {
                $update_data['skip_unchanged'] = $mapping_data['skip_unchanged'] === '1' ? '1' : '0';
            }
            
            // Add batch_size if present (CRITICAL FIX)
            if (isset($mapping_data['batch_size'])) {
                $update_data['batch_size'] = intval($mapping_data['batch_size']);
            }
            
            // Add schedule_type if present (for scheduled imports)
            if (isset($mapping_data['schedule_type'])) {
                $valid_schedules = array('none', 'disabled', '15min', 'hourly', '6hours', 'daily', 'weekly', 'monthly');
                $schedule = sanitize_text_field($mapping_data['schedule_type']);
                if (in_array($schedule, $valid_schedules)) {
                    $update_data['schedule_type'] = $schedule;
                }
            }
            
            // Add schedule_method if present (action_scheduler or server_cron)
            if (isset($mapping_data['schedule_method'])) {
                $valid_methods = array('action_scheduler', 'server_cron');
                $method = sanitize_text_field($mapping_data['schedule_method']);
                if (in_array($method, $valid_methods)) {
                    $update_data['schedule_method'] = $method;
                }
            }
            
            // Update database
            $table_name = $wpdb->prefix . 'bfpi_imports';
            
            // Build format specifiers dynamically based on update_data keys
            $format_map = array(
                'field_mappings' => '%s',
                'import_filters' => '%s',
                'filter_logic' => '%s',
                'draft_non_matching' => '%d',
                'update_existing' => '%s',
                'skip_unchanged' => '%s',
                'batch_size' => '%d',
                'schedule_type' => '%s',
                'schedule_method' => '%s'
            );
            $formats = array();
            foreach (array_keys($update_data) as $key) {
                $formats[] = isset($format_map[$key]) ? $format_map[$key] : '%s';
            }
            
            $result = $wpdb->update(
                $table_name,
                $update_data,
                array('id' => $import_id),
                $formats,
                array('%d')
            );
            
            if ($result === false) {
                wp_send_json_error(array('message' => __('Database error: ', 'bootflow-product-xml-csv-importer') . $wpdb->last_error));
                return;
            }
            
            wp_send_json_success(array(
                'message' => __('Mapping configuration saved successfully.', 'bootflow-product-xml-csv-importer'),
                'updated_fields' => count($field_mappings)
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Handle test shipping formula AJAX request.
     *
     * @since    1.0.0
     */
    public function handle_test_shipping() {
        // Verify nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')), 'bfpi_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions to access this page.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        try {
            $formula = wp_unslash($_POST['formula'] ?? '');
            $weight = isset( $_POST['weight'] ) ? floatval( wp_unslash( $_POST['weight'] ) ) : 0;
            $length = isset( $_POST['length'] ) ? floatval( wp_unslash( $_POST['length'] ) ) : 0;
            $width  = isset( $_POST['width'] )  ? floatval( wp_unslash( $_POST['width'] ) )  : 0;
            $height = isset( $_POST['height'] ) ? floatval( wp_unslash( $_POST['height'] ) ) : 0;
            
            // Validate formula safety
            $validation = Bfpi_Processor::validate_formula($formula);
            if (is_wp_error($validation)) {
                wp_send_json_error(array('message' => $validation->get_error_message()));
                return;
            }

            // Execute formula
            $wrapped_formula = "
                \$func = function() use (\$weight, \$length, \$width, \$height) {
                    {$formula}
                };
                return \$func();
            ";
            
            $result = eval($wrapped_formula);
            
            wp_send_json_success(array(
                'result' => $result,
                'message' => __('Shipping formula test completed successfully.', 'bootflow-product-xml-csv-importer')
            ));
            
        } catch (ParseError $e) {
            wp_send_json_error(array(
                'message' => 'Syntax error: ' . $e->getMessage()
            ));
        } catch (Throwable $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Handle cron import execution.
     *
     * @since    1.0.0
     */
    public function handle_cron_import() {
        global $wpdb;
        
        // Verify secret key
        $settings = get_option('bfpi_settings', array());
        $secret = sanitize_text_field(wp_unslash($_GET['secret'] ?? $_REQUEST['secret'] ?? ''));
        
        if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import Cron: Received secret: ' . $secret); }
        if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import Cron: Expected secret: ' . ($settings['cron_secret_key'] ?? 'NOT SET')); }
        
        if (empty($secret) || empty($settings['cron_secret_key']) || $secret !== $settings['cron_secret_key']) {
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import Cron: Invalid secret key'); }
            wp_die(esc_html('Unauthorized'), 'Unauthorized', array('response' => 401));
        }
        
        // Find scheduled imports that are ready to run
        $table_name = $wpdb->prefix . 'bfpi_imports';
        $current_time = current_time('mysql');
        
        // Query 1: New imports due for a fresh run (scheduled or completed)
        $new_imports = $wpdb->get_results(
            $wpdb->prepare("
                SELECT * FROM {$table_name}
                WHERE status IN ('scheduled', 'completed')
                AND schedule_type IN ('15min', 'hourly', '6hours', 'daily', 'weekly', 'monthly')
                AND (last_run IS NULL OR DATE_ADD(last_run, INTERVAL 
                    CASE schedule_type
                        WHEN '15min' THEN 15
                        WHEN 'hourly' THEN 60
                        WHEN '6hours' THEN 360
                        WHEN 'daily' THEN 1440
                        WHEN 'weekly' THEN 10080
                        WHEN 'monthly' THEN 43200
                        ELSE 1
                    END MINUTE
                ) <= %s)
                LIMIT 5
            ", $current_time),
            ARRAY_A
        );
        
        // Query 2: Stuck/interrupted imports that need resuming (processing but no activity for 2+ minutes)
        $stuck_imports = $wpdb->get_results(
            $wpdb->prepare("
                SELECT * FROM {$table_name}
                WHERE status = 'processing'
                AND schedule_type IN ('15min', 'hourly', '6hours', 'daily', 'weekly', 'monthly')
                AND processed_products < total_products
                AND updated_at < DATE_SUB(%s, INTERVAL 2 MINUTE)
                LIMIT 5
            ", $current_time),
            ARRAY_A
        );
        
        // Merge both lists, avoiding duplicates
        $scheduled_imports = $new_imports ?: array();
        $seen_ids = array_column($scheduled_imports, 'id');
        foreach (($stuck_imports ?: array()) as $stuck) {
            if (!in_array($stuck['id'], $seen_ids)) {
                $scheduled_imports[] = $stuck;
            }
        }
        
        if (empty($scheduled_imports)) {
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import Cron: No imports ready to run'); }
            echo 'No imports scheduled';
            exit;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import Cron: Found ' . count($scheduled_imports) . ' imports to run (new=' . count($new_imports ?: array()) . ', stuck=' . count($stuck_imports ?: array()) . ')'); }
        
        // Process each scheduled import
        foreach ($scheduled_imports as $import) {
            try {
                // Determine if this is a RESUME (stuck processing) or a NEW run
                $is_resuming = ($import['status'] === 'processing' && intval($import['processed_products']) > 0);
                
                if ($is_resuming) {
                    // RESUME: Continue from where we left off - do NOT reset processed_products!
                    $offset = intval($import['processed_products']);
                    $wpdb->update(
                        $table_name,
                        array('last_run' => current_time('mysql')),
                        array('id' => $import['id']),
                        array('%s'),
                        array('%d')
                    );
                    if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import Cron: RESUMING import ID ' . $import['id'] . ' from offset ' . $offset . '/' . $import['total_products']); }
                } else {
                    // NEW RUN: Reset and start from 0
                    $offset = 0;
                    $wpdb->update(
                        $table_name,
                        array(
                            'status' => 'processing',
                            'processed_products' => 0,
                            'last_run' => current_time('mysql')
                        ),
                        array('id' => $import['id']),
                        array('%s', '%d', '%s'),
                        array('%d')
                    );
                    if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import Cron: Starting NEW import ID ' . $import['id']); }
                }
                
                // Run the import in chunks with a TIME LIMIT
                $importer = new Bfpi_Importer();
                // Use import-specific batch_size if set, otherwise fall back to global chunk_size
                // Cap at 100 to avoid memory/timeout issues with large batches
                $chunk_size = intval($import['batch_size'] ?? $settings['chunk_size'] ?? 50);
                $chunk_size = min($chunk_size, 100); // Safety cap
                if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import Cron: chunk_size=' . $chunk_size . ', starting offset=' . $offset); }
                
                // Try to set generous time limit
                if ( function_exists( 'set_time_limit' ) ) {
                    @set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                }
                
                // Loop with a TIME LIMIT - don't try to process everything in one HTTP request!
                // Shared hosting typically kills processes after 60-300 seconds.
                // Process for max 120 seconds, then exit cleanly. Next cron call will continue.
                $completed = false;
                $max_execution_seconds = 270; // 4.5 minutes max per cron call
                $cron_start_time = time();
                $max_iterations = 10000; // Safety limit to prevent infinite loops
                $iteration = 0;
                
                while (!$completed && $iteration < $max_iterations) {
                    $iteration++;
                    
                    // CHECK TIME LIMIT before each chunk
                    $elapsed = time() - $cron_start_time;
                    if ($elapsed >= $max_execution_seconds) {
                        if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import Cron: Time limit (' . $max_execution_seconds . 's) reached after ' . $iteration . ' chunks at offset ' . $offset . '. Will continue on next cron run.'); }
                        break;
                    }
                    
                    $result = $importer->process_import_chunk($offset, $chunk_size, $import['id']);
                    
                    if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import Cron: Chunk #' . $iteration . ' offset=' . $offset . ', processed=' . ($result['processed'] ?? 0) . ', total_processed=' . ($result['total_processed'] ?? '?') . ', completed=' . ($result['completed'] ? 'YES' : 'NO') . ' [' . $elapsed . 's elapsed]'); }
                    
                    if ($result['completed']) {
                        $completed = true;
                    } else if (isset($result['locked']) && $result['locked']) {
                        // Another process is running, wait and retry (max 2 times)
                        if ($iteration > 2) {
                            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import Cron: Still locked after retries, exiting'); }
                            break;
                        }
                        if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import Cron: Locked, waiting 5s...'); }
                        sleep(5);
                    } else if (isset($result['stopped']) && $result['stopped']) {
                        // Import was stopped/failed
                        if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import Cron: Import stopped/failed'); }
                        break;
                    } else if (isset($result['skipped']) && $result['skipped']) {
                        // Chunk was skipped (already processed), use total_processed as next offset
                        $offset = $result['total_processed'] ?? ($offset + $chunk_size);
                    } else {
                        // Continue to next chunk
                        $offset = $result['total_processed'] ?? ($offset + $chunk_size);
                    }
                }
                
                $total_elapsed = time() - $cron_start_time;
                
                // Update status based on result
                if ($completed) {
                    $wpdb->update(
                        $table_name,
                        array('status' => 'completed'),
                        array('id' => $import['id']),
                        array('%s'),
                        array('%d')
                    );
                    if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import Cron: COMPLETED import ID ' . $import['id'] . ' in ' . $total_elapsed . 's (' . $iteration . ' chunks)'); }
                } else {
                    // Still processing - status stays as 'processing', next cron call will resume
                    if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import Cron: Import ID ' . $import['id'] . ' paused at offset ' . $offset . ', elapsed=' . $total_elapsed . 's, will RESUME on next cron call'); }
                }
                
            } catch (Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import Cron: Error processing import ID ' . $import['id'] . ': ' . $e->getMessage()); }
                
                $wpdb->update(
                    $table_name,
                    array('status' => 'error'),
                    array('id' => $import['id']),
                    array('%s'),
                    array('%d')
                );
            }
        }
        
        echo 'Processed ' . intval( count($scheduled_imports) ) . ' imports';
        exit;
    }

    /**
     * Handle single import cron execution.
     */
    public function handle_single_import_cron() {
        global $wpdb;
        
        $import_id = intval($_GET['import_id'] ?? 0);
        $secret = sanitize_text_field(wp_unslash($_GET['secret'] ?? ''));
        
        if (empty($import_id) || empty($secret)) {
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import: Missing import_id or secret'); }
            wp_die(esc_html('Invalid request'), 'Bad Request', array('response' => 400));
        }
        
        $stored_secret = get_option('bfpi_secret_' . $import_id);
        if (empty($stored_secret) || $secret !== $stored_secret) {
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import: Invalid secret for import #' . $import_id); }
            wp_die(esc_html('Unauthorized'), 'Unauthorized', array('response' => 401));
        }
        
        $table_name = $wpdb->prefix . 'bfpi_imports';
        $import = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $import_id), ARRAY_A);
        
        if (!$import) {
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import: Import #' . $import_id . ' not found'); }
            echo 'Import not found';
            exit;
        }
        
        if (empty($import['schedule_type']) || $import['schedule_type'] === 'none') {
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import: Import #' . $import_id . ' has no schedule'); }
            echo 'No schedule configured';
            exit;
        }
        
        // Check if it's time to run
        $should_run = false;
        if (empty($import['last_run'])) {
            $should_run = true;
        } else {
            $last_run = strtotime($import['last_run']);
            $intervals = array('15min'=>900, 'hourly'=>3600, '6hours'=>21600, 'daily'=>86400, 'weekly'=>604800, 'monthly'=>2592000);
            $interval = $intervals[$import['schedule_type']] ?? 0;
            $should_run = (time() >= ($last_run + $interval));
        }
        
        if (!$should_run) {
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import: Import #' . $import_id . ' not ready to run yet'); }
            echo 'Not time to run yet';
            exit;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import: Starting import #' . $import_id); }
        
        try {
            // Check if we need to download fresh XML from URL
            if (!empty($import['original_file_url'])) {
                $old_file = $import['file_url'];
                
                // Download fresh file
                $download_result = $this->download_import_file($import['original_file_url'], $import_id);
                
                if ($download_result['success']) {
                    $new_file_path = $download_result['file_path'];
                    
                    // Delete old XML file if URL changed or new file downloaded
                    if (!empty($old_file) && $old_file !== $new_file_path && file_exists($old_file)) {
                        wp_delete_file($old_file);
                        if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import: Deleted old file: ' . $old_file); }
                    }
                    
                    // Update file_url in database
                    $wpdb->update(
                        $table_name,
                        array('file_url' => $new_file_path),
                        array('id' => $import_id),
                        array('%s'),
                        array('%d')
                    );
                    
                    $import['file_url'] = $new_file_path;
                } else {
                    throw new Exception('Failed to download file: ' . ($download_result['message'] ?? 'Unknown error'));
                }
            }
            
            $wpdb->update($table_name, array('status'=>'processing', 'last_run'=>current_time('mysql')), array('id'=>$import_id), array('%s','%s'), array('%d'));
            
            $importer = new Bfpi_Importer();
            $result = $importer->import_batch($import_id, 0, 5);
            
            if ($result['completed']) {
                $wpdb->update($table_name, array('status'=>'completed'), array('id'=>$import_id), array('%s'), array('%d'));
                if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import: Completed import #' . $import_id); }
                echo esc_html__('Import completed successfully', 'bootflow-product-xml-csv-importer');
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import: Partial import #' . $import_id . ' - ' . $result['processed'] . '/' . $result['total']); }
                echo 'Processing: ' . intval($result['processed']) . '/' . intval($result['total']);
            }
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import: Error in import #' . $import_id . ': ' . $e->getMessage()); }
            $wpdb->update($table_name, array('status'=>'error'), array('id'=>$import_id), array('%s'), array('%d'));
            echo 'Error: ' . esc_html($e->getMessage());
        }
        
        exit;
    }

    /**
     * Handle AJAX control import (pause/resume/stop/retry).
     */
    public function ajax_control_import() {
        global $wpdb;
        
        check_ajax_referer('bfpi_nonce', 'nonce');
        if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_send_json_error( 'Insufficient permissions.' ); }
        
        $import_id = intval(wp_unslash($_POST['import_id'] ?? 0));
        $action = sanitize_text_field(wp_unslash($_POST['control_action'] ?? ''));
        
        if (!$import_id || !$action) {
            wp_send_json_error('Missing parameters');
        }
        
        $table = $wpdb->prefix . 'bfpi_imports';
        $import = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $import_id));
        
        if (!$import) {
            wp_send_json_error('Import not found');
        }
        
        switch ($action) {
            case 'pause':
                $wpdb->update($table, array('status' => 'paused'), array('id' => $import_id), array('%s'), array('%d'));
                
                // Clear cron jobs
                $hooks = array('bfpi_process_chunk', 'bfpi_retry_chunk', 'bfpi_single_chunk');
                foreach ($hooks as $hook) {
                    wp_clear_scheduled_hook($hook, array($import_id));
                }
                
                // Clear transient locks to stop running batch immediately
                delete_transient('bfpi_import_lock_' . $import_id);
                delete_transient('bfpi_import_lock_time_' . $import_id);
                
                wp_send_json_success(array('status' => 'paused', 'message' => 'Import paused'));
                break;
                
            case 'resume':
                $wpdb->update($table, array('status' => 'processing'), array('id' => $import_id), array('%s'), array('%d'));
                
                // Schedule next chunk to start processing with correct parameters
                $current_offset = intval($import->processed_products);
                $batch_size = intval($import->batch_size) ?: 10;
                wp_schedule_single_event(time() + 2, 'bfpi_process_chunk', array($import_id, $current_offset, $batch_size));
                
                wp_send_json_success(array('status' => 'processing', 'message' => 'Import resumed'));
                break;
                
            case 'stop':
                $wpdb->update($table, array('status' => 'failed'), array('id' => $import_id), array('%s'), array('%d'));
                
                // Clear ALL cron jobs
                $hooks = array('bfpi_process_chunk', 'bfpi_retry_chunk', 'bfpi_single_chunk');
                foreach ($hooks as $hook) {
                    wp_clear_scheduled_hook($hook, array($import_id));
                    wp_clear_scheduled_hook($hook);
                }
                
                // Clear transient locks to stop running batch immediately
                delete_transient('bfpi_import_lock_' . $import_id);
                delete_transient('bfpi_import_lock_time_' . $import_id);
                
                wp_send_json_success(array('status' => 'failed', 'message' => 'Import stopped'));
                break;
                
            case 'retry':
                $wpdb->update($table, array('status' => 'processing'), array('id' => $import_id), array('%s'), array('%d'));
                wp_send_json_success(array('status' => 'processing', 'message' => 'Import retrying'));
                break;
                
            default:
                wp_send_json_error('Invalid action');
        }
    }

    /**
     * Handle async batch processing (for re-run button).
     */
    public function handle_process_batch() {
        global $wpdb;
        
        // Security: verify nonce and capability
        check_ajax_referer('bfpi_nonce', 'nonce');
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }
        
        $import_id = intval(wp_unslash($_POST['import_id'] ?? 0));
        $offset = intval(wp_unslash($_POST['offset'] ?? 0));
        $limit = intval(wp_unslash($_POST['limit'] ?? 50));
        
        if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import: import_id=' . $import_id . ', offset=' . $offset . ', limit=' . $limit); }
        
        if (empty($import_id)) {
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import: Missing import_id in process_batch'); }
            wp_send_json_error('Missing import ID');
        }
        
        // CHECK IMPORT STATUS BEFORE PROCESSING
        $table = $wpdb->prefix . 'bfpi_imports';
        $import = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $import_id));
        
        if (!$import) {
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import: Import not found: ' . $import_id); }
            wp_send_json_error('Import not found');
        }
        
        if ($import->status !== 'processing') {
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import: Import status is ' . $import->status . ' - ABORTING BATCH'); }
            wp_send_json_error('Import not in processing status: ' . $import->status);
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import: Processing batch for import #' . $import_id . ', offset=' . $offset); }
        
        // DEBUG: Check if importer class exists
        if (!class_exists('Bfpi_Importer')) {
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import: Importer class does not exist, loading...'); }
            require_once BFPI_PLUGIN_DIR . 'includes/class-bfpi-importer.php';
        }
        if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import: Importer class loaded successfully'); }
        
        try {
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import: Creating Importer instance...'); }
            $importer = new Bfpi_Importer();
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import: Importer instance created, calling process_import_chunk...'); }
            
            $result = $importer->process_import_chunk($offset, $limit, $import_id);
            
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import: Batch result - processed=' . ($result['processed'] ?? 0) . ', errors=' . count($result['errors'] ?? [])); }
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import: Full result: ' . wp_json_encode($result)); }
            
            wp_send_json_success($result);
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import: EXCEPTION in batch processing: ' . $e->getMessage()); }
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('WC XML CSV AI Import: Exception trace: ' . $e->getTraceAsString()); }
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Handle WP Cron chunk processing.
     * Called by wp_schedule_single_event hook.
     *
     * @since    1.0.0
     * @param    int $import_id Import ID
     * @param    int $offset Starting offset
     * @param    int $limit Chunk size
     */
    public function handle_cron_process_chunk($import_id, $offset, $limit) {
        $upload_dir = wp_upload_dir();
        $log_file = $upload_dir['basedir'] . '/bootflow-product-xml-csv-importer/logs/import_debug.log';
        
        try {
            // Load importer class if not loaded
            if (!class_exists('Bfpi_Importer')) {
                require_once BFPI_PLUGIN_DIR . 'includes/class-bfpi-importer.php';
            }
            
            $importer = new Bfpi_Importer($import_id);
            $result = $importer->process_import_chunk($offset, $limit, $import_id);
            
            
        } catch (Exception $e) {
        }
    }

    /**
     * Display logs viewer page.
     *
     * @since    1.0.0
     */
    public function display_logs_page() {
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'bootflow-product-xml-csv-importer'));
        }
        
        global $wpdb;
        ?>
        <div class="wrap bfpi-import">
            <div class="bootflow-header-row">
                <h1><?php echo esc_html__('Import Logs', 'bootflow-product-xml-csv-importer'); ?></h1>
                <?php $this->render_language_switcher(); ?>
            </div>
            
            <?php
            // Get imports for dropdown
            $table = $wpdb->prefix . 'bfpi_imports';
            // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder -- %i requires WP 6.2+
            $imports = $wpdb->get_results( $wpdb->prepare( "SELECT id, name FROM %i ORDER BY id DESC LIMIT 20", $table ) );
            
            // Handle import selection
            $selected_import = isset($_GET['import_id']) ? intval($_GET['import_id']) : ($imports[0]->id ?? 0);
            
            // Get logs from database
            $logs_table = $wpdb->prefix . 'bfpi_import_logs';
            $logs = array();
            
            if ($wpdb->get_var("SHOW TABLES LIKE '{$logs_table}'") == $logs_table && $selected_import > 0) {
                $logs = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$logs_table} WHERE import_id = %d ORDER BY created_at DESC LIMIT 1000",
                    $selected_import
                ));
            }
            
            // Also show import_debug.log file
            $upload_dir = wp_upload_dir();
            $debug_log = $upload_dir['basedir'] . '/bootflow-product-xml-csv-importer/logs/import_debug.log';
            $show_file_log = isset($_GET['view']) && $_GET['view'] === 'file';
            ?>
            
            <div class="log-viewer-controls" style="margin: 20px 0; padding: 15px; background: #fff; border: 1px solid #ccd0d4;">
                <form method="get" style="display: inline-block; margin-right: 20px;">
                    <input type="hidden" name="page" value="bfpi-import-logs">
                    
                    <label for="view-select"><?php esc_html_e('View:', 'bootflow-product-xml-csv-importer'); ?></label>
                    <select name="view" id="view-select" onchange="this.form.submit()" style="margin-right: 20px;">
                        <option value="database" <?php selected(!$show_file_log); ?>>Database Logs</option>
                        <option value="file" <?php selected($show_file_log); ?>>Debug File (import_debug.log)</option>
                    </select>
                    
                    <?php if (!$show_file_log && !empty($imports)): ?>
                        <label for="import-select"><?php esc_html_e('Import:', 'bootflow-product-xml-csv-importer'); ?></label>
                        <select name="import_id" id="import-select" onchange="this.form.submit()">
                            <?php foreach ($imports as $import): ?>
                                <option value="<?php echo esc_attr($import->id); ?>" <?php selected($selected_import, $import->id); ?>>
                                    #<?php echo esc_html($import->id); ?> - <?php echo esc_html($import->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </form>
                
                <button type="button" class="button" onclick="location.reload()">
                    <?php esc_html_e('Refresh', 'bootflow-product-xml-csv-importer'); ?>
                </button>
                
                <label style="margin-left: 20px;">
                    <input type="checkbox" id="auto-refresh" onchange="toggleAutoRefresh(this.checked)">
                    <?php esc_html_e('Auto-refresh (5s)', 'bootflow-product-xml-csv-importer'); ?>
                </label>
                
                <?php if ($show_file_log && file_exists($debug_log)): ?>
                    <a href="?page=bfpi-import-logs&view=file&action=clear" class="button" onclick="return confirm('<?php esc_html_e('Clear debug log file?', 'bootflow-product-xml-csv-importer'); ?>')" style="margin-left: 10px;">
                        <?php esc_html_e('Clear Log', 'bootflow-product-xml-csv-importer'); ?>
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="log-viewer-content" style="background: #1e1e1e; color: #d4d4d4; padding: 20px; border-radius: 4px; font-family: 'Courier New', monospace; font-size: 13px; line-height: 1.5; max-height: 600px; overflow-y: auto;">
                <?php
                if ($show_file_log) {
                    // Show import_debug.log file
                    if (isset($_GET['action']) && $_GET['action'] === 'clear' && file_exists($debug_log)) {
                        echo '<div class="notice notice-success"><p>' . esc_html__('Debug log cleared.', 'bootflow-product-xml-csv-importer') . '</p></div>';
                    }
                    
                    if (file_exists($debug_log)) {
                        global $wp_filesystem;
                        if ( empty( $wp_filesystem ) ) {
                            require_once ABSPATH . '/wp-admin/includes/file.php';
                            WP_Filesystem();
                        }
                        $log_content = $wp_filesystem->get_contents($debug_log);
                        if (empty($log_content)) {
                            echo '<div style="color: #888;">' . esc_html__('Log file is empty.', 'bootflow-product-xml-csv-importer') . '</div>';
                        } else {
                            // Show last 500 lines
                            $lines = explode("\n", $log_content);
                            $lines = array_slice($lines, -500);
                            $log_content = implode("\n", $lines);
                            
                            $log_content = htmlspecialchars($log_content);
                            $log_content = preg_replace('/\[([\d\-: .]+)\]/', '<span style="color: #9e9e9e;">[$1]</span>', $log_content);
                            $log_content = preg_replace('/(ERROR|CRITICAL|CATEGORY_ERROR)/', '<span style="color: #f44336; font-weight: bold;">$1</span>', $log_content);
                            $log_content = preg_replace('/(WARNING)/', '<span style="color: #ff9800; font-weight: bold;">$1</span>', $log_content);
                            $log_content = preg_replace('/(CATEGORY_CREATED|CATEGORY_EXISTS|TAGS_ASSIGNED)/', '<span style="color: #4caf50; font-weight: bold;">$1</span>', $log_content);
                            $log_content = preg_replace('/(CATEGORY_HIERARCHY|CREATE_CATEGORY_HIERARCHY)/', '<span style="color: #2196f3; font-weight: bold;">$1</span>', $log_content);
                            
                            $allowed_log_html = array( 'span' => array( 'style' => array() ) );
                            echo '<pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word;">' . wp_kses( $log_content, $allowed_log_html ) . '</pre>';
                        }
                    } else {
                        echo '<div style="color: #f44336;">' . esc_html__('Debug log file not found.', 'bootflow-product-xml-csv-importer') . '</div>';
                    }
                } else {
                    // Show database logs
                    if (empty($logs)) {
                        echo '<div style="color: #888;">' . esc_html__('No logs found for this import.', 'bootflow-product-xml-csv-importer') . '</div>';
                    } else {
                        foreach (array_reverse($logs) as $log) {
                            $level_color = array(
                                'error' => '#f44336',
                                'warning' => '#ff9800',
                                'info' => '#4caf50',
                                'debug' => '#2196f3'
                            )[$log->level] ?? '#d4d4d4';
                            
                            echo '<div style="margin-bottom: 10px; border-left: 3px solid ' . esc_attr($level_color) . '; padding-left: 10px;">';
                            echo '<span style="color: #9e9e9e;">[' . esc_html($log->created_at) . ']</span> ';
                            echo '<span style="color: ' . esc_attr($level_color) . '; font-weight: bold;">' . esc_html(strtoupper($log->level)) . '</span> ';
                            echo '<span>' . esc_html($log->message) . '</span>';
                            if (!empty($log->context)) {
                                echo '<div style="color: #888; font-size: 11px; margin-top: 5px;">' . esc_html($log->context) . '</div>';
                            }
                            echo '</div>';
                        }
                    }
                }
                ?>
            </div>
            </div>
            
            <?php
            $bfpi_log_viewer_js = <<<'BFPI_JS'
            var autoRefreshInterval = null;
            
            function toggleAutoRefresh(enabled) {
                localStorage.setItem('bfpi_import_logs_auto_refresh', enabled ? '1' : '0');
                
                if (enabled) {
                    autoRefreshInterval = setInterval(function() {
                        location.reload();
                    }, 5000);
                } else {
                    if (autoRefreshInterval) {
                        clearInterval(autoRefreshInterval);
                        autoRefreshInterval = null;
                    }
                }
            }
            
            document.addEventListener('DOMContentLoaded', function() {
                var logContent = document.querySelector('.log-viewer-content');
                if (logContent) {
                    logContent.scrollTop = logContent.scrollHeight;
                }
                
                var autoRefreshEnabled = localStorage.getItem('bfpi_import_logs_auto_refresh') === '1';
                var checkbox = document.getElementById('auto-refresh');
                if (checkbox) {
                    checkbox.checked = autoRefreshEnabled;
                    if (autoRefreshEnabled) {
                        toggleAutoRefresh(true);
                    }
                }
            });
BFPI_JS;
            wp_add_inline_script( $this->plugin_name . '-admin', $bfpi_log_viewer_js, 'after' );
            ?>
        </div>
        <?php
    }

    /**
     * Handle update import URL AJAX request.
     *
     * @since    1.0.0
     */
    public function handle_update_import_url() {
        // Verify nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')), 'bfpi_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        $import_id = intval(wp_unslash($_POST['import_id'] ?? 0));
        $new_url = esc_url_raw( wp_unslash( $_POST['file_url'] ?? '' ) );
        
        if (!$import_id || !$new_url) {
            wp_send_json_error(array('message' => __('Invalid import ID or URL.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'bfpi_imports';
        
        // Get current import to find old file
        $import = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $import_id
        ), ARRAY_A);
        
        if (!$import) {
            wp_send_json_error(array('message' => __('Import not found.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        // Delete old XML file if it exists and URL is changing
        if (!empty($import['file_url']) && $import['file_url'] !== $new_url) {
            if (file_exists($import['file_url'])) {
                wp_delete_file($import['file_url']);
            }
        }
        
        // Update URL in database
        $updated = $wpdb->update(
            $table_name,
            array('original_file_url' => $new_url),
            array('id' => $import_id),
            array('%s'),
            array('%d')
        );
        
        if ($updated === false) {
            wp_send_json_error(array('message' => __('Failed to update URL in database.', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        wp_send_json_success(array('message' => __('URL updated successfully. Next import will use the new URL.', 'bootflow-product-xml-csv-importer')));
    }

    /**
     * Download import file from URL for cron jobs.
     * WP.org compliance: URL validation and wp_safe_remote_get
     *
     * @since    1.0.0
     * @param    string $url File URL
     * @param    int $import_id Import ID
     * @return   array Result with success status and file_path or message
     */
    private function download_import_file($url, $import_id) {
        // WP.org compliance: validate URL for SSRF protection
        $url_validation = Bfpi_Security::validate_remote_url($url);
        if (!$url_validation['valid']) {
            return array('success' => false, 'message' => $url_validation['error']);
        }

        $upload_dir = wp_upload_dir();
        $basedir = $upload_dir['basedir'];
        $plugin_upload_dir = $basedir . '/bootflow-product-xml-csv-importer/';
        
        if (!is_dir($plugin_upload_dir)) {
            wp_mkdir_p($plugin_upload_dir);
        }
        
        $base_filename = sanitize_file_name(basename(wp_parse_url($url, PHP_URL_PATH)));
        if (empty($base_filename)) {
            $base_filename = 'import_' . absint($import_id);
        }
        
        $file_path = $plugin_upload_dir . time() . '_' . $base_filename;
        
        // Download with streaming
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Required for streaming large file downloads
        $temp_file = fopen($file_path, 'w');
        if (!$temp_file) {
            return array('success' => false, 'message' => 'Failed to create temporary file');
        }
        
        // WP.org compliance: use wp_safe_remote_get
        $response = wp_safe_remote_get($url, array(
            'timeout' => 300,
            'redirection' => 5,
            'sslverify' => true,
            'stream' => true,
            'filename' => $file_path,
            'user-agent' => 'Bootflow-WooCommerce-Importer/' . BFPI_VERSION
        ));
        
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
        fclose($temp_file);
        
        if (is_wp_error($response)) {
            if (file_exists($file_path)) wp_delete_file($file_path);
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            if (file_exists($file_path)) wp_delete_file($file_path);
            return array('success' => false, 'message' => 'HTTP Status: ' . $response_code);
        }
        
        if (!file_exists($file_path) || filesize($file_path) === 0) {
            return array('success' => false, 'message' => 'Downloaded file is empty');
        }
        
        return array('success' => true, 'file_path' => $file_path);
    }
    
    /**
     * AJAX handler to detect attribute values from source field.
     */
    public function ajax_detect_attribute_values() {
        check_ajax_referer('bfpi_nonce', 'nonce');
        if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_send_json_error( 'Insufficient permissions.' ); }
        
        $import_id = intval(wp_unslash($_POST['import_id'] ?? 0));
        $source_field = sanitize_text_field(wp_unslash($_POST['source_field'] ?? ''));
        
        if (!$import_id || !$source_field) {
            wp_send_json_error(array('message' => 'Missing parameters'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'bfpi_imports';
        $import = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $import_id));
        
        if (!$import) {
            wp_send_json_error(array('message' => 'Import not found'));
        }
        
        $file_path = $import->file_path;
        if (!file_exists($file_path)) {
            wp_send_json_error(array('message' => 'Import file not found'));
        }
        
        // Parse file to extract unique values from source field
        $values = array();
        
        try {
            if ($import->file_type === 'xml') {
                $xml_parser = new Bfpi_XML_Parser();
                $products = $xml_parser->parse($file_path, $import->product_wrapper);
                
                // Extract values from source field
                foreach ($products as $product) {
                    $value = $this->get_nested_value($product, $source_field);
                    if (!empty($value)) {
                        if (is_array($value)) {
                            // If array of values (e.g., multiple attributes)
                            foreach ($value as $v) {
                                if (is_array($v) && isset($v['value'])) {
                                    $values[] = $v['value'];
                                } else {
                                    $values[] = (string)$v;
                                }
                            }
                        } else {
                            $values[] = (string)$value;
                        }
                    }
                }
            } else {
                // CSV parsing
                $csv_parser = new Bfpi_CSV_Parser();
                $products = $csv_parser->parse($file_path);
                
                foreach ($products as $product) {
                    if (isset($product[$source_field]) && !empty($product[$source_field])) {
                        // Check if comma-separated
                        if (strpos($product[$source_field], ',') !== false) {
                            $split_values = array_map('trim', explode(',', $product[$source_field]));
                            $values = array_merge($values, $split_values);
                        } else {
                            $values[] = $product[$source_field];
                        }
                    }
                }
            }
            
            // Get unique values and limit to first 10 for UI
            $values = array_unique($values);
            $values = array_filter($values); // Remove empty
            $values = array_values($values); // Re-index
            
            // Limit to 20 values max for UI performance
            if (count($values) > 20) {
                $values = array_slice($values, 0, 20);
            }
            
            if (empty($values)) {
                wp_send_json_error(array('message' => 'No values found in source field: ' . $source_field));
            }
            
            wp_send_json_success(array('values' => $values));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error parsing file: ' . $e->getMessage()));
        }
    }
    
    /**
     * Get nested value from array using dot notation.
     */
    private function get_nested_value($array, $path) {
        $keys = explode('.', $path);
        $value = $array;
        
        foreach ($keys as $key) {
            // Handle array notation like [0]
            if (preg_match('/(.+)\[(\d+)\]/', $key, $matches)) {
                $key = $matches[1];
                $index = intval($matches[2]);
                
                if (isset($value[$key]) && is_array($value[$key]) && isset($value[$key][$index])) {
                    $value = $value[$key][$index];
                } else {
                    return null;
                }
            } elseif (isset($value[$key])) {
                $value = $value[$key];
            } else {
                return null;
            }
        }
        
        return $value;
    }

    /**
     * AJAX: Save mapping recipe
     */
    public function ajax_save_recipe() {
        check_ajax_referer('bfpi_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'bootflow-product-xml-csv-importer')));
        }
        
        $recipe_name = sanitize_text_field(wp_unslash($_POST['recipe_name'] ?? ''));
        $mapping_data = isset($_POST['mapping_data']) ? $_POST['mapping_data'] : array();
        $existing_recipe_id = sanitize_text_field(wp_unslash($_POST['recipe_id'] ?? ''));
        
        if (empty($recipe_name)) {
            wp_send_json_error(array('message' => __('Recipe name is required', 'bootflow-product-xml-csv-importer')));
        }
        
        // Get existing recipes
        $recipes = get_option('bfpi_recipes', array());
        
        // Check if updating existing recipe (by ID) or by name match
        $recipe_id = null;
        $is_update = false;
        
        // First check if recipe_id was provided (loaded recipe)
        if (!empty($existing_recipe_id) && isset($recipes[$existing_recipe_id])) {
            $recipe_id = $existing_recipe_id;
            $is_update = true;
        } else {
            // Check if a recipe with this name already exists
            foreach ($recipes as $id => $recipe) {
                if (strtolower($recipe['name']) === strtolower($recipe_name)) {
                    $recipe_id = $id;
                    $is_update = true;
                    break;
                }
            }
        }
        
        // If no existing recipe found, create new ID
        if (!$recipe_id) {
            $recipe_id = sanitize_title($recipe_name) . '_' . time();
        }
        
        // Save/update recipe
        $recipes[$recipe_id] = array(
            'name' => $recipe_name,
            'mapping_data' => $mapping_data,
            'created_at' => $is_update && isset($recipes[$recipe_id]['created_at']) 
                ? $recipes[$recipe_id]['created_at'] 
                : current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        update_option('bfpi_recipes', $recipes);
        
        $message = $is_update 
            ? __('Recipe updated successfully', 'bootflow-product-xml-csv-importer')
            : __('Recipe saved successfully', 'bootflow-product-xml-csv-importer');
        
        wp_send_json_success(array(
            'message' => $message,
            'recipe_id' => $recipe_id,
            'is_update' => $is_update,
            'recipes' => $this->get_recipes_list()
        ));
    }

    /**
     * AJAX: Load recipe
     */
    public function ajax_load_recipe() {
        check_ajax_referer('bfpi_nonce', 'nonce');
        if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_send_json_error( 'Insufficient permissions.' ); }
        
        $recipe_id = sanitize_text_field(wp_unslash($_POST['recipe_id'] ?? ''));
        
        if (empty($recipe_id)) {
            wp_send_json_error(array('message' => __('Recipe ID is required', 'bootflow-product-xml-csv-importer')));
        }
        
        $recipes = get_option('bfpi_recipes', array());
        
        if (!isset($recipes[$recipe_id])) {
            wp_send_json_error(array('message' => __('Recipe not found', 'bootflow-product-xml-csv-importer')));
        }
        
        wp_send_json_success(array(
            'recipe' => $recipes[$recipe_id],
            'message' => __('Recipe loaded successfully', 'bootflow-product-xml-csv-importer')
        ));
    }

    /**
     * AJAX: Delete recipe
     */
    public function ajax_delete_recipe() {
        check_ajax_referer('bfpi_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'bootflow-product-xml-csv-importer')));
        }
        
        $recipe_id = sanitize_text_field(wp_unslash($_POST['recipe_id'] ?? ''));
        
        if (empty($recipe_id)) {
            wp_send_json_error(array('message' => __('Recipe ID is required', 'bootflow-product-xml-csv-importer')));
        }
        
        $recipes = get_option('bfpi_recipes', array());
        
        if (isset($recipes[$recipe_id])) {
            unset($recipes[$recipe_id]);
            update_option('bfpi_recipes', $recipes);
        }
        
        wp_send_json_success(array(
            'message' => __('Recipe deleted successfully', 'bootflow-product-xml-csv-importer'),
            'recipes' => $this->get_recipes_list()
        ));
    }

    /**
     * AJAX: Get recipes list
     */
    public function ajax_get_recipes() {
        check_ajax_referer('bfpi_nonce', 'nonce');
        if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_send_json_error( 'Insufficient permissions.' ); }
        
        wp_send_json_success(array(
            'recipes' => $this->get_recipes_list()
        ));
    }

    /**
     * Helper: Get recipes list for dropdown
     */
    private function get_recipes_list() {
        $recipes = get_option('bfpi_recipes', array());
        $list = array();
        
        foreach ($recipes as $id => $recipe) {
            $list[] = array(
                'id' => $id,
                'name' => $recipe['name'],
                'created_at' => $recipe['created_at']
            );
        }
        
        // Sort by created_at descending (newest first)
        usort($list, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return $list;
    }

    /**
     * AJAX: Auto-detect mapping based on field name matching
     */
    public function ajax_auto_detect_mapping() {
        check_ajax_referer('bfpi_nonce', 'nonce');
        if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_send_json_error( 'Insufficient permissions.' ); }
        
        $source_fields = isset($_POST['source_fields']) ? array_map('sanitize_text_field', $_POST['source_fields']) : array();
        
        if (empty($source_fields)) {
            wp_send_json_error(array('message' => __('No source fields provided', 'bootflow-product-xml-csv-importer')));
        }
        
        // WooCommerce field aliases for matching
        $field_aliases = array(
            'sku' => array('sku', 'product_code', 'item_code', 'article', 'artikuls', 'product_sku', 'code', 'item_sku', 'articlecode', 'itemcode', 'productcode', 'id', 'product_id'),
            'name' => array('name', 'title', 'product_name', 'product_title', 'item_name', 'nosaukums', 'productname', 'item', 'productdescription'),
            'description' => array('description', 'desc', 'content', 'product_description', 'full_description', 'apraksts', 'long_description', 'longdescription', 'fulldescription'),
            'short_description' => array('short_description', 'short_desc', 'excerpt', 'summary', 'shortdescription', 'brief', 'intro'),
            'regular_price' => array('price', 'regular_price', 'cena', 'retail_price', 'list_price', 'msrp', 'regularprice', 'listprice', 'baseprice', 'base_price', 'unit_price'),
            'sale_price' => array('sale_price', 'special_price', 'discount_price', 'saleprice', 'discountprice', 'offer_price'),
            'stock_quantity' => array('stock', 'quantity', 'qty', 'stock_quantity', 'inventory', 'daudzums', 'stockqty', 'stock_qty', 'available', 'count', 'amount'),
            'weight' => array('weight', 'svars', 'mass', 'wt', 'productweight', 'product_weight'),
            'length' => array('length', 'garums', 'len', 'productlength'),
            'width' => array('width', 'platums', 'wid', 'productwidth'),
            'height' => array('height', 'augstums', 'hgt', 'productheight'),
            'categories' => array('category', 'categories', 'kategorija', 'cat', 'product_category', 'produkta_kategorija', 'categorypath', 'category_path'),
            'tags' => array('tags', 'tag', 'birkas', 'keywords', 'product_tags'),
            'images' => array('images', 'image', 'attels', 'picture', 'photo', 'img', 'product_image', 'gallery', 'image_url', 'imageurl', 'picture_url', 'pictureurl', 'photos'),
            'featured_image' => array('featured_image', 'main_image', 'primary_image', 'featuredimage', 'mainimage', 'primaryimage', 'thumbnail'),
            'brand' => array('brand', 'manufacturer', 'razotajs', 'make', 'producer', 'vendor'),
            'ean' => array('ean', 'ean13', 'ean_code', 'barcode', 'gtin13'),
            'upc' => array('upc', 'upc_code', 'gtin12'),
            'isbn' => array('isbn', 'isbn13', 'isbn10'),
            'mpn' => array('mpn', 'manufacturer_part_number', 'part_number', 'partnumber'),
            'gtin' => array('gtin', 'gtin14', 'global_trade_item_number'),
            'status' => array('status', 'product_status', 'availability', 'state', 'active'),
            'manage_stock' => array('manage_stock', 'managestock', 'track_stock', 'trackstock'),
            'stock_status' => array('stock_status', 'stockstatus', 'availability_status', 'in_stock', 'instock'),
            'backorders' => array('backorders', 'backorder', 'allow_backorder'),
            'tax_status' => array('tax_status', 'taxstatus', 'taxable'),
            'tax_class' => array('tax_class', 'taxclass', 'tax_rate', 'vat_class'),
            'featured' => array('featured', 'is_featured', 'highlight', 'recommended'),
            'virtual' => array('virtual', 'is_virtual', 'digital'),
            'downloadable' => array('downloadable', 'is_downloadable', 'download'),
            'sold_individually' => array('sold_individually', 'soldindividually', 'single_only'),
            'reviews_allowed' => array('reviews_allowed', 'reviewsallowed', 'enable_reviews', 'allow_reviews'),
            'purchase_note' => array('purchase_note', 'purchasenote', 'order_note'),
            'menu_order' => array('menu_order', 'menuorder', 'sort_order', 'position'),
            'external_url' => array('external_url', 'externalurl', 'affiliate_link', 'product_url'),
            'meta_title' => array('meta_title', 'metatitle', 'seo_title', 'page_title'),
            'meta_description' => array('meta_description', 'metadescription', 'seo_description'),
            'meta_keywords' => array('meta_keywords', 'metakeywords', 'seo_keywords', 'keywords', 'tags_seo', 'search_keywords'),
            'shipping_class' => array('shipping_class', 'shippingclass', 'delivery_class'),
            'upsell_ids' => array('upsell_ids', 'upsell', 'upsells', 'upsell_products', 'upsell_product_ids', 'related_upsell'),
            'cross_sell_ids' => array('cross_sell_ids', 'cross_sell', 'crosssell', 'cross_sells', 'crosssells', 'cross_sell_products'),
            'grouped_products' => array('grouped_products', 'grouped', 'group_products', 'product_group', 'grouped_product_ids'),
            'parent_id' => array('parent_id', 'parent', 'parent_product', 'parent_sku', 'parent_product_id'),
        );
        
        $suggestions = array();
        $matched_woo_fields = array(); // Track already matched WooCommerce fields
        
        // First pass: exact matches
        foreach ($source_fields as $source_field) {
            $source_lower = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $source_field));
            
            foreach ($field_aliases as $woo_field => $aliases) {
                if (isset($matched_woo_fields[$woo_field])) {
                    continue; // Skip already matched fields
                }
                
                foreach ($aliases as $alias) {
                    $alias_clean = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $alias));
                    
                    if ($source_lower === $alias_clean) {
                        $suggestions[$woo_field] = array(
                            'source_field' => $source_field,
                            'confidence' => 100,
                            'match_type' => 'exact'
                        );
                        $matched_woo_fields[$woo_field] = true;
                        break 2;
                    }
                }
            }
        }
        
        // Second pass: partial matches (contains)
        foreach ($source_fields as $source_field) {
            $source_lower = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $source_field));
            
            foreach ($field_aliases as $woo_field => $aliases) {
                if (isset($matched_woo_fields[$woo_field])) {
                    continue;
                }
                
                foreach ($aliases as $alias) {
                    $alias_clean = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $alias));
                    
                    // Check if source contains alias or alias contains source
                    if (strlen($alias_clean) >= 3 && (
                        strpos($source_lower, $alias_clean) !== false || 
                        strpos($alias_clean, $source_lower) !== false
                    )) {
                        // Calculate confidence based on match quality
                        $confidence = 70;
                        if (strpos($source_lower, $alias_clean) === 0 || strpos($alias_clean, $source_lower) === 0) {
                            $confidence = 85; // Starts with match is better
                        }
                        
                        if (!isset($suggestions[$woo_field]) || $suggestions[$woo_field]['confidence'] < $confidence) {
                            $suggestions[$woo_field] = array(
                                'source_field' => $source_field,
                                'confidence' => $confidence,
                                'match_type' => 'partial'
                            );
                            $matched_woo_fields[$woo_field] = true;
                        }
                        break;
                    }
                }
            }
        }
        
        // Sort suggestions by confidence
        uasort($suggestions, function($a, $b) {
            return $b['confidence'] - $a['confidence'];
        });
        
        $total_fields = count($field_aliases);
        $matched_fields = count($suggestions);
        
        wp_send_json_success(array(
            'suggestions' => $suggestions,
            'matched_count' => $matched_fields,
            'total_fields' => $total_fields,
            'message' => sprintf(
                // translators: %1$d is matched count, %2$d is total fields
                __('Auto-detected %1$d of %2$d fields', 'bootflow-product-xml-csv-importer'),
                $matched_fields,
                $total_fields
            )
        ));
    }
    
    /**
     * AJAX handler to get products count for an import
     */
    public function ajax_get_products_count() {
        
        // Verify nonce - use false to return false instead of die()
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')), 'bfpi_nonce')) {
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('ajax_get_products_count - nonce failed'); }
            wp_send_json_error(array('message' => __('Security check failed', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        if (!current_user_can('manage_woocommerce')) {
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('ajax_get_products_count - permission denied'); }
            wp_send_json_error(array('message' => __('Permission denied', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        $import_id = isset($_POST['import_id']) ? intval(wp_unslash($_POST['import_id'])) : 0;
        if (defined('WP_DEBUG') && WP_DEBUG) { error_log('ajax_get_products_count - import_id: ' . $import_id); }
        
        if (!$import_id) {
            wp_send_json_error(array('message' => __('Invalid import ID', 'bootflow-product-xml-csv-importer')));
            return;
        }
        
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}postmeta WHERE meta_key = '_wc_import_id' AND meta_value = %d",
            $import_id
        ));
        
        if (defined('WP_DEBUG') && WP_DEBUG) { error_log('ajax_get_products_count - count: ' . $count); }
        
        wp_send_json_success(array(
            'count' => intval($count),
            'import_id' => $import_id
        ));
    }
    
    /**
     * AJAX handler to delete products in batches with progress
     */
    public function ajax_delete_products_batch() {
        // Enable error handling to catch fatal errors
        try {
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('ajax_delete_products_batch STARTED'); }
            
            // Verify nonce - use global nonce for consistency
            $import_id = isset($_POST['import_id']) ? intval(wp_unslash($_POST['import_id'])) : 0;
            
            if (!$import_id) {
                wp_send_json_error(array('message' => __('Invalid import ID', 'bootflow-product-xml-csv-importer')));
                return;
            }
            
            // Try global nonce first, then import-specific
            $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
            $valid_nonce = wp_verify_nonce($nonce, 'bfpi_nonce') || 
                           wp_verify_nonce($nonce, 'delete_products_' . $import_id);
            
            if (!$valid_nonce) {
                if (defined('WP_DEBUG') && WP_DEBUG) { error_log('ajax_delete_products_batch - nonce failed'); }
                wp_send_json_error(array('message' => __('Security check failed', 'bootflow-product-xml-csv-importer')));
                return;
            }
            
            if (!current_user_can('manage_woocommerce')) {
                if (defined('WP_DEBUG') && WP_DEBUG) { error_log('ajax_delete_products_batch - permission denied'); }
                wp_send_json_error(array('message' => __('Permission denied', 'bootflow-product-xml-csv-importer')));
                return;
            }
        
        $batch_size = isset($_POST['batch_size']) ? intval(wp_unslash($_POST['batch_size'])) : 10;
        $offset = isset($_POST['offset']) ? intval(wp_unslash($_POST['offset'])) : 0;
        
        // Increase time limit for deletion
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }
        
        global $wpdb;
        
        if (defined('WP_DEBUG') && WP_DEBUG) { error_log('DELETE_PRODUCTS_BATCH: Starting for import_id=' . $import_id . ', batch_size=' . $batch_size); }
        
        // Get batch of product IDs
        $product_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '_wc_import_id' AND meta_value = %d LIMIT %d",
            $import_id,
            $batch_size
        ));
        
        if (defined('WP_DEBUG') && WP_DEBUG) { error_log('DELETE_PRODUCTS_BATCH: Found ' . count($product_ids) . ' products to delete'); }
        
        $deleted_count = 0;
        foreach ($product_ids as $product_id) {
            try {
                if (wp_delete_post($product_id, true)) {
                    $deleted_count++;
                }
            } catch (Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) { error_log('DELETE_PRODUCTS_BATCH: Error deleting product ' . $product_id . ': ' . $e->getMessage()); }
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) { error_log('DELETE_PRODUCTS_BATCH: Deleted ' . $deleted_count . ' products'); }
        
        // Get remaining count
        $remaining = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}postmeta WHERE meta_key = '_wc_import_id' AND meta_value = %d",
            $import_id
        ));
        
        $completed = ($remaining == 0);
        
        // If completed, update import's processed_products count to 0
        if ($completed) {
            $wpdb->update(
                $wpdb->prefix . 'bfpi_imports',
                array('processed_products' => 0),
                array('id' => $import_id),
                array('%d'),
                array('%d')
            );
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) { error_log('ajax_delete_products_batch SUCCESS: deleted=' . $deleted_count . ', remaining=' . $remaining); }
        
        wp_send_json_success(array(
            'deleted' => $deleted_count,
            'remaining' => intval($remaining),
            'completed' => $completed,
            'message' => $completed 
                ? __('All products deleted successfully', 'bootflow-product-xml-csv-importer')
                // translators: placeholder values
                : sprintf(__('Deleted %1$d products, %2$d remaining...', 'bootflow-product-xml-csv-importer'), $deleted_count, $remaining)
        ));
        
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('ajax_delete_products_batch EXCEPTION: ' . $e->getMessage()); }
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        } catch (Error $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log('ajax_delete_products_batch ERROR: ' . $e->getMessage()); }
            wp_send_json_error(array('message' => 'Fatal Error: ' . $e->getMessage()));
        }
    }
}
