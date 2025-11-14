# Quick Start Guide - IEP Analyzer

## Get It Running in 5 Minutes

### Step 1: Copy Files to Laragon
```
Copy all files from the analyzer folder to:
C:\laragon\www\analyzer\
```

Directory structure should look like:
```
C:\laragon\www\analyzer\
├── index.php
├── extract.php
├── README.md
├── .gitignore
├── uploads\
└── assets\
    ├── style.css
    └── app.js
```

### Step 2: Start Laragon
1. Open Laragon
2. Click "Start All" (if not already running)
3. Should see Apache and MySQL running

### Step 3: Open in Browser
Navigate to: **http://localhost/analyzer**

That's it! You're ready to go.

---

## First Upload Test

1. Upload a test IEP PDF
2. Wait for extraction
3. You should see:
   - Student information card
   - Collapsible sections
   - Extracted data

If you see "No data extracted" for most sections, it might mean:
- The PDF uses different field names (we can customize)
- The PDF is image-based (not text-based)
- Form structure is unusual

---

## Common Tasks

### Change Upload Location
Edit `extract.php`, line ~40:
```php
$uploadDir = __DIR__ . '/uploads/';  // Change this path
```

### Increase File Size Limit
Edit `extract.php`, line ~27:
```php
if ($file['size'] > 10 * 1024 * 1024) {  // 10MB - change this
```

### Add New Field Mapping
Edit `extract.php`, look for `$fieldMap` array (~75), add:
```php
'your_field_name' => ['section', 'field'],
```

### Change Color Scheme
Edit `assets/style.css`, find:
```css
#667eea (primary purple)
#764ba2 (secondary purple)
#f0f0f0 (light gray)
```
Replace with your colors.

---

## Testing Without Real PDFs

For testing the UI without actual PDFs:
1. Edit `extract.php` to return mock data
2. Or create a test file that bypasses PDF processing

Example mock response in `extract.php`:
```php
// Add this at the very top for testing
if ($_GET['test'] ?? false) {
    echo json_encode([
        'success' => true,
        'data' => [
            'student' => [
                'name' => 'John Doe',
                'dob' => '1/15/2015',
                'grade' => '3rd',
                'id' => '12345'
            ],
            // ... etc
        ]
    ]);
    exit;
}
```

Then access: `http://localhost/analyzer/extract.php?test=1`

---

## Customizing for Your IEP Format

Different states and districts use different IEP forms. To customize:

1. **Identify field names** - Upload a PDF, check "All Extracted Fields" section
2. **Update field mapping** - Add new mappings in `extract.php` `$fieldMap`
3. **Test** - Upload PDF again and verify categorization

Example - if your district calls it "Primary_Disability":
```php
'primary_disability' => ['disability', 'category'],
```

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Blank page | Check PHP error log in Laragon, refresh browser |
| Upload button not working | Check uploads/ folder permissions |
| No data showing | PDF might be image-based or have custom fields |
| File won't upload | Check file size limit or file type |

---

## Next Steps

- [ ] Test with real IEP forms
- [ ] Customize field mappings for your district's format
- [ ] Add your school/district branding
- [ ] Share with your team

---

Need help? Check README.md for more details or troubleshooting section.
