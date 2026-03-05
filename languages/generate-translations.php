#!/usr/bin/env php
<?php
/**
 * Translation Generator for Bootflow Product Importer
 * 
 * Reads the .pot file, applies translations from JSON language files,
 * and generates .po + .mo files for each language.
 * 
 * Usage: php generate-translations.php
 */

$plugin_dir = dirname(__DIR__);
$lang_dir = __DIR__;
$pot_file = $lang_dir . '/bootflow-product-importer.pot';
$text_domain = 'bootflow-product-importer';

// Supported languages
$languages = array(
    'lv'    => 'Latviešu',
    'es_ES' => 'Español',
    'de_DE' => 'Deutsch',
    'fr_FR' => 'Français',
    'pt_BR' => 'Português (Brasil)',
    'ja'    => '日本語',
    'it_IT' => 'Italiano',
    'nl_NL' => 'Nederlands',
    'ru_RU' => 'Русский',
    'zh_CN' => '中文(简体)',
    'pl_PL' => 'Polski',
    'tr_TR' => 'Türkçe',
    'sv_SE' => 'Svenska',
    'id_ID' => 'Bahasa Indonesia',
    'ar'    => 'العربية',
);

echo "=== Bootflow Translation Generator ===\n\n";

// Parse .pot file to get all msgid entries with their metadata
$pot_content = file_get_contents($pot_file);
if (!$pot_content) {
    die("ERROR: Cannot read {$pot_file}\n");
}

// Parse pot into entries
$entries = parse_pot($pot_content);
echo "Found " . count($entries) . " translatable strings in .pot\n\n";

// Process each language
foreach ($languages as $locale => $name) {
    echo "Processing {$name} ({$locale})...\n";
    
    // Load translation JSON
    $json_file = $lang_dir . "/translations/{$locale}.json";
    $translations = array();
    if (file_exists($json_file)) {
        $translations = json_decode(file_get_contents($json_file), true) ?: array();
    } else {
        echo "  WARNING: No translation file found at {$json_file}\n";
        continue;
    }
    
    // Generate .po file
    $po_file = $lang_dir . "/{$text_domain}-{$locale}.po";
    $po_content = generate_po($locale, $name, $entries, $translations);
    file_put_contents($po_file, $po_content);
    
    $translated = 0;
    $total = count($entries);
    foreach ($entries as $entry) {
        $msgid = $entry['msgid'];
        if (isset($translations[$msgid]) && !empty($translations[$msgid])) {
            $translated++;
        }
    }
    
    echo "  .po created: {$translated}/{$total} strings translated (" . round($translated/$total*100) . "%)\n";
    
    // Compile .mo file
    $mo_file = $lang_dir . "/{$text_domain}-{$locale}.mo";
    $cmd = "msgfmt -o " . escapeshellarg($mo_file) . " " . escapeshellarg($po_file) . " 2>&1";
    $output = shell_exec($cmd);
    if (file_exists($mo_file)) {
        echo "  .mo compiled OK\n";
    } else {
        echo "  ERROR compiling .mo: {$output}\n";
    }
}

echo "\nDone!\n";

// ============================================================
// Functions
// ============================================================

function parse_pot($content) {
    $entries = array();
    $lines = explode("\n", $content);
    $current = null;
    $current_field = '';
    $in_header = true;
    
    for ($i = 0; $i < count($lines); $i++) {
        $line = rtrim($lines[$i]);
        
        // Skip empty lines - they separate entries
        if ($line === '') {
            if ($current !== null && !empty($current['msgid'])) {
                $entries[] = $current;
            }
            $current = null;
            $current_field = '';
            $in_header = false;
            continue;
        }
        
        // Comment lines
        if (preg_match('/^#/', $line)) {
            if ($current === null) {
                $current = array('msgid' => '', 'comments' => array(), 'msgid_plural' => '');
            }
            $current['comments'][] = $line;
            continue;
        }
        
        // msgid line
        if (preg_match('/^msgid\s+"(.*)"$/', $line, $m)) {
            if ($current === null) {
                $current = array('msgid' => '', 'comments' => array(), 'msgid_plural' => '');
            }
            $current['msgid'] = $m[1];
            $current_field = 'msgid';
            continue;
        }
        
        // msgid_plural line
        if (preg_match('/^msgid_plural\s+"(.*)"$/', $line, $m)) {
            $current['msgid_plural'] = $m[1];
            $current_field = 'msgid_plural';
            continue;
        }
        
        // msgstr line (skip in pot parsing)
        if (preg_match('/^msgstr/', $line)) {
            $current_field = 'msgstr';
            continue;
        }
        
        // Continuation line
        if (preg_match('/^"(.*)"$/', $line, $m)) {
            if ($current !== null && $current_field === 'msgid') {
                $current['msgid'] .= $m[1];
            } elseif ($current !== null && $current_field === 'msgid_plural') {
                $current['msgid_plural'] .= $m[1];
            }
            continue;
        }
    }
    
    // Don't forget last entry
    if ($current !== null && !empty($current['msgid'])) {
        $entries[] = $current;
    }
    
    return $entries;
}

function generate_po($locale, $name, $entries, $translations) {
    $date = date('Y-m-d H:iO');
    
    $header = <<<EOH
# {$name} translation for Bootflow Product Importer
# Copyright (C) 2026 Bootflow
# This file is distributed under the same license as the Bootflow Product Importer package.
#
msgid ""
msgstr ""
"Project-Id-Version: Bootflow Product Importer 0.9.2\\n"
"Report-Msgid-Bugs-To: support@bootflow.io\\n"
"POT-Creation-Date: {$date}\\n"
"PO-Revision-Date: {$date}\\n"
"Last-Translator: Bootflow <support@bootflow.io>\\n"
"Language-Team: {$name}\\n"
"Language: {$locale}\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=2; plural=(n != 1);\\n"


EOH;

    $body = '';
    foreach ($entries as $entry) {
        // Add comments
        if (!empty($entry['comments'])) {
            foreach ($entry['comments'] as $comment) {
                $body .= $comment . "\n";
            }
        }
        
        $msgid = $entry['msgid'];
        $msgstr = isset($translations[$msgid]) ? $translations[$msgid] : '';
        
        // Escape for .po format
        $msgid_escaped = po_escape($msgid);
        $msgstr_escaped = po_escape($msgstr);
        
        $body .= "msgid \"{$msgid_escaped}\"\n";
        $body .= "msgstr \"{$msgstr_escaped}\"\n\n";
    }
    
    return $header . $body;
}

function po_escape($str) {
    // The string should already be in gettext escape format from the .pot
    // Just ensure msgstr translations are properly escaped
    return $str;
}
