<?php
header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=chaleurmatic;charset=utf8", 'admin', 'admin', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Créer la table si elle n'existe pas
    $pdo->exec("CREATE TABLE IF NOT EXISTS blocked_slots (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        type         ENUM('date','weekday','creneau_global') NOT NULL,
        date_value   VARCHAR(10) DEFAULT NULL,
        weekday      TINYINT    DEFAULT NULL,
        creneau      VARCHAR(50) DEFAULT NULL
    )");

    $rows = $pdo->query("SELECT * FROM blocked_slots")->fetchAll(PDO::FETCH_ASSOC);

    $result = ['weekdays' => [], 'creneau_global' => [], 'dates' => []];

    foreach ($rows as $r) {
        if ($r['type'] === 'weekday') {
            $result['weekdays'][] = (int)$r['weekday'];
        } elseif ($r['type'] === 'creneau_global') {
            $result['creneau_global'][] = $r['creneau'];
        } elseif ($r['type'] === 'date') {
            $d = $r['date_value'];
            if (!isset($result['dates'][$d])) $result['dates'][$d] = [];
            $result['dates'][$d][] = $r['creneau']; // null = journée entière
        }
    }

    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['weekdays' => [], 'creneau_global' => [], 'dates' => []]);
}
