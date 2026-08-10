<?php
    $trainingID = $_GET['id'];

    require_once 'db_connection.php';

    $stmt = $conn->prepare("SELECT * FROM  trainings WHERE training_id=?");
    $stmt->bind_param("s", $trainingID);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $data = $result->fetch_assoc();
        $trainingDays = $data['training_days'];

        for($i = 1; $i <= $trainingDays; $i++){
            $table = "training-$trainingID-$i";
    
            $stmt1 = $conn->prepare("DROP TABLE IF EXISTS `$table`");
            if($stmt1->execute()){
                //echo "Table deleted successfully.";
            } else {
                //echo "Table deletion unsuccessful." . $stmt1->error();
            }
        }
    }

    $stmt2 = $conn->prepare("DELETE FROM trainings WHERE training_id = ?");
    $stmt2->bind_param("s",  $trainingID);
    if($stmt2->execute()){
        $_SESSION['participants'] = "";
        //echo "Row deleted successfully.";
        header('Location: ../pages/main.php');
        exit();
    } else {
        die('Error deleting row: ' . $stmt2->error());
    }

?>