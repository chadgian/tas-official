<?php
if (isset($_GET['username']) && isset($_GET['password'])) {
  include '../processes/db_connection.php';

  $username = $_GET['username'];
  $password = $_GET['password'];

  $loginAttendanceStmt = $conn->prepare("SELECT * FROM `_self-attendance-user` WHERE username = ? AND password = ?");
  $loginAttendanceStmt->bind_param("ss", $username, $password);
  if ($loginAttendanceStmt->execute()) {
    $result = $loginAttendanceStmt->get_result();

    if ($result->num_rows > 0) {
      session_start();
      $_SESSION['username'] = $username;
      header("Location: index.php");
    } else {
      header("Location: login.php?err=1");
    }
  } else {
    echo $loginAttendanceStmt->error;
  }
} else {
  header("Location: index.php");
}

