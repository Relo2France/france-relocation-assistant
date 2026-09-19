    </main><!-- #primary -->

    <?php
    // Mirrors the public site's SiteFooter. The footer carries the site's
    // claim about itself, so it says what the claim rests on: official
    // sources, re-checked weekly. The guide list is the first five guides in
    // site/src/content/guides.ts, by slug.
    $r2f_footer_guides = array(
        'long-stay-visa-overview'   => __('Long-Stay Visa Overview', 'relo2france'),
        'visitor-visa-requirements' => __('The Long-Stay Visitor Visa', 'relo2france'),
        'buying-property-france'    => __('Buying Property in France', 'relo2france'),
        'role-of-notaire'           => __('The Role of the Notaire', 'relo2france'),
        'french-healthcare-overview' => __('French Healthcare Overview', 'relo2france'),
    );
    ?>
    <footer id="colophon" class="site-footer">
        <div class="footer-inner">
            <div class="footer-content">
                <div class="footer-section footer-brand">
                    <span class="wordmark">Relo<span class="wordmark-2">2</span>France</span>
                    <p class="footer-blurb"><?php esc_html_e('Every requirement for an American moving to France, in the order you need it.', 'relo2france'); ?></p>
                    <p class="footer-checked"><?php esc_html_e('Checked against official French sources weekly', 'relo2france'); ?></p>
                </div>

                <div class="footer-section">
                    <h2><?php esc_html_e('Guides', 'relo2france'); ?></h2>
                    <ul>
                        <?php foreach ($r2f_footer_guides as $r2f_slug => $r2f_title) : ?>
                            <li><a href="<?php echo esc_url(home_url('/guides/' . $r2f_slug . '/')); ?>"><?php echo esc_html($r2f_title); ?></a></li>
                        <?php endforeach; ?>
                        <li><a href="<?php echo esc_url(home_url('/guides/')); ?>"><?php esc_html_e('All guides', 'relo2france'); ?></a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h2><?php esc_html_e('Membership', 'relo2france'); ?></h2>
                    <ul>
                        <li><a href="<?php echo esc_url(home_url('/how-it-works/')); ?>"><?php esc_html_e('How it works', 'relo2france'); ?></a></li>
                        <li><a href="<?php echo esc_url(home_url('/pricing/')); ?>"><?php esc_html_e('Pricing — $99 for life', 'relo2france'); ?></a></li>
                        <li><a href="<?php echo esc_url(home_url('/register/lifetime-membership/')); ?>"><?php esc_html_e('Join', 'relo2france'); ?></a></li>
                        <li><a href="<?php echo esc_url(home_url('/portal/')); ?>"><?php esc_html_e('Sign in', 'relo2france'); ?></a></li>
                        <li><a href="<?php echo esc_url(home_url('/account/')); ?>"><?php esc_html_e('Your account', 'relo2france'); ?></a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h2><?php esc_html_e('Relo2France', 'relo2france'); ?></h2>
                    <ul>
                        <li><a href="<?php echo esc_url(home_url('/about/')); ?>"><?php esc_html_e('About', 'relo2france'); ?></a></li>
                        <li><a href="<?php echo esc_url(home_url('/portal/')); ?>"><?php esc_html_e('Member portal', 'relo2france'); ?></a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?php echo esc_html(date('Y')); ?> Relo2France. <?php esc_html_e('Not affiliated with the French government.', 'relo2france'); ?></p>
                <p class="footer-disclaimer"><?php esc_html_e('Information here is drawn from official French government sources and is general, not legal or tax advice. Requirements change and vary by consulate — confirm your own case before you act on it.', 'relo2france'); ?></p>
            </div>
        </div>
    </footer>
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
