<?php
header('Content-Type: application/json; charset=utf-8');
// Simple file-backed final-result endpoint (GET/POST)
// - GET returns 200 JSON { payload: [...], createdAt: ... } or 404 if none
// - POST accepts JSON payload (any JSON), creates the file atomically and returns 200+body; returns 409 if already exists

$FILE = __DIR__ . '/final-result.json';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (!file_exists($FILE)) {
        http_response_code(404);
        echo json_encode(['error' => 'no final result']);
        exit;
    }
    $json = file_get_contents($FILE);
    // Return stored JSON as-is
    echo $json;
    exit;
}

if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        http_response_code(400);
        echo json_encode(['error' => 'empty body']);
        exit;
    }

    $data = json_decode($raw, true);
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid json']);
        exit;
    }

    $payload = ['payload' => $data, 'createdAt' => date(DATE_ATOM)];
    $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

    // Attempt an exclusive create. 'x' fails if file exists -- prevents overwrite.
    $fp = @fopen($FILE, 'x');
    if ($fp === false) {
        http_response_code(409);
        echo json_encode(['error' => 'final result already exists']);
        exit;
    }

    fwrite($fp, $encoded);
    fclose($fp);

    // Return stored record
    echo $encoded;
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'method not allowed']);
