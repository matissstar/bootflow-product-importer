#!/usr/bin/env php
<?php
/**
 * FREE version build script — strips PRO method bodies and UI sections.
 *
 * Usage:  php free-strip.php <build-directory>
 *
 * This script is called by build.sh AFTER files are copied to the build
 * directory. It modifies the copies in-place — the working directory is
 * never touched.
 *
 * @package Bfpi
 */

if (php_sapi_name() !== 'cli') {
    die('CLI only');
}

if ($argc < 2) {
    die("Usage: php free-strip.php <build-directory>\n");
}

$build_dir = rtrim($argv[1], '/');
if (!is_dir($build_dir)) {
    die("Build directory not found: $build_dir\n");
}

echo "FREE strip: Processing build directory $build_dir\n";

// ────────────────────────────────────────────────────────────────────────────
// 1. class-bfpi-admin.php  — stub PRO methods, remove URL branch
// ────────────────────────────────────────────────────────────────────────────
$admin_file = "$build_dir/includes/admin/class-bfpi-admin.php";
if (file_exists($admin_file)) {
    $code = file_get_contents($admin_file);

    // Methods to stub with wp_send_json_error (AJAX handlers)
    $ajax_stubs = array(
        'handle_test_url'        => 'URL import testing is available in the Pro version.',
        'handle_ai_auto_mapping' => 'AI auto-mapping is available in the Pro version.',
        'handle_test_ai'         => 'AI testing is available in the Pro version.',
        'handle_test_php'        => 'PHP formula testing is available in the Pro version.',
        'handle_test_shipping'   => 'Shipping formula testing is available in the Pro version.',
        'ajax_save_recipe'       => 'Mapping templates are available in the Pro version.',
        'ajax_load_recipe'       => 'Mapping templates are available in the Pro version.',
        'ajax_delete_recipe'     => 'Mapping templates are available in the Pro version.',
    );

    foreach ($ajax_stubs as $method => $message) {
        $code = stub_method($code, $method,
            "        wp_send_json_error(array('message' => __('" . addslashes($message) . "', 'bootflow-product-xml-csv-importer')));\n"
        );
    }

    // Methods that exit/die instead of wp_send_json
    $exit_stubs = array(
        'handle_cron_import'        => "        wp_die(esc_html__('Scheduled imports are available in the Pro version.', 'bootflow-product-xml-csv-importer'), 'Pro Feature', array('response' => 403));\n",
        'handle_single_import_cron' => "        wp_die(esc_html__('Scheduled imports are available in the Pro version.', 'bootflow-product-xml-csv-importer'), 'Pro Feature', array('response' => 403));\n",
    );

    foreach ($exit_stubs as $method => $body) {
        $code = stub_method($code, $method, $body);
    }

    // download_import_file — private, returns array
    $code = stub_method($code, 'download_import_file',
        "        return array('success' => false, 'message' => __('URL import is available in the Pro version.', 'bootflow-product-xml-csv-importer'));\n"
    );

    // display_logs_page — replace with simple message
    $code = stub_method($code, 'display_logs_page',
        "        if (!current_user_can('manage_woocommerce')) { return; }\n" .
        "        echo '<div class=\"wrap bfpi-import\"><h1>' . esc_html__('Import Logs', 'bootflow-product-xml-csv-importer') . '</h1>';\n" .
        "        echo '<p>' . esc_html__('Detailed import logging is available in the Pro version. Basic import status is shown on the Import History page.', 'bootflow-product-xml-csv-importer') . '</p></div>';\n"
    );

    // Remove URL branch from handle_file_upload using brace counting
    $code = strip_elseif_branch($code, 'url');

    file_put_contents($admin_file, $code);
    echo "  ✓ class-bfpi-admin.php — PRO methods stubbed\n";
} else {
    echo "  ✗ class-bfpi-admin.php not found\n";
}

