<?php
/**
 * Example Usage of Collaborative LaTeX Editor Plugin
 * 
 * This file demonstrates various ways to use the plugin
 * in your WordPress themes and plugins.
 */

// Example 1: Create a new LaTeX document programmatically
function create_latex_document_example() {
    $post_data = array(
        'post_title'    => 'My Mathematical Proof',
        'post_type'     => 'latex_document',
        'post_status'   => 'publish',
        'post_author'   => get_current_user_id()
    );
    
    $post_id = wp_insert_post($post_data);
    
    if ($post_id) {
        // Set initial LaTeX content
        $latex_content = <<<'LATEX'
\documentclass{article}
\usepackage{amsmath}

\title{Proof of Euler's Identity}
\author{Mathematics Department}

\begin{document}
\maketitle

\section{Introduction}
We will prove one of the most beautiful equations in mathematics:

\begin{equation}
e^{i\pi} + 1 = 0
\end{equation}

\section{Proof}
Starting with Euler's formula:
\[
e^{ix} = \cos(x) + i\sin(x)
\]

When $x = \pi$:
\begin{align}
e^{i\pi} &= \cos(\pi) + i\sin(\pi) \\
         &= -1 + i(0) \\
         &= -1
\end{align}

Therefore:
\[
e^{i\pi} + 1 = 0 \quad \square
\]

\end{document}
LATEX;
        
        update_post_meta($post_id, '_latex_content', $latex_content);
        update_post_meta($post_id, '_latex_version', 1);
        
        return $post_id;
    }
    
    return false;
}

// Example 2: Display editor in a custom template
function display_latex_editor_in_template() {
    // In your template file (e.g., single-latex_document.php)
    ?>
    <div class="latex-document-container">
        <h1><?php the_title(); ?></h1>
        
        <?php 
        // Display the editor using shortcode
        echo do_shortcode('[latex_editor id="' . get_the_ID() . '" height="700px"]'); 
        ?>
        
        <div class="document-meta">
            <p>Last modified: <?php echo get_post_meta(get_the_ID(), '_latex_last_modified', true); ?></p>
        </div>
    </div>
    <?php
}

// Example 3: Add custom template to the editor
add_filter('collab_latex_default_template', 'add_custom_latex_template');
function add_custom_latex_template($template) {
    return <<<'LATEX'
\documentclass{article}
\usepackage{amsmath}
\usepackage{graphicx}
\usepackage{hyperref}

\title{Research Paper Template}
\author{Your Name \\ Your Institution}
\date{\today}

\begin{document}

\maketitle

\begin{abstract}
Your abstract goes here. Provide a brief summary of your research,
methodology, and key findings.
\end{abstract}

\section{Introduction}
Introduce your topic and research question here.

\section{Literature Review}
Discuss relevant previous work.

\section{Methodology}
Describe your research methods.

\section{Results}
Present your findings with equations like:
\begin{equation}
F = ma
\end{equation}

\section{Discussion}
Analyze your results.

\section{Conclusion}
Summarize your work and implications.

\bibliographystyle{plain}
\bibliography{references}

\end{document}
LATEX;
}

// Example 4: Create a page template for LaTeX editing
/*
Template Name: LaTeX Editor Page
*/
function latex_editor_page_template() {
    get_header();
    ?>
    
    <div class="latex-page-wrapper">
        <div class="page-header">
            <h1>LaTeX Document Editor</h1>
            <p>Collaborative mathematical document editing</p>
        </div>
        
        <?php
        // Get or create a document for this page
        $page_id = get_the_ID();
        $latex_doc_id = get_post_meta($page_id, '_associated_latex_doc', true);
        
        if (!$latex_doc_id) {
            // Create new document
            $latex_doc_id = wp_insert_post(array(
                'post_title' => get_the_title() . ' - LaTeX Document',
                'post_type' => 'latex_document',
                'post_status' => 'publish'
            ));
            update_post_meta($page_id, '_associated_latex_doc', $latex_doc_id);
        }
        
        echo do_shortcode('[latex_editor id="' . $latex_doc_id . '"]');
        ?>
    </div>
    
    <?php
    get_footer();
}

// Example 5: Add a widget for quick LaTeX editing
class Quick_LaTeX_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'quick_latex_widget',
            'Quick LaTeX Editor',
            array('description' => 'A quick LaTeX equation editor')
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . $instance['title'] . $args['after_title'];
        }
        
        ?>
        <div class="quick-latex-widget">
            <textarea id="quick-latex-input" rows="3" placeholder="Enter LaTeX equation...">E = mc^2</textarea>
            <div id="quick-latex-output" style="padding: 10px; margin-top: 10px; border: 1px solid #ddd;"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#quick-latex-input').on('input', function() {
                var latex = $(this).val();
                var output = $('#quick-latex-output');
                
                try {
                    katex.render(latex, output[0], {
                        throwOnError: false,
                        displayMode: true
                    });
                } catch(e) {
                    output.text('Invalid LaTeX');
                }
            });
            
            // Trigger initial render
            $('#quick-latex-input').trigger('input');
        });
        </script>
        <?php
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'LaTeX Equation';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" 
                   name="<?php echo $this->get_field_name('title'); ?>" type="text" 
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }
}

