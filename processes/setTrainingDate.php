<?php
require_once 'db_connection.php';

$trainingID = $_POST['trainingID'] ?? '';
$trainingStartDate = $_POST['trainingStartDate'] ?? '';

if ($trainingID === '' || $trainingStartDate === '') {
    header('Location: ../pages/main.php');
    exit();
}

$conn->query("ALTER TABLE trainings ADD COLUMN IF NOT EXISTS training_start_date DATE NULL");

$stmt = $conn->prepare("UPDATE trainings SET training_start_date = ? WHERE training_id = ?");
$stmt->bind_param('ss', $trainingStartDate, $trainingID);

if ($stmt->execute()) {
    header("Location: ../pages/viewTraining.php?id=$trainingID");
    exit();
}

echo "Error: " . $stmt->error;
?>
