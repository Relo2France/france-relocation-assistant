<?php
/**
 * SEO Settings Admin Page
 * 
 * @package France_Relocation_Assistant
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap fra-admin-wrap">
    <h1>🔍 SEO Settings</h1>
    
    <div class="fra-admin-content">
        <!-- SEO Status -->
        <div class="fra-admin-card">
            <h2>📊 SEO Status</h2>
            
            <?php
            $yoast_active = defined('WPSEO_VERSION');
            $rankmath_active = class_exists('RankMath');
            $aioseo_active = function_exists('aioseo');
            ?>
            
            <table class="fra-status-table">
                <tr>
                    <td><strong>Schema Markup</strong></td>
                    <td><span class="fra-status-ok">✓ Active</span></td>
                    <td>JSON-LD structured data automatically added to pages with the assistant</td>
                </tr>
                <tr>
                    <td><strong>FAQ Schema</strong></td>
                    <td><span class="fra-status-ok">✓ Active</span></td>
                    <td>8 FAQ entries for rich snippet eligibility</td>
                </tr>
                <tr>
                    <td><strong>HowTo Schema</strong></td>
                    <td><span class="fra-status-ok">✓ Active</span></td>
                    <td>Property purchase guide with step-by-step schema</td>
                </tr>
                <tr>
                    <td><strong>Meta Tags</strong></td>
                    <td>
                        <?php if ($yoast_active || $rankmath_active || $aioseo_active) : ?>
                            <span class="fra-status-info">ℹ Deferred to SEO Plugin</span>
                        <?php else : ?>
                            <span class="fra-status-ok">✓ Active</span>
                        <?php endif; ?>
                    </td>
                    <td>Description, keywords, robots directives</td>
                </tr>
                <tr>
                    <td><strong>Open Graph</strong></td>
                    <td>
                        <?php if ($yoast_active || $rankmath_active || $aioseo_active) : ?>
                            <span class="fra-status-info">ℹ Deferred to SEO Plugin</span>
                        <?php else : ?>
                            <span class="fra-status-ok">✓ Active</span>
                        <?php endif; ?>
                    </td>
                    <td>Social sharing optimization</td>
                </tr>
            </table>
            
            <?php if ($yoast_active) : ?>
                <div class="fra-notice fra-notice-info">
                    <strong>Yoast SEO Detected:</strong> Meta tags and Open Graph are handled by Yoast. Schema markup from France Relocation Assistant will supplement Yoast's schema.
                </div>
            <?php elseif ($rankmath_active) : ?>
                <div class="fra-notice fra-notice-info">
                    <strong>Rank Math Detected:</strong> Meta tags and Open Graph are handled by Rank Math. Schema markup from France Relocation Assistant will supplement Rank Math's schema.
                </div>
            <?php elseif ($aioseo_active) : ?>
                <div class="fra-notice fra-notice-info">
                    <strong>All in One SEO Detected:</strong> Meta tags are handled by AIOSEO. Schema markup from France Relocation Assistant will supplement AIOSEO's schema.
                </div>
            <?php else : ?>
                <div class="fra-notice fra-notice-warning">
                    <strong>No SEO Plugin Detected:</strong> France Relocation Assistant is providing basic meta tags. For best results, consider installing <a href="https://wordpress.org/plugins/wordpress-seo/" target="_blank">Yoast SEO</a> or <a href="https://wordpress.org/plugins/seo-by-rank-math/" target="_blank">Rank Math</a>.
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Target Keywords -->
        <div class="fra-admin-card">
            <h2>🎯 Target Keywords</h2>
            <p>These keywords are automatically integrated into schema markup and meta tags. Use them in your page content and blog posts for best SEO results.</p>
            
            <h3>Primary Keywords (High Volume)</h3>
            <div class="fra-keyword-list">
                <?php
                $keywords = FRA_SEO::get_keywords();
                foreach ($keywords['primary'] as $kw) {
                    echo '<span class="fra-keyword fra-keyword-primary">' . esc_html($kw) . '</span>';
                }
                ?>
            </div>
            
            <h3>Long-Tail Keywords (Lower Competition, Higher Intent)</h3>
            <div class="fra-keyword-list">
                <?php
                foreach ($keywords['long_tail'] as $kw) {
                    echo '<span class="fra-keyword fra-keyword-longtail">' . esc_html($kw) . '</span>';
                }
                ?>
            </div>
        </div>
        
        <!-- Content Strategy -->
        <div class="fra-admin-card">
            <h2>📝 Content Strategy Suggestions</h2>
            <p>To maximize SEO impact, consider creating blog posts around these topics:</p>
            
            <?php $suggestions = FRA_SEO::get_content_suggestions(); ?>
            
            <h3>Blog Post Ideas</h3>
            <ol class="fra-content-ideas">
                <?php foreach ($suggestions['blog_post_ideas'] as $idea) : ?>
                    <li><?php echo esc_html($idea); ?></li>
                <?php endforeach; ?>
            </ol>
            
            <h3>Recommended Category Structure</h3>
            <p>Create these categories for your blog to build topical authority:</p>
            <ul class="fra-category-list">
                <?php foreach ($suggestions['category_structure'] as $slug => $name) : ?>
                    <li><code><?php echo esc_html($slug); ?></code> → <?php echo esc_html($name); ?></li>
                <?php endforeach; ?>
            </ul>
            
            <h3>Internal Linking Strategy</h3>
            <ul>
                <?php foreach ($suggestions['internal_linking'] as $tip) : ?>
                    <li><?php echo esc_html($tip); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <!-- Schema Preview -->
        <div class="fra-admin-card">
            <h2>🔬 Schema Markup Preview</h2>
            <p>This is the structured data automatically added to pages with the France Relocation Assistant shortcode:</p>
            
            <div class="fra-schema-preview">
                <h4>FAQ Schema (8 Questions)</h4>
                <p>Enables FAQ rich snippets in Google search results:</p>
                <ul>
                    <li>What visa do I need to move to France from the USA?</li>
                    <li>Can Americans buy property in France?</li>
                    <li>How does French healthcare work for American expats?</li>
                    <li>What is the 183-day rule for French tax residency?</li>
                    <li>Can I exchange my US driver's license in France?</li>
                    <li>How much money do I need to move to France?</li>
                    <li>Can I work in France on a visitor visa?</li>
                    <li>How do I open a French bank account as an American?</li>
                </ul>
                
                <h4>HowTo Schema</h4>
                <p>Enables how-to rich snippets for property purchase process:</p>
                <ul>
                    <li>Step 1: Find a Property</li>
                    <li>Step 2: Make an Offer</li>
                    <li>Step 3: Sign the Compromis de Vente</li>
                    <li>Step 4: Arrange Financing</li>
                    <li>Step 5: Complete Due Diligence</li>
                    <li>Step 6: Sign the Acte de Vente</li>
                </ul>
            </div>
            
            <p>
                <a href="https://search.google.com/test/rich-results" target="_blank" class="button">
                    Test Rich Results →
                </a>
                <a href="https://validator.schema.org/" target="_blank" class="button">
                    Validate Schema →
                </a>
            </p>
        </div>
        
        <!-- Technical SEO Checklist -->
        <div class="fra-admin-card">
            <h2>✅ Technical SEO Checklist</h2>
            <p>Ensure these items are configured for optimal search performance:</p>
            
            <table class="fra-checklist-table">
                <tr>
                    <td>☐</td>
                    <td><strong>XML Sitemap</strong></td>
                    <td>Submit sitemap to Google Search Console. Most SEO plugins generate this automatically.</td>
                </tr>
                <tr>
                    <td>☐</td>
                    <td><strong>Google Search Console</strong></td>
                    <td>Verify your site and monitor indexing status.</td>
                </tr>
                <tr>
                    <td>☐</td>
                    <td><strong>Page Speed</strong></td>
                    <td>Test with <a href="https://pagespeed.web.dev/" target="_blank">PageSpeed Insights</a>. Aim for 90+ on mobile.</td>
                </tr>
                <tr>
                    <td>☐</td>
                    <td><strong>Mobile-Friendly</strong></td>
                    <td>Test with <a href="https://search.google.com/test/mobile-friendly" target="_blank">Mobile-Friendly Test</a>.</td>
                </tr>
                <tr>
                    <td>☐</td>
                    <td><strong>SSL Certificate</strong></td>
                    <td>Ensure your site uses HTTPS.</td>
                </tr>
                <tr>
                    <td>☐</td>
                    <td><strong>Permalink Structure</strong></td>
                    <td>Use "Post name" structure in Settings → Permalinks.</td>
                </tr>
            </table>
        </div>
    </div>
</div>

<style>
.fra-admin-wrap {
    max-width: 1200px;
}
.fra-admin-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}
.fra-admin-card h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}
.fra-admin-card h3 {
    margin-top: 20px;
    margin-bottom: 10px;
    font-size: 14px;
}
.fra-status-table {
    width: 100%;
    border-collapse: collapse;
}
.fra-status-table td {
    padding: 10px;
    border-bottom: 1px solid #eee;
}
.fra-status-table td:first-child {
    width: 150px;
}
.fra-status-table td:nth-child(2) {
    width: 180px;
}
.fra-status-ok {
    color: #00a32a;
    font-weight: 500;
}
.fra-status-info {
    color: #0073aa;
    font-weight: 500;
}
.fra-notice {
    padding: 12px 15px;
    border-radius: 4px;
    margin-top: 15px;
}
.fra-notice-info {
    background: #e7f5ff;
    border-left: 4px solid #0073aa;
}
.fra-notice-warning {
    background: #fff8e5;
    border-left: 4px solid #dba617;
}
.fra-keyword-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 20px;
}
.fra-keyword {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 13px;
}
.fra-keyword-primary {
    background: #1e3a5f;
    color: #fff;
}
.fra-keyword-longtail {
    background: #f0f0f1;
    color: #1e1e1e;
    border: 1px solid #c3c4c7;
}
.fra-content-ideas {
    background: #f6f7f7;
    padding: 15px 15px 15px 35px;
    border-radius: 4px;
}
.fra-content-ideas li {
    margin: 8px 0;
}
.fra-category-list {
    background: #f6f7f7;
    padding: 15px 15px 15px 35px;
    border-radius: 4px;
}
.fra-category-list code {
    background: #fff;
    padding: 2px 6px;
    border-radius: 3px;
}
.fra-schema-preview {
    background: #f9f9f9;
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 15px;
}
.fra-schema-preview h4 {
    margin-top: 0;
    margin-bottom: 8px;
}
.fra-schema-preview ul {
    margin: 0 0 15px 20px;
    font-size: 13px;
}
.fra-checklist-table {
    width: 100%;
    border-collapse: collapse;
}
.fra-checklist-table td {
    padding: 10px;
    border-bottom: 1px solid #eee;
    vertical-align: top;
}
.fra-checklist-table td:first-child {
    width: 30px;
    font-size: 18px;
}
.fra-checklist-table td:nth-child(2) {
    width: 180px;
}
</style>
