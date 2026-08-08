<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$action = strtolower((string) ($_GET['action'] ?? ''));
$allowedActions = ['news', 'gallery', 'matches', 'standings'];

if (!in_array($action, $allowedActions, true)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit;
}

switch ($action) {
    case 'news':
        echo json_encode(['items' => fetchNews(6)]);
        break;
    case 'gallery':
        echo json_encode(['items' => fetchGallery(6)]);
        break;
    case 'matches':
        echo json_encode(['items' => fetchMatches(6)]);
        break;
    case 'standings':
        echo json_encode(['items' => fetchStandings(10)]);
        break;
}
