<?php
header('Content-Type: application/json');

// Initialize debug logs first (before error handler)
$debugLogs = [];

// Suppress any warnings/notices that might break JSON output
error_reporting(E_ALL);
ini_set('display_errors', 0);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    global $debugLogs;
    $debugLogs[] = "[Backend.ErrorHandler] PHP Error ($errno): $errstr at $errfile:$errline";
    return true; // Don't execute default error handler
});

// Enable error logging
error_log('[Backend] Extract.php loaded');

// Handle file upload and extraction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf'])) {
    $debugLogs[] = "[Backend] Processing POST request";
    
    try {
        $file = $_FILES['pdf'];
        
        // Log file upload
        $debugLogs[] = "[Backend] File upload received: " . json_encode([
            'name' => $file['name'],
            'size' => $file['size'],
            'type' => $file['type'],
            'error' => $file['error']
        ]);
        
        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload failed: error code ' . $file['error']);
        }
        
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new Exception('File size exceeds 10MB limit');
        }
        
        $debugLogs[] = "[Backend] File validation: size check passed (" . $file['size'] . " bytes)";
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $debugLogs[] = "[Backend] MIME type detected: " . $mime;
        
        if ($mime !== 'application/pdf') {
            throw new Exception('File must be a PDF, got: ' . $mime);
        }
        
        $debugLogs[] = "[Backend] File validation: MIME type check passed";
        
        // Create unique filename
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
            $debugLogs[] = "[Backend] Created uploads directory";
        }
        
        $filename = uniqid('iep_') . '.pdf';
        $filepath = $uploadDir . $filename;
        
        $debugLogs[] = "[Backend] Saving file to: " . $filepath;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Failed to save file');
        }
        
        $debugLogs[] = "[Backend] File saved successfully";
        
        // Extract PDF data
        $debugLogs[] = "[Backend] Starting PDF extraction...";
        $extractedData = extractPDFData($filepath, $debugLogs);
        
        $debugLogs[] = "[Backend] PDF extraction complete, extracted " . count($extractedData) . " items";
        $debugLogs[] = "[Backend] Extracted data keys: " . json_encode(array_keys($extractedData));
        
        // ===== LOG RAW CLAUDE DATA =====
        $debugLogs[] = "[Backend] ===== RAW CLAUDE DATA (before organization) =====";
        foreach ($extractedData as $key => $value) {
            if (is_array($value)) {
                $sampleItems = array_slice($value, 0, 2);
                $debugLogs[] = "[Backend.RAWDATA] $key: array[" . count($value) . "] = " . json_encode($sampleItems);
            } else {
                $debugLogs[] = "[Backend.RAWDATA] $key: " . json_encode($value);
            }
        }
        $debugLogs[] = "[Backend] ===== END RAW DATA =====";
        
        // Organize data into sections
        $debugLogs[] = "[Backend] Organizing extracted data into sections...";
        $organizedData = organizePDFData($extractedData, $debugLogs);
        
        $debugLogs[] = "[Backend] Data organization complete";
        
        // Analyze for pain points
        $debugLogs[] = "[Backend] Analyzing IEP for pain points...";
        $painPoints = analyzePainPoints($organizedData, $debugLogs);
        $organizedData['pain_points'] = $painPoints;
        
        $debugLogs[] = "[Backend] Pain points analysis complete - found " . count($painPoints) . " issues";
        
        // Clean up uploaded file
        unlink($filepath);
        $debugLogs[] = "[Backend] Temporary file deleted";
        
        echo json_encode([
            'success' => true,
            'data' => $organizedData,
            'debug' => $debugLogs
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        $errorLog = "[Backend] Error: " . $e->getMessage();
        $debugLogs[] = $errorLog;
        
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'debug' => $debugLogs
        ]);
    } catch (Throwable $t) {
        http_response_code(500);
        $debugLogs[] = "[Backend] Throwable caught: " . $t->getMessage();
        
        echo json_encode([
            'success' => false,
            'error' => 'Server error: ' . $t->getMessage(),
            'debug' => $debugLogs
        ]);
    }
    exit;
}

