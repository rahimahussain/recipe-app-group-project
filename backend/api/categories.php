<?php
/**
 * GET /api/categories.php
 * List all recipe categories
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->query("
        SELECT c.*, COUNT(rc.recipe_id) as recipe_count
        FROM categories c
        LEFT JOIN recipe_categories rc ON c.id = rc.category_id
        GROUP BY c.id
        ORDER BY c.name
    ");

    $categories = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch categories.']);
}