<?php defined('ABSPATH') || exit; $status = isset($_GET['enquiry']) && is_string($_GET['enquiry']) ? sanitize_key($_GET['enquiry']) : ''; ?>
<section class="container section contact-grid">
<div>
<?php if ($status): $messages = ['saved' => 'Your demo enquiry has been saved. You can view it in WordPress → Demo enquiries. No email was sent.', 'invalid' => 'Please complete all required fields, use a valid email address and keep your message under 3,000 characters. Tick the demo data consent box.', 'expired' => 'This form has expired. Please try again using the refreshed form below.', 'limited' => 'Please wait a minute before sending another demo enquiry.', 'failed' => 'We could not save your enquiry. Please try again.']; if (isset($messages[$status])): ?>
<div id="form-result" class="notice <?php echo $status === 'saved' ? '' : 'error'; ?>" tabindex="-1" role="status"><?php echo esc_html($messages[$status]); ?></div>
<?php endif; endif; ?>
<?php if (function_exists('av_handle_enquiry')): ?>
<form class="contact-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <h2><?php av_text('form_title'); ?></h2><p><?php av_text('form_intro'); ?></p>
    <input type="hidden" name="action" value="av_enquiry"><?php wp_nonce_field('av_enquiry', 'av_nonce'); ?>
    <div class="trap" aria-hidden="true"><label>Leave this field empty<input name="website_url" type="text" tabindex="-1" autocomplete="off"></label></div>
    <div class="form-row"><label>Full name <span aria-hidden="true">*</span><input name="full_name" autocomplete="name" required maxlength="100" placeholder="Your name"></label><label>Work email <span aria-hidden="true">*</span><input type="email" name="email" autocomplete="email" required maxlength="190" placeholder="you@company.com"></label></div>
    <div class="form-row"><label>Organization<input name="organization" autocomplete="organization" maxlength="150" placeholder="Company or institution"></label><label>Enquiry type <span aria-hidden="true">*</span><select name="topic" required><option value="">Please select</option><option value="Research collaboration">Research collaboration</option><option value="Corporate enquiry">Corporate enquiry</option><option value="General question">General question</option></select></label></div>
    <label>Your message <span aria-hidden="true">*</span><textarea name="message" required minlength="10" maxlength="3000" rows="5" placeholder="What would you like to explore together?"></textarea></label>
    <label class="consent"><input type="checkbox" name="consent" value="1" required><span>I understand this is a demo. I agree to store this sample enquiry in the local WordPress database. I will not include health information or sensitive personal data.</span></label>
    <button class="button" type="submit">Send demo enquiry <?php av_arrow(); ?></button>
    <p class="form-hint">* Required fields · Demo only: saved locally, no email sent.<br>Use sample details, such as Alex Morgan and alex@example.com.</p>
</form>
<?php else: ?><p class="notice">The enquiry demo needs the included <code>aurevia-demo.php</code> file in <code>wp-content/mu-plugins/</code>.</p><?php endif; ?>
</div>
<aside class="contact-aside"><span aria-hidden="true">✳</span><h2><?php av_text('aside_title'); ?></h2><p><?php av_text('aside_text'); ?></p><div class="contact-detail"><small><?php av_text('email_label'); ?></small><strong><?php av_text('email_address'); ?></strong><p class="form-hint">Illustrative address — no mailbox is connected.</p></div><div class="contact-detail"><small>A NOTE ON THIS CONCEPT</small><p>Aurevia is a fictional life-sciences company created to demonstrate a thoughtful, responsive corporate website.</p></div></aside>
</section>
