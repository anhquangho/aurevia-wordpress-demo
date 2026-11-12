<?php
defined('ABSPATH') || exit;
get_header(); ?>
<main id="main" class="container section"><p class="eyebrow">AUREVIA LIFE SCIENCES</p><h1><?php echo is_404() ? 'This page took a different path.' : esc_html(get_bloginfo('name')); ?></h1><p><?php echo is_404() ? 'Let’s get you back to the science.' : 'Activate the Aurevia theme, then visit Tools → Aurevia demo to create the four sample pages.'; ?></p><a class="button" href="<?php echo esc_url(home_url('/')); ?>">Back to Home <?php av_arrow(); ?></a></main>
<?php get_footer(); ?>
