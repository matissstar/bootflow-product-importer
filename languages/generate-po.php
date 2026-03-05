#!/usr/bin/env php
<?php
/**
 * Translation .po/.mo generator for Bootflow Product Importer.
 * 
 * Reads .pot template + per-language JSON dictionaries → generates .po and .mo files.
 *
 * Usage: php generate-po.php [locale]
 *   php generate-po.php        → generate all languages
 *   php generate-po.php lv     → generate only Latvian
 */

$plugin_slug = 'bootflow-product-importer';
$languages_dir = __DIR__;
$pot_file = $languages_dir . '/' . $plugin_slug . '.pot';
$translations_dir = $languages_dir . '/translations';

// Supported locales
$supported_locales = [
    'lv'    => ['name' => 'Latviešu',         'nplurals' => 3, 'plural' => '(n%10==1 && n%100!=11 ? 0 : n != 0 ? 1 : 2)'],
    'es_ES' => ['name' => 'Español',           'nplurals' => 2, 'plural' => '(n != 1)'],
    'de_DE' => ['name' => 'Deutsch',           'nplurals' => 2, 'plural' => '(n != 1)'],
    'fr_FR' => ['name' => 'Français',          'nplurals' => 2, 'plural' => '(n > 1)'],
    'pt_BR' => ['name' => 'Português do Brasil', 'nplurals' => 2, 'plural' => '(n > 1)'],
    'ja'    => ['name' => '日本語',              'nplurals' => 1, 'plural' => '0'],
    'it_IT' => ['name' => 'Italiano',          'nplurals' => 2, 'plural' => '(n != 1)'],
    'nl_NL' => ['name' => 'Nederlands',        'nplurals' => 2, 'plural' => '(n != 1)'],
    'ru_RU' => ['name' => 'Русский',           'nplurals' => 3, 'plural' => '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)'],
    'zh_CN' => ['name' => '简体中文',            'nplurals' => 1, 'plural' => '0'],
    'pl_PL' => ['name' => 'Polski',            'nplurals' => 3, 'plural' => '(n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)'],
    'tr_TR' => ['name' => 'Türkçe',            'nplurals' => 2, 'plural' => '(n > 1)'],
    'sv_SE' => ['name' => 'Svenska',           'nplurals' => 2, 'plural' => '(n != 1)'],
    'id_ID' => ['name' => 'Bahasa Indonesia',  'nplurals' => 1, 'plural' => '0'],
    'ar'    => ['name' => 'العربية',            'nplurals' => 6, 'plural' => '(n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 ? 4 : 5)'],
];

// Parse command line
$target_locale = $argv[1] ?? null;

if (!file_exists($pot_file)) {
    die("ERROR: .pot file not found: $pot_file\n");
}

// Parse .pot file
$pot_entries = parse_pot_file($pot_file);
echo "Parsed " . count($pot_entries) . " entries from .pot file\n";

// Determine which locales to generate
$locales_to_generate = $target_locale ? [$target_locale => $supported_locales[$target_locale]] : $supported_locales;

foreach ($locales_to_generate as $locale => $locale_info) {
    echo "\n--- Generating $locale ({$locale_info['name']}) ---\n";
    
    // Load translation JSON
    $json_file = $translations_dir . '/' . $locale . '.json';
    if (!file_exists($json_file)) {
        echo "  WARNING: Translation file not found: $json_file — skipping\n";
        continue;
    }
    
    $translations = json_decode(file_get_contents($json_file), true);
    if ($translations === null) {
        echo "  ERROR: Invalid JSON in $json_file\n";
        continue;
    }
    
    echo "  Loaded " . count($translations) . " translations\n";
    
    // Generate .po file
    $po_file = $languages_dir . '/' . $plugin_slug . '-' . $locale . '.po';
    $translated_count = generate_po_file($po_file, $pot_entries, $translations, $locale, $locale_info);
    echo "  Generated .po: $translated_count/" . count($pot_entries) . " translated\n";
    
    // Compile .mo file
    $mo_file = $languages_dir . '/' . $plugin_slug . '-' . $locale . '.mo';
    $cmd = "msgfmt -o " . escapeshellarg($mo_file) . " " . escapeshellarg($po_file) . " 2>&1";
    $output = shell_exec($cmd);
    if (file_exists($mo_file)) {
        echo "  Compiled .mo: " . round(filesize($mo_file) / 1024, 1) . " KB\n";
    } else {
        echo "  ERROR compiling .mo: $output\n";
    }
}

