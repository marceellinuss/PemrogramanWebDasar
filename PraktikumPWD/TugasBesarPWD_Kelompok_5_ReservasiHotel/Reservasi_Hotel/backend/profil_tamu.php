<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tamu') {
    header("Location: ../frontend/login.html");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$sql = "SELECT foto_profil FROM tamu WHERE user_id = $user_id LIMIT 1";
$hasil = mysqli_query($koneksi, $sql);
$data_lama = mysqli_fetch_assoc($hasil);
$foto_lama = $data_lama ? $data_lama['foto_profil'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap    = mysqli_real_escape_string($koneksi, trim($_POST['nama_lengkap']));
    $tanggal_lahir   = trim($_POST['tanggal_lahir']); // boleh kosong
    $tanggal_lahir   = mysqli_real_escape_string($koneksi, $tanggal_lahir);

    $jenis_kelamin = isset($_POST['jenis_kelamin']) ? $_POST['jenis_kelamin'] : '';
    if ($jenis_kelamin !== 'Laki-laki' && $jenis_kelamin !== 'Perempuan') {
        $jenis_kelamin = '';
    }
    $jenis_kelamin = mysqli_real_escape_string($koneksi, $jenis_kelamin);

    $nomor_identitas = mysqli_real_escape_string($koneksi, trim($_POST['nomor_identitas']));
    $alamat          = mysqli_real_escape_string($koneksi, trim($_POST['alamat']));
    $lewati_foto     = isset($_POST['lewati_foto']) ? 1 : 0;

    $nama_file_foto  = $foto_lama;

    if (!$lewati_foto && isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
        $tmp_name  = $_FILES['foto_profil']['tmp_name'];
        $nama_asli = basename($_FILES['foto_profil']['name']);
        $ext       = pathinfo($nama_asli, PATHINFO_EXTENSION);
        $nama_baru = 'tamu_' . $user_id . '_' . time() . '.' . $ext;

        $tujuan = "../upload/" . $nama_baru;

        if (move_uploaded_file($tmp_name, $tujuan)) {
            $nama_file_foto = "upload/" . $nama_baru;
        }
    }

    if ($lewati_foto && !$foto_lama) {
        $nama_file_foto = "upload/default_black.png";
    }

    $sql_update = "
        UPDATE tamu
        SET nama_lengkap    = '$nama_lengkap',
            tanggal_lahir   = " . ($tanggal_lahir !== '' ? "'$tanggal_lahir'" : "NULL") . ",
            jenis_kelamin   = " . ($jenis_kelamin !== '' ? "'$jenis_kelamin'" : "NULL") . ",
            nomor_identitas = '$nomor_identitas',
            alamat          = '$alamat',
            foto_profil     = " . ($nama_file_foto ? "'$nama_file_foto'" : "NULL") . "
        WHERE user_id = $user_id
    ";

    if (mysqli_query($koneksi, $sql_update)) {
        header("Location: dashboard_tamu.php");
        exit;
    } else {
        echo "Gagal menyimpan data: " . mysqli_error($koneksi);
    }
} else {
    header("Location: ../frontend/profil_tamu.html");
    exit;
}
?>
