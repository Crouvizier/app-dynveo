<?php
// includes/analytics.php
require_once __DIR__ . '/../config/database.php';

class Analytics {

    public static function track($type, $targetId = null) {
        $pdo = getDB();

        // Anonymisation IP (On masque le dernier bloc)
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $anonymizedIp = preg_replace('/(\d+)\.(\d+)\.(\d+)\.(\d+)/', '$1.$2.$3.0', $ip);

        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        $stmt = $pdo->prepare("INSERT INTO analytics (type, target_id, user_ip, user_agent) VALUES (?, ?, ?, ?)");
        $stmt->execute([$type, $targetId, $anonymizedIp, $ua]);
    }

    public static function getStats($days = 30) {
        $pdo = getDB();

        // Stats Globales
        $stats = [];

        // Total visites
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM analytics WHERE type='visit'");
        $stats['total_visits'] = $stmt->fetch()['total'];

        // Clics par site (Top 5)
        $stmt = $pdo->query("
            SELECT s.name, COUNT(a.id) as clicks 
            FROM analytics a 
            JOIN sites s ON a.target_id = s.id 
            WHERE a.type = 'click_site' 
            GROUP BY s.id 
            ORDER BY clicks DESC 
            LIMIT 5
        ");
        $stats['top_sites'] = $stmt->fetchAll();

        return $stats;
    }
}
?>