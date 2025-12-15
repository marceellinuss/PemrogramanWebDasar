<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tamu') {
    header("Location: ../frontend/login.html");
    exit;
}

$user_id       = (int)$_SESSION['user_id'];
$nama_username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Tamu';

$pesan_sukses = '';
$pesan_error  = '';

$sql_profil = "
    SELECT 
        u.username,
        u.email,
        t.nama_lengkap,
        t.tanggal_lahir,
        t.jenis_kelamin,
        t.nomor_identitas,
        t.alamat,
        t.foto_profil
    FROM users u
    LEFT JOIN tamu t ON t.user_id = u.id
    WHERE u.id = $user_id
    LIMIT 1
";
$hasil_profil = mysqli_query($koneksi, $sql_profil);
$data = mysqli_fetch_assoc($hasil_profil);

$username_skrg = $data ? $data['username'] : $nama_username;
$email_skrg    = $data ? $data['email']    : '';

$nama_lengkap    = $data ? $data['nama_lengkap']    : '';
$tanggal_lahir   = $data ? $data['tanggal_lahir']   : '';
$jenis_kelamin   = $data ? $data['jenis_kelamin']   : '';
$nomor_identitas = $data ? $data['nomor_identitas'] : '';
$alamat          = $data ? $data['alamat']          : '';

$nama_tampil = $nama_lengkap !== '' ? $nama_lengkap : $username_skrg;

