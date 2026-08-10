<?php

session_start();
session_destroy();
$_SESSION[] = "";
session_abort();

header("Location: ../index.php");
