    </main><!-- #primary -->

    <footer id="colophon" class="site-footer">
        <div class="footer-inner">
            <div class="footer-content">
                <div class="footer-section">
                    <h4><?php esc_html_e('About', 'relo2france'); ?></h4>
                    <p>
                        <?php echo wp_kses_post(get_theme_mod('footer_text', __('Information provided for general guidance only. Immigration, tax, and legal matters require professional consultation. Always verify with official French government sources.', 'relo2france'))); ?>
                    </p>
                </div>
                
                <div class="footer-section">
                    <h4><?php esc_html_e('Official Resources', 'relo2france'); ?></h4>
                    <ul>
                        <li><a href="https://france-visas.gouv.fr" target="_blank" rel="noopener">France-Visas.gouv.fr</a></li>
                        <li><a href="https://www.service-public.fr" target="_blank" rel="noopener">Service-Public.fr</a></li>
                        <li><a href="https://www.ameli.fr" target="_blank" rel="noopener">Ameli.fr (Healthcare)</a></li>
                        <li><a href="https://www.impots.gouv.fr" target="_blank" rel="noopener">Impots.gouv.fr (Taxes)</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4><?php esc_html_e('Navigation', 'relo2france'); ?></h4>
                    <?php 
                    if (has_nav_menu('footer')) {
                        relo2france_footer_menu();
                    } else {
                        ?>
                        <ul>
                            <li><a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'relo2france'); ?></a></li>
                            <?php if (relo2france_fra_active()) : ?>
                                <li><a href="<?php echo esc_url(home_url('/')); ?>#fra-app"><?php esc_html_e('Relocation Assistant', 'relo2france'); ?></a></li>
                            <?php endif; ?>
                        </ul>
                        <?php
                    }
                    ?>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="footer-flags">🇺🇸 → 🇫🇷</div>
                <p>
                    &copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?>. 
                    <?php esc_html_e('All rights reserved.', 'relo2france'); ?>
                </p>
                <p>
                    <?php esc_html_e('Built for Americans relocating to France.', 'relo2france'); ?>
                </p>
            </div>
        </div>
    </footer>
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