/**
 * Organize extracted fields into IEP sections
 */
function organizePDFData($extractedData, &$debugLogs) {
    $debugLogs[] = "[Backend.organizePDFData] Starting organization with " . count($extractedData) . " extracted items";
    
    $organized = [
        'student' => [
            'name' => '',
            'dob' => '',
            'grade' => '',
            'id' => '',
        ],
        'iep_dates' => [
            'meeting_date' => '',
            'effective_date' => '',
            'review_date' => '',
        ],
        'disability' => [
            'category' => '',
            'evaluation_date' => '',
        ],
        'performance' => [],
        'goals' => [],
        'accommodations' => [],
        'services' => [],
        'transition' => [],
        'pain_points' => [],
    ];
    
    $fieldMap = [
        'student_name' => ['student', 'name'],
        'name' => ['student', 'name'],
        'dob' => ['student', 'dob'],
        'date_of_birth' => ['student', 'dob'],
        'grade' => ['student', 'grade'],
        'student_id' => ['student', 'id'],
        'disability_category' => ['disability', 'category'],
        'iep_meeting_date' => ['iep_dates', 'meeting_date'],
        'effective_date' => ['iep_dates', 'effective_date'],
        'next_review_date' => ['iep_dates', 'review_date'],
        'review_date' => ['iep_dates', 'review_date'],
    ];
    
    if (is_array($extractedData) && count($extractedData) > 0) {
        $isAssociative = !is_numeric(key($extractedData));
        
        if ($isAssociative) {
            $mappedCount = 0;
            $directArrayCount = 0;
            
            foreach ($extractedData as $key => $value) {
                $lowerKey = strtolower(str_replace([' ', '_', '-'], '_', $key));
                
                // Handle arrays from Claude directly
                if ($lowerKey === 'performance_levels' && is_array($value)) {
                    $debugLogs[] = "[Backend.organizePDFData] Processing 'performance_levels', raw count: " . count($value);
                    $debugLogs[] = "[Backend.organizePDFData] Raw values: " . json_encode(array_slice($value, 0, 3));
                    $organized['performance'] = array_filter(array_map(function($item) {
                        return is_string($item) ? trim($item) : (is_array($item) ? trim(implode(' ', $item)) : trim((string)$item));
                    }, $value), function($item) { return !empty($item); });
                    $directArrayCount++;
                    $debugLogs[] = "[Backend.organizePDFData] After filter 'performance_levels': " . count($organized['performance']) . " items";
                } 
                elseif ($lowerKey === 'goals' && is_array($value)) {
                    $debugLogs[] = "[Backend.organizePDFData] Processing 'goals', raw count: " . count($value);
                    $debugLogs[] = "[Backend.organizePDFData] Raw values: " . json_encode(array_slice($value, 0, 3));
                    $organized['goals'] = array_filter(array_map(function($item) {
                        return is_string($item) ? trim($item) : (is_array($item) ? trim(implode(' ', $item)) : trim((string)$item));
                    }, $value), function($item) { return !empty($item); });
                    $directArrayCount++;
                    $debugLogs[] = "[Backend.organizePDFData] After filter 'goals': " . count($organized['goals']) . " items";
                } 
                elseif ($lowerKey === 'accommodations' && is_array($value)) {
                    $debugLogs[] = "[Backend.organizePDFData] Processing 'accommodations', raw count: " . count($value);
                    $debugLogs[] = "[Backend.organizePDFData] Raw values: " . json_encode(array_slice($value, 0, 3));
                    $organized['accommodations'] = array_filter(array_map(function($item) {
                        return is_string($item) ? trim($item) : (is_array($item) ? trim(implode(' ', $item)) : trim((string)$item));
                    }, $value), function($item) { return !empty($item); });
                    $directArrayCount++;
                    $debugLogs[] = "[Backend.organizePDFData] After filter 'accommodations': " . count($organized['accommodations']) . " items";
                } 
                elseif ($lowerKey === 'services' && is_array($value)) {
                    $debugLogs[] = "[Backend.organizePDFData] Processing 'services', raw count: " . count($value);
                    $organized['services'] = array_filter(array_map(function($item) {
                        return is_string($item) ? trim($item) : (is_array($item) ? trim(implode(' ', $item)) : trim((string)$item));
                    }, $value), function($item) { return !empty($item); });
                    $directArrayCount++;
                    $debugLogs[] = "[Backend.organizePDFData] After filter 'services': " . count($organized['services']) . " items";
                } 
                elseif ($lowerKey === 'transition_planning' && !empty($value)) {
                    $organized['transition'] = is_array($value) ? array_filter($value, function($item) { return !empty($item); }) : ($value ? [$value] : []);
                    $directArrayCount++;
                    $debugLogs[] = "[Backend.organizePDFData] Transition planning: " . count($organized['transition']) . " items";
                } 
                elseif (isset($fieldMap[$lowerKey])) {
                    [$section, $field] = $fieldMap[$lowerKey];
                    $organized[$section][$field] = $value;
                    $mappedCount++;
                }
            }
            
            $debugLogs[] = "[Backend.organizePDFData] Mapped " . $mappedCount . " scalar fields";
            $debugLogs[] = "[Backend.organizePDFData] Processed " . $directArrayCount . " direct arrays";
        }
    }
    
    $debugLogs[] = "[Backend.organizePDFData] Complete - Goals: " . count($organized['goals']) . ", Accommodations: " . count($organized['accommodations']) . ", Services: " . count($organized['services']);
    
    return $organized;
}

