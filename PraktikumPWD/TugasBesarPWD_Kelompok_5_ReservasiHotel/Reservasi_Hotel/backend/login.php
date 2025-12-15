<?php
session_start();
require 'koneksi.php';

function tampil_halaman_pesan($judul, $isi_html) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title><?php echo htmlspecialchars($judul); ?></title>
        <link rel="stylesheet" href="../frontend/aset/css/style.css">
    </head>
    <body>
        <div class="container">
            <h2><?php echo htmlspecialchars($judul); ?></h2>
            <p><?php echo $isi_html; ?></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $role_req = isset($_POST['role']) ? $_POST['role'] : '';

    if ($email === '' || $password === '') {
        $isi  = "Email dan password wajib diisi.<br><br>";
        $isi .= "<a href=\"../frontend/login.html\" class=\"tautan-tombol\">Kembali ke Login</a>";
        tampil_halaman_pesan("Login Gagal", $isi);
    }

    $email_aman = mysqli_real_escape_string($koneksi, $email);
    $sql = "SELECT * FROM users WHERE email = '$email_aman' LIMIT 1";
    $hasil = mysqli_query($koneksi, $sql);

    if (mysqli_num_rows($hasil) === 0) {
        $isi  = "Email <b>" . htmlspecialchars($email) . "</b> tidak terdaftar dalam sistem.<br><br>";
        $isi .= "<a href=\"../frontend/login.html\" class=\"tautan-tombol\">Kembali ke Login</a>";
        $isi .= " <a href=\"../frontend/register_tamu.html\" class=\"tautan-tombol secunder\">Registrasi sebagai Tamu?</a>";
        tampil_halaman_pesan("Login Gagal", $isi);
    }

    $data_user = mysqli_fetch_assoc($hasil);

    if ((int)$data_user['is_active'] !== 1) {
        if (!empty($data_user['activation_token'])) {
            $base_url = "http://" . $_SERVER['HTTP_HOST'] . str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));
            $link_aktivasi = $base_url . "/aktivasi.php?token=" . $data_user['activation_token'];

            $isi  = "Akun untuk email <b>" . htmlspecialchars($email) . "</b> belum diaktivasi.<br>";
            $isi .= "Silakan lakukan aktivasi terlebih dahulu sebelum login.<br><br>";
            $isi .= "<a href=\"$link_aktivasi\" class=\"tautan-tombol\">Aktivasi Akun Sekarang</a>";
            $isi .= " <a href=\"../frontend/login.html\" class=\"tautan-tombol secunder\">Kembali ke Login</a>";
            tampil_halaman_pesan("Akun Belum Aktif", $isi);
        } else {
            $isi  = "Akun Anda belum aktif dan token aktivasi tidak ditemukan.<br>";
            $isi .= "Silakan lakukan registrasi ulang untuk mendapatkan akun baru.<br><br>";
            $isi .= "<a href=\"../frontend/register_tamu.html\" class=\"tautan-tombol\">Registrasi sebagai Tamu</a>";
            tampil_halaman_pesan("Akun Belum Aktif", $isi);
        }
    }

    if (!password_verify($password, $data_user['password'])) {
        $isi  = "Password yang Anda masukkan tidak sesuai.<br><br>";
        $isi .= "<a href=\"../frontend/login.html\" class=\"tautan-tombol\">Kembali ke Login</a>";
        tampil_halaman_pesan("Login Gagal", $isi);
    }

    if ($role_req !== '' && $data_user['role'] !== $role_req) {
        if ($role_req === 'tamu') {
            $isi  = "Akun ini adalah akun Pemilik Hotel, tidak bisa login melalui halaman Tamu.<br><br>";
            $isi .= "<a href=\"../frontend/login_admin.html\" class=\"tautan-tombol\">Login sebagai Pemilik Hotel</a>";
        } else {
            $isi  = "Akun ini adalah akun Tamu, tidak bisa login sebagai Pemilik Hotel.<br><br>";
            $isi .= "<a href=\"../frontend/login.html\" class=\"tautan-tombol\">Login sebagai Tamu</a>";
        }
        tampil_halaman_pesan("Akses Ditolak", $isi);
    }

    $_SESSION['user_id']  = $data_user['id'];
    $_SESSION['username'] = $data_user['username'];
    $_SESSION['role']     = $data_user['role'];

    $user_id = (int)$data_user['id'];

    if ($data_user['role'] === 'admin') {
        $sql_profil = "SELECT nama_lengkap FROM admins WHERE user_id = $user_id LIMIT 1";
        $hasil_profil = mysqli_query($koneksi, $sql_profil);
        $profil = mysqli_fetch_assoc($hasil_profil);

        if (!$profil || $profil['nama_lengkap'] === null || $profil['nama_lengkap'] === '') {
            header("Location: ../frontend/profil_admin.html");
            exit;
        }

        header("Location: dashboard_admin.php");
        exit;

    } else { // role = tamu
        $sql_profil = "SELECT nama_lengkap FROM tamu WHERE user_id = $user_id LIMIT 1";
        $hasil_profil = mysqli_query($koneksi, $sql_profil);
        $profil = mysqli_fetch_assoc($hasil_profil);

        if (!$profil || $profil['nama_lengkap'] === null || $profil['nama_lengkap'] === '') {
            header("Location: ../frontend/profil_tamu.html");
            exit;
        }

        header("Location: dashboard_tamu.php");
        exit;
    }

} else {
    tampil_halaman_pesan("Akses Tidak Valid", "Halaman ini hanya bisa diakses melalui form login.");
}
?>
