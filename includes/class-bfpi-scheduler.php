<?php
/**
 * Scheduled Import Handler using Action Scheduler
 *
 * This class handles automatic scheduled imports using WordPress Action Scheduler
 * (bundled with WooCommerce) for reliable background processing.
 *
 * Two methods available:
 * 1. Action Scheduler (default) - Self-chaining jobs, works without server cron
 * 2. Server Cron - External cron calls URL endpoint
 *
 * @package    Bfpi
 * @subpackage Bfpi/includes
 * @since      1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Bfpi_Scheduler {

    /**
     * Action hook name for processing import chunks
     */
    const ACTION_PROCESS_CHUNK = 'bfpi_process_import_chunk';

    /**
     * Action hook name for checking scheduled imports
     */
    const ACTION_CHECK_SCHEDULED = 'bfpi_check_scheduled_imports';

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - register hooks
     */
    private function __construct() {
        // Register action handlers
        add_action(self::ACTION_PROCESS_CHUNK, array($this, 'process_import_chunk'), 10, 2);
        add_action(self::ACTION_CHECK_SCHEDULED, array($this, 'check_scheduled_imports'));

        // Schedule the checker to run every minute
        $this->schedule_checker();
    }

    /**
     * Check if Action Scheduler is available
     */
    public static function is_action_scheduler_available() {
        return function_exists('as_schedule_single_action') && function_exists('as_has_scheduled_action');
    }

    /**
     * Get current scheduling method from settings
     * 
     * @return string 'action_scheduler' or 'server_cron'
     */
    public static function get_scheduling_method() {
        $settings = get_option('bfpi_settings', array());
        return $settings['scheduling_method'] ?? 'action_scheduler';
    }

    /**
     * Schedule the checker to run every minute (if using Action Scheduler)
     */
    private function schedule_checker() {
        if (!self::is_action_scheduler_available()) {
            return;
        }

        if (self::get_scheduling_method() !== 'action_scheduler') {
            return;
        }

        // Check if already scheduled
        if (!as_has_scheduled_action(self::ACTION_CHECK_SCHEDULED)) {
            // Schedule to run every minute
            as_schedule_recurring_action(
                time(),
                60, // Every 60 seconds
                self::ACTION_CHECK_SCHEDULED,
                array(),
                'bfpi-import'
            );
        }
    }

    /**
     * Rescue stuck imports that are in processing/pending state but have no scheduled action
     * This handles cases where import was interrupted (timeout, page closed, error)
     */
    private function rescue_stuck_imports() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bfpi_imports';

        // Find imports that are processing/pending but haven't been updated in 5+ minutes
        // This indicates they are stuck
        $stuck_imports = $wpdb->get_results(
            $wpdb->prepare("
                SELECT id, processed_products, total_products, status, updated_at
                FROM {$table_name}
                WHERE status IN ('processing', 'pending')
                AND total_products > 0
                AND processed_products < total_products
                AND updated_at < DATE_SUB(%s, INTERVAL 5 MINUTE)
                LIMIT 3
            ", current_time('mysql')),
            ARRAY_A
        );

        if (empty($stuck_imports)) {
            return;
        }

        foreach ($stuck_imports as $import) {
            $import_id = intval($import['id']);
            $offset = intval($import['processed_products']);

            // Check if there's already a pending action for this import
            if (as_has_scheduled_action(self::ACTION_PROCESS_CHUNK, array('import_id' => $import_id))) {
                continue;
            }

            // Also check with offset parameter (Action Scheduler might store args differently)
            $pending_actions = as_get_scheduled_actions(array(
                'hook' => self::ACTION_PROCESS_CHUNK,
                'status' => \ActionScheduler_Store::STATUS_PENDING,
                'args' => array('import_id' => $import_id),
                'per_page' => 1,
            ), 'ids');

            if (!empty($pending_actions)) {
                continue;
            }

            // No pending action found - this import is stuck! Rescue it.
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Bfpi Scheduler: RESCUING stuck import ID ' . $import_id . ' at offset ' . $offset . ' (last update: ' . $import['updated_at'] . ')');
            }

            // Update status to processing (in case it was pending)
            $wpdb->update(
                $table_name,
                array(
                    'status' => 'processing',
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $import_id),
                array('%s', '%s'),
                array('%d')
            );

            // Schedule the next chunk to continue from where it left off
            as_schedule_single_action(
                time(),
                self::ACTION_PROCESS_CHUNK,
                array('import_id' => $import_id, 'offset' => $offset),
                'bfpi-import'
            );
        }
    }

    /**
     * Check for scheduled imports that need to run
     * Called by Action Scheduler every minute
     */
    public function check_scheduled_imports() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bfpi_imports';
        $current_time = current_time('mysql');

        // FIRST: Check for stuck imports (processing/pending without scheduled action)
        $this->rescue_stuck_imports();

        // Find imports that are ready to run
        $scheduled_imports = $wpdb->get_results(
            $wpdb->prepare("
                SELECT id, schedule_type, processed_products, total_products 
                FROM {$table_name}
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

        if (empty($scheduled_imports)) {
            return;
        }

        // Schedule each import to start processing
        foreach ($scheduled_imports as $import) {
            $import_id = intval($import['id']);
            
            // Check if already has a pending action
            if (as_has_scheduled_action(self::ACTION_PROCESS_CHUNK, array('import_id' => $import_id))) {
                continue;
            }

            // Reset processed_products for new run and set status
            $wpdb->update(
                $table_name,
                array(
                    'status' => 'processing',
                    'processed_products' => 0,
                    'last_run' => current_time('mysql')
                ),
                array('id' => $import_id),
                array('%s', '%d', '%s'),
                array('%d')
            );

            // Schedule first chunk immediately
            as_schedule_single_action(
                time(),
                self::ACTION_PROCESS_CHUNK,
                array('import_id' => $import_id, 'offset' => 0),
                'bfpi-import'
            );        }
    }

    /**
     * Process a single import chunk
     * Called by Action Scheduler
     *
     * @param int $import_id Import ID
     * @param int $offset Current offset
     */
    public function process_import_chunk($import_id, $offset = 0) {
        global $wpdb;

        $import_id = intval($import_id);
        $offset = intval($offset);
        // CHECK KILL FLAG FIRST - stop immediately if killed
        $killed_specific = get_transient('bfpi_import_killed_' . $import_id);
        $killed_global = get_transient('bfpi_import_killed_global');
        if ($killed_specific || $killed_global) {            // Delete specific transient after detecting it
            delete_transient('bfpi_import_killed_' . $import_id);
            // Only delete global transient if it's for this import
            if ($killed_global && strpos($killed_global, $import_id . ':') === 0) {
                delete_transient('bfpi_import_killed_global');
            }
            return;
        }

        // Get import record
        $table_name = $wpdb->prefix . 'bfpi_imports';
        $import = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $import_id),
            ARRAY_A
        );

        if (!$import) {            return;
        }

        // Check if import was stopped/paused
        if ($import['status'] === 'failed' || $import['status'] === 'paused') {            return;
        }

        // Get settings
        $settings = get_option('bfpi_settings', array());
        $chunk_size = intval($import['batch_size'] ?? $settings['chunk_size'] ?? 50);

        try {
            // Process chunk
            $importer = new Bfpi_Importer();
            $result = $importer->process_import_chunk($offset, $chunk_size, $import_id);

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Bfpi Scheduler: Chunk result - processed=' . ($result['processed'] ?? 0) . ', total_processed=' . ($result['total_processed'] ?? 0) . ', completed=' . ($result['completed'] ? 'YES' : 'NO'));
            }

            if ($result['completed']) {
                // Import completed - update status
                $wpdb->update(
                    $table_name,
                    array('status' => 'completed'),
                    array('id' => $import_id),
                    array('%s'),
                    array('%d')
                );

                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Bfpi Scheduler: Import ID ' . $import_id . ' completed!');
                }
            } else if (isset($result['locked']) && $result['locked']) {
                // Another process is running, reschedule for later
                as_schedule_single_action(
                    time() + 10, // Wait 10 seconds
                    self::ACTION_PROCESS_CHUNK,
                    array('import_id' => $import_id, 'offset' => $offset),
                    'bfpi-import'
                );            } else if (isset($result['stopped']) && $result['stopped']) {
                // Import was stopped            } else {
                // Schedule next chunk IMMEDIATELY (1 second delay for safety)
                $next_offset = $result['total_processed'] ?? ($offset + $chunk_size);
                
                as_schedule_single_action(
                    time() + 1,
                    self::ACTION_PROCESS_CHUNK,
                    array('import_id' => $import_id, 'offset' => $next_offset),
                    'bfpi-import'
                );            }

        } catch (Exception $e) {
            $wpdb->update(
                $table_name,
                array('status' => 'error'),
                array('id' => $import_id),
                array('%s'),
                array('%d')
            );
        }
    }

    /**
     * Manually trigger import to start (for Action Scheduler method)
     *
     * @param int $import_id Import ID
     * @return bool Success
     */
    public static function trigger_import($import_id) {
        if (!self::is_action_scheduler_available()) {
            return false;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'bfpi_imports';

        // Reset and set status
        $wpdb->update(
            $table_name,
            array(
                'status' => 'processing',
                'processed_products' => 0,
                'last_run' => current_time('mysql')
            ),
            array('id' => $import_id),
            array('%s', '%d', '%s'),
            array('%d')
        );

        // Schedule first chunk
        as_schedule_single_action(
            time(),
            self::ACTION_PROCESS_CHUNK,
            array('import_id' => intval($import_id), 'offset' => 0),
            'bfpi-import'
        );

        return true;
    }

    /**
     * Cancel all pending actions for an import
     *
     * @param int $import_id Import ID
     */
    public static function cancel_import($import_id) {
        if (!self::is_action_scheduler_available()) {
            return;
        }

        as_unschedule_all_actions(
            self::ACTION_PROCESS_CHUNK,
            array('import_id' => intval($import_id)),
            'bfpi-import'
        );
    }

    /**
     * Unschedule the checker when plugin is deactivated
     */
    public static function deactivate() {
        if (!self::is_action_scheduler_available()) {
            return;
        }

        as_unschedule_all_actions(self::ACTION_CHECK_SCHEDULED, array(), 'bootflow-product-xml-csv-importer');
    }
}
