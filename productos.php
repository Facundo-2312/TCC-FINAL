<?php

session_start();

if (!isset($_SESSION['Usuario'])) {
    header('Location: /proj/Login.php');
    exit();
}

header('Location: /proj/MVCsix1.0/index.php');
exit();
