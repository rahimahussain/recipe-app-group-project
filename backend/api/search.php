<?php
/**
 * GET /api/search.php
 * Advanced search endpoint with autocomplete support
 * Matches enterprise schema with FULLTEXT search
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../models/Recipe.php';

try {
    $query = trim($_GET['q'] ?? '');
    $suggestions = isset($_GET['suggest']) && $_GET['suggest'] === 'true';
    $limit = min(20, max(1, (int)($_GET['limit'] ?? 10)));

    $recipeModel = new Recipe();

    if (empty($query)) {
        echo json_encode([
            'success' => true,
            'recipes' => [],
            'message' => 'No search query provided.'
        ]);
        exit;
    }

    if ($suggestions) {
        // Autocomplete/search suggestions
        $result = $recipeModel->search([
            'q' => $query,
            'limit' => $limit,
            'sort' => 'newest'
        ]);

        // Format suggestions
        $suggestions = array_map(function($recipe) {
            return [
                'id' => $recipe['id'],
                'title' => $recipe['title'],
                'slug' => $recipe['slug'],
                'image_url' => $recipe['image_url'],
                'type' => 'recipe'
            ];
        }, $result['recipes'] ?? []);

        echo json_encode([
            'success' => true,
            'suggestions' => $suggestions,
            'query' => $query
        ]);
    } else {
        // Full search results
        $result = $recipeModel->search([
            'q' => $query,
            'limit' => $limit,
            'sort' => $_GET['sort'] ?? 'newest'
        ]);

        echo json_encode($result);
    }

} catch (Exception $e) {
    error_log("Search endpoint error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Search failed.', 'code' => 'SERVER_ERROR']);
}