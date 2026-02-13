<?php
// api.php (Public)
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/analytics.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$pdo = getDB();

try {
    switch ($action) {
        case 'list_sites':
            $stmt = $pdo->query("SELECT id, name, url, description, image_path, cms, version FROM sites ORDER BY sort_order ASC, created_at DESC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'list_projects':
            $stmt = $pdo->query("SELECT id, name, url, description, image_path, tech_stack, status FROM projects ORDER BY sort_order ASC, created_at DESC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'track':
            // Enregistre une visite ou un clic
            $type = $_GET['type'] ?? 'visit'; // visit, click_site, click_project
            $id = $_GET['target_id'] ?? null;
            Analytics::track($type, $id);
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Action inconnue']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
}
?>