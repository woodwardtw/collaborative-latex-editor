# Collaborative LaTeX Editor for WordPress

A real-time collaborative LaTeX editing plugin for WordPress with live rendering and multi-user support.

## Features

- **Real-time Collaboration**: Multiple users can edit the same LaTeX document simultaneously
- **Live Preview**: Instant LaTeX rendering with KaTeX as you type
- **Split View**: Side-by-side editor and preview panels
- **Auto-save**: Automatic document saving with conflict detection
- **Presence Indicators**: See who else is currently editing
- **LaTeX Templates**: Quick insert buttons for common LaTeX structures
- **Syntax Highlighting**: CodeMirror-powered editor with LaTeX syntax support
- **Version Control**: Track document versions and changes
- **Download Support**: Export documents as .tex files
- **WordPress Integration**: Custom post type with full WordPress permission system

## Installation

1. Upload the plugin folder to `/wp-content/plugins/collaborative-latex-editor/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. A new "LaTeX Documents" post type will appear in your admin menu

## Usage

### Creating a New Document

1. Go to **LaTeX Documents > Add New** in your WordPress admin
2. Enter a title for your document
3. The editor will load with a default LaTeX template
4. Start editing - changes auto-save every 2 seconds

### Using the Shortcode

Add the editor to any post or page using the shortcode:

```
[latex_editor id="123" height="800px"]
```

Parameters:
- `id`: The post ID of the LaTeX document (defaults to current post)
- `height`: Height of the editor container (defaults to 600px)

### Editor Features

**Toolbar Buttons:**
- **Save**: Manually save the document (Ctrl+S / Cmd+S)
- **Download**: Export as .tex file
- **Template Buttons**: Insert common LaTeX structures

**Quick Insert Templates:**
- Equation
- Matrix
- Align environment
- Fractions
- Integrals
- Summations

### Collaborative Editing

- **Active Users**: Avatars appear in the toolbar showing who's currently editing
- **Auto-sync**: Changes from other users are pulled every 5 seconds
- **Conflict Resolution**: Last-write-wins with version tracking
- **Presence Updates**: User presence updated every 10 seconds

### LaTeX Support

The preview pane supports standard LaTeX math notation:

**Inline Math:**
```latex
$E = mc^2$
```

**Display Math:**
```latex
$$\int_{-\infty}^{\infty} e^{-x^2} dx = \sqrt{\pi}$$
```

**Equation Environments:**
```latex
\begin{equation}
x = \frac{-b \pm \sqrt{b^2 - 4ac}}{2a}
\end{equation}
```

**Matrices:**
```latex
\begin{bmatrix}
1 & 2 \\
3 & 4
\end{bmatrix}
```

## REST API Endpoints

The plugin provides REST API endpoints for programmatic access:

### Get Document
```
GET /wp-json/collab-latex/v1/document/{id}
```

### Update Document
```
POST /wp-json/collab-latex/v1/document/{id}/update
Body: { "content": "LaTeX content here" }
```

### Update Presence
```
POST /wp-json/collab-latex/v1/document/{id}/presence
```

## Permissions

The plugin respects WordPress user capabilities:
- **Read**: View documents
- **Edit**: Edit documents
- **Delete**: Delete documents

Users need appropriate permissions for each action.

## Technical Details

### Libraries Used

- **KaTeX**: Fast LaTeX rendering (v0.16.9)
- **CodeMirror**: Code editor with syntax highlighting (v5.65.2)
- **jQuery**: DOM manipulation and AJAX

### Browser Support

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+

### Server Requirements

- WordPress 5.8+
- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.2+

## Configuration

You can modify sync and presence intervals in the JavaScript initialization:

```javascript
new CollaborativeLatexEditor(containerId, postId, {
    syncDelay: 2000,           // Auto-save delay (ms)
    presenceInterval: 10000,   // Presence update interval (ms)
    autoSave: true             // Enable auto-save
});
```

## Customization

### Custom Styles

Override default styles by adding CSS to your theme:

```css
.collab-latex-container {
    border-color: #your-color;
}
```

### Add Custom Templates

Extend the templates object in `assets/js/editor.js`:

```javascript
const templates = {
    mytemplate: '\\begin{myenv}\n  \n\\end{myenv}'
};
```

## Troubleshooting

**Editor not loading:**
- Check browser console for JavaScript errors
- Ensure all dependencies are loading correctly
- Verify user has read permissions

**Auto-save not working:**
- Check user has edit permissions
- Verify REST API is accessible
- Check for JavaScript console errors

**Preview not rendering:**
- Ensure KaTeX is loading correctly
- Check for LaTeX syntax errors
- Verify math delimiters are correct

## Development

### File Structure

```
collaborative-latex-editor/
├── collaborative-latex-editor.php   # Main plugin file
├── assets/
│   ├── css/
│   │   └── editor.css               # Editor styles
│   └── js/
│       └── editor.js                # Editor JavaScript
├── templates/
│   └── editor-template.php          # Editor HTML template
└── README.md                        # This file
```

### Hooks and Filters

**Actions:**
- `collab_latex_before_save`: Before document save
- `collab_latex_after_save`: After document save
- `collab_latex_document_created`: When new document created

**Filters:**
- `collab_latex_editor_config`: Modify editor configuration
- `collab_latex_default_template`: Change default template
- `collab_latex_capabilities`: Modify required capabilities

## Contributing

Contributions are welcome! Please follow WordPress coding standards.

## License

GPL v2 or later

## Support

For issues and questions, please use the WordPress support forums or create an issue on GitHub.

## Credits

Built with:
- KaTeX by Khan Academy
- CodeMirror by Marijn Haverbeke
- WordPress by Automattic

## Changelog

### 1.0.0
- Initial release
- Real-time collaborative editing
- LaTeX rendering with KaTeX
- Auto-save functionality
- Presence indicators
- Template insertion
- REST API endpoints
