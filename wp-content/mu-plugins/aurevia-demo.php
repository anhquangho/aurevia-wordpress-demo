<?php
/**
 * Plugin Name: Aurevia Demo Enquiries
 * Description: Local demonstration inbox. Does not send email or call external services.
 * Version: 1.0.0
 * License: GPL-2.0-or-later
 */
defined('ABSPATH') || exit;

add_action('init', function () {
    register_post_type('av_enquiry', [
        'labels' => ['name' => 'Demo enquiries', 'singular_name' => 'Demo enquiry', 'all_items' => 'All enquiries'],
        'public' => false, 'publicly_queryable' => false, 'show_ui' => true, 'show_in_rest' => false,
        'exclude_from_search' => true, 'supports' => ['title'], 'menu_icon' => 'dashicons-email-alt',
        'capability_type' => 'post', 'map_meta_cap' => true,
        'capabilities' => ['create_posts' => 'do_not_allow', 'edit_posts' => 'manage_options', 'edit_others_posts' => 'manage_options', 'delete_posts' => 'manage_options', 'delete_others_posts' => 'manage_options', 'read_private_posts' => 'manage_options', 'edit_published_posts' => 'manage_options', 'delete_published_posts' => 'manage_options', 'publish_posts' => 'manage_options'],
    ]);
});
add_action('add_meta_boxes_av_enquiry', function () {
    add_meta_box('av-enquiry-details', 'Demo enquiry details', function ($post) {
        if (!current_user_can('manage_options')) { return; }
        echo '<p><strong>Local demo record. No email was sent.</strong></p><table class="widefat striped"><tbody>';
        foreach (['full_name'=>'Name','email'=>'Email','organization'=>'Organization','topic'=>'Topic','message'=>'Message','consent'=>'Demo consent'] as $key=>$label) {
            echo '<tr><th style="width:140px">' . esc_html($label) . '</th><td style="white-space:pre-wrap">' . esc_html(get_post_meta($post->ID, '_av_' . $key, true)) . '</td></tr>';
        }
        echo '</tbody></table><p>Use Move to Trash, then Empty Trash to permanently remove sample records.</p>';
    });
});
add_filter('manage_av_enquiry_posts_columns', function ($columns) {
    return ['cb'=>$columns['cb'], 'title'=>'Enquiry', 'av_topic'=>'Topic', 'av_email'=>'Email', 'date'=>'Received'];
});
add_action('manage_av_enquiry_posts_custom_column', function ($column, $id) {
    if (in_array($column, ['av_topic','av_email'], true)) { echo esc_html(get_post_meta($id, '_' . $column, true)); }
}, 10, 2);

function av_handle_enquiry(): void {
    $ids = get_option('av_page_ids', []);
    $url = !empty($ids['contact']) ? get_permalink((int)$ids['contact']) : home_url('/');
    $redirect = static function (string $status) use ($url): void {
        wp_safe_redirect(add_query_arg('enquiry', $status, $url) . '#form-result', 303);
        exit;
    };
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $redirect('invalid'); }
    $input = static function (string $key): string {
        return isset($_POST[$key]) && is_string($_POST[$key]) ? wp_unslash($_POST[$key]) : '';
    };
    if (!wp_verify_nonce($input('av_nonce'), 'av_enquiry')) { $redirect('expired'); }
    if ($input('website_url') !== '') { $redirect('invalid'); }
    $data = [
        'full_name'=>sanitize_text_field($input('full_name')), 'email'=>sanitize_email($input('email')),
        'organization'=>sanitize_text_field($input('organization')), 'topic'=>sanitize_text_field($input('topic')),
        'message'=>sanitize_textarea_field($input('message')), 'consent'=>$input('consent') === '1' ? 'Agreed to local demo storage' : '',
    ];
    if (!$data['full_name'] || strlen($data['full_name']) > 400 || !is_email($input('email')) || strlen($data['email']) > 190 || strlen($data['organization']) > 600 || !in_array($data['topic'], ['Research collaboration','Corporate enquiry','General question'], true) || mb_strlen($data['message']) < 10 || mb_strlen($data['message']) > 3000 || !$data['consent']) { $redirect('invalid'); }
    // Hash the address; never retain the original IP. No cookies or third-party services.
    $key = 'av_rate_' . hash_hmac('sha256', $_SERVER['REMOTE_ADDR'] ?? 'local', wp_salt());
    if (get_transient($key)) { $redirect('limited'); }
    $id = wp_insert_post(['post_type'=>'av_enquiry', 'post_status'=>'private', 'post_title'=>$data['full_name'] . ' — ' . $data['topic']], true);
    if (is_wp_error($id)) { $redirect('failed'); }
    foreach ($data as $field=>$value) { update_post_meta($id, '_av_' . $field, $value); }
    set_transient($key, 1, MINUTE_IN_SECONDS);
    $redirect('saved');
}
add_action('admin_post_av_enquiry', 'av_handle_enquiry');
add_action('admin_post_nopriv_av_enquiry', 'av_handle_enquiry');