// ────────────────────────────────────────────────────────────────────────────
// 2. class-bfpi-importer.php  — stub execute_shipping_class_formula (eval)
// ────────────────────────────────────────────────────────────────────────────
$importer_file = "$build_dir/includes/class-bfpi-importer.php";
if (file_exists($importer_file)) {
    $code = file_get_contents($importer_file);

    // Stub execute_shipping_class_formula — remove eval()
    $code = stub_method($code, 'execute_shipping_class_formula',
        "        // Pro feature: shipping class formula execution\n" .
        "        return '';\n"
    );

    file_put_contents($importer_file, $code);
    echo "  ✓ class-bfpi-importer.php — eval removed\n";
} else {
    echo "  ✗ class-bfpi-importer.php not found\n";
}

// ────────────────────────────────────────────────────────────────────────────
// 3. step-1-upload.php  — remove URL upload option, scheduling, update existing
// ────────────────────────────────────────────────────────────────────────────
$step1_file = "$build_dir/includes/admin/partials/step-1-upload.php";
if (file_exists($step1_file)) {
    $code = file_get_contents($step1_file);
    $removed = 0;

    // ── 3a. Remove URL radio button label (exact match) ──
    $url_label = <<<'BLOCK'
                    <label class="upload-method-option">
                        <input type="radio" name="upload_method" value="url" />
                        <span class="method-icon">🔗</span>
                        <span class="method-title">
                            <?php esc_html_e('From URL', 'bootflow-product-xml-csv-importer'); ?>
                        </span>
                        <span class="method-desc"><?php esc_html_e('Import from external URL or FTP server', 'bootflow-product-xml-csv-importer'); ?></span>
                    </label>
BLOCK;
    if (strpos($code, $url_label) !== false) {
        $code = str_replace($url_label, '', $code);
        $removed++;
        echo "    Removed: URL upload radio button\n";
    }

    // ── 3b. Remove entire URL input section div ──
    $url_section_start = '            <!-- URL Input -->';
    $url_section_end   = '            </div>' . "\n\n" . '            <!-- File Type Selection -->';
    $pos_start = strpos($code, $url_section_start);
    $pos_end   = strpos($code, $url_section_end);
    if ($pos_start !== false && $pos_end !== false) {
        $code = substr($code, 0, $pos_start) . '            <!-- File Type Selection -->' . substr($code, $pos_end + strlen($url_section_end));
        $removed++;
        echo "    Removed: URL input section\n";
    }

    // ── 3c. Remove Schedule Configuration section ──
    $sched_block = "            <!-- Schedule Configuration -->\n" .
        "            <div class=\"form-group\">\n" .
        "                <label for=\"schedule_type\"><strong><" . "?php esc_html_e('Schedule Type', 'bootflow-product-xml-csv-importer'); ?" . "></strong></label>\n" .
        "                <select id=\"schedule_type\" name=\"schedule_type\" class=\"regular-text\">\n" .
        "                    <option value=\"disabled\"><" . "?php esc_html_e('Manual Import Only', 'bootflow-product-xml-csv-importer'); ?" . "></option>\n" .
        "                    <option value=\"15min\"><" . "?php esc_html_e('Every 15 Minutes', 'bootflow-product-xml-csv-importer'); ?" . "></option>\n" .
        "                    <option value=\"hourly\"><" . "?php esc_html_e('Every Hour', 'bootflow-product-xml-csv-importer'); ?" . "></option>\n" .
        "                    <option value=\"6hours\"><" . "?php esc_html_e('Every 6 Hours', 'bootflow-product-xml-csv-importer'); ?" . "></option>\n" .
        "                    <option value=\"daily\"><" . "?php esc_html_e('Daily Import', 'bootflow-product-xml-csv-importer'); ?" . "></option>\n" .
        "                    <option value=\"weekly\"><" . "?php esc_html_e('Weekly Import', 'bootflow-product-xml-csv-importer'); ?" . "></option>\n" .
        "                    <option value=\"monthly\"><" . "?php esc_html_e('Monthly Import', 'bootflow-product-xml-csv-importer'); ?" . "></option>\n" .
        "                </select>\n" .
        "                <p class=\"description\"><" . "?php esc_html_e('Choose how often this import should run automatically. Manual imports can be run at any time.', 'bootflow-product-xml-csv-importer'); ?" . "></p>\n" .
        "            </div>\n";
    if (strpos($code, $sched_block) !== false) {
        $code = str_replace($sched_block, '', $code);
        $removed++;
        echo "    Removed: Schedule Configuration section\n";
    } else {
        echo "    WARNING: Schedule Configuration section not found\n";
    }

    // ── 3c2. Also remove the Upload Method Selection div (only file radio remains) ──
    $method_block = "            <!-- Upload Method Selection -->\n" .
        "            <div class=\"form-group\">\n" .
        "                <label><strong><" . "?php esc_html_e('Upload Method', 'bootflow-product-xml-csv-importer'); ?" . "></strong></label>\n" .
        "                <div class=\"upload-method-selection\">\n" .
        "                    <label class=\"upload-method-option\">\n" .
        "                        <input type=\"radio\" name=\"upload_method\" value=\"file\" checked />\n" .
        "                        <span class=\"method-icon\">\xF0\x9F\x93\x81</span>\n" .
        "                        <span class=\"method-title\"><" . "?php esc_html_e('Upload File', 'bootflow-product-xml-csv-importer'); ?" . "></span>\n" .
        "                        <span class=\"method-desc\"><" . "?php esc_html_e('Upload XML/CSV file from your computer', 'bootflow-product-xml-csv-importer'); ?" . "></span>\n" .
        "                    </label>\n" .
        "                    \n" .
        "                </div>\n" .
        "            </div>\n";
    if (strpos($code, $method_block) !== false) {
        $code = str_replace($method_block, '', $code);
        $removed++;
        echo "    Removed: Upload Method Selection (radio)\n";
    }

    // ── 3d. Remove update_existing checkbox div ──
    $update_block = <<<'BLOCK'
                        <div class="advanced-item">
                            <label>
                                <input type="checkbox" name="update_existing" value="1" />
                                <?php esc_html_e('Update Existing Products', 'bootflow-product-xml-csv-importer'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Update products that already exist (matched by SKU).', 'bootflow-product-xml-csv-importer'); ?></p>
                        </div>
BLOCK;
    if (strpos($code, $update_block) !== false) {
        $code = str_replace($update_block, '', $code);
        $removed++;
        echo "    Removed: update_existing checkbox\n";
    }

    // ── 3e. Remove JS upload method toggle (URL-related) ──
    $js_toggle = <<<'BLOCK'
    // Upload method selection
    $('input[name="upload_method"]').on('change', function() {
        const method = $(this).val();
        if (method === 'file') {
            $('#file-upload-section').show();
            $('#url-upload-section').hide();
        } else {
            $('#file-upload-section').hide();
            $('#url-upload-section').show();
        }
    });
BLOCK;
    if (strpos($code, $js_toggle) !== false) {
        $code = str_replace($js_toggle, '', $code);
        $removed++;
        echo "    Removed: JS upload method toggle\n";
    }

    // ── 3f. Remove JS Test URL handler ──
    $js_test_url_start = "    // Test URL\n    \$('#test-url').on('click', function() {";
    $js_test_url_end   = "    });\n    \n    // Toggle advanced options";
    $pos_start = strpos($code, $js_test_url_start);
    $pos_end   = strpos($code, $js_test_url_end);
    if ($pos_start !== false && $pos_end !== false) {
        $code = substr($code, 0, $pos_start) . "    // Toggle advanced options" . substr($code, $pos_end + strlen($js_test_url_end));
        $removed++;
        echo "    Removed: JS Test URL handler\n";
    }

    // ── 3g. Remove JS URL validation check ──
    $js_url_check = <<<'BLOCK'
        if (uploadMethod === 'url' && !$('#file_url').val().trim()) {
            showMessage('Please enter a file URL.', 'error');
            return;
        }
BLOCK;
    if (strpos($code, $js_url_check) !== false) {
        $code = str_replace($js_url_check, '', $code);
        $removed++;
        echo "    Removed: JS URL validation\n";
    }

    file_put_contents($step1_file, $code);
    echo "  ✓ step-1-upload.php — $removed PRO elements removed\n";
} else {
    echo "  ✗ step-1-upload.php not found\n";
}

// ────────────────────────────────────────────────────────────────────────────
// 4. step-2-mapping.php  — remove scheduling, templates, AI, PRO processing modes
// ────────────────────────────────────────────────────────────────────────────
$step2_file = "$build_dir/includes/admin/partials/step-2-mapping.php";
if (file_exists($step2_file)) {
    $code = file_get_contents($step2_file);

    // Remove scheduling section: the entire if ($is_url_source) block containing schedule_type
    $code = strip_template_conditional($code, 'is_url_source');
    // Also remove hidden schedule_type input for non-URL sources (negated condition)
    $open_tag = '<' . '?php';
    $close_tag = '?' . '>';
    $code = preg_replace(
        '/' . preg_quote($open_tag, '/') . '\s+if\s*\(\s*!\s*\$is_url_source\s*\)\s*:\s*' . preg_quote($close_tag, '/') . '[\s\S]*?schedule_type[\s\S]*?' . preg_quote($open_tag, '/') . '\s+endif;\s*' . preg_quote($close_tag, '/') . '/i',
        '',
        $code,
        1
    );

    // Remove recipe/template controls section (div.mapping-recipes-section)
    $code = preg_replace(
        '/<div[^>]*class=["\'][^"\']*mapping-recipes-section[^"\']*["\'][^>]*>[\s\S]*?<div[^>]*id=["\']recipe-status-message["\'][\s\S]*?<\/div>\s*<\/div>/i',
        '',
        $code,
        1
    );

    // Remove AI auto-map section
    // First remove the PHP code block that checks for AI settings
    $code = preg_replace(
        '/' . preg_quote($open_tag, '/') . '\s*\n\s*\/\/\s*Check if AI API key is configured[\s\S]*?\$has_any_ai\s*=[^;]+;\s*' . preg_quote($close_tag, '/') . '/i',
        '',
        $code,
        1
    );
    // Then remove the AI auto-mapping conditional block (handles nested if/endif)
    $code = strip_template_conditional($code, 'can_ai_mapping');

    // Replace processing mode selects - keep only 'direct' option
    // Match <select> with processing_mode and replace all options
    $code = preg_replace_callback(
        '/(<select[^>]*(?:name=["\'][^"\']*processing_mode[^"\']*["\']|class=["\'][^"\']*processing-mode-select[^"\']*["\'])[^>]*>)([\s\S]*?)(<\/select>)/i',
        function($matches) {
            return $matches[1] . "\n" .
                "                                                    <option value=\"direct\" selected>" . "Direct Mapping</option>\n" .
                "                                                " . $matches[3];
        },
        $code
    );

    // Remove PHP formula, AI processing, and hybrid config panels
    // Remove config-panel divs (php formula textarea, AI prompt, hybrid config)
    $code = preg_replace(
        '/<div[^>]*class=["\'][^"\']*(?:php-formula-config|ai-config|hybrid-config)[^"\']*config-panel[^"\']*["\'][^>]*>[\s\S]*?<\/div>/i',
        '',
        $code
    );

    // Remove $can_ai_processing and $can_hybrid_processing variables at top
    $code = preg_replace(
        '/\$can_ai_processing\s*=\s*[^;]+;\s*\n/i',
        '',
        $code
    );
    $code = preg_replace(
        '/\$can_hybrid_processing\s*=\s*[^;]+;\s*\n/i',
        '',
        $code
    );

    file_put_contents($step2_file, $code);
    echo "  ✓ step-2-mapping.php — PRO UI removed\n";
} else {
    echo "  ✗ step-2-mapping.php not found\n";
}

// ────────────────────────────────────────────────────────────────────────────
// 5. import-edit.php  — remove shipping formula, PRO processing modes
// ────────────────────────────────────────────────────────────────────────────
$edit_file = "$build_dir/includes/admin/partials/import-edit.php";
if (file_exists($edit_file)) {
    $code = file_get_contents($edit_file);

    // Remove shipping formula section (PHP template if-block)
    // Matches the shipping_class_formula conditional and replaces with if(false)
    // to keep the else branch intact
    $open_tag = '<' . '?php';
    $close_tag = '?' . '>';
    $code = preg_replace(
        '/' . preg_quote($open_tag, '/') . '\s+if\s*\(\s*\$field_key\s*===\s*[\'"]shipping_class_formula[\'"]\s*\)\s*:\s*' . preg_quote($close_tag, '/') . '[\s\S]*?' . preg_quote($open_tag, '/') . '\s+else\s*:\s*' . preg_quote($close_tag, '/') . '/i',
        $open_tag . ' if (false): ' . $close_tag . $open_tag . ' else: ' . $close_tag,
        $code,
        1
    );

    // Replace processing mode selects — keep only 'direct'
    $code = preg_replace_callback(
        '/(<select[^>]*name=["\'][^"\']*processing_mode[^"\']*["\'][^>]*>)([\s\S]*?)(<\/select>)/i',
        function($matches) {
            return $matches[1] . "\n" .
                "                                        <option value=\"direct\" selected>Direct Mapping</option>\n" .
                "                                    " . $matches[3];
        },
        $code
    );

    file_put_contents($edit_file, $code);
    echo "  ✓ import-edit.php — PRO UI removed\n";
} else {
    echo "  ✗ import-edit.php not found\n";
}

// ────────────────────────────────────────────────────────────────────────────
// 6. settings-page.php  — remove AI, Scheduling, Logging, Security tabs
// ────────────────────────────────────────────────────────────────────────────
$settings_file = "$build_dir/includes/admin/partials/settings-page.php";
if (file_exists($settings_file)) {
    $code = file_get_contents($settings_file);

    // Remove tab navigation links for PRO tabs
    $pro_tabs = array('ai-providers', 'ai_providers', 'scheduling', 'logging', 'security');
    foreach ($pro_tabs as $tab) {
        // Remove <a> or <li> tab nav items
        $code = preg_replace(
            '/<(?:a|li)[^>]*(?:href=["\']#' . preg_quote($tab, '/') . '["\']|data-tab=["\']' . preg_quote($tab, '/') . '["\'])[^>]*>[\s\S]*?<\/(?:a|li)>/i',
            '',
            $code
        );
    }

    // Remove tab content panels for PRO tabs
    foreach ($pro_tabs as $tab) {
        // Match <div id="tab-{name}" ...> ... </div> (entire tab panel)
        $code = preg_replace(
            '/<div[^>]*id=["\'](?:tab[_-])?' . preg_quote($tab, '/') . '["\'][^>]*>[\s\S]*?(?=<div[^>]*id=["\'](?:tab[_-])?|<\/form>|$)/i',
            '',
            $code,
            1
        );
    }

    file_put_contents($settings_file, $code);
    echo "  ✓ settings-page.php — PRO tabs removed\n";
} else {
    echo "  ✗ settings-page.php not found\n";
}

// ────────────────────────────────────────────────────────────────────────────
// 7. step-3-progress.php  — remove detailed log sections
// ────────────────────────────────────────────────────────────────────────────
$step3_file = "$build_dir/includes/admin/partials/step-3-progress.php";
if (file_exists($step3_file)) {
    $code = file_get_contents($step3_file);

    // Remove detailed_logs PHP conditional blocks
    // Pattern: if (... detailed_logs ...) { ... }
    $code = preg_replace(
        '/if\s*\(\s*[^)]*detailed_logs[^)]*\)\s*\{[\s\S]*?\}\s*/i',
        '',
        $code
    );
    // Also remove any php-if (detailed_logs) endif alternative-syntax blocks
    $open_tag = '<' . '?php';
    $close_tag = '?' . '>';
    $code = preg_replace(
        '/' . preg_quote($open_tag, '/') . '\s+if\s*\([^)]*detailed_logs[^)]*\)\s*:\s*' . preg_quote($close_tag, '/') . '[\s\S]*?' . preg_quote($open_tag, '/') . '\s+endif;\s*' . preg_quote($close_tag, '/') . '/i',
        '',
        $code
    );

    // Remove log polling JavaScript sections
    $code = preg_replace(
        '/\/\/\s*(?:Log|Detailed)\s*(?:polling|log)[\s\S]*?(?=\/\/\s*[A-Z]|\}\s*\)\s*;|\n\s*\n\s*\/\*)/i',
        '',
        $code,
        2
    );

    file_put_contents($step3_file, $code);
    echo "  ✓ step-3-progress.php — detailed logs removed\n";
} else {
    echo "  ✗ step-3-progress.php not found\n";
}

// ────────────────────────────────────────────────────────────────────────────
// 8. class-bfpi.php  — remove PRO hook registrations
// ────────────────────────────────────────────────────────────────────────────
$main_file = "$build_dir/includes/class-bfpi.php";
if (file_exists($main_file)) {
    $code = file_get_contents($main_file);

    // Remove cron-related hook registrations
    // Lines like: $this->loader->add_action('wp_ajax_nopriv_bfpi_cron_import', $plugin_admin, 'handle_cron_import');
    $cron_hooks = array(
        'handle_cron_import',
        'handle_single_import_cron',
        'bfpi_process_chunk',
    );
    foreach ($cron_hooks as $hook) {
        $code = preg_replace(
            '/^\s*\$this->loader->add_action\([^)]*' . preg_quote($hook, '/') . '[^)]*\);\s*$/m',
            '',
            $code
        );
    }

    // Remove recipe endpoint registrations
    $recipe_hooks = array(
        'ajax_save_recipe',
        'ajax_load_recipe',
        'ajax_delete_recipe',
        'ajax_get_recipes',
    );
    foreach ($recipe_hooks as $hook) {
        $code = preg_replace(
            '/^\s*\$this->loader->add_action\([^)]*' . preg_quote($hook, '/') . '[^)]*\);\s*$/m',
            '',
            $code
        );
    }

    // Remove AI-related hook registrations
    $ai_hooks = array(
        'handle_ai_auto_mapping',
        'handle_test_ai',
    );
    foreach ($ai_hooks as $hook) {
        $code = preg_replace(
            '/^\s*\$this->loader->add_action\([^)]*' . preg_quote($hook, '/') . '[^)]*\);\s*$/m',
            '',
            $code
        );
    }

    // Remove test URL hook
    $code = preg_replace(
        '/^\s*\$this->loader->add_action\([^)]*handle_test_url[^)]*\);\s*$/m',
        '',
        $code
    );

    // Remove test PHP/shipping hooks
    $code = preg_replace(
        '/^\s*\$this->loader->add_action\([^)]*handle_test_php[^)]*\);\s*$/m',
        '',
        $code
    );
    $code = preg_replace(
        '/^\s*\$this->loader->add_action\([^)]*handle_test_shipping[^)]*\);\s*$/m',
        '',
        $code
    );

    // Remove scheduler loading
    $code = preg_replace(
        '/^\s*(?:if\s*\([^)]*\)\s*\{?\s*)?require_once[^;]*class-bfpi-scheduler[^;]*;\s*\}?\s*$/m',
        '',
        $code
    );

    // Remove AI providers loading
    $code = preg_replace(
        '/^\s*(?:if\s*\([^)]*\)\s*\{?\s*)?require_once[^;]*class-bfpi-ai-providers[^;]*;\s*\}?\s*$/m',
        '',
        $code
    );

    file_put_contents($main_file, $code);
    echo "  ✓ class-bfpi.php — PRO hooks removed\n";
} else {
    echo "  ✗ class-bfpi.php not found\n";
}

echo "\nFREE strip completed.\n";

// ════════════════════════════════════════════════════════════════════════════
// Helper: Replace a method body with a stub
// ════════════════════════════════════════════════════════════════════════════
/**
 * Find a PHP method by name and replace its body with a stub.
 *
 * Uses brace-counting to accurately find the method boundary,
 * regardless of nested braces in the body.
 *
 * @param string $code     Full file contents
 * @param string $method   Method name (e.g. 'handle_test_php')
 * @param string $stub     Replacement body (without surrounding braces)
 * @return string          Modified code
 */
function stub_method($code, $method, $stub) {
    // Find the method signature
    // Match: public/private/protected function method_name(...)  {
    $pattern = '/((?:public|private|protected)\s+(?:static\s+)?function\s+' . preg_quote($method, '/') . '\s*\([^)]*\)\s*)\{/';

    if (!preg_match($pattern, $code, $matches, PREG_OFFSET_CAPTURE)) {
        echo "  ! Method '$method' not found\n";
        return $code;
    }

    // $matches[0][1] is the offset of the full match
    // The opening brace is at the end of the match
    $sig = $matches[1][0]; // The signature part (before {)
    $brace_pos = $matches[0][1] + strlen($matches[0][0]) - 1; // Position of {

    // Count braces to find the closing }
    $depth = 1;
    $pos = $brace_pos + 1;
    $len = strlen($code);
    $in_string = false;
    $string_char = '';
    $in_line_comment = false;
    $in_block_comment = false;

    while ($pos < $len && $depth > 0) {
        $char = $code[$pos];
        $next = ($pos + 1 < $len) ? $code[$pos + 1] : '';

        // Handle line comments
        if ($in_line_comment) {
            if ($char === "\n") {
                $in_line_comment = false;
            }
            $pos++;
            continue;
        }

        // Handle block comments
        if ($in_block_comment) {
            if ($char === '*' && $next === '/') {
                $in_block_comment = false;
                $pos += 2;
                continue;
            }
            $pos++;
            continue;
        }

        // Handle strings
        if ($in_string) {
            if ($char === '\\') {
                $pos += 2; // Skip escaped character
                continue;
            }
            if ($char === $string_char) {
                $in_string = false;
            }
            $pos++;
            continue;
        }

        // Check for comment starts
        if ($char === '/' && $next === '/') {
            $in_line_comment = true;
            $pos += 2;
            continue;
        }
        if ($char === '/' && $next === '*') {
            $in_block_comment = true;
            $pos += 2;
            continue;
        }

        // Check for string starts
        if ($char === '"' || $char === "'") {
            $in_string = true;
            $string_char = $char;
            $pos++;
            continue;
        }

        // Count braces
        if ($char === '{') {
            $depth++;
        } elseif ($char === '}') {
            $depth--;
        }

        $pos++;
    }

    if ($depth !== 0) {
        echo "  ! Could not find closing brace for '$method'\n";
        return $code;
    }

    // $pos is now right after the closing }
    // Replace from opening { to closing } (inclusive)
    $before = substr($code, 0, $brace_pos);
    $after = substr($code, $pos);

    $replacement = "{\n$stub    }";

    echo "    Stubbed: $method()\n";
    return $before . $replacement . $after;
}

/**
 * Strip an elseif branch from code using brace counting.
 *
 * Finds `} elseif ($upload_method === '$value') {` and replaces the body
 * with a stub, keeping the surrounding if/else structure intact.
 *
 * @param string $code  File contents
 * @param string $value The value in the elseif condition (e.g. 'url')
 * @return string       Modified code
 */
function strip_elseif_branch($code, $value) {
    $pattern = '/\}\s*elseif\s*\(\s*\$upload_method\s*===\s*[\'"]' . preg_quote($value, '/') . '[\'"]\s*\)\s*\{/';
    
    if (!preg_match($pattern, $code, $matches, PREG_OFFSET_CAPTURE)) {
        echo "  ! elseif branch '$value' not found\n";
        return $code;
    }
    
    $match_start = $matches[0][1];
    $match_text = $matches[0][0];
    $brace_pos = $match_start + strlen($match_text) - 1; // Position of {
    
    // Count braces to find the closing }
    $depth = 1;
    $pos = $brace_pos + 1;
    $len = strlen($code);
    $in_string = false;
    $string_char = '';
    $in_line_comment = false;
    $in_block_comment = false;
    
    while ($pos < $len && $depth > 0) {
        $char = $code[$pos];
        $next = ($pos + 1 < $len) ? $code[$pos + 1] : '';
        
        if ($in_line_comment) {
            if ($char === "\n") { $in_line_comment = false; }
            $pos++;
            continue;
        }
        if ($in_block_comment) {
            if ($char === '*' && $next === '/') { $in_block_comment = false; $pos += 2; continue; }
            $pos++;
            continue;
        }
        if ($in_string) {
            if ($char === '\\') { $pos += 2; continue; }
            if ($char === $string_char) { $in_string = false; }
            $pos++;
            continue;
        }
        if ($char === '/' && $next === '/') { $in_line_comment = true; $pos += 2; continue; }
        if ($char === '/' && $next === '*') { $in_block_comment = true; $pos += 2; continue; }
        if ($char === '"' || $char === "'") { $in_string = true; $string_char = $char; $pos++; continue; }
        
        if ($char === '{') { $depth++; }
        elseif ($char === '}') { $depth--; }
        
        $pos++;
    }
    
    if ($depth !== 0) {
        echo "  ! Could not find closing brace for elseif '$value'\n";
        return $code;
    }
    
    // $pos is right after the closing }
    // Replace from after the opening { to before the closing }
    $before = substr($code, 0, $brace_pos + 1); // includes the {
    $after = substr($code, $pos - 1); // includes the }
    
    $stub_body = "\n                throw new Exception(__('URL import is available in the Pro version.', 'bootflow-product-xml-csv-importer'));\n            ";
    
    echo "    Stripped elseif branch: $value\n";
    return $before . $stub_body . $after;
}

/**
 * Remove a PHP template conditional block with proper nesting.
 *
 * Handles: if ($var): ... endif;  with nested if/else/endif inside.
 *
 * @param string $code     File contents
 * @param string $var_name Variable in the condition (e.g. 'can_ai_mapping')
 * @return string          Modified code
 */
function strip_template_conditional($code, $var_name) {
    $open_tag = '<' . '?php';
    $close_tag = '?' . '>';

    // Find the opening "if ($var_name):"
    $pattern = '/' . preg_quote($open_tag, '/') . '\s+if\s*\(\s*\$' . preg_quote($var_name, '/') . '\s*\)\s*:\s*' . preg_quote($close_tag, '/') . '/i';

    if (!preg_match($pattern, $code, $matches, PREG_OFFSET_CAPTURE)) {
        echo "  ! Template conditional '\$$var_name' not found\n";
        return $code;
    }

    $start = $matches[0][1];
    $pos = $start + strlen($matches[0][0]);
    $len = strlen($code);
    $depth = 1;

    // Scan forward counting if/endif depth in PHP template syntax
    while ($pos < $len && $depth > 0) {
        // Look for <?php if|elseif|else|endif
        if (preg_match('/' . preg_quote($open_tag, '/') . '\s+(if|elseif|else|endif)\b/i', $code, $m, PREG_OFFSET_CAPTURE, $pos)) {
            $keyword = strtolower($m[1][0]);
            $kw_pos = $m[0][1];

            if ($keyword === 'if') {
                $depth++;
                $pos = $kw_pos + strlen($m[0][0]);
            } elseif ($keyword === 'endif') {
                $depth--;
                if ($depth === 0) {
                    // Find the end of this endif statement (including close-tag)
                    $end_pattern = '/' . preg_quote($open_tag, '/') . '\s+endif;\s*' . preg_quote($close_tag, '/') . '/i';
                    if (preg_match($end_pattern, $code, $em, PREG_OFFSET_CAPTURE, $kw_pos)) {
                        $end = $em[0][1] + strlen($em[0][0]);
                        $removed = substr($code, $start, $end - $start);
                        echo "    Stripped template conditional: \$$var_name (" . substr_count($removed, "\n") . " lines)\n";
                        return substr($code, 0, $start) . substr($code, $end);
                    }
                }
                $pos = $kw_pos + strlen($m[0][0]);
            } else {
                $pos = $kw_pos + strlen($m[0][0]);
            }
        } else {
            break; // No more PHP keywords found
        }
    }

    echo "  ! Could not find closing endif for '\$$var_name'\n";
    return $code;
}
