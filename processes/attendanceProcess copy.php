<?php
    include_once 'db_connection.php';
    date_default_timezone_set('Asia/Manila');

    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        if ($_POST['name-result'] != "") {
            $nameResult = explode(':', $_POST['name-result'])[2];
            $participantID = explode(':', $_POST['name-result'])[1];
            //echo "Your Name: ".  $nameResult ."<br>";
            
            $trainingID = $_POST['training'];
            $trainingDay = $_POST['days'];
            $trainingInOROut = $_POST['inORout'];
            $participantName = $_POST['name-result'];

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
                    header("Location: {$_SERVER['HTTP_REFERER']}");
                    exit();
                } else {
                    $query = "UPDATE `$trainingTable` SET logout='$currentTime' WHERE participant_id = ?";
                    //echo $query;
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param('s', $participantID);
                    $stmt->execute();
                    header("Location: {$_SERVER['HTTP_REFERER']}");
                    exit();
                }

            // $stmt1 = $conn->prepare("SELECT * FROM `$trainingTable` WHERE participant_name = ?");
            // $stmt1->bind_param('s', $participantName);
            // $stmt1->execute();
            // $result1 = $stmt1->get_result();



        } else {
            $scan = "Please scan first!";
            header('Location: ../scan.php?scan=' . urlencode($scan));
            exit();
        }
    } else {
        //echo "Form not submitted.";
    }

?>
<script src="../script/jquery-3.6.0.min.js"></script>
<script>
    var socket  = new WebSocket('ws://localhost:8080');
    socket.send("attendance-updated");
</script>