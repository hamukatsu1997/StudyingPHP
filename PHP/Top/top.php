<?php

session_start();

if (!isset($_SESSION['userId'])) {

    header('Location: ../../HTML/Top/top.html');

    exit;
}

$userId = $_SESSION['userId'];
$userName = $_SESSION['userName'];

?>