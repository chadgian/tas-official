<?php
include __DIR__ ."../../components/processes/db_connection.php";

$getSelfAttendanceDetails = $conn->prepare("SELECT * FROM `_self-attendance-details`");
$getSelfAttendanceDetails->execute();
$selfAttendanceResult = $getSelfAttendanceDetails->get_result();

while ($selfAttendanceData = $selfAttendanceResult->fetch_assoc()) {
  $trainingID = $selfAttendanceData['self_attendance_id'];

  $loginStartTime = $selfAttendanceData['loginStartTime'];
  $loginEndTime = $selfAttendanceData['loginEndTime'];

  $logoutStartTime = $selfAttendanceData['logoutStartTime'];
  $logoutEndTime = $selfAttendanceData['logoutEndTime'];

  $availability = $selfAttendanceData['availability'];
}

$getTrainingDetails = $conn->prepare("SELECT * FROM `training_details` WHERE trainingID = ?");
$getTrainingDetails->bind_param("i", $trainingID);

$getTrainingDetails->execute();

$getTrainingResult = $getTrainingDetails->get_result();

while ($getTrainingData = $getTrainingResult->fetch_assoc()){
  $trainingName = $getTrainingData['trainingName'];
  
}