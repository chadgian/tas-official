<?php
session_start();
include 'db_connection.php';

$trainingID = $_POST['trainingID'] ?? '';
$participantID = $_POST['participantID'] ?? '';

if ($trainingID === '' || $participantID === '') {
  header('Location: ../pages/viewTraining.php?id=' . urlencode($trainingID));
  exit();
}

$stmtTraining = $conn->prepare("SELECT training_days FROM trainings WHERE training_id = ? LIMIT 1");
$stmtTraining->bind_param('s', $trainingID);
$stmtTraining->execute();
$training = $stmtTraining->get_result()->fetch_assoc();
$trainingDays = (int)($training['training_days'] ?? 0);

for ($day = 1; $day <= $trainingDays; $day++) {
  $trainingTable = "training-$trainingID-$day";
  $stmt = $conn->prepare("DELETE FROM `$trainingTable` WHERE participant_id = ?");
  $stmt->bind_param('s', $participantID);
  $stmt->execute();
}

header('Location: ../pages/batchEditParticipants.php?id=' . urlencode($trainingID));
exit();
