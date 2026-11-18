<?php
header('Content-Type: application/json');
require 'db.php';

// Get the 5 most recent scans, newest first
$result = $conn->query('SELECT input_text, score, risk_level, scanned_at FROM scans ORDER BY id DESC LIMIT 5');

$scans = [];
while ($row = $result->fetch_assoc()) {
    // Shorten long text so the list stays tidy
    if (strlen($row['input_text']) > 60) {
        $row['input_text'] = substr($row['input_text'], 0, 60) . '...';
    }
    $scans[] = $row;
}

echo json_encode($scans);