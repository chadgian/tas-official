<?php
date_default_timezone_set('Asia/Manila');
include "../processes/db_connection.php";

$payload = json_decode(file_get_contents('php://input'), true);
if ($payload && isset($payload['trainingID'], $payload['participants']) && is_array($payload['participants'])) {
  $trainingID = $payload['trainingID'];
  $defaultDay = (int)($payload['day'] ?? 1);
  $defaultMode = ($payload['mode'] ?? 'in') === 'out' ? 'out' : 'in';

  $status = false;
  foreach ($payload['participants'] as $participant) {
    $participantID = (int)($participant['numID'] ?? 0);
    $timestamp = (string)($participant['timestamp'] ?? '');
    $day = (int)($participant['day'] ?? $defaultDay);
    $mode = (($participant['mode'] ?? $defaultMode) === 'out') ? 'out' : 'in';

    if ($participantID <= 0 || $timestamp === '') {
      continue;
    }

    $table = "training-$trainingID-$day";
    $column = $mode === 'in' ? 'login' : 'logout';
    $stmt = $conn->prepare("UPDATE `$table` SET `$column` = ? WHERE participant_id = ?");
    $stmt->bind_param("si", $timestamp, $participantID);
    if ($stmt->execute()) {
      $status = true;
    }
  }

  echo $status ? "ok" : "bad request";
  exit();
}

http_response_code(400);
echo "bad request";
