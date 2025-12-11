<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Load configuration
require_once __DIR__ . '/../inc/api_config.inc.php';

// 1. Verify API Key
if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) {
    die(json_encode(['error' => 'API key not configured in api_config.inc.php']));
}

// 2. Check if file_get_contents is allowed
if (!ini_get('allow_url_fopen')) {
    die(json_encode(['error' => 'file_get_contents() is disabled on this server (allow_url_fopen=Off)']));
}

// 3. Prepare the request
$requestData = [
    'model' => 'gpt-3.5-turbo',
    'messages' => [
        ['role' => 'user', 'content' => 'Hello!']
    ],
    'max_tokens' => 100
];

$options = [
    'http' => [
        'method' => 'POST',
        'header' => implode("\r\n", [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY,
            'Connection: close'
        ]),
        'content' => json_encode($requestData),
        'timeout' => 30,  // 30-second timeout
        'ignore_errors' => true  // To get response even on HTTP errors
    ],
    'ssl' => [
        'verify_peer' => false,  // Disable for testing (enable in production)
        'verify_peer_name' => false
    ]
];

// 4. Make the request
$context = stream_context_create($options);
$response = @file_get_contents(
    'https://api.openai.com/v1/chat/completions',
    false,
    $context
);

// 5. Handle the response
header('Content-Type: application/json');

if ($response === false) {
    // Get the last error
    $error = error_get_last();
    die(json_encode([
        'error' => 'API request failed',
        'details' => $error['message'] ?? 'Unknown error'
    ]));
}

// 6. Validate the response
$decoded = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die(json_encode([
        'error' => 'Invalid JSON response',
        'raw_response' => $response
    ]));
}

// 7. Output the successful response
echo $response;
?>