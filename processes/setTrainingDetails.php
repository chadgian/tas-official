<?php
require_once 'db_connection.php';

$trainingID = $_POST['trainingID'] ?? '';
$trainingStartDate = $_POST['trainingStartDate'] ?? '';
$trainingVenue = $_POST['trainingVenue'] ?? '';

if ($trainingID === '' || $trainingStartDate === '' || $trainingVenue === '') {
    header('Location: ../pages/main.php');
    exit();
}

$conn->query("ALTER TABLE trainings ADD COLUMN IF NOT EXISTS training_start_date DATE NULL");
$conn->query("ALTER TABLE trainings ADD COLUMN IF NOT EXISTS training_venue VARCHAR(255) NULL");

$stmt = $conn->prepare("UPDATE trainings SET training_start_date = ?, training_venue = ? WHERE training_id = ?");
$stmt->bind_param('sss', $trainingStartDate, $trainingVenue, $trainingID);

if ($stmt->execute()) {
    header("Location: ../pages/viewTraining.php?id=$trainingID");
    exit();
}

echo "Error: " . $stmt->error;
?>
