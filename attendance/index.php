<?php
session_start();
if (!isset($_SESSION['username'])) {
  header('Location: login.php');
  exit();
}
if ($_SESSION['username'] == "hrd") {
  header("Location: admin/index.php");
  exit();
}
header('Location: scanner.php');
exit();
