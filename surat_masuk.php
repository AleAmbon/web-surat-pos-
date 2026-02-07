<?php
include 'middleware/auth.php';
checkAuth();
include 'config/koneksi.php';

// Handle Add/Edit/Delete
if (isset($_POST['submit'])) {
    $no_surat = mysqli_real_escape_string($koneksi, $_POST['no_surat']);
    $tgl_surat = $_POST['tgl_surat'];
    $tgl_terima = $_POST['tgl_terima'];
    $asal_surat = mysqli_real_escape_string($koneksi, $_POST['asal_surat']);
    $perihal = mysqli_real_escape_string($koneksi, $_POST['perihal']);
    
    // File Upload handling
    $file_name = "";
    if (isset($_FILES['file_surat']) && $_FILES['file_surat']['error'] == 0) {
        $target_dir = "uploads/";
        $file_ext = pathinfo($_FILES["file_surat"]["name"], PATHINFO_EXTENSION);
        $file_name = time() . "_" . uniqid() . "." . $file_ext;
        $target_file = $target_dir . $file_name;
        move_uploaded_file($_FILES["file_surat"]["tmp_name"], $target_file);
    }

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $id = $_POST['id'];
        $update_file = $file_name ? ", file_surat='$file_name'" : "";
        $query = "UPDATE surat_masuk SET no_surat='$no_surat', tgl_surat='$tgl_surat', tgl_terima='$tgl_terima', asal_surat='$asal_surat', perihal='$perihal' $update_file WHERE id=$id";
    } else {
        $query = "INSERT INTO surat_masuk (no_surat, tgl_surat, tgl_terima, asal_surat, perihal, file_surat) VALUES ('$no_surat', '$tgl_surat', '$tgl_terima', '$asal_surat', '$perihal', '$file_name')";
    }
    mysqli_query($koneksi, $query);
    header("Location: surat_masuk.php");
    exit();
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Delete file if exists
    $res = mysqli_query($koneksi, "SELECT file_surat FROM surat_masuk WHERE id=$id");
    $data = mysqli_fetch_assoc($res);
    if($data['file_surat'] && file_exists("uploads/".$data['file_surat'])) unlink("uploads/".$data['file_surat']);
    
    mysqli_query($koneksi, "DELETE FROM surat_masuk WHERE id=$id");
    header("Location: surat_masuk.php");
    exit();
}

// Search Handling
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$where = $search ? "WHERE no_surat LIKE '%$search%' OR asal_surat LIKE '%$search%' OR perihal LIKE '%$search%'" : "";

// Fetch Data
$query = "SELECT * FROM surat_masuk $where ORDER BY created_at DESC";
$result = mysqli_query($koneksi, $query);

