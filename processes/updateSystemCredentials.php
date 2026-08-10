<?php
session_start();
require_once 'db_connection.php';

$currentUsername = $_POST['currentUsername'] ?? '';
$currentPassword = $_POST['currentPassword'] ?? '';
$newUsername = $_POST['newUsername'] ?? '';
$newPassword = $_POST['newPassword'] ?? '';
$trainingID = $_POST['trainingID'] ?? '';

if ($currentUsername === '' || $currentPassword === '' || $newUsername === '' || $newPassword === '') {
  header('Location: ../pages/main.php');
  exit();
}

$currentPasswordHash = md5($currentPassword);
$newPasswordHash = md5($newPassword);

$stmt = $conn->prepare("SELECT id, username FROM users WHERE username = ? AND password = ? LIMIT 1");
$stmt->bind_param('ss', $currentUsername, $currentPasswordHash);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
  $update = $conn->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
  $update->bind_param('ssi', $newUsername, $newPasswordHash, $user['id']);

  if ($update->execute()) {
    $_SESSION['username'] = $newUsername;
    $_SESSION['password'] = $newPasswordHash;
    header('Location: ../pages/viewTraining.php?id=' . urlencode($trainingID));
    exit();
  }
}

header('Location: ../pages/viewTraining.php?id=' . urlencode($trainingID) . '&cred=error');
exit();
