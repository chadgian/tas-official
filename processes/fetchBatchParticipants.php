<?php
include 'db_connection.php';

header('Content-Type: application/json; charset=utf-8');

$trainingID = $_GET['id'] ?? '';
if ($trainingID === '') {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Missing training ID.']);
  exit;
}

$trainingTable = "training-$trainingID-1";
$stmtParticipants = $conn->prepare("SELECT participant_id, lastname, firstname, middle_initial, agency FROM `$trainingTable` ORDER BY participant_id ASC");
if (!$stmtParticipants) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Unable to load participants.']);
  exit;
}

$stmtParticipants->execute();
$participants = $stmtParticipants->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
  'success' => true,
  'participants' => $participants
]);
