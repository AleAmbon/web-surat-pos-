<?php
session_start();

function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: auth/login.php");
        exit();
    }
}

function checkAdmin() {
    if ($_SESSION['role'] !== 'admin') {
        header("Location: dashboard.php");
        exit();
    }
}
?>
