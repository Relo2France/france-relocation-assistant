<?php
/**
 * Knowledge Base Expansion Page
 * 
 * @package France_Relocation_Assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

$api_key = get_option('fra_api_key', '');
$knowledge_base = France_Relocation_Assistant::get_instance()->get_knowledge_base();

$categories = array(
    'visas' => 'Visas & Immigration',
    'property' => 'Property Purchase',
    'healthcare' => 'Healthcare',
    'taxes' => 'Taxes',
    'driving' => 'Driving & Vehicles',
    'shipping' => 'Shipping & Pets',
    'banking' => 'Banking',
    'settling' => 'Settling In'
);

// Handle topic generation
$generated_topic = null;
$generation_error = null;

if (isset($_POST['fra_generate_topic']) && check_admin_referer('fra_kb_nonce')) {
    if (empty($api_key)) {
        $generation_error = 'Please configure your API key in API Settings first.';
    } else {
        $topic_title = sanitize_text_field(wp_unslash($_POST['topic_title'] ?? ''));
        $category = sanitize_text_field(wp_unslash($_POST['category'] ?? ''));
        $additional_context = sanitize_textarea_field(wp_unslash($_POST['additional_context'] ?? ''));
        
        if (empty($topic_title) || empty($category)) {
            $generation_error = 'Please provide both a topic title and category.';
        } else {
            $prompt = "Generate a comprehensive knowledge base entry for a US to France relocation guide about: \"$topic_title\"

Category: {$categories[$category]}

" . (!empty($additional_context) ? "Additional context: $additional_context\n\n" : "") . "

Please respond with a JSON object (no markdown, just raw JSON) containing:
{
    \"title\": \"The topic title\",
    \"keywords\": [\"array\", \"of\", \"search\", \"keywords\", \"at least 8\"],
    \"content\": \"The full content with **bold headers** and • bullet points. Include specific requirements, costs (in euros), timelines, and step-by-step processes where applicable. Content should be 300-500 words.\",
    \"sources\": [
        {\"name\": \"Official Source Name\", \"url\": \"https://official-source.gouv.fr/\"},
        {\"name\": \"Another Source\", \"url\": \"https://another-source.fr/\"}
    ],
    \"lastVerified\": \"" . date('F Y') . "\"
}

Focus on practical, actionable information for Americans relocating to France. Include official French government sources where possible.";

            $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
                'timeout' => 90,
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'x-api-key' => $api_key,
                    'anthropic-version' => '2023-06-01'
                ),
                'body' => json_encode(array(
                    'model' => 'claude-sonnet-4-20250514',
                    'max_tokens' => 2048,
                    'messages' => array(
                        array('role' => 'user', 'content' => $prompt)
                    )
                ))
            ));
            
            if (is_wp_error($response)) {
                $generation_error = 'API request failed: ' . $response->get_error_message();
            } else {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                
                if (isset($body['error'])) {
                    $generation_error = 'API error: ' . ($body['error']['message'] ?? 'Unknown error');
                } elseif (isset($body['content'][0]['text'])) {
                    $json_text = $body['content'][0]['text'];
                    // Clean up any markdown formatting
                    $json_text = preg_replace('/^```json\s*/', '', $json_text);
                    $json_text = preg_replace('/\s*```$/', '', $json_text);
                    $generated_topic = json_decode($json_text, true);
                    
                    if (!$generated_topic) {
                        $generation_error = 'Failed to parse generated content. Raw response: ' . substr($body['content'][0]['text'], 0, 200);
                    }
                }
            }
        }
    }
}

// Handle saving topic to knowledge base
if (isset($_POST['fra_save_topic']) && check_admin_referer('fra_kb_nonce')) {
    $category = sanitize_text_field(wp_unslash($_POST['save_category'] ?? ''));
    $topic_key = sanitize_key($_POST['topic_key'] ?? '');
    $topic_data = array(
        'title' => sanitize_text_field(wp_unslash($_POST['save_title'] ?? '')),
        'keywords' => array_values(array_filter(array_map('trim', array_map('sanitize_text_field', array_map('wp_unslash', explode(',', $_POST['save_keywords'] ?? '')))))),
        'content' => wp_kses_post(wp_unslash($_POST['save_content'] ?? '')),
        'sources' => json_decode(wp_unslash($_POST['save_sources'] ?? '[]'), true),
        'lastVerified' => sanitize_text_field(wp_unslash($_POST['save_verified'] ?? date('F Y')))
    );
    
    // Ensure sources is always an array
    if (!is_array($topic_data['sources'])) {
        $topic_data['sources'] = array();
    }
    
    if (!empty($category) && !empty($topic_key) && !empty($topic_data['title'])) {
        $knowledge_base[$category][$topic_key] = $topic_data;
        update_option('fra_knowledge_base', $knowledge_base);
        echo '<div class="notice notice-success is-dismissible"><p>Topic "' . esc_html($topic_data['title']) . '" saved successfully to ' . esc_html($categories[$category]) . '!</p></div>';
        $knowledge_base = get_option('fra_knowledge_base'); // Refresh
    }
}