/**
 * Extract PDF data using Claude AI
 */
function extractPDFData($filepath, &$debugLogs) {
    $debugLogs[] = "[Backend.extractPDFData] Starting extraction from: " . basename($filepath);
    
    $pdfText = extractTextFromPDF($filepath, $debugLogs);
    
    if (!empty($pdfText)) {
        $debugLogs[] = "[Backend.extractPDFData] Text extracted: " . strlen($pdfText) . " bytes";
        $fields = extractWithClaude($pdfText, $debugLogs);
    } else {
        $debugLogs[] = "[Backend.extractPDFData] WARNING: No text extracted";
        $fields = [];
    }
    
    $debugLogs[] = "[Backend.extractPDFData] Total items: " . count($fields);
    return $fields;
}

/**
 * Extract text from PDF
 */
function extractTextFromPDF($filepath, &$debugLogs) {
    $debugLogs[] = "[Backend.extractTextFromPDF] Starting text extraction";
    
    $text = '';
    
    try {
        $possiblePaths = [
            __DIR__ . '/vendor/autoload.php',
            __DIR__ . '/../vendor/autoload.php',
            __DIR__ . '/../../vendor/autoload.php',
        ];
        
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                require_once $path;
                if (class_exists('Smalot\PdfParser\Parser')) {
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile($filepath);
                    $pages = $pdf->getPages();
                    foreach ($pages as $page) {
                        $text .= $page->getText() . "\n";
                    }
                    $debugLogs[] = "[Backend.extractTextFromPDF] Extracted " . strlen($text) . " bytes using pdfparser";
                    return $text;
                }
                break;
            }
        }
    } catch (Exception $e) {
        $debugLogs[] = "[Backend.extractTextFromPDF] pdfparser error: " . $e->getMessage();
    }
    
    $debugLogs[] = "[Backend.extractTextFromPDF] Using fallback text extraction";
    
    $content = file_get_contents($filepath);
    
    if (strpos($content, '/FlateDecode') !== false) {
        if (preg_match_all('/stream\s*\n(.*?)\s*endstream/s', $content, $streams)) {
            foreach ($streams[1] as $stream) {
                if (empty(trim($stream))) continue;
                
                $decompressed = false;
                if (function_exists('gzuncompress') && strlen($stream) > 2 && ord($stream[0]) === 0x78) {
                    $decompressed = @gzuncompress($stream);
                }
                
                if ($decompressed === false && function_exists('gzinflate')) {
                    $decompressed = @gzinflate($stream);
                }
                
                if ($decompressed !== false && strlen($decompressed) > 0) {
                    if (preg_match_all('/\(([^\(\)]{3,})\)/', $decompressed, $matches)) {
                        foreach ($matches[1] as $str) {
                            if (preg_match('/[a-zA-Z0-9\s\.\,\-\/\:\;\'\"]/i', $str)) {
                                $text .= trim($str) . ' ';
                            }
                        }
                    }
                }
            }
        }
    }
    
    if (strlen($text) < 100) {
        if (preg_match_all('/\(([^\(\)]{3,})\)/', $content, $matches)) {
            foreach ($matches[1] as $str) {
                if (preg_match('/[a-zA-Z0-9\s\.\,\-\/\:\;\'\"]/i', $str)) {
                    $text .= trim($str) . ' ';
                }
            }
        }
    }
    
    $debugLogs[] = "[Backend.extractTextFromPDF] Extracted " . strlen($text) . " bytes using fallback";
    return $text;
}

