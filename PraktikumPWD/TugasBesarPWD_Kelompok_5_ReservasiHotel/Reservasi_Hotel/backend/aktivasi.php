<?php
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

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $token_aman = mysqli_real_escape_string($koneksi, $token);

    $sql = "
        UPDATE users
        SET is_active = 1, activation_token = NULL
        WHERE activation_token = '$token_aman'
        LIMIT 1
    ";

    mysqli_query($koneksi, $sql);

    if (mysqli_affected_rows($koneksi) > 0) {
        $isi  = "Akun Anda sudah berhasil diaktivasi.<br>";
        $isi .= "Silakan login untuk masuk ke sistem reservasi hotel.<br><br>";
        $isi .= "<a href=\"../frontend/login.html\" class=\"tautan-tombol\">Pergi ke Halaman Login</a>";
        tampil_halaman_pesan("Aktivasi Berhasil", $isi);
    } else {
        $isi  = "Token aktivasi tidak valid atau sudah pernah digunakan.<br><br>";
        $isi .= "<a href=\"../frontend/login.html\" class=\"tautan-tombol\">Pergi ke Halaman Login</a>";
        $isi .= " <a href=\"../frontend/register_tamu.html\" class=\"tautan-tombol secunder\">Registrasi Ulang</a>";
        tampil_halaman_pesan("Aktivasi Gagal", $isi);
    }
} else {
    tampil_halaman_pesan("Token Tidak Ditemukan", "Token aktivasi tidak ditemukan pada URL.");
}
?>
