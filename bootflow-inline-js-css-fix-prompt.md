# Bootflow FREE — Move Inline JS and CSS to Enqueued Files

WordPress.org requires that JavaScript and CSS are loaded via `wp_enqueue_script` / `wp_enqueue_style`, NOT with inline `<script>` and `<style>` tags in PHP templates.

File `includes/admin/partials/settings-page.php` has an inline `<script>` block (lines 317-341) and an inline `<style>` block (lines 343-361). Move both to the proper enqueued files.

## The CSS (simple — just move it)

The inline CSS (lines 343-361) is:
```css
.tab-content {
    display: none;
    margin-top: 20px;
}
.tab-content.active {
    display: block;
}
.form-table fieldset {
    margin: 0;
}
.form-table fieldset label {
    display: inline-block;
    margin-right: 15px;
}
.toggle-password {
    margin-left: 10px;
}
```

**Action:** Append this CSS to the END of `assets/css/admin.css`. Then delete the entire `<style>...</style>` block from settings-page.php.

## The JavaScript (needs special handling — has PHP inside)

The inline JS (lines 317-341) contains PHP translation calls:
```javascript
$(this).text(type === 'password' ? '<?php esc_html_e('Show', '...'); ?>' : '<?php esc_html_e('Hide', '...'); ?>');
```

Since PHP cannot run inside a static .js file, use `wp_localize_script` to pass the translated strings, then use them in the JS.

### Step 1: In `includes/admin/class-bfpi-admin.php`, in the `enqueue_scripts()` method

Find where `wp_localize_script` is already called for `bfpi_ajax`. Add the translated strings to that same localize call, OR add a second localize call. For example, add these strings to the existing `bfpi_ajax` array:

```php
'show_text' => __('Show', 'bootflow-product-xml-csv-importer'),
'hide_text' => __('Hide', 'bootflow-product-xml-csv-importer'),
```

### Step 2: Add the settings page JS to `assets/js/admin.js`

Append this to the END of `assets/js/admin.js`:

```javascript
// Settings page tab switching
(function($) {
    $(document).ready(function() {
        // Tab switching (only on settings page)
        $('.bfpi-settings-wrap .nav-tab').on('click', function(e) {
            e.preventDefault();
            var target = $(this).attr('href');
            
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            $('.tab-content').removeClass('active');
            $(target).addClass('active');
        });
        
        // Toggle password visibility
        $('.toggle-password').on('click', function() {
            var input = $(this).prev('input');
            var type = input.attr('type') === 'password' ? 'text' : 'password';
            input.attr('type', type);
            if (typeof bfpi_ajax !== 'undefined') {
                $(this).text(type === 'password' ? bfpi_ajax.show_text : bfpi_ajax.hide_text);
            }
        });
    });
})(jQuery);
```

Note: I used `.bfpi-settings-wrap .nav-tab` selector to scope it to the settings page only, so it doesn't conflict with other tab switching on other pages. Make sure the settings page wrapper div has this class. If the wrapper currently uses a different class, use that class instead, or add `bfpi-settings-wrap` to it.

### Step 3: Delete the inline blocks from settings-page.php

Delete the entire `<script type="text/javascript">...</script>` block (lines 317-341).
Delete the entire `<style type="text/css">...</style>` block (lines 343-361).

## Also clean up dead cron code

While you're in `includes/admin/class-bfpi-admin.php`, also delete these unused code blocks:

**In `display_step_2_mapping()` method — delete the import_secret/cron_url generation block:**
```php
        $import_secret = get_option('bfpi_secret_' . $import_id);
        if (empty($import_secret)) {
            $import_secret = wp_generate_password(32, false);
            update_option('bfpi_secret_' . $import_id, $import_secret);
        }
        
        $cron_url = admin_url('admin-ajax.php?action=bfpi_single_cron&import_id=' . $import_id . '&secret=' . $import_secret);
```

**In `display_settings_page()` method — delete the cron_secret_key generation:**
```php
        if (empty($settings['cron_secret_key'])) {
            $settings['cron_secret_key'] = wp_generate_password(32, false);
            update_option('bfpi_settings', $settings);
        }
```

## VERIFICATION

```bash
# No inline script or style tags (except text/template which is OK)
grep -rn "<script\|<style" --include="*.php" includes/ | grep -v 'type="text/template"\|security.php'

# No dead cron code
grep -n "cron_url\|import_secret\|cron_secret_key" includes/admin/class-bfpi-admin.php

# Strings are in localize call
grep -n "show_text\|hide_text" includes/admin/class-bfpi-admin.php
```

First two commands: ZERO results.
Third command: should show the strings in wp_localize_script.
