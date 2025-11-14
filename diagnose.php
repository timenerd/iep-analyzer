<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnose PDF - IEP Analyzer</title>
    <link rel="stylesheet" href="assets/style.css">
    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔍</text></svg>">
    <style>
        .nav-link {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .upload-section {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }
        
        .upload-section input[type="file"] {
            padding: 10px;
            margin: 15px 0;
        }
        
        .upload-section button {
            background: #007acc;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background 0.3s ease;
        }
        
        .upload-section button:hover {
            background: #005a9e;
        }
        
        .results {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }
        
        .section {
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-left: 4px solid #007acc;
            border-radius: 4px;
        }
        
        .section h3 {
            margin-top: 0;
            color: #007acc;
            font-size: 1.1rem;
        }
        
        pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 12px;
            line-height: 1.4;
        }
        
        .stat {
            display: inline-block;
            margin-right: 30px;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #007acc;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
        }
        
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 12px;
            border-radius: 4px;
            margin: 10px 0;
        }
        
        .success {
            background: #d4edda;
            border: 1px solid #28a745;
            padding: 12px;
            border-radius: 4px;
            margin: 10px 0;
        }
        
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 12px;
            border-radius: 4px;
            margin: 10px 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #f0f0f0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav>
        <div class="nav-container">
            <a href="../../" class="nav-brand">
                <span class="nav-brand-icon">📋</span>
                <span>GuideAI</span>
            </a>
            <button class="nav-toggle" id="navToggle">
                <span>☰</span>
            </button>
            <ul class="nav-links" id="navLinks">
                <li><a href="../../" class="nav-link" data-page="home">🏠 Tools</a></li>
                <li class="nav-divider"></li>
                <li><a href="./" class="nav-link" data-page="analyzer">📋 IEP Analyzer</a></li>
                <li class="nav-divider"></li>
                <li><a href="diagnose" class="nav-link" data-page="diagnose">🔍 Diagnose PDF</a></li>
                <li class="nav-divider"></li>
                <li><a href="../al/" class="nav-link" data-page="accommodations">🏠 Accommodations</a></li>
                <li class="nav-divider"></li>
                <li><a href="#" class="nav-link" id="quickGuideLink">📖 Quick Guide</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <header class="header">
            <h1>📊 PDF Diagnostic Tool</h1>
            <p class="subtitle">Upload a PDF to analyze its internal structure and text markers</p>
        </header>

        <main class="main-content">
            <section class="upload-section">
                <form id="uploadForm" enctype="multipart/form-data">
                    <input type="file" id="pdfFile" accept=".pdf" required>
                    <button type="submit">Analyze PDF</button>
                </form>
                <div id="loading" style="display:none; color: #007acc;">
                    <p>⏳ Analyzing PDF structure...</p>
                </div>
            </section>

            <div id="results"></div>
        </main>
    </div>

    <script>
        // Navigation functionality
        document.addEventListener('DOMContentLoaded', () => {
            const navToggle = document.getElementById('navToggle');
            const navLinks = document.getElementById('navLinks');
            const navLinkItems = document.querySelectorAll('.nav-link');

            // Toggle mobile menu
            navToggle.addEventListener('click', () => {
                navLinks.classList.toggle('active');
            });

            // Close menu when a link is clicked
            navLinkItems.forEach(link => {
                link.addEventListener('click', (e) => {
                    // Only close for internal navigation
                    if (link.getAttribute('href') !== '#') {
                        navLinks.classList.remove('active');
                    }
                });
            });

            // Set active nav link based on current page
            const currentPage = window.location.pathname.split('/').pop() || 'index';
            const pageNameOnly = currentPage.replace('.php', '');
            navLinkItems.forEach(link => {
                const href = link.getAttribute('href');
                if (href && (href === pageNameOnly || href === currentPage || (currentPage === '' && href === 'index'))) {
                    link.classList.add('active');
                }
            });

            // Quick guide functionality
            const quickGuideLink = document.getElementById('quickGuideLink');
            if (quickGuideLink) {
                quickGuideLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    alert('📖 Quick Guide:\n\n1. Upload a PDF to analyze\n2. The tool shows PDF structure\n3. Look for text markers (BT/ET, Tj, etc.)\n4. Check recommendations\n\nThis helps identify why extraction might fail!');
                });
            }
        });
    </script>

    <script>
        document.getElementById('uploadForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const file = document.getElementById('pdfFile').files[0];
            if (!file) return;

            document.getElementById('loading').style.display = 'block';
            document.getElementById('results').innerHTML = '';

            const formData = new FormData();
            formData.append('pdf', file);
            formData.append('action', 'diagnose');

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const html = await response.text();
                document.getElementById('results').innerHTML = html;
                document.getElementById('loading').style.display = 'none';
                window.scrollTo(0, document.body.scrollHeight);
            } catch (error) {
                document.getElementById('results').innerHTML = '<div class="error">Error: ' + error.message + '</div>';
                document.getElementById('loading').style.display = 'none';
            }
        });
    </script>
