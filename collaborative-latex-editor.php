<?php
/**
 * Plugin Name: Collaborative LaTeX Editor
 * Plugin URI: https://github.com/yourusername/collaborative-latex-editor
 * Description: Real-time collaborative LaTeX editing with live rendering
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: collab-latex
 */

if (!defined('ABSPATH')) {
    exit;
}

define('COLLAB_LATEX_VERSION', '1.0.2');
define('COLLAB_LATEX_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('COLLAB_LATEX_PLUGIN_URL', plugin_dir_url(__FILE__));

class Collaborative_LaTeX_Editor {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('init', array($this, 'register_post_type'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_shortcode('latex_editor', array($this, 'render_editor_shortcode'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('wp_ajax_save_latex_document', array($this, 'ajax_save_document'));
        add_action('wp_ajax_get_latex_document', array($this, 'ajax_get_document'));
        add_filter('the_content', array($this, 'filter_latex_document_content'));
    }
    
    public function register_post_type() {
        $labels = array(
            'name' => __('LaTeX Documents', 'collab-latex'),
            'singular_name' => __('LaTeX Document', 'collab-latex'),
            'add_new' => __('Add New', 'collab-latex'),
            'add_new_item' => __('Add New LaTeX Document', 'collab-latex'),
            'edit_item' => __('Edit LaTeX Document', 'collab-latex'),
            'new_item' => __('New LaTeX Document', 'collab-latex'),
            'view_item' => __('View LaTeX Document', 'collab-latex'),
            'search_items' => __('Search LaTeX Documents', 'collab-latex'),
            'not_found' => __('No documents found', 'collab-latex'),
            'not_found_in_trash' => __('No documents found in Trash', 'collab-latex')
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-edit-large',
            'supports' => array('title', 'author', 'revisions'),
            'capability_type' => 'post',
            'rewrite' => array('slug' => 'latex-docs')
        );
        
        register_post_type('latex_document', $args);
    }
    
    public function enqueue_assets() {
        if (is_singular('latex_document') || has_shortcode(get_post()->post_content ?? '', 'latex_editor')) {
            // KaTeX for LaTeX rendering
            wp_enqueue_style('katex', 'https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css', array(), '0.16.9');
            wp_enqueue_script('katex', 'https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js', array(), '0.16.9', true);
            wp_enqueue_script('katex-auto-render', 'https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js', array('katex'), '0.16.9', true);
            
            // CodeMirror for editing
            wp_enqueue_style('codemirror', 'https://cdn.jsdelivr.net/npm/codemirror@5.65.2/lib/codemirror.css', array(), '5.65.2');
            wp_enqueue_script('codemirror', 'https://cdn.jsdelivr.net/npm/codemirror@5.65.2/lib/codemirror.js', array(), '5.65.2', true);
            wp_enqueue_script('codemirror-stex', 'https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/stex/stex.js', array('codemirror'), '5.65.2', true);
            
            // Plugin assets
            wp_enqueue_style('collab-latex-editor', COLLAB_LATEX_PLUGIN_URL . 'assets/css/editor.css', array(), COLLAB_LATEX_VERSION);
            wp_enqueue_script('collab-latex-editor', COLLAB_LATEX_PLUGIN_URL . 'assets/js/editor.js', array('jquery', 'katex-auto-render', 'codemirror'), COLLAB_LATEX_VERSION, true);
            
            wp_localize_script('collab-latex-editor', 'collabLatexConfig', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('latex_editor_nonce'),
                'userId' => get_current_user_id(),
                'userName' => wp_get_current_user()->display_name,
                'restUrl' => rest_url('collab-latex/v1/'),
                'restNonce' => wp_create_nonce('wp_rest')
            ));
        }
    }
    
    public function enqueue_admin_assets($hook) {
        global $post_type;
        if ('latex_document' === $post_type) {
            $this->enqueue_assets();
        }
    }
    
    public function register_rest_routes() {
        register_rest_route('collab-latex/v1', '/document/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_document_rest'),
            'permission_callback' => array($this, 'check_read_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));
        
        register_rest_route('collab-latex/v1', '/document/(?P<id>\d+)/update', array(
            'methods' => 'POST',
            'callback' => array($this, 'update_document_rest'),
            'permission_callback' => array($this, 'check_edit_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
                'content' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => function($param) {
                        // Don't sanitize - preserve backslashes for LaTeX
                        return $param;
                    }
                ),
            ),
        ));
        
        register_rest_route('collab-latex/v1', '/document/(?P<id>\d+)/presence', array(
            'methods' => 'POST',
            'callback' => array($this, 'update_presence'),
            'permission_callback' => array($this, 'check_read_permission'),
        ));
    }
    
    public function check_read_permission($request) {
        $post_id = $request->get_param('id');
        return current_user_can('read_post', $post_id);
    }
    
    public function check_edit_permission($request) {
        $post_id = $request->get_param('id');
        return current_user_can('edit_post', $post_id);
    }
    
    public function get_document_rest($request) {
        $post_id = $request->get_param('id');
        $content = get_post_meta($post_id, '_latex_content', true);
        $version = get_post_meta($post_id, '_latex_version', true);
        
        return rest_ensure_response(array(
            'success' => true,
            'content' => $content ?: '',
            'version' => intval($version),
            'title' => get_the_title($post_id)
        ));
    }
    
    public function update_document_rest($request) {
        $post_id = $request->get_param('id');

        // Get the raw body to preserve backslashes
        $body = $request->get_body();
        $data = json_decode($body, true);
        $content = isset($data['content']) ? $data['content'] : '';

        error_log('Content received (first 200 chars): ' . substr($content, 0, 200));
        error_log('Has \\begin{document}: ' . (strpos($content, '\\begin{document}') !== false ? 'yes' : 'no'));

        $current_version = intval(get_post_meta($post_id, '_latex_version', true));

        // WordPress will strip slashes when retrieving, so we need to add them when saving
        update_post_meta($post_id, '_latex_content', wp_slash($content));
        update_post_meta($post_id, '_latex_version', $current_version + 1);
        update_post_meta($post_id, '_latex_last_modified', current_time('mysql'));
        update_post_meta($post_id, '_latex_last_author', get_current_user_id());

        return rest_ensure_response(array(
            'success' => true,
            'version' => $current_version + 1
        ));
    }
    
    public function update_presence($request) {
        $post_id = $request->get_param('id');
        $user_id = get_current_user_id();
        
        $presence = get_transient('latex_presence_' . $post_id) ?: array();
        $presence[$user_id] = array(
            'name' => wp_get_current_user()->display_name,
            'timestamp' => time()
        );
        
        set_transient('latex_presence_' . $post_id, $presence, 30);
        
        // Clean up old presence data (older than 30 seconds)
        foreach ($presence as $uid => $data) {
            if (time() - $data['timestamp'] > 30) {
                unset($presence[$uid]);
            }
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'active_users' => array_values($presence)
        ));
    }
    
    public function ajax_save_document() {
        check_ajax_referer('latex_editor_nonce', 'nonce');

        $post_id = intval($_POST['post_id']);
        // Don't use wp_unslash - we want to preserve backslashes
        $content = $_POST['content'];

        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Permission denied');
        }

        // Preserve backslashes in LaTeX content
        $content = wp_slash($content);

        update_post_meta($post_id, '_latex_content', $content);

        wp_send_json_success(array(
            'message' => 'Document saved successfully'
        ));
    }
    
    public function ajax_get_document() {
        check_ajax_referer('latex_editor_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        
        if (!current_user_can('read_post', $post_id)) {
            wp_send_json_error('Permission denied');
        }
        
        $content = get_post_meta($post_id, '_latex_content', true);
        
        wp_send_json_success(array(
            'content' => $content ?: ''
        ));
    }
    
    public function render_editor_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => get_the_ID(),
            'height' => '600px'
        ), $atts);

        $post_id = intval($atts['id']);

        if (!current_user_can('read_post', $post_id)) {
            return '<p>You do not have permission to view this document.</p>';
        }

        ob_start();
        include COLLAB_LATEX_PLUGIN_DIR . 'templates/editor-template.php';
        return ob_get_clean();
    }

    public function filter_latex_document_content($content) {
        // Only apply on single latex_document posts on the front end
        if (is_singular('latex_document') && !is_admin() && in_the_loop() && is_main_query()) {
            $post_id = get_the_ID();

            if (!current_user_can('read_post', $post_id)) {
                return '<p>You do not have permission to view this document.</p>';
            }

            // Generate the editor interface
            $atts = array(
                'id' => $post_id,
                'height' => '800px' // Taller height for full-page display
            );

            ob_start();
            include COLLAB_LATEX_PLUGIN_DIR . 'templates/editor-template.php';
            $editor_html = ob_get_clean();

            // Return editor interface instead of default content
            return $editor_html;
        }

        return $content;
    }
}

// Initialize the plugin
function collab_latex_init() {
    return Collaborative_LaTeX_Editor::get_instance();
}
add_action('plugins_loaded', 'collab_latex_init');
