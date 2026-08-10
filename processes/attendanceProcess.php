<?php
    include_once 'db_connection.php';
    date_default_timezone_set('Asia/Manila');

    $result = json_decode(file_get_contents('php://input'), true)['result'];


    $nameResult = explode(':', $result)[2];
    $participantID = explode(':', $result)[1];
    //echo "Your Name: ".  $nameResult ."<br>";
    
    $trainingID = json_decode(file_get_contents('php://input'), true)['trainingID'];
    $trainingDay = json_decode(file_get_contents('php://input'), true)['days'];
    $trainingInOROut = json_decode(file_get_contents('php://input'), true)['inORout'];

    $trainingTable = "training-".$trainingID."-".$trainingDay;
    $currentTime = date("H:i:s");
    // $currentTime = $currentTime + ""
    //echo $currentTime . "<br>" . $trainingTable ."<br>" . $trainingTime . "<br>";

        if ($trainingInOROut === "in"){
            $query = "UPDATE `$trainingTable` SET login='$currentTime' WHERE participant_id = ?";
            //echo $query;
            $stmt = $conn->prepare($query);
            $stmt->bind_param('s', $participantID);
            $stmt->execute();
            echo "success";
            exit();
        } else {
            $query = "UPDATE `$trainingTable` SET logout='$currentTime' WHERE participant_id = ?";
            //echo $query;
            $stmt = $conn->prepare($query);
            $stmt->bind_param('s', $participantID);
            $stmt->execute();
            echo "success";
            exit();
        }

    // $stmt1 = $conn->prepare("SELECT * FROM `$trainingTable` WHERE participant_name = ?");
    // $stmt1->bind_param('s', $participantName);
    // $stmt1->execute();
    // $result1 = $stmt1->get_result();

?>
<script src="../script/jquery-3.6.0.min.js"></script>
<script>
    var socket  = new WebSocket('ws://localhost:8080');
    socket.send("attendance-updated");
</script>