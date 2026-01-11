<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= isset($pageTitle) ? $pageTitle : 'Mess Admin' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f1f5f9;
            overflow-x: hidden; /* Prevent scroll when sidebar toggles */
        }
        /* SIDEBAR STYLES */
        .sidebar {
            width: 250px;
            height: 100vh;
            background: #0f172a;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #1e293b;
        }
      .sidebar-header h4 {
            color: #fff;
            margin: 0;
            font-weight: 700;
        }
      .sidebar-menu {
            padding: 10px 0;
            flex-grow: 1;
        }

      .sidebar-menu a {
            display: block;
            padding: 12px 25px;
            color: #cbd5e1;
            text-decoration: none;
            transition: 0.2s;
            border-left: 4px solid transparent;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: #1e293b;
            color: #fff;
            border-left-color: #3b82f6;
        }
      /* MAIN CONTENT WRAPPER */
        .main-content {
            margin-left: 250px;
            min-height: 100vh;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

      </style>
</head>
<body>
      <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4>Mess Admin</h4>
        </div>
        <div class="sidebar-menu">
            <?php $cur = basename($_SERVER['PHP_SELF']); ?>

<a href="create_user.php" class="<?= $cur == 'create_user.php' ? 'active' : '' ?>"><i class="bi bi-person-plus me-2"></i> Create User</a>



            
       </div>
    </div>
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- Header -->
        <div class="top-header">
            <button class="btn btn-light border" id="menu-toggle"><i class="bi bi-list fs-5"></i></button>
            <div class="fw-bold text-secondary">Admin Panel</div>
        </div>

        <!-- Dynamic Content -->
        <div class="p-4">
            <?= isset($content) ? $content : '' ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Sidebar Toggle Logic
        document.getElementById('menu-toggle').addEventListener('click', function() {
            document.body.classList.toggle('toggled');
        });
        / Close sidebar when clicking outside on mobile (overlay)
        document.body.addEventListener('click', function(e) {
            if (window.innerWidth <= 768 && document.body.classList.contains('toggled')) {
                if (!e.target.closest('.sidebar') && !e.target.closest('#menu-toggle')) {
                    document.body.classList.remove('toggled');
                }
            }
        });
    </script>
</body>
</html>