// Handle deleting topic
if (isset($_POST['fra_delete_topic']) && check_admin_referer('fra_kb_nonce')) {
    $del_category = sanitize_text_field(wp_unslash($_POST['del_category'] ?? ''));
    $del_key = sanitize_key($_POST['del_key'] ?? '');
    
    if (!empty($del_category) && !empty($del_key) && isset($knowledge_base[$del_category][$del_key])) {
        $deleted_title = $knowledge_base[$del_category][$del_key]['title'];
        unset($knowledge_base[$del_category][$del_key]);
        update_option('fra_knowledge_base', $knowledge_base);
        echo '<div class="notice notice-warning is-dismissible"><p>Topic "' . esc_html($deleted_title) . '" deleted.</p></div>';
    }
}
?>

<div class="wrap fra-admin-wrap">
    <h1>
        <span class="dashicons dashicons-database-add"></span>
        <?php _e('Expand Knowledge Base', 'france-relocation-assistant'); ?>
    </h1>
    
    <div class="fra-admin-header">
        <p class="fra-description">
            <?php _e('Use Claude AI to generate new knowledge base topics, or manually add and edit existing entries.', 'france-relocation-assistant'); ?>
        </p>
    </div>
    
    <?php if ($generation_error): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($generation_error); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="fra-admin-grid">
        <!-- AI Topic Generator -->
        <div class="fra-card">
            <h2><?php _e('🤖 AI Topic Generator', 'france-relocation-assistant'); ?></h2>
            
            <?php if (empty($api_key)): ?>
                <div class="fra-message fra-message-error">
                    <?php _e('Please configure your Anthropic API key in', 'france-relocation-assistant'); ?> 
                    <a href="<?php echo admin_url('admin.php?page=france-relocation-assistant-settings'); ?>"><?php _e('API Settings', 'france-relocation-assistant'); ?></a>
                </div>
            <?php else: ?>
                <form method="post">
                    <?php wp_nonce_field('fra_kb_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="topic_title"><?php _e('Topic Title', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="topic_title" id="topic_title" class="regular-text" 
                                       placeholder="e.g., French Driver's License Test Process" required>
                                <p class="description"><?php _e('What topic should Claude research and write about?', 'france-relocation-assistant'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="category"><?php _e('Category', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <select name="category" id="category" required>
                                    <?php foreach ($categories as $key => $label): ?>
                                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="additional_context"><?php _e('Additional Context', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <textarea name="additional_context" id="additional_context" rows="3" class="large-text" 
                                          placeholder="Optional: Any specific aspects to cover, recent changes, or focus areas..."></textarea>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="fra_generate_topic" class="button button-primary" value="<?php _e('Generate Topic with AI', 'france-relocation-assistant'); ?>">
                        <span class="description" style="margin-left: 10px;"><?php _e('~$0.02 per generation', 'france-relocation-assistant'); ?></span>
                    </p>
                </form>
            <?php endif; ?>
        </div>
        
        <!-- Current Knowledge Base -->
        <div class="fra-card">
            <h2><?php _e('📚 Current Knowledge Base', 'france-relocation-assistant'); ?></h2>
            
            <?php foreach ($knowledge_base as $cat_key => $topics): ?>
                <div class="fra-kb-category">
                    <h3><?php echo esc_html($categories[$cat_key] ?? ucfirst($cat_key)); ?> 
                        <span class="fra-topic-count">(<?php echo count($topics); ?>)</span>
                    </h3>
                    <ul class="fra-topic-list">
                        <?php foreach ($topics as $topic_key => $topic): ?>
                            <?php 
                            $has_in_practice = strpos($topic['content'] ?? '', '**In Practice**') !== false;
                            ?>
                            <li>
                                <span class="fra-topic-title">
                                    <?php echo esc_html($topic['title']); ?>
                                    <?php if ($has_in_practice): ?>
                                        <span class="fra-has-practice" title="Has In Practice section">✨</span>
                                    <?php endif; ?>
                                </span>
                                <span class="fra-topic-actions">
                                    <button type="button" class="button-link fra-add-in-practice" 
                                            data-category="<?php echo esc_attr($cat_key); ?>"
                                            data-key="<?php echo esc_attr($topic_key); ?>"
                                            data-title="<?php echo esc_attr($topic['title']); ?>"
                                            title="<?php echo $has_in_practice ? 'Regenerate In Practice section' : 'Add In Practice section'; ?>">
                                        <?php echo $has_in_practice ? '🔄' : '✨'; ?> <?php _e('In Practice', 'france-relocation-assistant'); ?>
                                    </button>
                                    <button type="button" class="button-link fra-edit-topic" 
                                            data-category="<?php echo esc_attr($cat_key); ?>"
                                            data-key="<?php echo esc_attr($topic_key); ?>"
                                            data-topic="<?php echo esc_attr(json_encode($topic)); ?>">
                                        <?php _e('Edit', 'france-relocation-assistant'); ?>
                                    </button>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Delete this topic?');">
                                        <?php wp_nonce_field('fra_kb_nonce'); ?>
                                        <input type="hidden" name="del_category" value="<?php echo esc_attr($cat_key); ?>">
                                        <input type="hidden" name="del_key" value="<?php echo esc_attr($topic_key); ?>">
                                        <button type="submit" name="fra_delete_topic" class="button-link fra-delete-topic">
                                            <?php _e('Delete', 'france-relocation-assistant'); ?>
                                        </button>
                                    </form>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Generated Topic Preview / Editor -->
        <div class="fra-card fra-card-full" id="fra-topic-editor" style="<?php echo $generated_topic ? '' : 'display:none;'; ?>">
            <h2><?php _e('📝 Topic Editor', 'france-relocation-assistant'); ?></h2>
            
            <form method="post">
                <?php wp_nonce_field('fra_kb_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="save_category"><?php _e('Category', 'france-relocation-assistant'); ?></label></th>
                        <td>
                            <select name="save_category" id="save_category" required>
                                <?php foreach ($categories as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php echo $generated_topic && isset($_POST['category']) && $_POST['category'] === $key ? 'selected' : ''; ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="topic_key"><?php _e('Topic Key', 'france-relocation-assistant'); ?></label></th>
                        <td>
                            <input type="text" name="topic_key" id="topic_key" class="regular-text" required
                                   value="<?php echo $generated_topic ? esc_attr(sanitize_key($generated_topic['title'])) : ''; ?>"
                                   placeholder="unique_topic_key">
                            <p class="description"><?php _e('Unique identifier (lowercase, underscores)', 'france-relocation-assistant'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="save_title"><?php _e('Title', 'france-relocation-assistant'); ?></label></th>
                        <td>
                            <input type="text" name="save_title" id="save_title" class="large-text" required
                                   value="<?php echo $generated_topic ? esc_attr($generated_topic['title']) : ''; ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="save_keywords"><?php _e('Keywords', 'france-relocation-assistant'); ?></label></th>
                        <td>
                            <input type="text" name="save_keywords" id="save_keywords" class="large-text"
                                   value="<?php echo $generated_topic ? esc_attr(implode(', ', $generated_topic['keywords'])) : ''; ?>"
                                   placeholder="keyword1, keyword2, keyword3">
                            <p class="description"><?php _e('Comma-separated search keywords', 'france-relocation-assistant'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="save_content"><?php _e('Content', 'france-relocation-assistant'); ?></label></th>
                        <td>
                            <textarea name="save_content" id="save_content" rows="15" class="large-text" required><?php echo $generated_topic ? esc_textarea($generated_topic['content']) : ''; ?></textarea>
                            <p class="description"><?php _e('Use **text** for bold headers, • for bullet points', 'france-relocation-assistant'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="save_sources_display"><?php _e('Sources', 'france-relocation-assistant'); ?></label></th>
                        <td>
                            <div id="fra-sources-list">
                                <?php if ($generated_topic && !empty($generated_topic['sources'])): ?>
                                    <?php foreach ($generated_topic['sources'] as $i => $source): ?>
                                        <div class="fra-source-row">
                                            <input type="text" class="fra-source-name" placeholder="Source name" value="<?php echo esc_attr($source['name']); ?>">
                                            <input type="url" class="fra-source-url" placeholder="https://..." value="<?php echo esc_attr($source['url']); ?>">
                                            <button type="button" class="button fra-remove-source">×</button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="button" id="fra-add-source"><?php _e('+ Add Source', 'france-relocation-assistant'); ?></button>
                            <input type="hidden" name="save_sources" id="save_sources" value="<?php echo $generated_topic ? esc_attr(json_encode($generated_topic['sources'])) : '[]'; ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="save_verified"><?php _e('Last Verified', 'france-relocation-assistant'); ?></label></th>
                        <td>
                            <input type="text" name="save_verified" id="save_verified" class="regular-text"
                                   value="<?php echo $generated_topic ? esc_attr($generated_topic['lastVerified']) : date('F Y'); ?>">
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="fra_save_topic" class="button button-primary" value="<?php _e('Save Topic to Knowledge Base', 'france-relocation-assistant'); ?>">
                    <button type="button" class="button" id="fra-cancel-edit"><?php _e('Cancel', 'france-relocation-assistant'); ?></button>
                </p>
            </form>
        </div>
    </div>
</div>

<style>
.fra-kb-category { margin-bottom: 20px; }
.fra-kb-category h3 { margin: 0 0 10px 0; font-size: 14px; }
.fra-topic-list { list-style: none; padding: 0; margin: 0; }
.fra-topic-list li { 
    display: flex; 
    justify-content: space-between; 
    padding: 8px 10px; 
    background: #f9f9f9; 
    margin-bottom: 4px;
    border-radius: 3px;
}
.fra-topic-title { font-size: 13px; }
.fra-topic-actions { display: flex; gap: 10px; }
.fra-delete-topic { color: #dc3232 !important; }
.fra-source-row { display: flex; gap: 10px; margin-bottom: 8px; }
.fra-source-name { width: 200px; }
.fra-source-url { flex: 1; }
.fra-remove-source { color: #dc3232; }
#fra-add-source { margin-top: 5px; }
</style>

<script>
jQuery(document).ready(function($) {
    // Edit topic
    $('.fra-edit-topic').on('click', function() {
        var topic = $(this).data('topic');
        var category = $(this).data('category');
        var key = $(this).data('key');
        
        $('#save_category').val(category);
        $('#topic_key').val(key);
        $('#save_title').val(topic.title);
        $('#save_keywords').val(topic.keywords ? topic.keywords.join(', ') : '');
        $('#save_content').val(topic.content);
        $('#save_verified').val(topic.lastVerified || '<?php echo date('F Y'); ?>');
        
        // Populate sources
        $('#fra-sources-list').empty();
        if (topic.sources) {
            topic.sources.forEach(function(source) {
                addSourceRow(source.name, source.url);
            });
        }
        updateSourcesJson();
        
        $('#fra-topic-editor').show();
        $('html, body').animate({ scrollTop: $('#fra-topic-editor').offset().top - 50 }, 500);
    });
    
    // Cancel edit
    $('#fra-cancel-edit').on('click', function() {
        $('#fra-topic-editor').hide();
    });
    
    // Add source row
    function addSourceRow(name, url) {
        var html = '<div class="fra-source-row">' +
            '<input type="text" class="fra-source-name" placeholder="Source name" value="' + (name || '') + '">' +
            '<input type="url" class="fra-source-url" placeholder="https://..." value="' + (url || '') + '">' +
            '<button type="button" class="button fra-remove-source">×</button>' +
            '</div>';
        $('#fra-sources-list').append(html);
    }
    
    $('#fra-add-source').on('click', function() {
        addSourceRow('', '');
    });
    
    $(document).on('click', '.fra-remove-source', function() {
        $(this).closest('.fra-source-row').remove();
        updateSourcesJson();
    });
    
    $(document).on('change', '.fra-source-name, .fra-source-url', function() {
        updateSourcesJson();
    });
    
    function updateSourcesJson() {
        var sources = [];
        $('.fra-source-row').each(function() {
            var name = $(this).find('.fra-source-name').val();
            var url = $(this).find('.fra-source-url').val();
            if (name || url) {
                sources.push({ name: name, url: url });
            }
        });
        $('#save_sources').val(JSON.stringify(sources));
    }
    
    // Update sources on form submit
    $('form').on('submit', function() {
        updateSourcesJson();
    });
    
    // Generate In Practice section
    $('.fra-add-in-practice').on('click', function() {
        var $btn = $(this);
        var category = $btn.data('category');
        var topic = $btn.data('key');
        var title = $btn.data('title');
        
        if (!confirm('Generate "In Practice" section for "' + title + '"?\n\nThis will research practical insights from forums, blogs, and expat communities.')) {
            return;
        }
        
        var originalText = $btn.html();
        $btn.html('<span class="spinner is-active" style="float:none; margin:0;"></span> Generating...').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'fra_generate_in_practice',
                nonce: '<?php echo wp_create_nonce('fra_admin_nonce'); ?>',
                category: category,
                topic: topic
            },
            success: function(response) {
                if (response.success) {
                    showInPracticeResult(response.data, category, topic, title);
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Request failed. Please try again.');
            },
            complete: function() {
                $btn.html(originalText).prop('disabled', false);
            }
        });
    });
    
    function showInPracticeResult(data, category, topic, title) {
        // Create modal if not exists
        if (!$('#fra-in-practice-modal').length) {
            $('body').append(`
                <div id="fra-in-practice-modal" class="fra-modal">
                    <div class="fra-modal-content">
                        <div class="fra-modal-header">
                            <h2>✨ Generated "In Practice" Section</h2>
                            <button type="button" class="fra-modal-close">&times;</button>
                        </div>
                        <div class="fra-modal-body">
                            <div class="fra-ip-topic-title"></div>
                            <div class="fra-ip-insights"></div>
                            <div class="fra-ip-sources"></div>
                            <div class="fra-ip-content"></div>
                        </div>
                        <div class="fra-modal-footer">
                            <button type="button" class="button button-primary fra-ip-apply">✓ Add to Topic</button>
                            <button type="button" class="button fra-modal-close">Cancel</button>
                        </div>
                    </div>
                </div>
            `);
            
            $(document).on('click', '.fra-modal-close', function() {
                $('#fra-in-practice-modal').hide();
            });
        }
        
        var $modal = $('#fra-in-practice-modal');
        
        // Populate modal
        $modal.find('.fra-ip-topic-title').html('<strong>Topic:</strong> ' + title);
        
        // Key insights
        var insightsHtml = '<strong>💡 Key Insights:</strong><ul>';
        (data.key_insights || []).forEach(function(insight) {
            insightsHtml += '<li>' + insight + '</li>';
        });
        insightsHtml += '</ul>';
        $modal.find('.fra-ip-insights').html(insightsHtml);
        
        // Sources
        var sourcesHtml = '<strong>📚 Sources:</strong> ';
        var sourceNames = (data.sources || []).map(function(s) { 
            return s.name + ' (' + s.type + ')'; 
        });
        sourcesHtml += sourceNames.join(', ');
        $modal.find('.fra-ip-sources').html(sourcesHtml);
        
        // Content preview
        $modal.find('.fra-ip-content').html('<strong>Content Preview:</strong><div class="fra-ip-preview">' + 
            (data.in_practice_content || '').replace(/\n/g, '<br>') + '</div>');
        
        // Store data for apply button
        $modal.data('category', category);
        $modal.data('topic', topic);
        $modal.data('content', data.in_practice_content);
        
        $modal.show();
    }
    
    // Apply In Practice content
    $(document).on('click', '.fra-ip-apply', function() {
        var $modal = $('#fra-in-practice-modal');
        var category = $modal.data('category');
        var topic = $modal.data('topic');
        var content = $modal.data('content');
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('Saving...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'fra_apply_in_practice',
                nonce: '<?php echo wp_create_nonce('fra_admin_nonce'); ?>',
                category: category,
                topic: topic,
                content: content
            },
            success: function(response) {
                if (response.success) {
                    $modal.hide();
                    alert('In Practice section added successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                    $btn.prop('disabled', false).text('✓ Add to Topic');
                }
            },
            error: function() {
                alert('Request failed. Please try again.');
                $btn.prop('disabled', false).text('✓ Add to Topic');
            }
        });
    });
});
</script>

<style>
.fra-has-practice {
    color: #6f42c1;
    font-size: 12px;
    margin-left: 5px;
}

.fra-add-in-practice {
    color: #6f42c1 !important;
}

.fra-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    z-index: 100000;
}

.fra-modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    border-radius: 8px;
    width: 90%;
    max-width: 700px;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
}

.fra-modal-header {
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.fra-modal-header h2 {
    margin: 0;
    font-size: 18px;
}

.fra-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.fra-modal-body {
    padding: 20px;
    overflow-y: auto;
    flex: 1;
}

.fra-modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #ddd;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.fra-ip-topic-title {
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.fra-ip-insights {
    margin-bottom: 15px;
    padding: 12px;
    background: #fff9e6;
    border-radius: 6px;
}

.fra-ip-insights ul {
    margin: 8px 0 0 20px;
}

.fra-ip-sources {
    margin-bottom: 15px;
    color: #666;
    font-size: 13px;
}

.fra-ip-preview {
    margin-top: 10px;
    padding: 15px;
    background: #f5f5f5;
    border-radius: 6px;
    max-height: 250px;
    overflow-y: auto;
    font-size: 13px;
    line-height: 1.6;
}
</style>
