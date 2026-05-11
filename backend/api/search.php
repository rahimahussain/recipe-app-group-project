<?php
/**
 * GET /api/search.php
 * Quick search endpoint (redirects to recipes with query)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../models/Recipe.php';

try {
    $query = $_GET['q'] ?? '';

    $recipeModel = new Recipe();
    $result = $recipeModel->search([
        'q' => $query,
        'limit' => 20
    ]);

    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Search failed.']);
}