<?php
// Vercel Serverless Router
// This file acts as a single entry point to prevent exceeding Vercel's 12-function limit.

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($uri === '/' || $uri === '' || $uri === '/index.php') {
    require __DIR__ . '/dashboard.php';
} elseif (preg_match('/^\/api\/(.+)$/', $uri, $matches)) {
    $file = __DIR__ . '/api/' . $matches[1];
    if (file_exists($file) && is_file($file)) {
        require $file;
    } else {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['error' => 'API endpoint not found', 'file' => $file]);
    }
} elseif (file_exists(__DIR__ . $uri) && is_file(__DIR__ . $uri) && pathinfo($uri, PATHINFO_EXTENSION) === 'php') {
    require __DIR__ . $uri;
} else {
    http_response_code(404);
    echo "404 Not Found";
}
