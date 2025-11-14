# IEP Form Analyzer

A clean, focused web application for extracting, parsing, and displaying data from Individual Educational Program (IEP) PDF forms.

## Features

- 📄 **PDF Upload** - Drag and drop or click to upload IEP forms
- 🎯 **Smart Parsing** - Automatically extracts and categorizes form fields
- 📊 **Organized Display** - Data grouped into logical IEP sections
- 🔍 **Easy Navigation** - Collapsible sections for quick access to specific information
- 💾 **Export Options** - Export data as JSON or print-friendly format
- 📱 **Responsive Design** - Works on desktop, tablet, and mobile

## IEP Sections Extracted

- **Student Info** - Name, DOB, Grade, Student ID
- **Disability Category** - Classification and evaluation date
- **IEP Dates** - Meeting date, effective date, review date
- **Current Performance Levels** - Academic and functional performance data
- **Annual Goals** - IEP goals and objectives
- **Accommodations & Modifications** - Special accommodations provided
- **Related Services** - Speech, OT, counseling, etc.
- **Transition Planning** - Post-secondary goals and transition services

## Installation

### Prerequisites
- PHP 7.2+ (Laravel comes with PHP 7.4+)
- Web server running (Laragon includes Apache)

### Setup Steps

1. **Copy files to Laragon**
   ```bash
   # Copy the analyzer folder to your Laragon www directory
   C:\laragon\www\analyzer\
   ```

2. **Ensure upload directory exists**
   ```bash
   mkdir C:\laragon\www\analyzer\uploads
   chmod 755 C:\laragon\www\analyzer\uploads
   ```

3. **Access the application**
   - Open your browser and go to: `http://localhost/analyzer`
   - Or: `http://yoursite.local/analyzer` (if configured in Laragon)

## Usage

### Basic Workflow

1. **Upload PDF**
   - Drag and drop an IEP PDF form into the upload area
   - Or click to browse and select a file
   - Wait for extraction (usually 1-2 seconds)

2. **View Results**
   - Student information appears in the summary card
   - Click section headers to expand/collapse categories
   - Scroll through all extracted data

3. **Export Data**
   - Click "Export as JSON" to download structured data
   - Click "Print Friendly" for printer-optimized view
   - Upload another form anytime

### File Structure

```
analyzer/
├── index.php           # Main upload and display interface
├── extract.php         # PHP backend for PDF parsing
├── uploads/           # Temporary PDF storage (auto-deleted)
├── assets/
│   ├── style.css      # Styling
│   └── app.js         # Frontend logic
└── README.md          # This file
```

## How It Works

### PDF Extraction Process

1. **File Upload** - User uploads PDF form
2. **Validation** - Checks file type and size (max 10MB)
3. **Parsing** - Extracts form field data from PDF structure
4. **Organization** - Maps fields to IEP categories
5. **Display** - Renders organized data in UI
6. **Cleanup** - Deletes temporary uploaded file

### Data Mapping

Common IEP field names are automatically mapped:
- `student_name` → Student Info
- `disability_category` → Disability Category
- `annual_goals` → Annual Goals
- `accommodations` → Accommodations
- etc.

Unmapped fields are automatically categorized based on keywords in field names.

## Customization

### Adding More Field Mappings

Edit `extract.php`, function `organizePDFData()`, section `$fieldMap`:

```php
$fieldMap = [
    'your_field_name' => ['section', 'field'],
    'another_field' => ['section', 'field'],
];
```

### Styling

All styles are in `assets/style.css`. Key color scheme:
- Primary: `#667eea` (purple)
- Secondary: `#764ba2` (darker purple)
- Accent: `#f0f0f0` (light gray)

### Adding New Sections

In `index.php`, add a new collapsible section:

```html
<section class="collapsible-section">
    <button class="section-header" data-section="newsection">
        <span class="toggle-icon">▶</span>
        <h3>New Section Title</h3>
    </button>
    <div class="section-content" id="newsection" style="display: none;">
        <div id="newsectionContent" class="content-empty">No data</div>
    </div>
</section>
```

Then in `assets/app.js`, add a display method:

```javascript
displayNewSection(data) {
    const container = document.getElementById('newsectionContent');
    // ... populate with data
}
```

## Troubleshooting

### PDF Upload Fails

- **"File must be a PDF"** - Ensure file is actually a PDF format
- **"File size exceeds 10MB"** - Reduce PDF file size or increase limit in `extract.php`
- **No data extracted** - PDF might be image-based or have unusual field structure

### Server Issues

- **"Cannot find extract.php"** - Check file paths and ensure all files are uploaded
- **Permission denied on uploads folder** - Run `chmod 755 uploads` on the folder
- **Blank page** - Check PHP error logs in Laragon

## Performance Notes

- File extraction is typically fast (< 2 seconds)
- Uploads are automatically deleted after processing
- No data is stored permanently unless exported

## Browser Support

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Future Enhancements

- [ ] Compare multiple IEP forms
- [ ] Template library for different IEP formats
- [ ] Batch processing (multiple PDFs)
- [ ] Database storage option
- [ ] Multi-user authentication
- [ ] IEP progress tracking
- [ ] Goal monitoring dashboard

## License

Use freely for educational purposes.

## Support

For issues or questions:
1. Check the troubleshooting section
2. Review browser console for error messages
3. Check PHP error logs

---

**Last Updated:** November 2025  
**Version:** 1.0
