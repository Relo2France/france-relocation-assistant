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
    // Show new centered header on front page
    if (is_front_page()) : 
    ?>
    <header id="masthead" class="site-header site-header-centered">
        <div class="header-centered-inner">
            <!-- Logo -->
            <?php if (has_custom_logo()) : ?>
                <div class="header-logo-large">
                    <?php the_custom_logo(); ?>
                </div>
            <?php else : ?>
                <div class="header-logo-large header-logo-placeholder">
                    <span>🇫🇷</span>
                </div>
            <?php endif; ?>
            
            <!-- Site Title -->
            <h1 class="header-site-title">
                <a href="<?php echo esc_url(home_url('/')); ?>">
                    <?php echo esc_html( get_bloginfo( 'name' ) ); ?>
                </a>
            </h1>
            
            <!-- Tagline -->
            <p class="header-tagline">
                <?php echo esc_html(get_theme_mod('header_tagline', __('Your Complete US to France Relocation Resource', 'relo2france'))); ?>
            </p>
            
            <!-- SEO Description -->
            <p class="header-description">
                <?php echo esc_html(get_theme_mod('header_description', __('Moving from America to France? Get AI-powered answers about visa requirements, buying property, enrolling in healthcare, understanding taxes, and settling into French life — all based on official government sources.', 'relo2france'))); ?>
            </p>
        </div>
    </header>
    <?php else : ?>
    <!-- Standard header for other pages -->
    <header id="masthead" class="site-header">
        <div class="header-inner">
            <?php relo2france_site_logo(); ?>
            
            <nav id="site-navigation" class="main-navigation">
                <button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
                    <span class="screen-reader-text"><?php esc_html_e('Menu', 'relo2france'); ?></span>
                    ☰
                </button>
                <?php relo2france_primary_menu(); ?>
            </nav>
        </div>
    </header>
    <?php endif; ?>

    <main id="primary" class="site-content">
