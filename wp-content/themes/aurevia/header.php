<?php defined('ABSPATH') || exit; ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class(); ?>><?php wp_body_open(); ?>
<a class="skip" href="#main">Skip to content</a>
<div class="announcement"><span>A little curiosity. A world of possibility.</span><a href="<?php echo esc_url(av_url('about')); ?>">Meet Aurevia <span aria-hidden="true">↗</span></a></div>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?php echo esc_url(av_url('home')); ?>" aria-label="Aurevia Life Sciences — Home"><span class="brand-icon" aria-hidden="true">✳</span><span>aurevia<span class="brand-sub">LIFE SCIENCES</span></span></a>
        <button class="menu-toggle" aria-expanded="false" aria-controls="primary-nav"><span>Menu</span><span class="menu-bars" aria-hidden="true">☰</span></button>
        <nav id="primary-nav" aria-label="Primary navigation">
            <?php foreach (['home' => 'Home', 'about' => 'About us', 'research' => 'Our research', 'contact' => 'Contact'] as $key => $label): ?>
            <a class="<?php echo $key === 'contact' ? 'nav-cta' : ''; ?>" href="<?php echo esc_url(av_url($key)); ?>" <?php if (av_type() === $key) { echo 'aria-current="page"'; } ?>><?php echo esc_html($label); ?><?php if ($key === 'contact') { av_arrow(); } ?></a>
            <?php endforeach; ?>
        </nav>
    </div>
</header>
