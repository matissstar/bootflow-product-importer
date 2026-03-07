<?php
/**
 * Feature Flags Configuration
 * 
 * This file defines which features are available.
 * All features are included in the WordPress.org version.
 *
 * Philosophy:
 * - No product count limits
 * - No artificial restrictions
 *
 * @since      1.0.0
 * @package    Bfpi
 * @subpackage Bfpi/includes/config
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin Edition Flag
 * NOTE: This constant is defined in the main plugin file only
 */

/**
 * Check plugin edition
 * 
 * @return bool Always false for WordPress.org version
 */
function bfpi_is_pro() {
    return false;
}

/**
 * Check if a specific feature is available
 * 
 * @param string $feature Feature key to check
 * @return bool True if feature is available
 */
function bfpi_has_feature($feature) {
    static $features = null;
    
    if ($features === null) {
        $features = include(__FILE__);
    }
    
    return isset($features[$feature]) && $features[$feature];
}

/**
 * Feature definitions - all features are available
 */
return array(
    'import_xml'                => true,
    'import_csv'                => true,
    'import_url'                => true,
    'simple_products'           => true,
    'variable_products'         => true,
    'grouped_products'          => true,
    'external_products'         => true,
    'variations'                => true,
    'attributes'                => true,
    'manual_mapping'            => true,
    'auto_mapping'              => true,
    'templates'                 => true,
    'smart_mapping'             => true,
    'mode_direct'               => true,
    'mode_static'               => true,
    'mode_mapping'              => true,
    'mode_php_formula'          => true,
    'mode_ai_processing'        => true,
    'mode_hybrid'               => true,
    'ai_processing'             => true,
    'filters_basic'             => true,
    'filters_advanced'          => true,
    'filters_regex'             => true,
    'conditional_logic'         => true,
    'pricing_engine'            => true,
    'pricing_global_markup'     => true,
    'pricing_fixed_amount'      => true,
    'pricing_rounding'          => true,
    'pricing_price_ranges'      => true,
    'pricing_by_category'       => true,
    'pricing_by_brand'          => true,
    'pricing_by_supplier'       => true,
    'pricing_multiple_rules'    => true,
    'pricing_conditions'        => true,
    'pricing_min_max'           => true,
    'update_existing'           => true,
    'selective_update'          => true,
    'skip_unchanged'            => true,
    'scheduling'                => true,
    'cron_imports'              => true,
    'import_logs'               => true,
    'error_reporting'           => true,
    'ai_mapping'                => true,
    'ai_translation'            => true,
    'ai_custom_prompts'         => true,
);
