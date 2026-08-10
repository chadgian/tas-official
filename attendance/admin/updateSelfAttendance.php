<?php
// updateSelfAttendance.php
include '../../processes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trainingID'], $_POST['days'])) {
  $trainingID = $_POST['trainingID'];
  $days = $_POST['days'];

  foreach ($days as $dayNumber => $details) {
    $date = $details['date'];
    $login_start = $details['login_start'];
    $login_end = $details['login_end'];
    $logout_start = $details['logout_start'];
    $logout_end = $details['logout_end'];
    $login_pw = $details['login_pw'];
    $logout_pw = $details['logout_pw'];

    if (!empty($details['schedID'])) {
      // Update existing schedule
      $schedID = $details['schedID'];
      $updateStmt = $conn->prepare("UPDATE `_self-attendance-details`
        SET date = ?, login_start = ?, login_end = ?, logout_start = ?, logout_end = ?, login_pw = ?, logout_pw = ?
        WHERE schedID = ?");
      $updateStmt->bind_param("sssssssi", $date, $login_start, $login_end, $logout_start, $logout_end, $login_pw, $logout_pw, $schedID);
      $updateStmt->execute();
    } else {
      // Insert new schedule
      $insertStmt = $conn->prepare("INSERT INTO `_self-attendance-details`
        (trainingID, dayNumber, date, login_start, login_end, logout_start, logout_end, login_pw, logout_pw)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $insertStmt->bind_param("iisssssss", $trainingID, $dayNumber, $date, $login_start, $login_end, $logout_start, $logout_end, $login_pw, $logout_pw);
      $insertStmt->execute();
    }
  }

  header("Location: index.php?training=$trainingID");
  exit();
} else {
  header("Location: adminSelfAttendance.php?error=1");
  exit();
}