/**
 * Use Claude API to extract IEP data
 */
function extractWithClaude($pdfText, &$debugLogs) {
    $debugLogs[] = "[Backend.extractWithClaude] Initializing Claude API";
    $debugLogs[] = "[Backend.extractWithClaude] __DIR__ = " . __DIR__;
    $debugLogs[] = "[Backend.extractWithClaude] DOCUMENT_ROOT = " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Not set');
    
    // Get the real project root, handling both /analyzer and /at aliases
    $projectRoot = realpath(__DIR__ . '/../../');
    if (!$projectRoot) {
        $projectRoot = dirname(dirname(__DIR__));
    }
    
    $envPaths = [
        $projectRoot . '/.env',          // Root of project
        __DIR__ . '/../../.env',
        __DIR__ . '/../.env',
        __DIR__ . '/.env',
    ];
    
    $debugLogs[] = "[Backend.extractWithClaude] Searching for .env in paths: " . json_encode($envPaths);
    
    $envLoaded = false;
    foreach ($envPaths as $envPath) {
        $debugLogs[] = "[Backend.extractWithClaude] Checking: " . $envPath . " (exists: " . (file_exists($envPath) ? 'yes' : 'no') . ")";
        if (file_exists($envPath)) {
            $debugLogs[] = "[Backend.extractWithClaude] ✓ Loading .env from: " . $envPath;
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines !== false) {
                foreach ($lines as $line) {
                    if (strpos(trim($line), '#') === 0) continue;
                    if (strpos($line, '=') !== false) {
                        [$key, $value] = explode('=', $line, 2);
                        $key = trim($key);
                        $value = trim($value, '\'" ');
                        if (!getenv($key)) {
                            putenv("$key=$value");
                        }
                    }
                }
                $envLoaded = true;
                $debugLogs[] = "[Backend.extractWithClaude] .env loaded successfully";
                break;
            }
        }
    }
    
    $apiKey = getenv('CLAUDE_API_KEY');
    if (empty($apiKey)) {
        $debugLogs[] = "[Backend.extractWithClaude] ERROR: CLAUDE_API_KEY not found after checking all paths";
        return [];
    }
    $debugLogs[] = "[Backend.extractWithClaude] ✓ CLAUDE_API_KEY found";
    
    $debugLogs[] = "[Backend.extractWithClaude] API key loaded";
    
    $pdfText = mb_convert_encoding($pdfText, 'UTF-8', 'UTF-8');
    $pdfText = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $pdfText);
    $pdfText = trim($pdfText);
    
    $prompt = "You are an IEP document parser. Extract all information and return ONLY valid JSON.\n\n";
    $prompt .= "CRITICAL: For array fields (performance_levels, goals, accommodations, services), return actual items found in the document, not empty arrays.\n\n";
    $prompt .= "Extract these fields:\n";
    $prompt .= "- student_name: Full name\n";
    $prompt .= "- date_of_birth: DOB\n";
    $prompt .= "- grade: Grade level\n";
    $prompt .= "- disability_category: Array of categories\n";
    $prompt .= "- iep_meeting_date: Meeting date\n";
    $prompt .= "- effective_date: Effective date\n";
    $prompt .= "- review_date: Review date\n";
    $prompt .= "- performance_levels: Array of complete baseline/present level statements. Each item must be a full sentence describing the student's current performance. Minimum 3 items.\n";
    $prompt .= "- goals: Array of complete annual goal statements. Each item must be a full goal statement with observable, measurable criteria. Minimum 3 items.\n";
    $prompt .= "- accommodations: Array of accommodation/modification statements. Each item must be specific. Minimum 3 items.\n";
    $prompt .= "- services: Array of service types (e.g., 'Speech Language Pathology', 'Special Education', 'Occupational Therapy'). Minimum 3 items if available.\n";
    $prompt .= "- transition_planning: Transition/post-secondary goals or planning statements\n\n";
    $prompt .= "Return ONLY this JSON structure with no markdown or explanation:\n{\n";
    $prompt .= "  \"student_name\": \"\",\n";
    $prompt .= "  \"date_of_birth\": \"\",\n";
    $prompt .= "  \"grade\": \"\",\n";
    $prompt .= "  \"disability_category\": [],\n";
    $prompt .= "  \"iep_meeting_date\": \"\",\n";
    $prompt .= "  \"effective_date\": \"\",\n";
    $prompt .= "  \"review_date\": \"\",\n";
    $prompt .= "  \"performance_levels\": [],\n";
    $prompt .= "  \"goals\": [],\n";
    $prompt .= "  \"accommodations\": [],\n";
    $prompt .= "  \"services\": [],\n";
    $prompt .= "  \"transition_planning\": \"\"\n}\n\n";
    $prompt .= "Document text:\n" . substr($pdfText, 0, 15000);
    
    try {
        $url = 'https://api.anthropic.com/v1/messages';
        
        $payload = [
            'model' => 'claude-opus-4-1',
            'max_tokens' => 3000,
            'messages' => [['role' => 'user', 'content' => $prompt]]
        ];
        
        $jsonPayload = json_encode($payload);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
            'content-type: application/json',
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            $debugLogs[] = "[Backend.extractWithClaude] API Error HTTP $httpCode";
            return [];
        }
        
        $responseData = json_decode($response, true);
        
        if (isset($responseData['content'][0]['text'])) {
            $claudeText = $responseData['content'][0]['text'];
            
            if (preg_match('/\{.*\}/s', $claudeText, $jsonMatch)) {
                $extractedData = json_decode($jsonMatch[0], true);
                if (is_array($extractedData)) {
                    $debugLogs[] = "[Backend.extractWithClaude] Successfully parsed Claude response";
                    return $extractedData;
                }
            }
        }
        
        $debugLogs[] = "[Backend.extractWithClaude] Could not parse response";
    } catch (Exception $e) {
        $debugLogs[] = "[Backend.extractWithClaude] Exception: " . $e->getMessage();
    }
    
    return [];
}

