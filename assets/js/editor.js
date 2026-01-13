(function($) {
    'use strict';
    
    class CollaborativeLatexEditor {
        constructor(containerId, postId, options = {}) {
            this.container = document.getElementById(containerId);
            this.postId = postId;
            this.editor = null;
            this.lastVersion = 0;
            this.saveTimeout = null;
            this.syncInterval = null;
            this.presenceInterval = null;
            this.isLocalChange = false;
            this.options = {
                syncDelay: 2000,
                presenceInterval: 10000,
                autoSave: true,
                ...options
            };
            
            this.init();
        }
        
        init() {
            this.setupEditor();
            this.setupPreview();
            this.setupToolbar();
            this.loadDocument();
            this.startSync();
            this.startPresence();
        }
        
        setupEditor() {
            const editorElement = this.container.querySelector('.collab-latex-editor');
            
            this.editor = CodeMirror(editorElement, {
                mode: 'stex',
                lineNumbers: true,
                lineWrapping: true,
                theme: 'default',
                autofocus: true,
                extraKeys: {
                    'Ctrl-S': () => this.saveDocument(),
                    'Cmd-S': () => this.saveDocument()
                }
            });
            
            this.editor.on('change', (cm, change) => {
                if (change.origin !== 'setValue') {
                    this.isLocalChange = true;
                    this.updatePreview();
                    this.debouncedSave();
                }
            });
        }
        
        setupPreview() {
            this.previewContainer = this.container.querySelector('.collab-latex-preview-content');
        }
        
        setupToolbar() {
            const saveBtn = this.container.querySelector('.collab-latex-save');
            const downloadBtn = this.container.querySelector('.collab-latex-download');
            const templateBtns = this.container.querySelectorAll('.collab-latex-template-btn');
            
            if (saveBtn) {
                saveBtn.addEventListener('click', () => this.saveDocument());
            }
            
            if (downloadBtn) {
                downloadBtn.addEventListener('click', () => this.downloadDocument());
            }
            
            templateBtns.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const template = e.target.dataset.template;
                    this.insertTemplate(template);
                });
            });
        }
        
        insertTemplate(templateName) {
            const templates = {
                equation: '\\[\n\\begin{equation}\n  \n\\end{equation}\n\\]',
                matrix: '\\[\n\\begin{bmatrix}\n  a & b \\\\\n  c & d\n\\end{bmatrix}\n\\]',
                align: '\\[\n\\begin{align}\n  y &= mx + b \\\\\n  y &= ax^2 + bx + c\n\\end{align}\n\\]',
                fraction: '\\frac{numerator}{denominator}',
                integral: '\\int_{a}^{b} f(x) \\, dx',
                sum: '\\sum_{i=1}^{n} x_i',
                limit: '\\lim_{x \\to \\infty} f(x)'
            };
            
            const template = templates[templateName];
            if (template) {
                const cursor = this.editor.getCursor();
                this.editor.replaceRange(template, cursor);
                this.editor.focus();
            }
        }
        
        updatePreview() {
            const content = this.editor.getValue();
            
            // Process the content for LaTeX rendering
            let processedContent = this.processLatexContent(content);
            
            this.previewContainer.innerHTML = processedContent;
            
            // Render LaTeX with KaTeX
            if (window.renderMathInElement) {
                renderMathInElement(this.previewContainer, {
                    delimiters: [
                        {left: '$$', right: '$$', display: true},
                        {left: '\\[', right: '\\]', display: true},
                        {left: '$', right: '$', display: false},
                        {left: '\\(', right: '\\)', display: false}
                    ],
                    throwOnError: false
                });
            }
        }
        
        processLatexContent(content) {
            // Convert LaTeX environments to HTML for better display
            let processed = content;
            
            // Convert section headers
            processed = processed.replace(/\\section\{([^}]+)\}/g, '<h2>$1</h2>');
            processed = processed.replace(/\\subsection\{([^}]+)\}/g, '<h3>$1</h3>');
            processed = processed.replace(/\\subsubsection\{([^}]+)\}/g, '<h4>$1</h4>');
            
            // Convert paragraphs
            processed = processed.replace(/\n\n/g, '</p><p>');
            processed = '<p>' + processed + '</p>';
            
            // Convert lists
            processed = processed.replace(/\\begin\{itemize\}([\s\S]*?)\\end\{itemize\}/g, (match, items) => {
                const listItems = items.replace(/\\item\s+([^\n]*)/g, '<li>$1</li>');
                return '<ul>' + listItems + '</ul>';
            });
            
            processed = processed.replace(/\\begin\{enumerate\}([\s\S]*?)\\end\{enumerate\}/g, (match, items) => {
                const listItems = items.replace(/\\item\s+([^\n]*)/g, '<li>$1</li>');
                return '<ol>' + listItems + '</ol>';
            });
            
            return processed;
        }
        
        loadDocument() {
            this.updateStatus('Loading...', 'saving');
            
            fetch(`${collabLatexConfig.restUrl}document/${this.postId}`, {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': collabLatexConfig.restNonce
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.editor.setValue(data.content || this.getDefaultTemplate());
                    this.lastVersion = data.version || 0;
                    this.updatePreview();
                    this.updateStatus('Loaded', 'saved');
                } else {
                    this.showToast('Failed to load document', 'error');
                }
            })
            .catch(error => {
                console.error('Load error:', error);
                this.showToast('Failed to load document', 'error');
            });
        }
        
        saveDocument() {
            if (!this.isLocalChange) return;
            
            this.updateStatus('Saving...', 'saving');
            
            const content = this.editor.getValue();
            
            fetch(`${collabLatexConfig.restUrl}document/${this.postId}/update`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': collabLatexConfig.restNonce
                },
                body: JSON.stringify({ content })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.lastVersion = data.version;
                    this.isLocalChange = false;
                    this.updateStatus('Saved', 'saved');
                    setTimeout(() => this.updateStatus(''), 2000);
                } else {
                    this.updateStatus('Error saving', 'error');
                    this.showToast('Failed to save document', 'error');
                }
            })
            .catch(error => {
                console.error('Save error:', error);
                this.updateStatus('Error saving', 'error');
                this.showToast('Failed to save document', 'error');
            });
        }
        
        debouncedSave() {
            if (!this.options.autoSave) return;
            
            clearTimeout(this.saveTimeout);
            this.saveTimeout = setTimeout(() => {
                this.saveDocument();
            }, this.options.syncDelay);
        }
        
        startSync() {
            // Poll for updates from other users
            this.syncInterval = setInterval(() => {
                if (!this.isLocalChange) {
                    this.checkForUpdates();
                }
            }, 5000);
        }
        
        checkForUpdates() {
            fetch(`${collabLatexConfig.restUrl}document/${this.postId}`, {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': collabLatexConfig.restNonce
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.version > this.lastVersion) {
                    // Document was updated by another user
                    const cursor = this.editor.getCursor();
                    this.editor.setValue(data.content);
                    this.editor.setCursor(cursor);
                    this.lastVersion = data.version;
                    this.updatePreview();
                    this.showToast('Document updated by another user', 'success');
                }
            })
            .catch(error => {
                console.error('Sync error:', error);
            });
        }
        
        startPresence() {
            // Update presence information
            this.updatePresence();
            this.presenceInterval = setInterval(() => {
                this.updatePresence();
            }, this.options.presenceInterval);
        }
        
        updatePresence() {
            fetch(`${collabLatexConfig.restUrl}document/${this.postId}/presence`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': collabLatexConfig.restNonce
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.displayActiveUsers(data.active_users);
                }
            })
            .catch(error => {
                console.error('Presence error:', error);
            });
        }
        
        displayActiveUsers(users) {
            const presenceContainer = this.container.querySelector('.collab-latex-presence');
            if (!presenceContainer) return;
            
            presenceContainer.innerHTML = '';
            
            users.forEach((user, index) => {
                if (user.name !== collabLatexConfig.userName) {
                    const userIcon = document.createElement('div');
                    userIcon.className = 'collab-latex-presence-icon';
                    userIcon.textContent = user.name.charAt(0).toUpperCase();
                    userIcon.title = user.name;
                    userIcon.style.background = this.getUserColor(index);
                    presenceContainer.appendChild(userIcon);
                }
            });
        }
        
        getUserColor(index) {
            const colors = ['#0073aa', '#dc3545', '#28a745', '#ffc107', '#6f42c1', '#e83e8c'];
            return colors[index % colors.length];
        }
        
        updateStatus(message, status = '') {
            const statusElement = this.container.querySelector('.collab-latex-status');
            if (statusElement) {
                statusElement.textContent = message;
                statusElement.className = 'collab-latex-status ' + status;
            }
        }
        
        showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `collab-latex-toast ${type}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
        
        downloadDocument() {
            const content = this.editor.getValue();
            const blob = new Blob([content], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `latex-document-${this.postId}.tex`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
        
        getDefaultTemplate() {
            return `\\documentclass{article}
