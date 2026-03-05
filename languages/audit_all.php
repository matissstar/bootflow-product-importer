<?php
/**
 * Extract ALL __() and _e() strings from all PHP files,
 * then check coverage against all 15 language JSON files.
 */

$plugin_dir = dirname(__DIR__);
$translations_dir = __DIR__ . '/translations';

// 1. Find all PHP files recursively
$php_files = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($plugin_dir, RecursiveDirectoryIterator::SKIP_DOTS)
);
foreach ($iterator as $file) {
    if ($file->getExtension() === 'php' && strpos($file->getPathname(), '/languages/') === false) {
        $php_files[] = $file->getPathname();
    }
}
sort($php_files);

echo "Found " . count($php_files) . " PHP files\n\n";

// 2. Extract all __() and _e() strings with bootflow-product-importer text domain
$all_strings = [];
$string_locations = []; // track where each string comes from

foreach ($php_files as $filepath) {
    $content = file_get_contents($filepath);
    $relative = str_replace($plugin_dir . '/', '', $filepath);
    
    // Match __('...', 'bootflow-product-importer') and _e('...', 'bootflow-product-importer')
    // Handle both single and double quotes, including escaped quotes inside
    
    // Pattern for single-quoted strings
    if (preg_match_all("/(?:__|_e|esc_html__|esc_html_e|esc_attr__|esc_attr_e)\(\s*'((?:[^'\\\\]|\\\\.)*)'\s*,\s*'bootflow-product-importer'\s*\)/s", $content, $matches)) {
        foreach ($matches[1] as $str) {
            $str = stripslashes($str);
            if (!empty($str)) {
                $all_strings[$str] = true;
                $string_locations[$str][] = $relative;
            }
        }
    }
    
    // Pattern for double-quoted strings
    if (preg_match_all('/(?:__|_e|esc_html__|esc_html_e|esc_attr__|esc_attr_e)\(\s*"((?:[^"\\\\]|\\\\.)*)"\s*,\s*[\'"]bootflow-product-importer[\'"]\s*\)/s', $content, $matches)) {
        foreach ($matches[1] as $str) {
            $str = stripcslashes($str);
            if (!empty($str)) {
                $all_strings[$str] = true;
                $string_locations[$str][] = $relative;
            }
        }
    }
}

$unique_strings = array_keys($all_strings);
sort($unique_strings);
echo "Found " . count($unique_strings) . " unique translatable strings\n\n";

// 3. Check against all language JSON files
$locales = ['lv', 'es_ES', 'de_DE', 'fr_FR', 'pt_BR', 'ja', 'it_IT', 'nl_NL', 'ru_RU', 'zh_CN', 'pl_PL', 'tr_TR', 'sv_SE', 'id_ID', 'ar'];

$all_missing = [];

foreach ($locales as $locale) {
    $json_file = $translations_dir . '/' . $locale . '.json';
    if (!file_exists($json_file)) {
        echo "WARNING: $locale.json NOT FOUND!\n";
        $all_missing[$locale] = $unique_strings;
        continue;
    }
    
    $translations = json_decode(file_get_contents($json_file), true);
    if ($translations === null) {
        echo "ERROR: Invalid JSON in $locale.json\n";
        continue;
    }
    
    $missing = [];
    $translated = 0;
    
    foreach ($unique_strings as $str) {
        if (isset($translations[$str]) && $translations[$str] !== '') {
            $translated++;
        } else {
            $missing[] = $str;
        }
    }
    
    $total = count($unique_strings);
    $pct = round($translated / $total * 100, 1);
    echo sprintf("%-8s: %d/%d translated (%s%%) — %d MISSING\n", $locale, $translated, $total, $pct, count($missing));
    
    if (!empty($missing)) {
        $all_missing[$locale] = $missing;
    }
}

// 4. Output missing strings per language to files for review
echo "\n--- DETAILED MISSING STRINGS ---\n";
foreach ($all_missing as $locale => $missing) {
    $outfile = __DIR__ . '/missing_' . $locale . '.txt';
    $content = "Missing translations for $locale (" . count($missing) . " strings):\n\n";
    foreach ($missing as $i => $str) {
        $locs = isset($string_locations[$str]) ? implode(', ', array_unique($string_locations[$str])) : '?';
        $content .= ($i+1) . ". [" . $locs . "]\n   EN: " . $str . "\n\n";
    }
    file_put_contents($outfile, $content);
    echo "$locale: " . count($missing) . " missing → saved to missing_$locale.txt\n";
}

// 5. Also check if .pot file has all strings
$pot_file = __DIR__ . '/bootflow-product-importer.pot';
$pot_content = file_get_contents($pot_file);
$not_in_pot = [];
foreach ($unique_strings as $str) {
    // Simple check - escape for pot format and search
    $escaped = addcslashes($str, '"\\');
    if (strpos($pot_content, $escaped) === false) {
        $not_in_pot[] = $str;
    }
}

echo "\n--- STRINGS NOT IN .POT FILE ---\n";
echo count($not_in_pot) . " strings missing from .pot\n";
foreach ($not_in_pot as $s) {
    echo "  - " . substr($s, 0, 100) . "\n";
}

echo "\nDone!\n";
