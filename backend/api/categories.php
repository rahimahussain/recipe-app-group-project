<?php
/**
 * GET /api/categories.php
 * List all recipe categories with recipe counts
 * Matches enterprise schema
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance()->getConnection();

    // Check if single category requested
    $slug = $_GET['slug'] ?? '';

    if ($slug) {
        $stmt = $db->prepare("
            SELECT c.*, COUNT(rc.recipe_id) as recipe_count
            FROM categories c
            LEFT JOIN recipe_categories rc ON c.id = rc.category_id
            WHERE c.slug = :slug
            GROUP BY c.id
        ");
        $stmt->execute([':slug' => $slug]);
        $category = $stmt->fetch();

        if (!$category) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Category not found.']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'category' => $category
        ]);
    } else {
        // List all categories
        $stmt = $db->query("
            SELECT c.*, COUNT(rc.recipe_id) as recipe_count
            FROM categories c
            LEFT JOIN recipe_categories rc ON c.id = rc.category_id
            GROUP BY c.id
            ORDER BY c.name ASC
        ");

        $categories = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'categories' => $categories,
            'total' => count($categories)
        ]);
    }

} catch (Exception $e) {
    error_log("Categories endpoint error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch categories.', 'code' => 'SERVER_ERROR']);
}