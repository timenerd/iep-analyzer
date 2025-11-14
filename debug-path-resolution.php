<?php
/**
 * Debug Script: Path Resolution for .env Files
 * Use this to verify that /at/ and /analyzer/ resolve the same .env file
 */

header('Content-Type: application/json');

$debug = [];

// Get the real project root
$projectRoot = realpath(__DIR__ . '/../../');
if (!$projectRoot) {
    $projectRoot = dirname(dirname(__DIR__));
}

$debug['request_url'] = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$debug['__DIR__'] = __DIR__;
$debug['realpath_result'] = realpath(__DIR__ . '/../../');
$debug['calculated_project_root'] = $projectRoot;
$debug['request_method'] = $_SERVER['REQUEST_METHOD'];
$debug['php_self'] = $_SERVER['PHP_SELF'];

// Check all possible .env paths
$envPaths = [
    $projectRoot . '/.env' => 'Project Root',
    __DIR__ . '/../../.env' => 'Two levels up',
    __DIR__ . '/../.env' => 'One level up',
    __DIR__ . '/.env' => 'Same directory',
];

$debug['env_search_paths'] = [];

foreach ($envPaths as $path => $description) {
    $exists = file_exists($path);
    $realPath = $exists ? realpath($path) : null;
    
    $debug['env_search_paths'][] = [
        'description' => $description,
        'path' => $path,
        'exists' => $exists,
        'real_path' => $realPath,
        'size' => $exists ? filesize($path) : 0,
    ];
    
    if ($exists) {
        $debug['first_found_env'] = [
            'path' => $path,
            'description' => $description,
            'real_path' => $realPath,
        ];
        break;
    }
}

// Check environment variables
$debug['environment_variables'] = [
    'CLAUDE_API_KEY' => [
        'set' => !empty(getenv('CLAUDE_API_KEY')),
        'value_length' => strlen(getenv('CLAUDE_API_KEY') ?? ''),
    ],
];

// Compare access methods
if (strpos($_SERVER['REQUEST_URI'], '/at/') !== false) {
    $debug['access_method'] = 'via /at/ alias';
} elseif (strpos($_SERVER['REQUEST_URI'], '/analyzer/') !== false) {
    $debug['access_method'] = 'via /analyzer/ direct';
} else {
    $debug['access_method'] = 'unknown';
}

// Return as JSON
echo json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>