</body>
</html>

<?php

// Handle POST request for PDF analysis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf'])) {
    $file = $_FILES['pdf'];
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo '<div class="error">File upload error</div>';
        exit;
    }

    // Read PDF content
    $content = file_get_contents($file['tmp_name']);
    $size = strlen($content);

    // ===== ANALYSIS =====
    
    echo '<div class="results">';
    echo '<h2>📋 PDF Analysis Results</h2>';
    
    // File info
    echo '<div class="section">';
    echo '<h3>File Information</h3>';
    echo '<div class="stat"><div class="stat-value">' . $file['size'] . '</div><div class="stat-label">File Size (bytes)</div></div>';
    echo '<div class="stat"><div class="stat-value">' . substr_count($content, "\n") . '</div><div class="stat-label">Lines</div></div>';
    echo '</div>';

    // PDF Version
    echo '<div class="section">';
    echo '<h3>PDF Version</h3>';
    if (preg_match('/%PDF-(\d\.\d)/', $content, $matches)) {
        echo '<div class="success">PDF Version: ' . $matches[1] . '</div>';
    } else {
        echo '<div class="warning">Could not detect PDF version</div>';
    }
    echo '</div>';

    // Text markers
    echo '<div class="section">';
    echo '<h3>Text Extraction Markers</h3>';
    
    $markers = [
        'BT' => 'Text block start',
        'ET' => 'Text block end',
        'Tj' => 'Show text string',
        'TJ' => 'Show text with positioning',
        'Td' => 'Text position',
        'TD' => 'Text position with leading',
        'T*' => 'Text move to next line',
        'Tm' => 'Text matrix',
        '/T' => 'Form field name (with /V for value)',
        '/V' => 'Form field value (with /T for name)',
        'stream' => 'Content stream',
        'endstream' => 'Content stream end',
    ];

    $counts = [];
    foreach ($markers as $marker => $description) {
        $count = substr_count($content, $marker);
        $counts[$marker] = $count;
        $icon = $count > 0 ? '✅' : '❌';
        echo '<div class="stat" style="display: block; margin-bottom: 5px;">';
        echo $icon . ' <strong>' . $marker . '</strong>: ' . $count . ' (' . $description . ')';
        echo '</div>';
    }
    echo '</div>';

    // Form fields
    echo '<div class="section">';
    echo '<h3>Form Fields Detection</h3>';
    
    if (preg_match_all('/\/T\s*\((.*?)\)\s*\/V\s*\((.*?)\)/s', $content, $matches)) {
        echo '<div class="success">Found ' . count($matches[0]) . ' form fields</div>';
        echo '<table>';
        echo '<tr><th>Field Name</th><th>Field Value</th></tr>';
        for ($i = 0; $i < min(20, count($matches[0])); $i++) {
            $key = htmlspecialchars(substr($matches[1][$i], 0, 50));
            $value = htmlspecialchars(substr($matches[2][$i], 0, 50));
            echo '<tr><td>' . $key . '</td><td>' . $value . '</td></tr>';
        }
        if (count($matches[0]) > 20) {
            echo '<tr><td colspan="2">... and ' . (count($matches[0]) - 20) . ' more fields</td></tr>';
        }
        echo '</table>';
    } else {
        echo '<div class="warning">No standard form fields found</div>';
    }
    echo '</div>';

    // Text extraction patterns
    echo '<div class="section">';
    echo '<h3>Text Extraction Methods</h3>';

    // Method 1: BT...ET blocks
    if (preg_match_all('/BT\s+(.*?)\s+ET/s', $content, $matches)) {
        echo '<div class="success">Method 1 (BT/ET blocks): ' . count($matches[0]) . ' blocks found</div>';
        if (count($matches[0]) > 0) {
            echo '<p>Sample block (first 200 chars):</p>';
            echo '<pre>' . htmlspecialchars(substr($matches[1][0], 0, 200)) . '...</pre>';
        }
    } else {
        echo '<div class="warning">Method 1 (BT/ET blocks): No matches</div>';
    }

    // Method 2: Form text
    if (preg_match_all('/\/F\d+\s+[\d.]+\s+Tf\s+(.*?)\s+Tj/s', $content, $matches)) {
        echo '<div class="success">Method 2 (Form text): ' . count($matches[0]) . ' matches found</div>';
    } else {
        echo '<div class="warning">Method 2 (Form text): No matches</div>';
    }

    // Method 3: Show text operators
    if (preg_match_all('/\(([^)]*)\)\s*Tj/', $content, $matches)) {
        echo '<div class="success">Method 3 (Tj operators): ' . count($matches[0]) . ' text strings found</div>';
        if (count($matches[0]) > 0) {
            echo '<p>Sample extracted strings:</p>';
            $samples = array_slice($matches[1], 0, 10);
            echo '<pre>' . htmlspecialchars(implode("\n", array_filter($samples, fn($s) => strlen(trim($s)) > 0))) . '</pre>';
        }
    } else {
        echo '<div class="warning">Method 3 (Tj operators): No matches</div>';
    }

    // Method 4: TJ arrays
    if (preg_match_all('/\[(.*?)\]\s*TJ/', $content, $matches)) {
        echo '<div class="success">Method 4 (TJ arrays): ' . count($matches[0]) . ' arrays found</div>';
    } else {
        echo '<div class="warning">Method 4 (TJ arrays): No matches</div>';
    }

    echo '</div>';

    // Content streams
    echo '<div class="section">';
    echo '<h3>Content Streams</h3>';
    
    if (preg_match_all('/stream\s*(.*?)\s*endstream/s', $content, $matches)) {
        echo '<div class="success">Found ' . count($matches[0]) . ' content streams</div>';
        
        $totalSize = 0;
        foreach ($matches[1] as $stream) {
            $totalSize += strlen($stream);
        }
        echo '<p>Total content: ' . $totalSize . ' bytes</p>';
        
        if (count($matches[0]) > 0) {
            echo '<p>First stream sample (first 300 chars):</p>';
            $sample = $matches[1][0];
            // Check if compressed
            if (preg_match('/FlateDecode/', $content)) {
                echo '<div class="warning">⚠️ Content appears to be compressed (FlateDecode)</div>';
                echo '<p>Compressed streams require decompression for text extraction</p>';
            } else {
                echo '<pre>' . htmlspecialchars(substr($sample, 0, 300)) . '...</pre>';
            }
        }
    } else {
        echo '<div class="error">No content streams found</div>';
    }
    echo '</div>';

    // Recommendations
    echo '<div class="section">';
    echo '<h3>📝 Recommendations</h3>';
    
    $recommendations = [];
    
    if ($counts['/T'] > 0) {
        $recommendations[] = '✅ PDF has form fields - Standard extraction should work';
    } else {
        $recommendations[] = '❌ No form fields found';
    }
    
    if ($counts['BT'] > 0) {
        $recommendations[] = '✅ PDF has text blocks (BT/ET) - Content is extractable';
    }
    
    if ($counts['Tj'] > 0) {
        $recommendations[] = '✅ PDF has text operators (Tj) - Text can be extracted';
    }
    
    if (preg_match('/FlateDecode/', $content)) {
        $recommendations[] = '⚠️ Content is compressed - May need decompression';
    }
    
    if (preg_match('/Encrypt/', $content)) {
        $recommendations[] = '⚠️ PDF has encryption - May be password protected';
    }
    
    if (count($recommendations) == 0) {
        $recommendations[] = '❓ Unknown PDF structure';
    }
    
    foreach ($recommendations as $rec) {
        echo '<p>' . $rec . '</p>';
    }
    
    echo '</div>';

    // Hex dump of sample
    echo '<div class="section">';
    echo '<h3>Raw Content Sample</h3>';
    echo '<p>First 500 characters of PDF (to understand structure):</p>';
    echo '<pre>' . htmlspecialchars(substr($content, 0, 500)) . '</pre>';
    echo '</div>';

    // Next steps
    echo '<div class="section" style="background: #d4edda; border-left-color: #28a745;">';
    echo '<h3>🚀 Next Steps</h3>';
    
    if ($counts['/T'] > 0) {
        echo '<p>1. Your PDF <strong>has form fields</strong> - the current extraction should work</p>';
        echo '<p>2. Check <strong>Backend Logging</strong> in the application to see why they\'re not being found</p>';
        echo '<p>3. Field names may not be in the fieldMap - we may need to add them</p>';
    } elseif ($counts['Tj'] > 0 || $counts['BT'] > 0) {
        echo '<p>1. Your PDF <strong>has text content</strong> but uses different markers</p>';
        echo '<p>2. We need to <strong>update the regex patterns</strong> in extract.php</p>';
        echo '<p>3. Current patterns look for BT/ET and /T/V, but your PDF may use other structures</p>';
    } else {
        echo '<p>1. PDF structure is <strong>unusual or compressed</strong></p>';
        echo '<p>2. May need specialized extraction or decompression</p>';
    }
    
    echo '</div>';
    
    echo '</div>';
    exit;
}
?>