\\usepackage{amsmath}
\\usepackage{amssymb}

\\title{Your Document Title}
\\author{Your Name}
\\date{\\today}

\\begin{document}

\\maketitle

\\section{Introduction}

Start writing your LaTeX document here. You can use inline math like $E = mc^2$ or display math:

\\[
\\int_{-\\infty}^{\\infty} e^{-x^2} dx = \\sqrt{\\pi}
\\]

\\section{Examples}

\\subsection{Equations}

The quadratic formula is:
\\begin{equation}
x = \\frac{-b \\pm \\sqrt{b^2 - 4ac}}{2a}
\\end{equation}

\\subsection{Matrices}

A matrix example:
\\[
\\begin{bmatrix}
1 & 2 & 3 \\\\
4 & 5 & 6 \\\\
7 & 8 & 9
\\end{bmatrix}
\\]

\\end{document}`;
        }
        
        destroy() {
            clearInterval(this.syncInterval);
            clearInterval(this.presenceInterval);
            clearTimeout(this.saveTimeout);
        }
    }
    
    // Initialize editors on page load
    $(document).ready(function() {
        $('.collab-latex-container').each(function() {
            const postId = $(this).data('post-id');
            const containerId = $(this).attr('id');
            
            if (postId && containerId) {
                new CollaborativeLatexEditor(containerId, postId);
            }
        });
    });
    
    // Expose to global scope
    window.CollaborativeLatexEditor = CollaborativeLatexEditor;
    
})(jQuery);