/**
 * Analyze IEP for potential pain points using Claude
 */
function analyzePainPoints($organizedData, &$debugLogs) {
    $debugLogs[] = "[Backend.analyzePainPoints] Starting pain points analysis";
    
    // Build context about the IEP
    $iepContext = "Here is the extracted IEP data:\n\n";
    $iepContext .= "STUDENT: " . ($organizedData['student']['name'] ?? 'Not provided') . "\n";
    $iepContext .= "DOB: " . ($organizedData['student']['dob'] ?? 'Not provided') . "\n";
    $iepContext .= "Grade: " . ($organizedData['student']['grade'] ?? 'Not provided') . "\n";
    $iepContext .= "Disability: " . (is_array($organizedData['disability']['category']) ? implode(', ', $organizedData['disability']['category']) : $organizedData['disability']['category']) . "\n\n";
    
    $iepContext .= "IEP DATES:\n";
    $iepContext .= "Meeting Date: " . ($organizedData['iep_dates']['meeting_date'] ?? 'Not provided') . "\n";
    $iepContext .= "Effective Date: " . ($organizedData['iep_dates']['effective_date'] ?? 'Not provided') . "\n";
    $iepContext .= "Review Date: " . ($organizedData['iep_dates']['review_date'] ?? 'Not provided') . "\n\n";
    
    $iepContext .= "PERFORMANCE LEVELS (Baseline):\n";
    if (!empty($organizedData['performance'])) {
        foreach ($organizedData['performance'] as $perf) {
            $iepContext .= "- " . $perf . "\n";
        }
    } else {
        $iepContext .= "- None provided\n";
    }
    $iepContext .= "\n";
    
    $iepContext .= "GOALS:\n";
    if (!empty($organizedData['goals'])) {
        foreach ($organizedData['goals'] as $goal) {
            $iepContext .= "- " . $goal . "\n";
        }
    } else {
        $iepContext .= "- None provided\n";
    }
    $iepContext .= "\n";
    
    $iepContext .= "ACCOMMODATIONS & MODIFICATIONS:\n";
    if (!empty($organizedData['accommodations'])) {
        foreach ($organizedData['accommodations'] as $acc) {
            $iepContext .= "- " . $acc . "\n";
        }
    } else {
        $iepContext .= "- None provided\n";
    }
    $iepContext .= "\n";
    
    $iepContext .= "SERVICES:\n";
    if (!empty($organizedData['services'])) {
        foreach ($organizedData['services'] as $svc) {
            $iepContext .= "- " . $svc . "\n";
        }
    } else {
        $iepContext .= "- None provided\n";
    }
    $iepContext .= "\n";
    
    $iepContext .= "TRANSITION PLANNING:\n";
    if (!empty($organizedData['transition'])) {
        if (is_array($organizedData['transition'])) {
            foreach ($organizedData['transition'] as $trans) {
                $iepContext .= "- " . $trans . "\n";
            }
        } else {
            $iepContext .= "- " . $organizedData['transition'] . "\n";
        }
    } else {
        $iepContext .= "- None provided\n";
    }
    
    $prompt = "You are an IEP expert analyzing a student's Individualized Education Program for potential issues and pain points that parents or advocates should review.\n\n";
    $prompt .= "Analyze this IEP carefully using the following framework:\n\n";
    $prompt .= "1. GOALS QUALITY: Are the goals appropriate and realistic for the student's disability and current performance levels? Are they measurable with specific criteria?\n\n";
    $prompt .= "2. SERVICE GAPS: Are there adequate services for the student's needs? Are any obvious gaps in support?\n\n";
    $prompt .= "3. MEASURABILITY: Can each goal's progress be objectively measured? Are there specific benchmarks or timelines?\n\n";
    $prompt .= "4. LRE (LEAST RESTRICTIVE ENVIRONMENT): Is the IEP maximizing the student's inclusion in general education? Are services delivered in typical settings when possible?\n\n";
    $prompt .= "5. CLARITY & CONTRADICTIONS: Are there any unclear, vague, or contradictory statements that could be misinterpreted?\n\n";
    $prompt .= $iepContext . "\n";
    $prompt .= "Return a JSON array of pain points. Each item should include:\n";
    $prompt .= "{\n";
    $prompt .= "  \"category\": \"GOALS_QUALITY\" | \"SERVICE_GAPS\" | \"MEASURABILITY\" | \"LRE\" | \"CLARITY\",\n";
    $prompt .= "  \"issue\": \"Brief description of the problem\",\n";
    $prompt .= "  \"detail\": \"Detailed explanation and why this matters\",\n";
    $prompt .= "  \"recommendation\": \"What should be addressed or questioned\"\n";
    $prompt .= "}\n\n";
    $prompt .= "Return ONLY valid JSON array with no markdown, backticks, or explanation. If no issues found, return empty array [].";
    
    try {
        // Get the real project root, handling both /analyzer and /at aliases
        $projectRoot = realpath(__DIR__ . '/../../');
        if (!$projectRoot) {
            $projectRoot = dirname(dirname(__DIR__));
        }
        
        $envPaths = [
            $projectRoot . '/.env',          // Root of project
            __DIR__ . '/../../.env',
            __DIR__ . '/../.env',
            __DIR__ . '/.env',
        ];
        
        $debugLogs[] = "[Backend.analyzePainPoints] Searching for .env in paths: " . json_encode($envPaths);
        
        $apiKey = getenv('CLAUDE_API_KEY');
        if (empty($apiKey)) {
            foreach ($envPaths as $envPath) {
                $debugLogs[] = "[Backend.analyzePainPoints] Checking: " . $envPath . " (exists: " . (file_exists($envPath) ? 'yes' : 'no') . ")";
                if (file_exists($envPath)) {
                    $debugLogs[] = "[Backend.analyzePainPoints] ✓ Loading .env from: " . $envPath;
                    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    if ($lines !== false) {
                        foreach ($lines as $line) {
                            if (strpos(trim($line), '#') === 0) continue;
                            if (strpos($line, '=') !== false) {
                                [$key, $value] = explode('=', $line, 2);
                                $key = trim($key);
                                $value = trim($value, '\'" ');
                                if (!getenv($key)) {
                                    putenv("$key=$value");
                                }
                            }
                        }
                        break;
                    }
                }
            }
            $apiKey = getenv('CLAUDE_API_KEY');
        }
        
        if (empty($apiKey)) {
            $debugLogs[] = "[Backend.analyzePainPoints] ERROR: CLAUDE_API_KEY not found after checking all paths";
            return [];
        }
        $debugLogs[] = "[Backend.analyzePainPoints] ✓ CLAUDE_API_KEY found";
        
        $url = 'https://api.anthropic.com/v1/messages';
        
        $payload = [
            'model' => 'claude-opus-4-1',
            'max_tokens' => 2000,
            'messages' => [['role' => 'user', 'content' => $prompt]]
        ];
        
        $jsonPayload = json_encode($payload);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
            'content-type: application/json',
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            $debugLogs[] = "[Backend.analyzePainPoints] API Error HTTP $httpCode";
            return [];
        }
        
        $responseData = json_decode($response, true);
        
        if (isset($responseData['content'][0]['text'])) {
            $claudeText = $responseData['content'][0]['text'];
            
            if (preg_match('/\[.*\]/s', $claudeText, $jsonMatch)) {
                $painPoints = json_decode($jsonMatch[0], true);
                if (is_array($painPoints)) {
                    $debugLogs[] = "[Backend.analyzePainPoints] Successfully parsed " . count($painPoints) . " pain points";
                    return $painPoints;
                }
            }
        }
        
        $debugLogs[] = "[Backend.analyzePainPoints] Could not parse response";
    } catch (Exception $e) {
        $debugLogs[] = "[Backend.analyzePainPoints] Exception: " . $e->getMessage();
    }
    
    return [];
}

// Handle GET requests
echo json_encode(['status' => 'ready']);
