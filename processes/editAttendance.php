<?php
  include_once 'db_connection.php';

  $trainingID = $_POST['trainingID'];
  $days = $_POST['days'];
  $participantNo = $_POST['participantNo'];
  $inORout = $_POST['inORout'];
  $time = $_POST['time'];

  $trainingTable = "training-".$trainingID."-".$days;

  if($inORout == "in"){
    $stmt = $conn->prepare("UPDATE `$trainingTable` SET login = ? WHERE participant_id = ?");
    $stmt->bind_param("ss", $time, $participantNo);
    if ($stmt->execute()){
      header ("Location: ../pages/viewTraining.php?id=$trainingID");
      exit();
    } else {
      echo "Error: ". $stmt->error;
    }

  } elseif ($inORout == "out"){
    $stmt = $conn->prepare("UPDATE `$trainingTable` SET logout = ? WHERE participant_id = ?");
    $stmt->bind_param("ss", $time, $participantNo);
    if ($stmt->execute()){
      header ("Location: ../pages/viewTraining.php?id=$trainingID");
      exit();
    } else {
      echo "Error: ". $stmt->error;
    }
  } else {
    echo "inORout variable is neither in or out.";
  }

?>