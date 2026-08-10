<?php
    $servername = "sql212.infinityfree.com";
    $username = "if0_38891514";
    $password = "cscregional6v2"; // This might be empty or your MySQL root password
    $database = "if0_38891514_training";

    $conn = new mysqli($servername, $username, $password, $database);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
?>