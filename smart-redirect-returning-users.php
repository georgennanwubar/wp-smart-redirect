<?php
/**
 * Plugin Name: Smart Redirect For Returning Users
 * Plugin URI:  https://github.com/georgennanwubar/wp-smart-redirect
 * Description: Redirect returning users based on cookie detection with exclusions, logging, analytics, and full admin control.
 * Version: 1.0
 * Author: George Nnanwubar (Manndi Technologies Limited)
 * Author URI: https://www.george.ng
 * Text Domain: smart-redirect-returning-users
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') or die('No script kiddies please!');

add_action('init', 'smart_redirect');
add_action('admin_menu', 'smart_redirect_admin_menu');
add_action('admin_init', 'smart_redirect_settings_init');

function smart_redirect() {
    if (!is_admin() && !defined('DOING_AJAX')) {
        $cookie_name = get_option('smart_cookie_name', 'smart_user_cookie');
        $cookie_value = get_option('smart_cookie_value', 'true');
        $redirect_url = get_option('smart_redirect_url', home_url('/'));
        $excluded_roles = (array) get_option('smart_excluded_roles', []);
        $enable_ga = get_option('smart_enable_ga', 'yes');
        $gtag_id = get_option('smart_gtag_id', '');
        $api_secret = get_option('smart_ga4_api_secret', '');
        $invert = get_option('smart_invert_url_logic', 'no');
        $applicable_urls = explode("\n", str_replace("\r", "", get_option('smart_applicable_urls', home_url('/'))));
        $current_url = (is_ssl() ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $should_redirect = ($invert === 'yes');

        foreach ($applicable_urls as $url) {
            if (trim($url) && strpos($current_url, trim($url)) !== false) {
                if ($invert === 'yes') {
                    $should_redirect = false;
                    break;
                } else {
                    $should_redirect = true;
                    break;
                }
            }
        }

        $current_user = wp_get_current_user();
        foreach ($excluded_roles as $role) {
            if (in_array($role, (array) $current_user->roles)) return;
        }

        if ($should_redirect && isset($_COOKIE[$cookie_name]) && $_COOKIE[$cookie_name] === $cookie_value) {
            $count = (int) get_option('smart_redirect_count', 0) + 1;
            update_option('smart_redirect_count', $count);

            $threshold = (int) get_option('smart_email_threshold', 100);
            $last_alert = (int) get_option('smart_last_alert_count', 0);
            if ($count >= $threshold && $count > $last_alert) {
                wp_mail(get_option('admin_email'), 'Smart Redirect Alert', "Redirect count reached $count.");
                update_option('smart_last_alert_count', $count);
            }

            $logs = get_option('smart_redirect_logs', []);
            $logs[] = current_time('mysql') . " | IP: " . $_SERVER['REMOTE_ADDR'];
            if (count($logs) > 1000) array_shift($logs);
            update_option('smart_redirect_logs', $logs);

            if ($enable_ga === 'yes') {
                echo "<script>
                (function(){
                    if (typeof gtag === 'function') {
                        gtag('event', 'redirect', {
                            event_category: 'smart_redirect',
                            event_label: 'cookie matched',
                            value: 1
                        });
                    }
                    window.location.href = '" . esc_js($redirect_url) . "';
                })();
                </script>";

                if (!function_exists('wp_remote_post')) return;
                $client_id = isset($_COOKIE['_ga']) ? explode('.', $_COOKIE['_ga'])[2] . '.' . explode('.', $_COOKIE['_ga'])[3] : uniqid();
                if (!empty($gtag_id) && !empty($api_secret)) {
                    $endpoint = "https://www.google-analytics.com/mp/collect?measurement_id={$gtag_id}&api_secret={$api_secret}";
                    $payload = json_encode([
                        "client_id" => $client_id,
                        "events" => [[
                            "name" => "redirect",
                            "params" => [
                                "event_category" => "smart_redirect",
                                "event_label" => "server-fallback",
                                "value" => 1
                            ]
                        ]]
                    ]);
                    wp_remote_post($endpoint, [
                        'body' => $payload,
                        'headers' => ['Content-Type' => 'application/json']
                    ]);
                }
            } else {
                wp_redirect($redirect_url);
            }
            exit;
        }
    }
}

function smart_redirect_admin_menu() {
    add_options_page('Smart Redirect Settings', 'Smart Redirect', 'manage_options', 'smart_redirect', 'smart_redirect_settings_page');
}

function smart_redirect_settings_page() {
    ?>
    <div class="wrap">
        <h1>Smart Redirect Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('smart_redirect_settings');
            do_settings_sections('smart_redirect');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function smart_redirect_settings_init() {
    register_setting('smart_redirect_settings', 'smart_cookie_name');
    register_setting('smart_redirect_settings', 'smart_cookie_value');
    register_setting('smart_redirect_settings', 'smart_redirect_url');
    register_setting('smart_redirect_settings', 'smart_email_threshold');
    register_setting('smart_redirect_settings', 'smart_excluded_roles');
    register_setting('smart_redirect_settings', 'smart_enable_ga');
    register_setting('smart_redirect_settings', 'smart_gtag_id');
    register_setting('smart_redirect_settings', 'smart_ga4_api_secret');
    register_setting('smart_redirect_settings', 'smart_applicable_urls');
    register_setting('smart_redirect_settings', 'smart_invert_url_logic');
    register_setting('smart_redirect_settings', 'smart_redirect_count');
    register_setting('smart_redirect_settings', 'smart_last_alert_count');
    register_setting('smart_redirect_settings', 'smart_redirect_logs');
    register_setting('smart_redirect_settings', 'smart_delete_data_on_uninstall');

    add_settings_section('smart_redirect_section', 'Redirect Settings', null, 'smart_redirect');

    add_settings_field('smart_cookie_name', 'Cookie Name', 'smart_cookie_name_render', 'smart_redirect', 'smart_redirect_section');
    add_settings_field('smart_cookie_value', 'Cookie Value', 'smart_cookie_value_render', 'smart_redirect', 'smart_redirect_section');
    add_settings_field('smart_redirect_url', 'Redirect URL', 'smart_redirect_url_render', 'smart_redirect', 'smart_redirect_section');
    add_settings_field('smart_email_threshold', 'Email Alert Threshold', 'smart_email_threshold_render', 'smart_redirect', 'smart_redirect_section');
    add_settings_field('smart_excluded_roles', 'Exclude User Roles', 'smart_excluded_roles_render', 'smart_redirect', 'smart_redirect_section');
    add_settings_field('smart_enable_ga', 'Enable Google Analytics', 'smart_enable_ga_render', 'smart_redirect', 'smart_redirect_section');
    add_settings_field('smart_gtag_id', 'GA4 Measurement ID', 'smart_gtag_id_render', 'smart_redirect', 'smart_redirect_section');
    add_settings_field('smart_ga4_api_secret', 'GA4 API Secret', 'smart_ga4_api_secret_render', 'smart_redirect', 'smart_redirect_section');
    add_settings_field('smart_applicable_urls', 'URLs to Match (one per line)', 'smart_applicable_urls_render', 'smart_redirect', 'smart_redirect_section');
    add_settings_field('smart_invert_url_logic', 'Invert URL Logic', 'smart_invert_url_logic_render', 'smart_redirect', 'smart_redirect_section');
    add_settings_field('smart_redirect_count', 'Redirect Count (read-only)', 'smart_redirect_count_render', 'smart_redirect', 'smart_redirect_section');
    add_settings_field('smart_last_alert_count', 'Last Alert Count (read-only)', 'smart_last_alert_count_render', 'smart_redirect', 'smart_redirect_section');
    add_settings_field('smart_redirect_logs', 'Redirect Logs (read-only)', 'smart_redirect_logs_render', 'smart_redirect', 'smart_redirect_section');
    add_settings_field('smart_delete_data_on_uninstall', 'Delete Data on Uninstall', 'smart_delete_data_on_uninstall_render', 'smart_redirect', 'smart_redirect_section');
}

// Individual field renderers
function smart_cookie_name_render() {
    $value = get_option('smart_cookie_name', 'smart_user_cookie');
    echo '<input type="text" name="smart_cookie_name" value="' . esc_attr($value) . '" />';
}
function smart_cookie_value_render() {
    $value = get_option('smart_cookie_value', 'true');
    echo '<input type="text" name="smart_cookie_value" value="' . esc_attr($value) . '" />';
}
function smart_redirect_url_render() {
    $value = get_option('smart_redirect_url', '');
    echo '<input type="text" name="smart_redirect_url" value="' . esc_attr($value) . '" />';
}
function smart_email_threshold_render() {
    $value = get_option('smart_email_threshold', 100);
    echo '<input type="number" name="smart_email_threshold" value="' . esc_attr($value) . '" />';
}
function smart_excluded_roles_render() {
    global $wp_roles;
    $selected = (array) get_option('smart_excluded_roles', []);
    foreach ($wp_roles->roles as $key => $role) {
        echo '<label><input type="checkbox" name="smart_excluded_roles[]" value="' . esc_attr($key) . '" ' . checked(in_array($key, $selected), true, false) . '> ' . esc_html($role['name']) . '</label><br>';
    }
}
function smart_enable_ga_render() {
    $value = get_option('smart_enable_ga', 'yes');
    echo '<select name="smart_enable_ga">
        <option value="yes" ' . selected($value, 'yes', false) . '>Yes</option>
        <option value="no" ' . selected($value, 'no', false) . '>No</option>
    </select>';
}
function smart_gtag_id_render() {
    $value = get_option('smart_gtag_id', '');
    echo '<input type="text" name="smart_gtag_id" value="' . esc_attr($value) . '" />';
}
function smart_ga4_api_secret_render() {
    $value = get_option('smart_ga4_api_secret', '');
    echo '<input type="text" name="smart_ga4_api_secret" value="' . esc_attr($value) . '" />';
}
function smart_applicable_urls_render() {
    $value = get_option('smart_applicable_urls', home_url('/'));
    echo '<textarea name="smart_applicable_urls" rows="4" style="width:100%;">' . esc_textarea($value) . '</textarea>';
}
function smart_invert_url_logic_render() {
    $value = get_option('smart_invert_url_logic', 'no');
    echo '<select name="smart_invert_url_logic">';
    echo '<option value="no" ' . selected($value, 'no', false) . '>No</option>';
    echo '<option value="yes" ' . selected($value, 'yes', false) . '>Yes</option>';
    echo '</select>';
}
function smart_redirect_count_render() {
    echo '<input type="text" readonly value="' . esc_attr(get_option('smart_redirect_count', 0)) . '" />';
}
function smart_last_alert_count_render() {
    echo '<input type="text" readonly value="' . esc_attr(get_option('smart_last_alert_count', 0)) . '" />';
}
function smart_redirect_logs_render() {
    $logs = (array) get_option('smart_redirect_logs', []);
    echo '<textarea readonly rows="4" style="width:100%;">' . esc_textarea(implode("\n", array_slice($logs, -5))) . '</textarea>';
}
function smart_delete_data_on_uninstall_render() {
    $value = get_option('smart_delete_data_on_uninstall', 'no');
    echo '<select name="smart_delete_data_on_uninstall">';
    echo '<option value="no" ' . selected($value, 'no', false) . '>No</option>';
    echo '<option value="yes" ' . selected($value, 'yes', false) . '>Yes</option>';
    echo '</select>';
}
?>
