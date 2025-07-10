<?php
require '../config/db.php';

$term = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? '';

if (!$term || !in_array($type, ['origin', 'destination'])) {
  echo json_encode([]);
  exit();
}

$stmt = $conn->prepare("SELECT DISTINCT $type FROM routes WHERE $type LIKE ? LIMIT 10");
$likeTerm = "%$term%";
$stmt->bind_param("s", $likeTerm);
$stmt->execute();
$result = $stmt->get_result();

$suggestions = [];
while ($row = $result->fetch_assoc()) {
  $suggestions[] = $row[$type];
}

header('Content-Type: application/json');
echo json_encode($suggestions);
