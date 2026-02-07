<?php
include 'middleware/auth.php';
checkAuth();
include 'config/koneksi.php';

// Get stats
$q_masuk = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM surat_masuk");
$q_keluar = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM surat_keluar");
$stats_masuk = mysqli_fetch_assoc($q_masuk)['total'];
$stats_keluar = mysqli_fetch_assoc($q_keluar)['total'];

// Get recent mail
$recent_masuk = mysqli_query($koneksi, "SELECT * FROM surat_masuk ORDER BY created_at DESC LIMIT 5");
$recent_keluar = mysqli_query($koneksi, "SELECT * FROM surat_keluar ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Surat App</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --secondary: #a855f7;
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
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        
        .menu-toggle { 
            display: none; 
            font-size: 1.5rem; 
            cursor: pointer; 
            background: var(--glass); 
            padding: 10px; 
            border-radius: 10px; 
            border: 1px solid var(--glass-border);
        }

        .user-profile { background: var(--glass); padding: 8px 16px; border-radius: 30px; border: 1px solid var(--glass-border); display: flex; align-items: center; gap: 10px; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; margin-bottom: 40px; }
        .stat-card { background: var(--glass); border: 1px solid var(--glass-border); border-radius: 20px; padding: 24px; transition: 0.3s; position: relative; overflow: hidden; }
        .stat-card:hover { transform: translateY(-5px); background: rgba(255, 255, 255, 0.05); }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px; font-size: 1.2rem; }
        .icon-blue { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .icon-purple { background: rgba(168, 85, 247, 0.1); color: #a855f7; }

        .welcome-hero { background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(168, 85, 247, 0.15) 100%); border: 1px solid var(--glass-border); border-radius: 24px; padding: 40px; margin-bottom: 40px; position: relative; overflow: hidden; }
        .welcome-hero h2 { font-size: 1.8rem; margin-bottom: 10px; }
        .welcome-hero p { color: var(--text-muted); max-width: 600px; }

        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px; }
        @media (max-width: 576px) {
            .dashboard-grid { grid-template-columns: 1fr; }
        }

        .card { background: var(--glass); border: 1px solid var(--glass-border); border-radius: 24px; padding: 24px; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card-title { font-size: 1.1rem; font-weight: 600; color: var(--text); }
        .view-all { font-size: 0.8rem; color: var(--primary); text-decoration: none; }
        
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 300px; }
        th { text-align: left; padding: 12px; font-size: 0.8rem; color: var(--text-muted); border-bottom: 1px solid var(--glass-border); }
        td { padding: 12px; font-size: 0.9rem; border-bottom: 1px solid var(--glass-border); }
        tr:last-child td { border-bottom: none; }
        .badge { padding: 4px 8px; border-radius: 6px; font-size: 0.7rem; background: rgba(255, 255, 255, 0.1); }
        
        .empty-state { text-align: center; padding: 20px; color: var(--text-muted); font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="logo">Surat App</div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="dashboard.php" class="nav-link active"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li class="nav-item"><a href="surat_masuk.php" class="nav-link"><i class="fas fa-envelope"></i> Surat Masuk</a></li>
            <li class="nav-item"><a href="surat_keluar.php" class="nav-link"><i class="fas fa-paper-plane"></i> Surat Keluar</a></li>
        </ul>
        <a href="auth/logout.php" class="nav-link logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="header">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div class="menu-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></div>
                <div>
                    <h1>Overview</h1>
                    <p style="color: var(--text-muted)">Welcome back, <?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'] ?? 'User')[0]); ?>!</p>
                </div>
            </div>
            <div class="user-profile">
                <i class="fas fa-user-circle"></i>
                <span><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></span>
            </div>
        </div>

        <div class="welcome-hero">
            <h2>Manage Documents Effortlessly ✨</h2>
            <p>Welcome to the Surat App dashboard. Track your incoming and outgoing mail with real-time statistics and clean organization.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-blue"><i class="fas fa-envelope-open"></i></div>
                <div style="font-size: 2rem; font-weight: 600;"><?php echo (int)$stats_masuk; ?></div>
                <div style="color: var(--text-muted); font-size: 0.9rem;">Total Incoming Mail</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-purple"><i class="fas fa-paper-plane"></i></div>
                <div style="font-size: 2rem; font-weight: 600;"><?php echo (int)$stats_keluar; ?></div>
                <div style="color: var(--text-muted); font-size: 0.9rem;">Total Outgoing Mail</div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Recent Incoming Mail</div>
                    <a href="surat_masuk.php" class="view-all">View All</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>No Surat</th>
                                <th>Asal</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($recent_masuk) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($recent_masuk)): ?>
                                <tr>
                                    <td><span class="badge"><?php echo htmlspecialchars($row['no_surat']); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['asal_surat']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($row['tgl_terima'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="empty-state">No recent mail found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Recent Outgoing Mail</div>
                    <a href="surat_keluar.php" class="view-all">View All</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>No Surat</th>
                                <th>Tujuan</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($recent_keluar) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($recent_keluar)): ?>
                                <tr>
                                    <td><span class="badge"><?php echo htmlspecialchars($row['no_surat']); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['tujuan_surat']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($row['tgl_kirim'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="empty-state">No recent mail found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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
