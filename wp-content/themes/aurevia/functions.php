<?php
defined('ABSPATH') || exit;
require_once __DIR__ . '/inc/content.php';

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('post-thumbnails');
    register_nav_menus(['primary' => 'Primary navigation']);
});
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('aurevia', get_theme_file_uri('/assets/site.css'), [], '1.1.0');
    wp_enqueue_script('aurevia', get_theme_file_uri('/assets/site.js'), [], '1.1.0', true);
});
add_action('wp_head', function () {
    $id = get_queried_object_id();
    $description = wp_strip_all_tags(get_post_field('post_content', $id));
    if (!$description) { $description = 'Aurevia Life Sciences — a fictional pharmaceutical website concept by Quang Ho.'; }
    echo '<meta name="description" content="' . esc_attr($description) . '">';
    echo '<link rel="icon" type="image/svg+xml" href="' . esc_url(get_theme_file_uri('/assets/favicon.svg')) . '">';
});

function av_type(?int $id = null): string {
    $type = (string) get_post_meta($id ?? get_queried_object_id(), '_av_type', true);
    return array_key_exists($type, av_defaults()) ? $type : '';
}
function av_data(): array {
    $base = av_defaults()[av_type()] ?? [];
    $saved = get_post_meta(get_queried_object_id(), '_av_content', true);
    return array_merge($base, is_array($saved) ? $saved : []);
}
function av_text(string $key): void {
    echo nl2br(esc_html(str_replace('\\n', "\n", av_data()[$key] ?? '')));
}
/** Presentation-only highlights: stored page copy remains plain, editable text. */
function av_title(): void {
    $text = str_replace('\\n', "\n", av_data()['hero_title'] ?? '');
    $colors = [
        'home' => ['tomorrow'=>'yellow', 'discovery'=>'purple'],
        'about' => ['purpose'=>'yellow', 'science'=>'green'],
        'research' => ['understanding'=>'yellow', 'imagining'=>'purple'],
        'contact' => ['conversation'=>'purple', 'possibilities'=>'green'],
    ][av_type()] ?? [];
    if (!$colors) { echo nl2br(esc_html($text)); return; }
    $pattern = '/\b(' . implode('|', array_map(static fn($word) => preg_quote($word, '/'), array_keys($colors))) . ')\b/i';
    foreach (preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE) as $part) {
        $tone = $colors[strtolower($part)] ?? '';
        echo $tone ? '<span class="word-highlight highlight-' . esc_attr($tone) . '">' . esc_html($part) . '</span>' : nl2br(esc_html($part));
    }
}
function av_url(string $type): string {
    $ids = get_option('av_page_ids', []);
    return !empty($ids[$type]) ? get_permalink((int) $ids[$type]) : home_url('/');
}
function av_arrow(): void { echo '<span aria-hidden="true">↗</span>'; }
function av_asset(string $name): string { return get_theme_file_uri('/assets/' . $name); }

// WordPress includes this editor: no Classic Editor plugin or page builder is needed.
add_filter('use_block_editor_for_post', function ($enabled, $post) {
    return av_type($post->ID) ? false : $enabled;
}, 10, 2);

/** Only called by the explicit installer or an administrator. Existing pages are preserved. */
function av_seed(): array {
    $ids = get_option('av_page_ids', []);
    foreach (av_defaults() as $type => $data) {
        $existing = !empty($ids[$type]) ? get_post((int) $ids[$type]) : get_page_by_path($type);
        if ($existing && $existing->post_status === 'trash') { $existing = null; }
        if ($existing) {
            if (get_post_meta($existing->ID, '_av_type', true) !== $type) {
                throw new RuntimeException('An unrelated page already uses the slug: ' . $type);
            }
            $ids[$type] = $existing->ID;
            continue;
        }
        $id = wp_insert_post([
            'post_title' => ucfirst($type), 'post_name' => $type, 'post_type' => 'page',
            'post_status' => 'publish', 'comment_status' => 'closed',
            'post_content' => '<!-- wp:paragraph --><p>' . esc_html($data['hero_intro']) . '</p><!-- /wp:paragraph -->',
        ], true);
        if (is_wp_error($id)) { throw new RuntimeException($id->get_error_message()); }
        update_post_meta($id, '_av_type', $type);
        update_post_meta($id, '_av_content', wp_slash($data));
        $ids[$type] = $id;
        update_option('av_page_ids', $ids);
    }
    update_option('show_on_front', 'page');
    update_option('page_on_front', $ids['home']);
    // Plain permalinks are intentional: work without Apache rewrite configuration.
    update_option('permalink_structure', '');
    return $ids;
}

add_action('add_meta_boxes_page', function ($post) {
    if (!av_type($post->ID)) { return; }
    add_meta_box('av-content', 'Aurevia — Page sections', function ($post) {
        wp_nonce_field('av_save_content', 'av_content_nonce');
        $data = array_merge(av_defaults()[av_type($post->ID)], (array) get_post_meta($post->ID, '_av_content', true));
        echo '<p>Edit the hero introduction in the main editor above. Edit the other text here. Use a new line in headings for a deliberate line break. Blank fields stay blank.</p><div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px">';
        foreach ($data as $key => $value) {
            if ($key === 'hero_intro') { continue; }
            echo '<label style="display:block"><strong>' . esc_html(ucwords(str_replace('_', ' ', $key))) . '</strong><textarea style="display:block;width:100%;margin-top:6px" rows="3" name="av_content[' . esc_attr($key) . ']">' . esc_textarea(str_replace('\\n', "\n", $value)) . '</textarea></label>';
        }
        echo '</div>';
    }, 'page', 'normal', 'high');
});
add_action('save_post_page', function ($id) {
    if (wp_is_post_revision($id) || wp_is_post_autosave($id) || !current_user_can('edit_post', $id)) { return; }
    if (empty($_POST['av_content_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['av_content_nonce'])), 'av_save_content')) { return; }
    $type = av_type($id);
    if (!$type || !isset($_POST['av_content']) || !is_array($_POST['av_content'])) { return; }
    $clean = [];
    foreach (av_defaults()[$type] as $key => $default) {
        if (isset($_POST['av_content'][$key]) && is_string($_POST['av_content'][$key])) {
            $clean[$key] = sanitize_textarea_field(wp_unslash($_POST['av_content'][$key]));
        }
    }
    update_post_meta($id, '_av_content', $clean);
});

add_action('admin_menu', function () {
    add_management_page('Aurevia demo', 'Aurevia demo', 'manage_options', 'aurevia', function () {
        if (!current_user_can('manage_options')) { return; }
        if (isset($_POST['av_seed'])) {
            check_admin_referer('av_seed');
            try { av_seed(); echo '<div class="notice notice-success"><p>Missing demo pages created. Existing content preserved.</p></div>'; }
            catch (Throwable $e) { echo '<div class="notice notice-error"><p>' . esc_html($e->getMessage()) . '</p></div>'; }
        }
        echo '<div class="wrap"><h1>Aurevia demo</h1><p>Fictional portfolio concept by Quang Ho. Edit page copy under Pages → Edit → Aurevia — Page sections. The main editor controls the hero introduction.</p><p>Contact messages appear under Demo enquiries. This demo does not send email.</p><form method="post">';
        wp_nonce_field('av_seed');
        echo '<p>Create missing demo pages without replacing your edited content. Also sets Home as the front page and selects plain permalinks for XAMPP.</p><button class="button button-primary" name="av_seed" value="1">Create missing demo pages</button></form></div>';
    });
});
