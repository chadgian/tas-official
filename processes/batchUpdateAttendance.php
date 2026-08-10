<?php
session_start();
include 'db_connection.php';

$trainingID = $_POST['trainingID'] ?? '';
$day = (int)($_POST['day'] ?? 1);
$participants = $_POST['participants'] ?? [];

if ($trainingID === '' || $day < 1 || !is_array($participants)) {
  header('Location: ../pages/viewTraining.php?id=' . urlencode($trainingID));
  exit();
}

$trainingTable = "training-$trainingID-$day";

foreach ($participants as $participantID => $data) {
  $login = trim((string)($data['login'] ?? ''));
  $logout = trim((string)($data['logout'] ?? ''));
  $loginValue = $login === '' ? null : $login;
  $logoutValue = $logout === '' ? null : $logout;
  $participantIdInt = (int)$participantID;

  $stmt = $conn->prepare("UPDATE `$trainingTable` SET login = ?, logout = ? WHERE participant_id = ?");
  $stmt->bind_param('ssi', $loginValue, $logoutValue, $participantIdInt);
  $stmt->execute();
}

$_SESSION['attendance_save_success'] = 'Attendance updated successfully.';
header('Location: ../pages/viewTraining.php?page=attendance&day=' . urlencode((string)$day) . '&id=' . urlencode($trainingID));
exit();
