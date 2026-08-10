<?php
require_once ('../processes/db_connection.php');

$trainingName = $_POST['training-name'];
$trainingMonth = $_POST['training-month'];
$trainingStartDate = $_POST['training-start-date'];
$trainingYear = $_POST['training-year'];
$trainingDays = $_POST['training-days'];

$conn->query("ALTER TABLE trainings ADD COLUMN IF NOT EXISTS training_start_date DATE NULL");
$conn->query("ALTER TABLE trainings ADD COLUMN IF NOT EXISTS training_venue VARCHAR(255) NULL");

$stmt2 = $conn->prepare("INSERT INTO trainings (training_name, training_month, training_start_date, training_year, training_days) VALUES (?, ?, ?, ?, ?)");
$stmt2->bind_param("sssss", $trainingName, $trainingMonth, $trainingStartDate, $trainingYear, $trainingDays);
$stmt2->execute();
$stmt2->close();

$stmt3 = $conn->prepare("SELECT training_id FROM trainings WHERE training_name = ? ORDER BY training_id DESC");
$stmt3->bind_param("s", $trainingName);
$stmt3->execute();
$result2 = $stmt3->get_result();

if ($result2->num_rows > 0) {
    $data = $result2->fetch_assoc();
    $training_id = $data['training_id'];

    for ($i = 0; $i < $trainingDays; $i++) {
        $table_name = 'training-' . $training_id . "-" . $i + 1;
        $stmt4 = $conn->prepare("CREATE TABLE IF NOT EXISTS `$table_name` (
                participant_id INT(50) PRIMARY KEY UNIQUE AUTO_INCREMENT,
                lastname VARCHAR(100) NOT NULL,
                firstname VARCHAR(100) NOT NULL,
                middle_initial VARCHAR(100) NULL,
                agency VARCHAR(100),
                login TIME(6) NULL DEFAULT NULL,
                logout TIME(6) NULL DEFAULT NULL

            )");

        if ($stmt4->execute()) {
            $stmt4->close();
        } else {
            echo "Error: " . $stmt4->error;
        }

    }

    header('Location: ../pages/main.php');
    exit();
} else {
    echo "ERROR!";
}
?>
