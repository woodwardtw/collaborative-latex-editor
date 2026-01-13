<?php
/**
 * Template for LaTeX Editor
 */

if (!defined('ABSPATH')) {
    exit;
}

$post_id = isset($atts['id']) ? intval($atts['id']) : get_the_ID();
$height = isset($atts['height']) ? esc_attr($atts['height']) : '600px';
$container_id = 'collab-latex-editor-' . $post_id;
?>

<div id="<?php echo $container_id; ?>" class="collab-latex-container" data-post-id="<?php echo $post_id; ?>" style="height: <?php echo $height; ?>;">
    
    <div class="collab-latex-toolbar">
        <div class="collab-latex-toolbar-left">
            <button class="collab-latex-btn collab-latex-save" title="Save Document (Ctrl+S)">
                <span class="dashicons dashicons-yes"></span> Save
            </button>
            <button class="collab-latex-btn collab-latex-btn-secondary collab-latex-download" title="Download as .tex">
                <span class="dashicons dashicons-download"></span> Download
            </button>
            
            <div class="collab-latex-templates">
                <button class="collab-latex-template-btn" data-template="equation" title="Insert Equation">Equation</button>
                <button class="collab-latex-template-btn" data-template="matrix" title="Insert Matrix">Matrix</button>
                <button class="collab-latex-template-btn" data-template="align" title="Insert Align">Align</button>
                <button class="collab-latex-template-btn" data-template="fraction" title="Insert Fraction">Fraction</button>
                <button class="collab-latex-template-btn" data-template="integral" title="Insert Integral">∫</button>
                <button class="collab-latex-template-btn" data-template="sum" title="Insert Sum">∑</button>
            </div>
        </div>
        
        <div class="collab-latex-toolbar-right">
            <div class="collab-latex-presence"></div>
            <div class="collab-latex-status"></div>
        </div>
    </div>
    
    <div class="collab-latex-workspace">
        <div class="collab-latex-editor-pane">
            <div class="collab-latex-editor"></div>
        </div>
        
        <div class="collab-latex-preview-pane">
            <div class="collab-latex-preview-content">
                <div class="collab-latex-loading">
                    <div class="collab-latex-spinner"></div>
                    <span>Loading document...</span>
                </div>
            </div>
        </div>
    </div>
    
</div>