// Edit data fetch
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $edit_res = mysqli_query($koneksi, "SELECT * FROM surat_masuk WHERE id=$id");
    $edit_data = mysqli_fetch_assoc($edit_res);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Masuk - Surat App</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --bg: #0f172a;
            --glass: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.08);
            --text: #f8fafc;
            --text-muted: #94a3b8;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg); color: var(--text); display: flex; min-height: 100vh; overflow-x: hidden; }
        
        /* Sidebar Responsive */
        .sidebar { 
            width: 260px; 
            background: rgba(15, 23, 42, 0.95); 
            backdrop-filter: blur(20px); 
            border-right: 1px solid var(--glass-border); 
            padding: 30px 20px; 
            display: flex; 
            flex-direction: column; 
            position: fixed; 
            height: 100vh; 
            z-index: 1000; 
            transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0 !important; }
            .menu-toggle { display: block !important; }
        }

        .logo { font-size: 1.5rem; font-weight: 600; background: linear-gradient(to right, #818cf8, #c084fc); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 40px; text-align: center; }
        .nav-menu { list-style: none; flex-grow: 1; }
        .nav-link { display: flex; align-items: center; padding: 12px 16px; color: var(--text-muted); text-decoration: none; border-radius: 12px; transition: 0.3s; margin-bottom: 8px; }
        .nav-link:hover, .nav-link.active { background: var(--glass); color: var(--text); border: 1px solid var(--glass-border); }
        .nav-link.active { background: rgba(99, 102, 241, 0.1); color: #818cf8; border-color: rgba(99, 102, 241, 0.2); }
        .logout-btn { margin-top: auto; color: #f87171; }

        .main-content { margin-left: 260px; flex-grow: 1; padding: 40px; transition: 0.4s; }
        
        .header-section { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; gap: 20px; }
        @media (max-width: 768px) {
            .header-section { flex-direction: column; }
            .search-container { width: 100% !important; }
        }

        .menu-toggle { 
            display: none; 
            font-size: 1.5rem; 
            cursor: pointer; 
            background: var(--glass); 
            padding: 10px; 
            border-radius: 10px; 
            border: 1px solid var(--glass-border);
            margin-bottom: 20px;
        }

        /* Table & Form Styles */
        .card { background: var(--glass); border: 1px solid var(--glass-border); border-radius: 20px; padding: 24px; margin-bottom: 30px; }
        .card-title { font-size: 1.25rem; font-weight: 600; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
        @media (max-width: 576px) { .form-grid { grid-template-columns: 1fr; } }
        
        .form-group { margin-bottom: 16px; }
        .form-group.full { grid-column: 1 / -1; }
        label { display: block; margin-bottom: 8px; font-size: 0.9rem; color: var(--text-muted); }
        input, textarea { width: 100%; padding: 12px; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--glass-border); border-radius: 10px; color: white; outline: none; transition: 0.3s; }
        input:focus { border-color: var(--primary); background: rgba(255,255,255,0.08); }
        input[type="file"] { padding: 8px; }
        
        .btn { padding: 10px 20px; border-radius: 10px; border: none; cursor: pointer; font-weight: 600; transition: 0.3s; text-decoration: none; display: inline-flex; align-items: center; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { opacity: 0.9; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3); }
        .btn-danger { background: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2); }
        .btn-danger:hover { background: #ef4444; color: white; }
        .btn-edit { background: rgba(59, 130, 246, 0.1); color: #60a5fa; margin-right: 8px; }
        .btn-file { background: rgba(16, 185, 129, 0.1); color: #34d399; margin-right: 8px; }
        .btn-sm { padding: 6px 12px; font-size: 0.85rem; }

        .search-container { position: relative; width: 300px; }
        .search-container input { padding-left: 40px; }
        .search-container i { position: absolute; left: 15px; top: 15px; color: var(--text-muted); }

        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; min-width: 600px; }
        th { text-align: left; padding: 12px; border-bottom: 1px solid var(--glass-border); color: var(--text-muted); font-size: 0.9rem; }
        td { padding: 16px 12px; border-bottom: 1px solid var(--glass-border); }
        tr:hover td { background: rgba(255,255,255,0.01); }
        
        .badge { padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; background: rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="logo">Surat App</div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li class="nav-item"><a href="surat_masuk.php" class="nav-link active"><i class="fas fa-envelope"></i> Surat Masuk</a></li>
            <li class="nav-item"><a href="surat_keluar.php" class="nav-link"><i class="fas fa-paper-plane"></i> Surat Keluar</a></li>
        </ul>
        <a href="auth/logout.php" class="nav-link logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="menu-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></div>
        
        <div class="header-section">
            <div>
                <h1>Surat Masuk</h1>
                <p style="color: var(--text-muted)">Manage incoming mail records.</p>
            </div>
            <form action="" method="GET" class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Search mail..." value="<?php echo htmlspecialchars($search); ?>">
            </form>
        </div>

        <div class="card">
            <div class="card-title"><?php echo $edit_data ? 'Edit Surat' : 'Tambah Surat'; ?></div>
            <form action="" method="POST" enctype="multipart/form-data">
                <?php if($edit_data): ?>
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_data['id']); ?>">
                <?php endif; ?>
                <div class="form-grid">
                    <div class="form-group">
                        <label>No Surat</label>
                        <input type="text" name="no_surat" required value="<?php echo htmlspecialchars($edit_data ? $edit_data['no_surat'] : ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Asal Surat</label>
                        <input type="text" name="asal_surat" required value="<?php echo htmlspecialchars($edit_data ? $edit_data['asal_surat'] : ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Tanggal Surat</label>
                        <input type="date" name="tgl_surat" required value="<?php echo htmlspecialchars($edit_data ? $edit_data['tgl_surat'] : ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Tanggal Terima</label>
                        <input type="date" name="tgl_terima" required value="<?php echo htmlspecialchars($edit_data ? $edit_data['tgl_terima'] : ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Lampiran File (PDF/Image)</label>
                        <input type="file" name="file_surat">
                    </div>
                    <div class="form-group full">
                        <label>Perihal</label>
                        <textarea name="perihal" rows="3" required><?php echo htmlspecialchars($edit_data ? $edit_data['perihal'] : ''); ?></textarea>
                    </div>
                </div>
                <button type="submit" name="submit" class="btn btn-primary">
                    <i class="fas fa-save" style="margin-right: 8px;"></i> Simpan Data
                </button>
                <?php if($edit_data): ?>
                    <a href="surat_masuk.php" class="btn btn-danger" style="margin-left: 10px;">Batal</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <div class="card-title">Daftar Surat Masuk</div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>No Surat</th>
                            <th>Asal</th>
                            <th>Tgl Terima</th>
                            <th>Perihal</th>
                            <th>File</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><div class="badge"><?php echo htmlspecialchars($row['no_surat']); ?></div></td>
                            <td><?php echo htmlspecialchars($row['asal_surat']); ?></td>
                            <td><?php echo date('d M Y', strtotime($row['tgl_terima'])); ?></td>
                            <td><?php echo htmlspecialchars($row['perihal']); ?></td>
                            <td>
                                <?php if($row['file_surat']): ?>
                                    <a href="uploads/<?php echo htmlspecialchars($row['file_surat']); ?>" target="_blank" class="btn btn-file btn-sm"><i class="fas fa-file-alt"></i></a>
                                <?php else: ?>
                                    <span style="color: #475569">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?edit=<?php echo htmlspecialchars($row['id']); ?>" class="btn btn-edit btn-sm"><i class="fas fa-edit"></i></a>
                                <a href="?delete=<?php echo htmlspecialchars($row['id']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus data?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>
