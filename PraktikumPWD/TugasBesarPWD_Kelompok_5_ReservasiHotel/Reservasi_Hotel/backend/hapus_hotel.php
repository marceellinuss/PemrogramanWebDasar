<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../frontend/login_admin.html");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header("Location: dashboard_admin.php");
    exit;
}

$hotel_id = (int)$_POST['id'];

$sql_del = "
    DELETE FROM hotel
    WHERE id = $hotel_id AND pemilik_id = $user_id
";

mysqli_query($koneksi, $sql_del);

header("Location: dashboard_admin.php");
exit;
