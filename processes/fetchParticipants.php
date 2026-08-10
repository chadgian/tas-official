<?php
  include 'db_connection.php';

  $trainingID = $_GET['id'];
  $searchValue = '%'.$_GET['search'].'%';

  $trainingTable = "training-$trainingID-1";
  $stmtParticipants = $conn->prepare("SELECT * FROM `$trainingTable` WHERE firstname LIKE ? OR lastname LIKE ? OR agency LIKE ?");
  $stmtParticipants->bind_param('sss', $searchValue, $searchValue, $searchValue);
  $stmtParticipants->execute();
  $resultParticipants = $stmtParticipants->get_result();
  while($dataParticipants = $resultParticipants->fetch_assoc()){
    $participantFName = $dataParticipants['firstname'];
    $participantLName = $dataParticipants['lastname'];
    $participantMI = $dataParticipants['middle_initial'];
    $participantAgency = $dataParticipants['agency'];
    $participantNo = $dataParticipants['participant_id'];
    $idLocation = "../generated_ids/training-$trainingID/$participantNo.jpg";
    $altLocation = "../sources/question-square.svg";
    $idSource = (file_exists($idLocation) ? $idLocation : $altLocation);
    $downloadID = (file_exists($idLocation) ? "'$idLocation' download" : "#");

    echo "
    <tr>
      <td class='text-center'>$participantNo</td>
      <td><div class='d-flex flex-column text-center'><p class='fw-bold my-0 py-0'>$participantLName</p><p class='text-muted my-0 py-0'>$participantFName $participantMI</p></div></td>
      <td class='text-center'>$participantAgency</td>
      <td class='text-center'><a href=$downloadID><img src='$idSource' width='30rem'></a></td>
    </tr>";
  }
?>