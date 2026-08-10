<?php

include 'db_connection.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$trainingID = $data['trainingID'];
$defaultDay = (int)($data['day'] ?? 1);
$defaultMode = ($data['inorout'] ?? 'in') === 'out' ? 'out' : 'in';
$participants = $data['participants'];

$status = false;
foreach ($participants as $participant) {
  $participantID = (int)($participant['numID'] ?? 0);
  $timestamp = (string)($participant['timestamp'] ?? '');
  $day = (int)($participant['day'] ?? $defaultDay);
  $mode = (($participant['mode'] ?? $defaultMode) === 'out') ? 'out' : 'in';
  if ($participantID <= 0 || $timestamp === '') {
    continue;
  }

  $trainingTable = "training-$trainingID-$day";
  $column = $mode === 'in' ? 'login' : 'logout';
  $saveDataStmt = $conn->prepare("UPDATE `$trainingTable` SET `$column` = ? WHERE participant_id = ?");
  $saveDataStmt->bind_param("si", $timestamp, $participantID);

  if ($saveDataStmt->execute()) {
    $status = true;
  }
}

echo $status ? 'ok' : 'bad request';
