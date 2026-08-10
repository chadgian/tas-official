<?php
require_once 'db_connection.php';

$trainingID = $_POST['trainingID'] ?? '';
$guestPassword = $_POST['guestPassword'] ?? '';

if ($trainingID === '' || $guestPassword === '') {
  header('Location: ../pages/viewTraining.php?id=' . urlencode($trainingID));
  exit();
}

$conn->query("ALTER TABLE trainings ADD COLUMN IF NOT EXISTS guest_scanner_token VARCHAR(64) NULL");
$conn->query("ALTER TABLE trainings ADD COLUMN IF NOT EXISTS guest_scanner_password VARCHAR(255) NULL");

$stmt = $conn->prepare("UPDATE trainings SET guest_scanner_password = ? WHERE training_id = ?");
$stmt->bind_param('ss', $guestPassword, $trainingID);
$stmt->execute();

header('Location: ../pages/viewTraining.php?id=' . urlencode($trainingID));
exit();
