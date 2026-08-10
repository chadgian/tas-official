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

for ($day = 1; $day <= $trainingDays; $day++) {
  $trainingTable = "training-$trainingID-$day";
  foreach ($participants as $participantID => $data) {
    $lastname = trim((string)($data['lastname'] ?? ''));
    $firstname = trim((string)($data['firstname'] ?? ''));
    $middleInitial = trim((string)($data['middle_initial'] ?? ''));
    $agency = trim((string)($data['agency'] ?? ''));

    $stmt = $conn->prepare("UPDATE `$trainingTable` SET lastname = ?, firstname = ?, middle_initial = ?, agency = ? WHERE participant_id = ?");
    $stmt->bind_param('sssss', $lastname, $firstname, $middleInitial, $agency, $participantID);
    $stmt->execute();
  }
}

header('Location: ../pages/viewTraining.php?id=' . urlencode($trainingID));
exit();
