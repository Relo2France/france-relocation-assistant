<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site-container">
    <a class="skip-link screen-reader-text" href="#primary">
        <?php esc_html_e('Skip to content', 'relo2france'); ?>
    </a>

    <?php
    // Mirrors the public site's SiteNav. The public pages are served by the
    // Worker on the same origin, so these are plain same-site links. Nothing
    // in the navigation is ever a legal requirement: this is the warm chrome.
    ?>
    <header id="masthead" class="site-header">
        <div class="header-inner">
            <a class="wordmark" href="<?php echo esc_url(home_url('/')); ?>">Relo<span class="wordmark-2">2</span>France</a>

            <nav id="site-navigation" class="main-navigation" aria-label="<?php esc_attr_e('Primary', 'relo2france'); ?>">
                <button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
                    <span class="screen-reader-text"><?php esc_html_e('Menu', 'relo2france'); ?></span>
                    <span aria-hidden="true">&#9776;</span>
                </button>
                <ul id="primary-menu" class="primary-menu">
                    <li><a href="<?php echo esc_url(home_url('/guides/')); ?>"><?php esc_html_e('Guides', 'relo2france'); ?></a></li>
                    <li><a href="<?php echo esc_url(home_url('/how-it-works/')); ?>"><?php esc_html_e('How it works', 'relo2france'); ?></a></li>
                    <li><a href="<?php echo esc_url(home_url('/pricing/')); ?>"><?php esc_html_e('Pricing', 'relo2france'); ?></a></li>
                </ul>
            </nav>

            <div class="header-actions">
                <?php if (is_user_logged_in()) : ?>
                    <a class="btn btn-outline" href="<?php echo esc_url(home_url('/account/')); ?>"><?php esc_html_e('Account', 'relo2france'); ?></a>
                    <a class="btn btn-primary" href="<?php echo esc_url(home_url('/portal/')); ?>"><?php esc_html_e('Member portal', 'relo2france'); ?></a>
                <?php else : ?>
                    <a class="btn btn-outline" href="<?php echo esc_url(home_url('/login/')); ?>"><?php esc_html_e('Sign in', 'relo2france'); ?></a>
                    <a class="btn btn-primary" href="<?php echo esc_url(home_url('/pricing/')); ?>"><?php esc_html_e('Get started', 'relo2france'); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main id="primary" class="site-content">
