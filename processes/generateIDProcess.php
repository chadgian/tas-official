<?php
// declare(strict_types=1);

session_start();
set_time_limit(0);
// ini_set('memory_limit', '-1');

require_once '../vendor/autoload.php';

function encryptText($string)
{
    $key = 'hrd@CSCRO6';

    // Initialization vector (IV) - 16 bytes for AES-128, 24 bytes for AES-192, 32 bytes for AES-256
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));

    // Encrypt the plaintext using AES-256-CBC algorithm
    $ciphertext = openssl_encrypt($string, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

    // Encrypted data (ciphertext) and IV should be stored securely for decryption
    $base64encoded = base64_encode($ciphertext) . "::" . base64_encode($iv);
    return $base64encoded;
}

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;


if (isset($_POST['id']) && isset($_POST['trainingName'])) {
    $id = $_POST['id'];
    $trainingName = strtoupper($_POST['trainingName']);
    $selectedParticipants = $_POST['selectedParticipants'] ?? [];
    if (!is_array($selectedParticipants)) {
        $selectedParticipants = [];
    }
    $selectedParticipants = array_values(array_filter(array_map('strval', $selectedParticipants), fn($value) => $value !== ''));
    if (count($selectedParticipants) === 0) {
        header("Location: ../pages/viewTraining.php?id=$id&ids=none");
        exit();
    }

    $bindParams = function (mysqli_stmt $stmt, string $types, array $params): void {
        $refs = [];
        $refs[] = $types;
        foreach ($params as $index => $value) {
            $refs[$index + 1] = &$params[$index];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);
    };

    include 'db_connection.php';
    $trainingTable = "training-$id-1";

    $conn->query("ALTER TABLE trainings ADD COLUMN IF NOT EXISTS training_start_date DATE NULL");
    $conn->query("ALTER TABLE trainings ADD COLUMN IF NOT EXISTS training_venue VARCHAR(255) NULL");

    $stmtTraining = $conn->prepare("SELECT training_start_date, training_venue, training_days FROM trainings WHERE training_id = ? LIMIT 1");
    $stmtTraining->bind_param('s', $id);
    $stmtTraining->execute();
    $trainingResult = $stmtTraining->get_result();
    $trainingRow = $trainingResult->fetch_assoc();
    $trainingDate = $trainingRow['training_start_date'] ?? '';
    $trainingVenue = $trainingRow['training_venue'] ?? '';
    $trainingDays = (int)($trainingRow['training_days'] ?? 0);

    if ($trainingDate === '' || $trainingVenue === '') {
        header("Location: ../pages/viewTraining.php?id=$id");
        exit();
    }

    $trainingDateLabel = $trainingDate;
    try {
        $startDate = new DateTimeImmutable($trainingDate);
        $endDate = $trainingDays > 1 ? $startDate->modify('+' . ($trainingDays - 1) . ' days') : $startDate;
        if ($trainingDays > 1) {
            if ($startDate->format('F') === $endDate->format('F') && $startDate->format('Y') === $endDate->format('Y')) {
                $trainingDateLabel = $startDate->format('F j') . '-' . $endDate->format('j, Y');
            } elseif ($startDate->format('Y') === $endDate->format('Y')) {
                $trainingDateLabel = $startDate->format('F j') . ' - ' . $endDate->format('F j, Y');
            } else {
                $trainingDateLabel = $startDate->format('F j, Y') . ' - ' . $endDate->format('F j, Y');
            }
        } else {
            $trainingDateLabel = $startDate->format('F j, Y');
        }
    } catch (Exception $e) {
        $trainingDateLabel = $trainingDate;
    }

    $_SESSION['generated_training_name'] = $trainingName;

    $folderName = "../generated_ids/training-$id/";

    $idFiles = scandir($folderName);

    foreach ($idFiles as $idname) {
        if ($idname != "." && $idname != "..") {
            if (is_file($folderName . $idname)) {
                unlink($folderName . $idname);
            }
        }
    }

    if (!file_exists($folderName)) {
        mkdir($folderName, 0777, true);
    }
    $IDgenerationDone = false;
    //echo "<h1>Generating ID...</h1>";

    while ($IDgenerationDone === false) {
        if (count($selectedParticipants) > 0) {
            $placeholders = implode(',', array_fill(0, count($selectedParticipants), '?'));
            $types = str_repeat('s', count($selectedParticipants));
            $stmt = $conn->prepare("SELECT * FROM `$trainingTable` WHERE participant_id IN ($placeholders) ORDER BY participant_id ASC");
            $bindParams($stmt, $types, $selectedParticipants);
        } else {
            $stmt = $conn->prepare("SELECT * FROM `$trainingTable` ORDER BY participant_id ASC");
        }
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $totalParticipants = $result->num_rows;

            while ($row = $result->fetch_assoc()) {
                $participantID = $row['participant_id'];
                $firstName = $row['firstname'];
                $lastName = $row['lastname'];
                $text = $row['lastname'] . ", " . $row['firstname'] . " " . $row['middle_initial'];
                $agency = $row['agency'];
                $IDfilename = "$participantID-$text.jpg";

                // Check if the the ID was already generated
                if (file_exists(realpath("$folderName/$IDfilename"))) {
                    //echo "$IDfilename already generated. Skipping...<br>";
                    continue;
                } else {
                    //echo "Generated $participantID IDs <br>";
                    // $counter++;
                    // Load the image
                    $image = imagecreatefrompng('../sources/id-source.png');

                    // ----------------- FOR NAME --------------------------


                    // Define the font and color for the text
                    $font = '../fonts/Poppins/Poppins-ExtraLight.otf'; // Path to your font file
                    $color = imagecolorallocate($image, 0, 0, 0); // Black font color
                    $fontSize = 50; //initial font size

                    $textBox = imagettfbbox($fontSize, 0, $font, $lastName); // Do this so you can get the width that the text will take up
                    $textWidth = abs($textBox[4] - $textBox[6]); // Here is the text width
                    $textHeight = abs($textBox[5] - $textBox[3]); // And this is the height

                    // This if-else statement will reduce the font size of the name if it doesn't fit the image
                    if ($textWidth > (imagesx($image) - (imagesx($image) / 9))) {
                        while ($textWidth > (imagesx($image) - (imagesx($image) / 9))) {
                            $fontSize = $fontSize - 10;
                            $textBox = imagettfbbox($fontSize, 0, $font, $lastName);
                            $textWidth = abs($textBox[4] - $textBox[6]);
                        }
                    }

                    $textX = (imagesx($image) - $textWidth) / 2; // Center the text horizontally
                    $textY = imagesy($image) - 50; // Adjust the Y position as needed

                    // Add the text to the image
                    imagettftext($image, $fontSize, 0, (int) $textX, (int) $textY, $color, $font, $lastName);

                    // ----------------------- FOR FIRST NAME -------------------------

                    // if (strpos($firstName, "Jr.") !== false){
                    //     $firstName = str_replace("Jr.", "", $firstName);
                    // }

                    // Define the font and color for the text
                    $font1 = '../fonts/Poppins/Poppins-Regular.otf'; // Path to your font file
                    $color = imagecolorallocate($image, 0, 0, 0); // Black font color
                    $fontSize = 170; //initial font size

                    $textBox = imagettfbbox($fontSize, 0, $font1, $firstName); // Do this so you can get the width that the text will take up
                    $textWidth = abs($textBox[4] - $textBox[6]); // Here is the text width

                    // This if-else statement will reduce the font size of the name if it doesn't fit the image
                    if ($textWidth > (imagesx($image) - (imagesx($image) / 10))) {
                        while ($textWidth > (imagesx($image) - (imagesx($image) / 10))) {
                            $fontSize = $fontSize - 10;
                            $textBox = imagettfbbox($fontSize, 0, $font1, $firstName);
                            $textWidth = abs($textBox[4] - $textBox[6]);
                        }
                    }

                    $fnameX = (imagesx($image) - $textWidth) / 2; // Center the text horizontally
                    $fnameY = imagesy($image) - 180; // Adjust the Y position as needed

                    // Add the text to the image
                    imagettftext($image, $fontSize, 0, (int) $fnameX, (int) $fnameY, $color, $font1, $firstName);

                    // --------------------- FOR PARTICIPANT ID --------------------

                    $idx = imagesx($image) * 0.09;
                    $idy = imagesy($image) - 50;

                    $idBox = imagettfbbox(30, 0, $font, $participantID);
                    $idBoxWidth = abs($idBox[2] - $idBox[0]);

                    imagettftext($image, 30, 0, (int) $idx, (int) $idy, $color, $font, $participantID);

                    // ----------------------- FOR TRAINING DETAILS --------------------

                    $nameFont = '../fonts/CooperHewitt/CooperHewitt-HeavyItalic.otf';
                    $dateFont = '../fonts/CooperHewitt/CooperHewitt-Bold.otf';
                    $venueFont = '../fonts/CooperHewitt/CooperHewitt-Medium.otf';

                    $nameColor = imagecolorallocate($image, 0, 74, 173);
                    $dateColor = imagecolorallocate($image, 255, 44, 47);
                    $venueColor = imagecolorallocate($image, 0, 0, 0);

                    // NOTE:
                    // width of training name should be $imagesx($image)*0.5579 (only 55.79% of the total width)
                    // the left border of the training details box should be $imagesx($image)*0.0632 (Only 6.32% of the total width)
                    // top border of the training name should be $imagesy($image)*0.2479 (Only 24.79% of the total height)
                    $trainingDetailW = imagesx($image) * 0.5579;

                    // Training Venue
                    $trainingVenueFontSize = 20;
                    $trainingVenueBox = imagettfbbox($trainingVenueFontSize, 0, $venueFont, $trainingVenue);
                    $trainingVenueWidth = abs($trainingVenueBox[2] - $trainingVenueBox[0]);

                    while ($trainingVenueWidth > $trainingDetailW) {
                        $trainingVenueFontSize--;
                        $trainingVenueBox = imagettfbbox($trainingVenueFontSize, 0, $venueFont, $trainingVenue);
                        $trainingVenueWidth = abs($trainingVenueBox[2] - $trainingVenueBox[0]);
                    }

                    $trainingVenueX = (imagesx($image) * 0.07) + ($trainingDetailW / 2) - ($trainingVenueWidth / 2);
                    $trainingVenueY = imagesy($image) * 0.49;

                    imagettftext($image, $trainingVenueFontSize, 0, (int) $trainingVenueX, (int) $trainingVenueY, $venueColor, $venueFont, $trainingVenue);

                    // Training Name
                    $trainingNameWordCount = str_word_count($trainingName);

                    if ($trainingNameWordCount > 3) {
                        $words = explode(" ", $trainingName);
                        $midpoint = ceil(count($words) / 2);

                        // SECOND PART OF THE TRAINING NAME
                        $secondPart = implode(" ", array_slice($words, $midpoint));
                        $firstPart = implode(" ", array_slice($words, 0, $midpoint));
                        //echo "First Midpoint: $midpoint";

                        while (strlen($secondPart) < strlen($firstPart)) {
                            $midpoint--;
                            //echo "Midpoint: $midpoint";
                            $secondPart = implode(" ", array_slice($words, $midpoint));
                            $firstPart = implode(" ", array_slice($words, 0, $midpoint));
                        }

                        $trainingNameFontSize = 60;
                        $trainingNameW = imagesx($image) * 0.5579; //  55.79% of the total width

                        $trainingNameBox2 = imagettfbbox($trainingNameFontSize, 0, $nameFont, $secondPart);
                        $actualNameWidth2 = abs($trainingNameBox2[2] - $trainingNameBox2[0]);
                        $actualNameHeight2 = abs($trainingNameBox2[7] - $trainingNameBox2[1]);


                        while ($actualNameWidth2 > $trainingNameW) {
                            $trainingNameFontSize--;
                            $trainingNameBox2 = imagettfbbox($trainingNameFontSize, 0, $nameFont, $secondPart);
                            $actualNameWidth2 = abs($trainingNameBox2[2] - $trainingNameBox2[0]);
                            $actualNameHeight2 = abs($trainingNameBox2[7] - $trainingNameBox2[1]);
                        }

                        $trainingNameX = (imagesx($image) * 0.07) + ($trainingDetailW / 2) - ($actualNameWidth2 / 2); // 6.32% from left side
                        $trainingNameY = imagesy($image) * 0.40; // 24.79% of the total height

                        // Training Name to image
                        imagettftext($image, $trainingNameFontSize, 0, (int) $trainingNameX, (int) $trainingNameY, $nameColor, $nameFont, $secondPart);


                        // FIRST PART OF THE TRAINING NAME

                        $trainingNameFontSize = 60;
                        $trainingNameW = imagesx($image) * 0.5579; //  55.79% of the total width

                        $trainingNameBox = imagettfbbox($trainingNameFontSize, 0, $nameFont, $firstPart);
                        $actualNameWidth = abs($trainingNameBox[2] - $trainingNameBox[0]);
                        $actualNameHeight = abs($trainingNameBox[7] - $trainingNameBox[1]);

                        while ($actualNameWidth > $trainingNameW) {
                            $trainingNameFontSize--;
                            $trainingNameBox = imagettfbbox($trainingNameFontSize, 0, $nameFont, $firstPart);
                            $actualNameWidth = abs($trainingNameBox[2] - $trainingNameBox[0]);
                            $actualNameHeight = abs($trainingNameBox[7] - $trainingNameBox[1]);
                        }

                        $trainingNameX = (imagesx($image) * 0.07) + ($trainingDetailW / 2) - ($actualNameWidth / 2); // 6.32% from left side
                        $trainingNameY = imagesy($image) * 0.40 - $actualNameHeight2 - abs(($actualNameHeight2 + $actualNameHeight) - imagesy($image) * 0.145) / 2; // 24.79% of the total height

                        // Training Name to image
                        imagettftext($image, $trainingNameFontSize, 0, (int) $trainingNameX, (int) $trainingNameY, $nameColor, $nameFont, $firstPart);

                        // Training Date
                        $trainingDateFontSize = 25;
                        $trainingDateBox = imagettfbbox($trainingDateFontSize, 0, $dateFont, $trainingDateLabel);
                        $trainingDateBoxWidth = abs($trainingDateBox[2] - $trainingDateBox[0]); //
                        $trainingDateX = (imagesx($image) * 0.07) + ($trainingDetailW / 2) - ($trainingDateBoxWidth / 2);
                        $trainingDateY = imagesy($image) * 0.45;

                        imagettftext($image, $trainingDateFontSize, 0, (int) $trainingDateX, (int) $trainingDateY, $dateColor, $dateFont, $trainingDateLabel);

                    } else {
                        $trainingNameFontSize = 60;
                        $trainingNameW = imagesx($image) * 0.5579; //  55.79% of the total width

                        $trainingNameBox = imagettfbbox($trainingNameFontSize, 0, $nameFont, $trainingName);
                        $actualNameWidth = abs($trainingNameBox[2] - $trainingNameBox[0]);

                        while ($actualNameWidth > $trainingNameW) {
                            $trainingNameFontSize--;
                            $trainingNameBox = imagettfbbox($trainingNameFontSize, 0, $nameFont, $trainingName);
                            $actualNameWidth = abs($trainingNameBox[2] - $trainingNameBox[0]);
                        }

                        $trainingNameX = (imagesx($image) * 0.07) + ($trainingDetailW / 2) - ($actualNameWidth / 2); // 6.32% from left side
                        $trainingNameY = imagesy($image) * 0.36; // 24.79% of the total height

                        // Training Name to image
                        imagettftext($image, $trainingNameFontSize, 0, (int) $trainingNameX, (int) $trainingNameY, $nameColor, $nameFont, $trainingName);

                        // Training Date
                        $trainingDateFontSize = 25;
                        $trainingDateBox = imagettfbbox($trainingDateFontSize, 0, $dateFont, $trainingDateLabel);
                        $trainingDateBoxWidth = abs($trainingDateBox[2] - $trainingDateBox[0]); //
                        $trainingDateX = (imagesx($image) * 0.07) + ($trainingDetailW / 2) - ($trainingDateBoxWidth / 2);
                        $trainingDateY = imagesy($image) * 0.43;

                        imagettftext($image, $trainingDateFontSize, 0, (int) $trainingDateX, (int) $trainingDateY, $dateColor, $dateFont, $trainingDateLabel);
                    }

                    //---------------------- FOR AGENCY ------------------------------
                    $agencyFontSize = 30;

                    $agencyBox = imagettfbbox($agencyFontSize, 0, $font, $agency);
                    $actualAgencyBoxWidth = abs($agencyBox[2] - $agencyBox[0]);

                    while ($actualAgencyBoxWidth > (imagesx($image) - (imagesx($image) / 6))) {
                        $agencyFontSize--;
                        $agencyBox = imagettfbbox($agencyFontSize, 0, $font, $agency);
                        $actualAgencyBoxWidth = abs($agencyBox[2] - $agencyBox[0]);
                    }

                    $agencyx = (imagesx($image) - $actualAgencyBoxWidth) / 2;
                    $agencyy = imagesy($image) * 0.57;

                    imagettftext($image, $agencyFontSize, 0, (int) $agencyx, (int) $agencyy, $color, $font, $agency);


                    //  ---------------- FOR QR Code ----------------------

                    // Generate the QR code
                    // $qrText = urlencode("$participantID:$text"); // URL or any other data you want to encode
                    // $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?data=$qrText&size=370x370&color=283242";
                    // $qrImage = imagecreatefrompng();

                    $qrCodeOptions = new QROptions([
                        'eccLevel' => QRCode::ECC_L,
                        'outputType' => QRCode::OUTPUT_IMAGE_PNG,
                        'version' => 2,
                        'scale' => 13,
                        'quietzoneSize' => 1,
                        'color' => [255, 0, 0],
                        // 'imageTransparent' => true,
                        'transparencyColor' => [0, 0, 0],
                    ]);

                    $qrCodeText = "$id::$participantID";
                    $qrCodeGenerator = new QRCode($qrCodeOptions);
                    $qrCodeImageData = $qrCodeGenerator->render($qrCodeText);
                    // $qrCodeImageData = $qrCodeGenerator->render(encryptText($qrCodeText));

                    try {
                        $qrImageData = file_get_contents($qrCodeImageData);

                        if ($qrImageData === false) {
                            echo "Failed to fetch QR code image <br>";
                        } else {
                            $qrImage = imagecreatefromstring($qrImageData);
                            if ($qrImage === false) {
                                // Handle error
                                echo "Failed to create image from QR code data <br>";
                            } else {
                                // Get the dimensions of the QR code image
                                $qrWidth = imagesx($qrImage);
                                $qrHeight = imagesy($qrImage);

                                // Define the position for the QR code (upper right corner with a margin)
                                $qrX = imagesx($image) - $qrWidth - 130;
                                $qrY = 50;

                                // Merge the QR code onto the main image
                                imagecopy($image, $qrImage, (int) $qrX, (int) $qrY, 0, 0, (int) $qrWidth, (int) $qrHeight);

                                // Output or save the modified image
                                // header('Content-Type: image/jpeg');
                                // imagejpeg($image);

                                //should automatically download but failed
                                // header('Content-Type: image/jpeg');
                                // header('Content-Disposition: attachment; filename="' . $participantID . ":" .$participantName . '.jpg"');

                                // ob_start();
                                // imagejpeg($image);
                                // $imageData = ob_get_clean();

                                // $zipFileNameInZip = "$participantID-$text.jpg";
                                // $zip->addFromString($zipFileNameInZip, $imageData);

                                $outputFilePath = "$folderName/$participantID.jpg"; // save it to local folder
                                imagejpeg($image, $outputFilePath);

                                // Free up memory
                                imagedestroy($image);
                                imagedestroy($qrImage);
                            }
                        }
                    } catch (Exception $e) {
                        echo "Error getting QR Code from the url: " . $e->getMessage() . "<br>";
                    }

                    // $renderer = new ImageRenderer(
                    //     new \BaconQrCode\Renderer\RendererStyle\RendererStyle(370), // Adjust the size of the QR code
                    //     new \BaconQrCode\Renderer\Image\ImagickImageBackEnd() // Adjust the size of the QR code
                    // );
                    // $writer = new Writer($renderer);
                    // $qrImage = $writer->writeString("$participantID:$text");
                }
            }

        } else {
            echo "Not executed: " . $stmt->error;
        }

        // $folderPath = $folderName;

        // Get the list of files and directories in the folder
        $files = scandir($folderName);

        // Initialize a counter for JPG files
        $jpgFileCount = 0;

        // Loop through each item in the files array
        foreach ($files as $file) {
            // Exclude "." and ".." which represent the current and parent directory
            if ($file != "." && $file != "..") {
                // Check if the current item is a file (not a directory) and ends with ".jpg"
                // && pathinfo($file, PATHINFO_EXTENSION) === 'jpg'
                // if (is_file($folderName . $file)) {
                //     // Increment the JPG file counter
                //     $jpgFileCount++;
                // }
                $jpgFileCount++;
            }
        }
        if ($totalParticipants === $jpgFileCount) {
            //echo "<h1>Generation Done!</h1>";

            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $width = 3.74 * 72;
            $height = 2.38 * 72;

            $files = scandir($folderName);

            $section = $phpWord->addSection();


            // Loop through each item in the files array
            foreach ($files as $file) {
                // Exclude "." and ".." which represent the current and parent directory
                if ($file != "." && $file != "..") {
                    // Check if the current item is a file (not a directory) and ends with ".jpg"
                    // && pathinfo($file, PATHINFO_EXTENSION) === 'jpg'
                    if (is_file($folderName . $file) && pathinfo($file, PATHINFO_EXTENSION) === 'jpg') {
                        // Increment the JPG file counter
                        $filepath = "$folderName$file";
                        // echo "<img src='$filepath'> <br><hr>";
                        $section->addImage($filepath, array('width' => $width, 'height' => $height));//, 'wrappingStyle' => 'inline', 'positioning' => 'relative'
                    }
                }
            }

            $filename = "training-$id-selected.docx";
            $filePath = "../generated_ids/$filename";
            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($filePath);

            // $zip = new ZipArchive();

            // $zipFileName = "training-$id.zip";

            // // Create the zip file
            // if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            //     // Open the folder
            //     $dir = opendir($folderName);

            //     // Loop through all files and add them to the zip archive
            //     while (($file = readdir($dir)) !== false) {
            //         if ($file != '.' && $file != '..') {
            //             $filePath = "$folderName/$file";
            //             // Add the file to the zip archive
            //             $zip->addFile($filePath, $file);
            //         }
            //     }

            //     // Close the folder
            //     closedir($dir);

            //     // Close the zip archive
            //     $zip->close();

            //     // Provide the zip file for download
            //     // header('Content-Type: application/zip');
            //     // header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
            //     // header('Content-Length: ' . filesize($zipFileName));
            //     // readfile($zipFileName);

            //     // Delete the zip file after download (optional)
            //     // unlink($zipFileName);
            // } else {
            //     echo "Failed to create zip file";
            // }

            $IDgenerationDone = true;
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            header('Content-Description: File Transfer');
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: public');
            header('Expires: 0');
            readfile($filePath);
            exit();
        } else {
            //echo "<h1>Do it again! $jpgFileCount jpg only</h1>";
            $IDgenerationDone = false;
        }
    }

} else {
    header("Location: ../pages/main.php");
    exit();
}

// $zip->close();

// Send the zip file to the browser for download
// if (file_exists($zipFileName)) {
//     header('Content-Type: application/zip');
//     header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
//     header('Content-Length: ' . filesize($zipFileName));
//     header('Cache-Control: no-cache, must-revalidate'); // Add this line to prevent caching
//     header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Add this line to prevent caching
//     ob_end_clean(); // Add this line to turn off output buffering
//     readfile($zipFileName);
//     // exit();

//     // Delete the zip file after download
//     // unlink($zipFileName);

//     // header("Location: participants.php?id=$id&days=1");
//     // exit();
// } else {
//     echo "Error: Zip file not found";
// }
?>
