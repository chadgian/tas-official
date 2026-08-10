<?php
    $key = 'hrd@CSCRO6';

    // Plain text to be encrypted
    $encryptedData = json_decode(file_get_contents('php://input'), true)['data'];

    // Split encrypted data and IV
    list($encrypted, $iv) = explode('::', $encryptedData, 2);

    // Decrypt
    $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, base64_decode($iv));

    if ($decrypted === false) {
        echo "Error decrypting data";
    }

    echo $decrypted;

    // include 'attendanceProcess.php';
?>