if ($data && !empty($data['foto_profil'])) {
    $foto_profil_url = "../" . ltrim($data['foto_profil'], '/');
} else {
    $foto_profil_url = "../upload/default_black.png";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {

    // ---------- UBAH FOTO ----------
    if ($_POST['aksi'] === 'ubah_foto') {

        if (isset($_FILES['foto_baru']) && $_FILES['foto_baru']['error'] === UPLOAD_ERR_OK) {
            $tmp_name  = $_FILES['foto_baru']['tmp_name'];
            $nama_asli = basename($_FILES['foto_baru']['name']);
            $ext       = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));

            // batasi ekstensi yang diizinkan
            $ekstensi_boleh = ['jpg','jpeg','png','gif'];
            if (!in_array($ext, $ekstensi_boleh)) {
                $pesan_error = "Format foto tidak didukung. Gunakan jpg, jpeg, png, atau gif.";
            } else {
                $nama_baru = 'tamu_' . $user_id . '_' . time() . '.' . $ext;
                $tujuan    = "../upload/" . $nama_baru;

                if (move_uploaded_file($tmp_name, $tujuan)) {
                    $path_db = "upload/" . $nama_baru;

                    $sql_update = "
                        UPDATE tamu
                        SET foto_profil = '" . mysqli_real_escape_string($koneksi, $path_db) . "'
                        WHERE user_id = $user_id
                    ";

                    if (mysqli_query($koneksi, $sql_update)) {
                        $pesan_sukses    = "Foto profil berhasil diperbarui.";
                        $foto_profil_url = "../" . $path_db;
                    } else {
                        $pesan_error = "Gagal menyimpan foto ke database: " . mysqli_error($koneksi);
                    }
                } else {
                    $pesan_error = "Gagal mengupload file foto.";
                }
            }
        } else {
            $pesan_error = "Silakan pilih file foto terlebih dahulu.";
        }
    }

    if ($_POST['aksi'] === 'ubah_username') {
        $username_baru = trim($_POST['username_baru']);

        if ($username_baru === '') {
            $pesan_error = "Username baru tidak boleh kosong.";
        } else {
            $username_aman = mysqli_real_escape_string($koneksi, $username_baru);

            $sql_cek = "
                SELECT id FROM users
                WHERE username = '$username_aman' AND id <> $user_id
                LIMIT 1
            ";
            $hasil_cek = mysqli_query($koneksi, $sql_cek);

            if (mysqli_num_rows($hasil_cek) > 0) {
                $pesan_error = "Username \"" . htmlspecialchars($username_baru) . "\" sudah digunakan pengguna lain.";
            } else {
                // update username di tabel users
                $sql_update_user = "
                    UPDATE users
                    SET username = '$username_aman'
                    WHERE id = $user_id
                ";

                if (mysqli_query($koneksi, $sql_update_user)) {
                    $pesan_sukses  = "Username berhasil diperbarui.";
                    $username_skrg = $username_baru;
                    // kalau nama_lengkap kosong, nama tampil ikut username
                    if ($nama_lengkap === '' || $nama_lengkap === null) {
                        $nama_tampil = $username_baru;
                    }
                    $_SESSION['username'] = $username_baru; // update session
                } else {
                    $pesan_error = "Gagal menyimpan username baru: " . mysqli_error($koneksi);
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pengaturan Profil Tamu</title>
  <link rel="stylesheet" href="../frontend/aset/css/style.css">
</head>
<body>
  <div class="container">
    <h2 class="judul-halaman">Pengaturan Profil Tamu</h2>
    <p class="subjudul">
      Di halaman ini Anda dapat melihat data profil dan mengganti foto profil serta username akun.
    </p>

    <?php if ($pesan_sukses !== ''): ?>
      <div class="pesan-sukses"><?php echo $pesan_sukses; ?></div>
    <?php endif; ?>

    <?php if ($pesan_error !== ''): ?>
      <div class="pesan-error"><?php echo $pesan_error; ?></div>
    <?php endif; ?>

    <div class="bagian-pengaturan">
      <h3>Data Akun & Profil</h3>

      <div class="grid-info">
        <div class="kolom">
          <div class="judul-kecil">Email</div>
          <div class="isi"><?php echo htmlspecialchars($email_skrg); ?></div>
        </div>
        <div class="kolom">
          <div class="judul-kecil">Username</div>
          <div class="isi"><?php echo htmlspecialchars($username_skrg); ?></div>
        </div>
        <div class="kolom">
          <div class="judul-kecil">Nama Lengkap</div>
          <div class="isi">
            <?php echo $nama_lengkap ? htmlspecialchars($nama_lengkap) : '-'; ?>
          </div>
        </div>
        <div class="kolom">
          <div class="judul-kecil">Tanggal Lahir</div>
          <div class="isi">
            <?php echo $tanggal_lahir ? htmlspecialchars($tanggal_lahir) : '-'; ?>
          </div>
        </div>
        <div class="kolom">
          <div class="judul-kecil">Jenis Kelamin</div>
          <div class="isi">
            <?php echo $jenis_kelamin ? htmlspecialchars($jenis_kelamin) : '-'; ?>
          </div>
        </div>
        <div class="kolom">
          <div class="judul-kecil">Nomor Identitas</div>
          <div class="isi">
            <?php echo $nomor_identitas ? htmlspecialchars($nomor_identitas) : '-'; ?>
          </div>
        </div>
      </div>

      <div style="margin-top:12px;">
        <div class="judul-kecil">Alamat</div>
        <div class="isi" style="white-space:pre-line;">
          <?php echo $alamat ? nl2br(htmlspecialchars($alamat)) : '-'; ?>
        </div>
      </div>
    </div>

    <div class="bagian-pengaturan">
      <h3>Foto Profil</h3>
      <img src="<?php echo htmlspecialchars($foto_profil_url); ?>" alt="Foto Profil" class="foto-besar">

      <form method="POST" action="pengaturan_tamu.php" enctype="multipart/form-data" class="tengah">
        <input type="hidden" name="aksi" value="ubah_foto">
        <label for="foto_baru" style="text-align:center;">Pilih foto baru (Gunakan jpg, jpeg, png, atau gif.)</label>
        <input type="file" id="foto_baru" name="foto_baru" accept="image/*">
        <button type="submit" style="margin-top:10px;">Ganti Foto Profil</button>
      </form>
    </div>

    <div class="bagian-pengaturan">
      <h3>Username</h3>
      <p class="tengah">
        Username saat ini: <strong><?php echo htmlspecialchars($username_skrg); ?></strong>
      </p>

      <form method="POST" action="pengaturan_tamu.php" class="tengah">
        <input type="hidden" name="aksi" value="ubah_username">
        <label for="username_baru" style="text-align:center;">Username baru</label>
        <input type="text" id="username_baru" name="username_baru" required>
        <button type="submit" style="margin-top:10px;">Ganti Username</button>
      </form>
    </div>

    <p class="tengah" style="margin-top:10px;">
      <a href="dashboard_tamu.php">Kembali ke Dashboard</a>
    </p>
  </div>
</body>
</html>