// Register the widget
add_action('widgets_init', function() {
    register_widget('Quick_LaTeX_Widget');
});

// Example 6: REST API usage from JavaScript
?>
<script>
// Fetch a document
async function fetchLatexDocument(documentId) {
    const response = await fetch(`/wp-json/collab-latex/v1/document/${documentId}`, {
        headers: {
            'X-WP-Nonce': wpApiSettings.nonce
        }
    });
    
    const data = await response.json();
    console.log('Document content:', data.content);
    return data;
}

// Update a document
async function updateLatexDocument(documentId, content) {
    const response = await fetch(`/wp-json/collab-latex/v1/document/${documentId}/update`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': wpApiSettings.nonce
        },
        body: JSON.stringify({ content })
    });
    
    const data = await response.json();
    console.log('Update result:', data);
    return data;
}

// Example usage
// fetchLatexDocument(123).then(doc => console.log(doc));
// updateLatexDocument(123, '\\documentclass{article}...');
</script>
<?php

// Example 7: Add custom capabilities
function setup_latex_capabilities() {
    $role = get_role('editor');
    if ($role) {
        $role->add_cap('edit_latex_documents');
        $role->add_cap('edit_others_latex_documents');
        $role->add_cap('publish_latex_documents');
        $role->add_cap('read_private_latex_documents');
    }
}
add_action('admin_init', 'setup_latex_capabilities');

// Example 8: Custom meta box for document settings
add_action('add_meta_boxes', 'add_latex_settings_metabox');
function add_latex_settings_metabox() {
    add_meta_box(
        'latex_settings',
        'Document Settings',
        'render_latex_settings_metabox',
        'latex_document',
        'side',
        'default'
    );
}

function render_latex_settings_metabox($post) {
    $allow_comments = get_post_meta($post->ID, '_latex_allow_comments', true);
    $template_type = get_post_meta($post->ID, '_latex_template_type', true);
    ?>
    <p>
        <label>
            <input type="checkbox" name="latex_allow_comments" value="1" 
                   <?php checked($allow_comments, '1'); ?>>
            Allow comments
        </label>
    </p>
    <p>
        <label>Template Type:</label>
        <select name="latex_template_type" style="width: 100%;">
            <option value="article" <?php selected($template_type, 'article'); ?>>Article</option>
            <option value="report" <?php selected($template_type, 'report'); ?>>Report</option>
            <option value="book" <?php selected($template_type, 'book'); ?>>Book</option>
            <option value="beamer" <?php selected($template_type, 'beamer'); ?>>Presentation</option>
        </select>
    </p>
    <?php
}

// Example 9: Export all documents as ZIP
function export_all_latex_documents() {
    $documents = get_posts(array(
        'post_type' => 'latex_document',
        'posts_per_page' => -1
    ));
    
    $zip = new ZipArchive();
    $filename = tempnam(sys_get_temp_dir(), 'latex_export_') . '.zip';
    
    if ($zip->open($filename, ZipArchive::CREATE) !== TRUE) {
        return false;
    }
    
    foreach ($documents as $doc) {
        $content = get_post_meta($doc->ID, '_latex_content', true);
        $safe_title = sanitize_file_name($doc->post_title);
        $zip->addFromString($safe_title . '.tex', $content);
    }
    
    $zip->close();
    
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="latex-documents.zip"');
    header('Content-Length: ' . filesize($filename));
    readfile($filename);
    unlink($filename);
    exit;
}

// Example 10: Scheduled backup of all LaTeX documents
add_action('collab_latex_daily_backup', 'backup_latex_documents');
function backup_latex_documents() {
    $documents = get_posts(array(
        'post_type' => 'latex_document',
        'posts_per_page' => -1
    ));
    
    $backup_data = array();
    
    foreach ($documents as $doc) {
        $backup_data[] = array(
            'id' => $doc->ID,
            'title' => $doc->post_title,
            'content' => get_post_meta($doc->ID, '_latex_content', true),
            'version' => get_post_meta($doc->ID, '_latex_version', true),
            'author' => $doc->post_author,
            'date' => $doc->post_date
        );
    }
    
    // Save to file or send to backup service
    $upload_dir = wp_upload_dir();
    $backup_file = $upload_dir['basedir'] . '/latex-backups/backup-' . date('Y-m-d') . '.json';
    
    wp_mkdir_p(dirname($backup_file));
    file_put_contents($backup_file, json_encode($backup_data, JSON_PRETTY_PRINT));
}

// Schedule the backup
if (!wp_next_scheduled('collab_latex_daily_backup')) {
    wp_schedule_event(time(), 'daily', 'collab_latex_daily_backup');
}
