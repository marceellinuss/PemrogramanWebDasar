<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../frontend/login_admin.html");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$pesan_error  = '';
$pesan_sukses = '';

$sql = "
    SELECT 
        u.username,
        u.email,
        a.nama_lengkap,
        a.nomor_telepon,
        a.alamat,
        a.foto_profil
    FROM users u
    LEFT JOIN admins a ON a.user_id = u.id
    WHERE u.id = $user_id
    LIMIT 1
";
$hasil = mysqli_query($koneksi, $sql);
if (!$hasil || mysqli_num_rows($hasil) === 0) {
    die("Data admin tidak ditemukan.");
}
$data = mysqli_fetch_assoc($hasil);

$username_lama     = $data['username'];
$email_admin       = $data['email'];
$nama_lengkap      = $data['nama_lengkap'];
$nomor_telepon     = $data['nomor_telepon'];
$alamat            = $data['alamat'];
$foto_profil_lama  = $data['foto_profil'];

if ($foto_profil_lama) {
    $foto_url = "../" . ltrim($foto_profil_lama, '/');
} else {
    $foto_url = "../upload/default_black.png";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_baru  = trim($_POST['username_baru']);
    $username_aman  = null;
    $foto_baru_db   = null;

    if ($username_baru === '') {
        $pesan_error = "Username tidak boleh kosong.";
    } else {
        $username_aman = mysqli_real_escape_string($koneksi, $username_baru);

        $sql_cek = "
            SELECT id
            FROM users
            WHERE username = '$username_aman' AND id <> $user_id
            LIMIT 1
        ";
        $hasil_cek = mysqli_query($koneksi, $sql_cek);
        if (mysqli_num_rows($hasil_cek) > 0) {
            $pesan_error = "Username \"" . htmlspecialchars($username_baru) . "\" sudah digunakan oleh pengguna lain.";
        }
    }

    if ($pesan_error === '' && isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
            $tmp_name  = $_FILES['foto_profil']['tmp_name'];
            $nama_asli = basename($_FILES['foto_profil']['name']);
            $ext       = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));

            // Batasi ekstensi file gambar
            $ekstensi_boleh = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($ext, $ekstensi_boleh)) {
                $pesan_error = "Format foto tidak didukung. Gunakan jpg, jpeg, png, atau gif.";
            } else {
                $nama_baru = 'admin_' . $user_id . '_' . time() . '.' . $ext;
                $tujuan    = "../upload/" . $nama_baru;

                if (move_uploaded_file($tmp_name, $tujuan)) {
                    $foto_baru_db = "upload/" . $nama_baru;
                } else {
                    $pesan_error = "Gagal mengunggah foto profil.";
                }
            }
        } else {
            $pesan_error = "Terjadi kesalahan saat mengunggah foto.";
        }
    }

    if ($pesan_error === '' && $username_aman !== null) {
        $sql_update_user = "
            UPDATE users
            SET username = '$username_aman'
            WHERE id = $user_id
        ";
        if (!mysqli_query($koneksi, $sql_update_user)) {
            $pesan_error = "Gagal mengubah username: " . mysqli_error($koneksi);
        } else {
            if ($foto_baru_db !== null) {
                $foto_aman = mysqli_real_escape_string($koneksi, $foto_baru_db);
                $sql_update_admin = "
                    UPDATE admins
                    SET foto_profil = '$foto_aman'
                    WHERE user_id = $user_id
                ";
                if (!mysqli_query($koneksi, $sql_update_admin)) {
                    $pesan_error = "Username berubah, tetapi foto gagal diperbarui: " . mysqli_error($koneksi);
                } else {
                    $foto_url = "../" . ltrim($foto_baru_db, '/');
                }
            }

            if ($pesan_error === '') {
                $pesan_sukses   = "Pengaturan profil berhasil disimpan.";
                $username_lama  = $username_baru;
                $_SESSION['username'] = $username_baru;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pengaturan Profil Pemilik Hotel</title>
  <link rel="stylesheet" href="../frontend/aset/css/style.css">
</head>
<body>
  <div class="container">
    <h2 class="judul-halaman">Pengaturan Profil Pemilik Hotel</h2>
    <p class="subjudul">
      Di halaman ini Anda dapat melihat data profil dan mengubah <strong>username</strong>
      serta <strong>foto profil</strong> akun pemilik hotel.
    </p>

    <?php if ($pesan_sukses): ?>
      <div class="pesan-sukses"><?php echo $pesan_sukses; ?></div>
    <?php endif; ?>

    <?php if ($pesan_error): ?>
      <div class="pesan-error"><?php echo $pesan_error; ?></div>
    <?php endif; ?>

    <div class="wrapper-profil">
      <img src="<?php echo htmlspecialchars($foto_url); ?>" alt="Foto Profil Admin" class="foto-bulat">
      <div class="nama-admin">
        <?php echo htmlspecialchars($nama_lengkap ?: $username_lama); ?>
      </div>
    </div>

    <div class="grid-info">
      <div>
        <div class="judul-kecil">Email</div>
        <div class="isi"><?php echo htmlspecialchars($email_admin); ?></div>
      </div>
      <div>
        <div class="judul-kecil">Username</div>
        <div class="isi"><?php echo htmlspecialchars($username_lama); ?></div>
      </div>
      <div>
        <div class="judul-kecil">Nama Lengkap</div>
        <div class="isi">
          <?php echo $nama_lengkap ? htmlspecialchars($nama_lengkap) : '-'; ?>
        </div>
      </div>
      <div>
        <div class="judul-kecil">Nomor Telepon</div>
        <div class="isi">
          <?php echo $nomor_telepon ? htmlspecialchars($nomor_telepon) : '-'; ?>
        </div>
      </div>
    </div>

    <div class="alamat-blok">
      <span class="judul-kecil">Alamat</span><br>
      <span class="isi">
        <?php echo $alamat ? nl2br(htmlspecialchars($alamat)) : '-'; ?>
      </span>
    </div>

    <hr style="margin:18px 0; border:none; border-top:1px solid #E0E0E0;">

    <form method="POST" action="pengaturan_admin.php" enctype="multipart/form-data">
      <label>Email (tidak dapat diubah)</label>
      <input type="text" value="<?php echo htmlspecialchars($email_admin); ?>" class="field-baca" readonly>

      <label>Username Sekarang</label>
      <input type="text" value="<?php echo htmlspecialchars($username_lama); ?>" class="field-baca" readonly>

      <label for="username_baru">Ganti Username</label>
      <input type="text" id="username_baru" name="username_baru"
             value="<?php echo htmlspecialchars($username_lama); ?>" required>

      <label for="foto_profil">Ganti Foto Profil (Gunakan jpg, jpeg, png, atau gif.)</label>
      <input type="file" id="foto_profil" name="foto_profil" accept="image/*">

      <button type="submit" style="margin-top:12px;">Simpan Perubahan</button>
    </form>

    <p style="margin-top:12px;font-size:0.9rem;">
      <a href="dashboard_admin.php">Kembali ke Dashboard Pemilik Hotel</a>
    </p>
  </div>
</body>
</html>
