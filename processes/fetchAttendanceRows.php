<?php
include_once("db_connection.php");

header('Content-Type: application/json; charset=utf-8');

$id = $_GET['id'] ?? '';
$days = $_GET['days'] ?? '';

if ($id === '' || $days === '') {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Missing training data.']);
  exit;
}

$trainingTable = "training-$id-$days";
$stmt = $conn->prepare("SELECT participant_id, lastname, firstname, middle_initial, agency, login, logout FROM `$trainingTable` ORDER BY participant_id ASC");
if (!$stmt) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Unable to load attendance.']);
  exit;
}

$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$html = '';
foreach ($rows as $row) {
  $login = !empty($row['login']) ? date("H:i", strtotime($row['login'])) : "";
  $logout = !empty($row['logout']) ? date("H:i", strtotime($row['logout'])) : "";
  $fullName = trim($row['lastname'] . ', ' . $row['firstname'] . ' ' . $row['middle_initial']);
  $search = strtolower(trim($row['participant_id'] . ' ' . $row['lastname'] . ' ' . $row['firstname'] . ' ' . $row['middle_initial'] . ' ' . $row['agency']));
  $html .= '<tr data-search="' . htmlspecialchars($search) . '">';
  $html .= '<td class="text-center">' . htmlspecialchars($row['participant_id']) . '</td>';
  $html .= '<td>' . htmlspecialchars($fullName) . '</td>';
  $html .= '<td class="text-center">' . htmlspecialchars($row['agency']) . '</td>';
  $html .= '<td class="text-center">' . htmlspecialchars($login) . '</td>';
  $html .= '<td class="text-center">' . htmlspecialchars($logout) . '</td>';
  $html .= '</tr>';
}

echo json_encode(['success' => true, 'html' => $html]);
