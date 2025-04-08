<?php
/**
 * Plugin Name: TWG Reading Time Estimator
 * Description: Displays an estimated reading time before articles with customizable options.
 * Version: 1.0
 * Author: Deepanker Verma
 * Author URI: https://thewpguides.com
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: twg-reading-time
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Function to calculate reading time
function twg_calculate_reading_time($content) {
    $word_count = str_word_count(wp_strip_all_tags($content)); 
    $reading_speed = get_option('twg_reading_speed', 200); // Ensure default value
    $reading_time = ceil($word_count / $reading_speed);
    return $reading_time;
}

// Function to display reading time
// Function to display reading time with exclusion logic
function twg_display_reading_time($content) {
    if (is_single() && in_the_loop() && is_main_query()) {
        $excluded_post_types = get_option('twg_excluded_post_types', []); // Get excluded post types
        
        if (in_array(get_post_type(), (array) $excluded_post_types)) {
            return $content; // Skip if post type is excluded
        }

        $word_count = str_word_count(wp_strip_all_tags($content)); 
        $reading_speed = get_option('twg_reading_speed', 200); // Default 200 WPM
        $total_seconds = ceil(($word_count / $reading_speed) * 60); // Convert to seconds

        $display_text = get_option('twg_custom_text', __('Reading Time:', 'twg-reading-time'));
        $minutes_text = get_option('twg_minutes_text', __('min', 'twg-reading-time')); // Custom minutes text
        $seconds_text = get_option('twg_seconds_text', __('sec', 'twg-reading-time')); // Custom seconds text
        $show_seconds = get_option('twg_display_seconds', 'no') === 'yes';

        if ($show_seconds) {
            $time_text = $total_seconds . ' ' . esc_html($seconds_text); // Show only seconds
        } else {
            $minutes = floor($total_seconds / 60);
            $seconds = $total_seconds % 60;

            if ($seconds > 0) {
                $time_text = $minutes . ' ' . esc_html($minutes_text) . ', ' . $seconds . ' ' . esc_html($seconds_text); // Show both minutes and seconds
            } else {
                $time_text = $minutes . ' ' . esc_html($minutes_text); // Show only minutes
            }
        }

        $reading_time_text = '<p><strong>' . esc_html($display_text) . '</strong> ' . esc_html($time_text) . '</p>';
        
        $placement = get_option('twg_placement', 'before');
        if ($placement === 'before') {
            return $reading_time_text . $content;
        } elseif ($placement === 'after') {
            return $content . $reading_time_text;
        }
    }
    return $content;
}

add_filter('the_content', 'twg_display_reading_time');

// Shortcode for manual placement
function twg_reading_time_shortcode() {
    if (is_single()) {
        return twg_display_reading_time('');
    }
    return '';
}
add_shortcode('twg_reading_time', 'twg_reading_time_shortcode');

// Add settings menu
function twg_add_settings_menu() {
    add_options_page(__('TWG Reading Time Settings', 'twg-reading-time'), __('Reading Time', 'twg-reading-time'), 'manage_options', 'twg-settings', 'twg_settings_page');
}
add_action('admin_menu', 'twg_add_settings_menu');

// Settings page content
function twg_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('TWG Reading Time Settings', 'twg-reading-time'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('twg_settings_group');
            do_settings_sections('twg-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings
function twg_register_settings() {
    register_setting('twg_settings_group', 'twg_reading_speed');
    register_setting('twg_settings_group', 'twg_placement');
    register_setting('twg_settings_group', 'twg_custom_text');
    register_setting('twg_settings_group', 'twg_display_seconds');
    register_setting('twg_settings_group', 'twg_excluded_post_types');
    register_setting('twg_settings_group', 'twg_minutes_text'); 
    register_setting('twg_settings_group', 'twg_seconds_text'); 
    
    add_settings_section('twg_main_section', __('Settings', 'twg-reading-time'), null, 'twg-settings');
    
    add_settings_field('twg_reading_speed', __('Reading Speed (words per minute):', 'twg-reading-time'), 'twg_reading_speed_field', 'twg-settings', 'twg_main_section');
    add_settings_field('twg_placement', __('Reading Time Placement:', 'twg-reading-time'), 'twg_placement_field', 'twg-settings', 'twg_main_section');
    add_settings_field('twg_custom_text', __('Custom Text:', 'twg-reading-time'), 'twg_custom_text_field', 'twg-settings', 'twg_main_section');
    add_settings_field('twg_display_seconds', __('Show Seconds:', 'twg-reading-time'), 'twg_display_seconds_field', 'twg-settings', 'twg_main_section');
    add_settings_field('twg_excluded_post_types', __('Exclude Post Types:', 'twg-reading-time'), 'twg_excluded_post_types_field', 'twg-settings', 'twg_main_section');
    add_settings_field('twg_minutes_text', __('Minutes Text:', 'twg-reading-time'), 'twg_minutes_text_field', 'twg-settings', 'twg_main_section');
    add_settings_field('twg_seconds_text', __('Seconds Text:', 'twg-reading-time'), 'twg_seconds_text_field', 'twg-settings', 'twg_main_section');
}
add_action('admin_init', 'twg_register_settings');

// Input field for reading speed
function twg_reading_speed_field() {
    $speed = get_option('twg_reading_speed', 200);
    echo '<input type="number" name="twg_reading_speed" value="' . esc_attr($speed) . '" min="50" max="1000" step="10" />';
}

// Minutes text field
function twg_minutes_text_field() {
    $text = get_option('twg_minutes_text', __('min', 'twg-reading-time'));
    echo '<input type="text" name="twg_minutes_text" value="' . esc_attr($text) . '" />';
}

// Seconds text field
function twg_seconds_text_field() {
    $text = get_option('twg_seconds_text', __('sec', 'twg-reading-time'));
    echo '<input type="text" name="twg_seconds_text" value="' . esc_attr($text) . '" />';
}

// Exclude post types field
function twg_excluded_post_types_field() {
    $post_types = get_post_types(['public' => true], 'names');
    $excluded = get_option('twg_excluded_post_types', []);
    echo '<select name="twg_excluded_post_types[]" multiple="multiple" style="height: 150px; width: 100%;">';
    foreach ($post_types as $post_type) {
        echo '<option value="' . esc_attr($post_type) . '" ' . (in_array($post_type, (array) $excluded) ? 'selected' : '') . '>' . esc_html($post_type) . '</option>';
    }
    echo '</select><p>Select post types to exclude from reading time display.</p>';
}


// Placement dropdown
function twg_placement_field() {
    $placement = get_option('twg_placement', 'before');
    echo '<select id="twg_placement" name="twg_placement">
            <option value="before" ' . selected($placement, 'before', false) . '>Before Content</option>
            <option value="after" ' . selected($placement, 'after', false) . '>After Content</option>
            <option value="manual" ' . selected($placement, 'manual', false) . '>Manual (Shortcode)</option>
          </select>';
    
    // Shortcode info (hidden by default)
    echo '<p id="twg_shortcode_notice" style="display: none; color: #0073aa;"><strong>Use this shortcode:</strong> <code>[twg_reading_time]</code></p>';

    // JavaScript to toggle the notice
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            var placementDropdown = document.getElementById("twg_placement");
            var shortcodeNotice = document.getElementById("twg_shortcode_notice");

            function toggleShortcodeNotice() {
                if (placementDropdown.value === "manual") {
                    shortcodeNotice.style.display = "block";
                } else {
                    shortcodeNotice.style.display = "none";
                }
            }

            placementDropdown.addEventListener("change", toggleShortcodeNotice);
            toggleShortcodeNotice(); // Run on load
        });
    </script>';
}


// Custom text field
function twg_custom_text_field() {
    $text = get_option('twg_custom_text', __('Reading Time:', 'twg-reading-time'));
    echo '<input type="text" name="twg_custom_text" value="' . esc_attr($text) . '" />';
}

// Show seconds checkbox
function twg_display_seconds_field() {
    $is_checked = get_option('twg_display_seconds', 'no') === 'yes';
    echo '<input type="checkbox" name="twg_display_seconds" value="yes" ' . checked($is_checked, true, false) . ' /> ' . esc_html__('Display seconds', 'twg-reading-time');
}


// Plugin activation hook to set default options
function twg_activate_plugin() {
    if (get_option('twg_reading_speed') === false) {
        update_option('twg_reading_speed', 200);
    }
}
register_activation_hook(__FILE__, 'twg_activate_plugin');