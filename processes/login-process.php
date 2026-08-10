<?php
require_once 'db_connection.php';

$username = $_POST['username'];
$password = md5($_POST['password']); // password hashing using MD5 encryption method

$stmt = $conn->prepare("SELECT * FROM users WHERE username=? AND password=?");
$stmt->bind_param("ss", $username, $password);

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();

    session_start();
    $_SESSION['username'] = $user['username'];
    $_SESSION['password'] = $user['password'];

    header('Location: ../pages/main.php');
} else {
    $loginError = "Wrong username or password!";
    header('Location: ../index.php?error=' . urlencode($loginError));
}

$stmt->close();
$conn->close();
exit();
?>