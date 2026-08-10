<?php
require '../vendor/autoload.php'; // Include the Composer autoloader
require_once 'db_connection.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if the file was uploaded without errors
    if (isset($_FILES["excelFile"]) && $_FILES["excelFile"]["error"] == UPLOAD_ERR_OK) {

        $trainingID = $_POST['trainingID'];

        $stmt = $conn->prepare("SELECT * FROM trainings WHERE training_id=?");
        $stmt->bind_param("i", $trainingID);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            $trainingDays = $data['training_days'];
            for ($i=1; $i<=$trainingDays; $i++){
                $trainingTable = "training-$trainingID-$i";
                $stmt2 = $conn->prepare("DELETE FROM `$trainingTable`");
                $stmt2->execute();
    
                if($stmt2->error){
                    echo $stmt->error;
                }
                $stmt2->close();
            }
        }

        $targetDir = "../sources/"; // Specify the directory where you want to save the uploaded files
        $targetFile = $targetDir . basename($_FILES["excelFile"]["name"]); // Get the path to the uploaded file

        // Move the uploaded file to the specified directory
        if (move_uploaded_file($_FILES["excelFile"]["tmp_name"], $targetFile)) {
            // echo "The file " . basename($_FILES["excelFile"]["name"]) . " has been uploaded.";

            // Load the Excel file
            $spreadsheet = IOFactory::load($targetFile);
            $sheet = $spreadsheet->getActiveSheet();

            // Flag to skip the first row
            $skipFirstRow = true;

            // Iterate through each row of the Excel file and insert data into the database
            foreach ($sheet->getRowIterator() as $row) {
                // Skip the first row
                if ($skipFirstRow) {
                    $skipFirstRow = false;
                    continue;
                }

                $rowData = [];
                foreach ($row->getCellIterator() as $cell) {
                    $rowData[] = $cell->getValue();
                }

                // Insert data into your database table for each day
                for ($i = 1; $i <= $trainingDays; $i++) {
                    $trainingTable = "training-$trainingID-$i";
                    $sql = "INSERT INTO `$trainingTable` (participant_id, lastname, firstname, middle_initial, agency) VALUES (?, ?, ?, ?, ?)";
                    $stmt1 = $conn->prepare($sql);
                    // $name = str_replace('ñ', 'n', $rowData[1]);
                    $stmt1->bind_param('sssss', $rowData[0], $rowData[1], $rowData[2], $rowData[3], $rowData[4]);
                    try {
                        $stmt1->execute();
                        continue;
                    } catch (Exception $e) {
                        $errorMessage = $e->getMessage();
                        if ($errorMessage == "Column 'participant_name' cannot be null"){
                            continue;
                        } else {
                            // echo "ERRORRRRR: " . $errorMessage;
                        }
                        
                    }
                }
            }

            unlink($targetFile);

            header("Location: ../pages/viewTraining.php?&id=$trainingID");
            exit();

            // Close the database connection
            $conn = null;
            // echo "Data imported successfully into the database.";

            
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    } else {
        echo "No file was uploaded or an error occurred during upload.";
    }
}
?>
