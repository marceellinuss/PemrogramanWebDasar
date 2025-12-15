<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tamu') {
    header("Location: ../frontend/login.html");
    exit;
}

$tamu_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard_tamu.php");
    exit;
}

$id_pesanan = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id_pesanan <= 0) {
    header("Location: dashboard_tamu.php");
    exit;
}

$sql = "
    DELETE FROM reservasi
    WHERE id = $id_pesanan
      AND tamu_id = $tamu_id
      AND status = 'dipesan'
    LIMIT 1
";

mysqli_query($koneksi, $sql);

header("Location: dashboard_tamu.php");
exit;
