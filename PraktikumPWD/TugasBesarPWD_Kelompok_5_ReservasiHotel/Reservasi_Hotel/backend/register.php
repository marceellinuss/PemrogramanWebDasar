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
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $role     = isset($_POST['role']) ? $_POST['role'] : '';

    if ($username === '' || $email === '' || $password === '' || ($role !== 'admin' && $role !== 'tamu')) {
        $isi = "Input tidak lengkap. Pastikan username, email, dan password terisi.<br><br>";
        if ($role === 'admin') {
            $isi .= "<a href=\"../frontend/register_admin.html\" class=\"tautan-tombol\">Kembali ke registrasi pemilik hotel</a>";
        } else {
            $isi .= "<a href=\"../frontend/register_tamu.html\" class=\"tautan-tombol\">Kembali ke registrasi tamu</a>";
        }
        tampil_halaman_pesan("Registrasi Gagal", $isi);
    }

    $username_aman = mysqli_real_escape_string($koneksi, $username);
    $email_aman    = mysqli_real_escape_string($koneksi, $email);

    $sql_cek = "
        SELECT username, email FROM users
        WHERE username = '$username_aman' OR email = '$email_aman'
        LIMIT 1
    ";
    $hasil_cek = mysqli_query($koneksi, $sql_cek);

    if ($baris = mysqli_fetch_assoc($hasil_cek)) {
        $isi = "";

        if ($baris['email'] === $email && $baris['username'] === $username) {
            $isi .= "Email <b>" . htmlspecialchars($email) . "</b> dan username <b>" . htmlspecialchars($username) . "</b> sudah digunakan.<br>";
        } elseif ($baris['email'] === $email) {
            $isi .= "Email <b>" . htmlspecialchars($email) . "</b> sudah digunakan.<br>";
        } elseif ($baris['username'] === $username) {
            $isi .= "Username <b>" . htmlspecialchars($username) . "</b> sudah digunakan.<br>";
        }

        $isi .= "Silakan gunakan email/username lain atau kembali ke halaman registrasi.<br><br>";
        if ($role === 'admin') {
            $isi .= "<a href=\"../frontend/register_admin.html\" class=\"tautan-tombol\">Registrasi sebagai Pemilik Hotel</a>";
        } else {
            $isi .= "<a href=\"../frontend/register_tamu.html\" class=\"tautan-tombol\">Registrasi sebagai Tamu</a>";
        }
        $isi .= " <a href=\"../frontend/login.html\" class=\"tautan-tombol secunder\">Kembali ke Login</a>";
        tampil_halaman_pesan("Data Sudah Terdaftar", $isi);
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $token_aktivasi = md5(uniqid($email, true));

    $role_aman     = mysqli_real_escape_string($koneksi, $role);
    $token_aman    = mysqli_real_escape_string($koneksi, $token_aktivasi);
    $password_aman = mysqli_real_escape_string($koneksi, $password_hash);

    $sql_user = "
        INSERT INTO users (username, email, password, role, is_active, activation_token)
        VALUES ('$username_aman', '$email_aman', '$password_aman', '$role_aman', 0, '$token_aman')
    ";

    if (mysqli_query($koneksi, $sql_user)) {
        $user_id = mysqli_insert_id($koneksi);

        if ($role === 'admin') {
            $sql_profil = "INSERT INTO admins (user_id) VALUES ($user_id)";
        } else {
            $sql_profil = "INSERT INTO tamu (user_id) VALUES ($user_id)";
        }
        mysqli_query($koneksi, $sql_profil);

        $base_url = "http://" . $_SERVER['HTTP_HOST'] . str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));
        $link_aktivasi = $base_url . "/aktivasi.php?token=" . $token_aktivasi;

        $isi  = "Akun untuk email <b>" . htmlspecialchars($email) . "</b> berhasil dibuat.<br>";
        $isi .= "Untuk dapat login, Anda perlu melakukan aktivasi akun terlebih dahulu.<br><br>";
        $isi .= "<a href=\"$link_aktivasi\" class=\"tautan-tombol\">Aktivasi Akun Sekarang</a>";
        $isi .= " <a href=\"../frontend/login.html\" class=\"tautan-tombol secunder\">Kembali ke Login</a><br><br>";

        tampil_halaman_pesan("Registrasi Berhasil", $isi);

    } else {
        $isi = "Terjadi kesalahan saat menyimpan data ke database.<br><br>"
             . "Pesan sistem: " . htmlspecialchars(mysqli_error($koneksi)) . "<br><br>"
             . "<a href=\"../frontend/register_tamu.html\" class=\"tautan-tombol\">Kembali ke Registrasi</a>";
        tampil_halaman_pesan("Kesalahan Sistem", $isi);
    }
} else {
    tampil_halaman_pesan("Akses Tidak Valid", "Halaman ini hanya bisa diakses melalui form registrasi.");
}
?>
