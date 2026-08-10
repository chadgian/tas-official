<?php

  include 'db_connection.php';

  $stmt = $conn->prepare("SELECT * FROM trainings ORDER BY training_id DESC");
  $stmt->execute();
  $result = $stmt->get_result();

  $jsonData  = array();

  if ($result->num_rows > 0){
    while ($data = $result->fetch_assoc()){
      $trainingName = $data['training_name'];
      $trainingMonth = $data['training_month'];
      $trainingYear = $data['training_year'];
      $trainingID = $data['training_id'];

      $entry = array(
        "Training" => "$trainingName",
        "Date" => "$trainingMonth $trainingYear",
        "ID" => "$trainingID"
      );

      array_push($jsonData, $entry);
    }
  }

  echo json_encode($jsonData);

  // for ($i=0; $i < 5; $i++) { 
  //   echo $i . '<br>';
  // }

?>