echo "\nDone!\n";

// ============================================================================
// Functions
// ============================================================================

function parse_pot_file($file) {
    $content = file_get_contents($file);
    $entries = [];
    
    // Split into blocks separated by empty lines
    $blocks = preg_split('/\n\n+/', $content);
    
    foreach ($blocks as $block) {
        $block = trim($block);
        if (empty($block)) continue;
        
        // Extract comments (lines starting with #)
        $comments = [];
        foreach (explode("\n", $block) as $line) {
            if (preg_match('/^#/', $line)) {
                $comments[] = $line;
            }
        }
        
        // Extract msgid (may be multiline)
        // Try multiline first: msgid ""\n"continuation..."
        if (preg_match('/^msgid\s+""\s*\n(".*"(?:\n".*")*)/m', $block, $m)) {
            $msgid = '';
            // Match content between outer quotes, handling escaped quotes \"
            preg_match_all('/"((?:[^"\\\\]|\\\\.)*)"/', $m[1], $cont);
            foreach ($cont[1] as $part) {
                $msgid .= $part;
            }
        } elseif (preg_match('/^msgid\s+"(.+)"$/m', $block, $m)) {
            // Single-line msgid with non-empty content
            $msgid = $m[1];
        } else {
            continue;
        }
        
        // Skip empty msgid (header)
        if (empty($msgid)) continue;
        
        // Unescape
        $msgid = stripcslashes($msgid);
        
        // Check for flags
        $flags = [];
        foreach ($comments as $c) {
            if (preg_match('/^#,\s*(.+)$/', $c, $m)) {
                $flags = array_merge($flags, array_map('trim', explode(',', $m[1])));
            }
        }
        
        $entries[] = [
            'msgid' => $msgid,
            'comments' => $comments,
            'flags' => $flags,
        ];
    }
    
    return $entries;
}

function generate_po_file($file, $pot_entries, $translations, $locale, $locale_info) {
    $date = date('Y-m-d H:i+0000');
    
    $header = <<<PO
# Translation file for Bootflow Product Importer - {$locale_info['name']}
# Copyright (C) 2026 Bootflow
# This file is distributed under the same license as the Bootflow Product Importer package.
msgid ""
msgstr ""
"Project-Id-Version: Bootflow Product Importer 0.9.2\\n"
"Report-Msgid-Bugs-To: support@bootflow.io\\n"
"POT-Creation-Date: 2026-03-04 19:52+0200\\n"
"PO-Revision-Date: $date\\n"
"Last-Translator: Bootflow Auto-Translate\\n"
"Language-Team: {$locale_info['name']}\\n"
"Language: $locale\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals={$locale_info['nplurals']}; plural={$locale_info['plural']};\\n"

PO;

    $output = $header;
    $translated_count = 0;
    
    foreach ($pot_entries as $entry) {
        $msgid = $entry['msgid'];
        $msgstr = $translations[$msgid] ?? '';
        
        // Add comments
        foreach ($entry['comments'] as $comment) {
            $output .= $comment . "\n";
        }
        
        // Escape for .po format
        $escaped_id = addcslashes($msgid, "\"\n\\");
        $escaped_str = addcslashes($msgstr, "\"\n\\");
        
        $output .= "msgid \"$escaped_id\"\n";
        $output .= "msgstr \"$escaped_str\"\n\n";
        
        if (!empty($msgstr)) {
            $translated_count++;
        }
    }
    
    file_put_contents($file, $output);
    return $translated_count;
}
