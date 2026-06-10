<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

if (get_option('smart_delete_data_on_uninstall') === 'yes') {
    delete_option('smart_cookie_name');
    delete_option('smart_cookie_value');
    delete_option('smart_redirect_url');
    delete_option('smart_email_threshold');
    delete_option('smart_excluded_roles');
    delete_option('smart_redirect_count');
    delete_option('smart_last_alert_count');
    delete_option('smart_enable_ga');
    delete_option('smart_gtag_id');
    delete_option('smart_ga4_api_secret');
    delete_option('smart_applicable_urls');
    delete_option('smart_invert_url_logic');
    delete_option('smart_redirect_logs');
    delete_option('smart_delete_data_on_uninstall');
}
?>
