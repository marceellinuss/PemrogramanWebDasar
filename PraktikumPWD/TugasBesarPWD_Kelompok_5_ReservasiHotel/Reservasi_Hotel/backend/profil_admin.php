<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../frontend/login_admin.html");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$sql = "SELECT foto_profil FROM admins WHERE user_id = $user_id LIMIT 1";
$hasil = mysqli_query($koneksi, $sql);
$data_lama = mysqli_fetch_assoc($hasil);
$foto_lama = $data_lama ? $data_lama['foto_profil'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap  = mysqli_real_escape_string($koneksi, trim($_POST['nama_lengkap']));
    $nomor_telepon = mysqli_real_escape_string($koneksi, trim($_POST['nomor_telepon']));
    $alamat        = mysqli_real_escape_string($koneksi, trim($_POST['alamat']));
    $lewati_foto   = isset($_POST['lewati_foto']) ? 1 : 0;

    $nama_file_foto = $foto_lama;

    if (!$lewati_foto && isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
        $tmp_name  = $_FILES['foto_profil']['tmp_name'];
        $nama_asli = basename($_FILES['foto_profil']['name']);
        $ext       = pathinfo($nama_asli, PATHINFO_EXTENSION);
        $nama_baru = 'admin_' . $user_id . '_' . time() . '.' . $ext;

        $tujuan = "../upload/" . $nama_baru;

        if (move_uploaded_file($tmp_name, $tujuan)) {
            $nama_file_foto = "upload/" . $nama_baru;
        }
    }

    if ($lewati_foto && !$foto_lama) {
        $nama_file_foto = "upload/default_black.png";
    }

    $sql_update = "
        UPDATE admins
        SET nama_lengkap  = '$nama_lengkap',
            nomor_telepon = '$nomor_telepon',
            alamat        = '$alamat',
            foto_profil   = " . ($nama_file_foto ? "'$nama_file_foto'" : "NULL") . "
        WHERE user_id = $user_id
    ";

    if (mysqli_query($koneksi, $sql_update)) {
        header("Location: dashboard_admin.php");
        exit;
    } else {
        echo "Gagal menyimpan data: " . mysqli_error($koneksi);
    }
} else {
    echo "Akses tidak valid.";
}
?>
