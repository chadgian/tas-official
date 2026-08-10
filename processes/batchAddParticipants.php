<?php
include 'db_connection.php';

$trainingID = $_POST['trainingID'] ?? '';
$participants = $_POST['participants'] ?? [];

if ($trainingID === '' || !is_array($participants)) {
  header('Location: ../pages/viewTraining.php?id=' . urlencode($trainingID));
  exit();
}

$stmtTraining = $conn->prepare("SELECT training_days FROM trainings WHERE training_id = ? LIMIT 1");
$stmtTraining->bind_param('s', $trainingID);
$stmtTraining->execute();
$training = $stmtTraining->get_result()->fetch_assoc();
$trainingDays = (int)($training['training_days'] ?? 0);

$trainingTable = "training-$trainingID-1";
$stmtLast = $conn->prepare("SELECT participant_id FROM `$trainingTable` ORDER BY participant_id DESC LIMIT 1");
$stmtLast->execute();
$lastRow = $stmtLast->get_result()->fetch_assoc();
$nextParticipantId = isset($lastRow['participant_id']) ? ((int)$lastRow['participant_id'] + 1) : 1;

$addedCount = 0;

foreach ($participants as $data) {
  $lastname = trim((string)($data['lastname'] ?? ''));
  $firstname = trim((string)($data['firstname'] ?? ''));
  $middleInitial = trim((string)($data['middle_initial'] ?? ''));
  $agency = trim((string)($data['agency'] ?? ''));

  if ($lastname === '' && $firstname === '' && $middleInitial === '' && $agency === '') {
    continue;
  }

  if ($lastname === '' || $firstname === '') {
    continue;
  }

  for ($day = 1; $day <= $trainingDays; $day++) {
    $dayTable = "training-$trainingID-$day";
    $stmtInsert = $conn->prepare("INSERT INTO `$dayTable` (participant_id, lastname, firstname, middle_initial, agency) VALUES (?, ?, ?, ?, ?)");
    $participantIdString = (string)$nextParticipantId;
    $stmtInsert->bind_param('sssss', $participantIdString, $lastname, $firstname, $middleInitial, $agency);
    $stmtInsert->execute();
  }

  $nextParticipantId++;
  $addedCount++;
}

header('Location: ../pages/viewTraining.php?id=' . urlencode($trainingID));
exit();
