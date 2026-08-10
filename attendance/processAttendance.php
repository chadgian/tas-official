<?php
date_default_timezone_set('Asia/Manila');

$currentTime = (new DateTime())->format("H:i:s");

include "../processes/db_connection.php";

$username = $_POST['username'];

$participantID = $_POST['paxID'];
$trainingID = $_POST['trainingID'];

// echo $username;

switch ($username) {
  case 'day1login':
    $table = "training-$trainingID-1";
    $column = "login";
    break;

  case 'day1logout':
    $table = "training-$trainingID-1";
    $column = "logout";
    break;

  case 'day2login':
    $table = "training-$trainingID-2";
    $column = "login";
    break;

  case 'day2logout':
    $table = "training-$trainingID-2";
    $column = "logout";
    break;
  case 'day3login':
    $table = "training-$trainingID-3";
    $column = "login";
    break;

  case 'day3logout':
    $table = "training-$trainingID-3";
    $column = "logout";
    break;
}

$saveAttendance = $conn->prepare("UPDATE `$table` SET $column = ? WHERE participant_id = ?");
$saveAttendance->bind_param("si", $currentTime, $participantID);

if ($saveAttendance->execute()) {
  echo "ok";
}