<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= isset($pageTitle) ? $pageTitle : 'Manager Panel' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
    <body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4>Manager Panel</h4>
        </div>
        <div class="sidebar-menu">
            <?php $cur = basename($_SERVER['PHP_SELF']); ?>
            <a href="dashboard.php" class="<?= $cur == 'dashboard.php' ? 'active' : '' ?>"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
            <a href="meal_entry.php" class="<?= $cur == 'meal_entry.php' ? 'active' : '' ?>"><i class="bi bi-egg-fried me-2"></i> Meal Entry</a>
            <a href="bazar_entry.php" class="<?= $cur == 'bazar_entry.php' ? 'active' : '' ?>"><i class="bi bi-cart-plus me-2"></i> Bazar Entry</a>
            <a href="deposit.php" class="<?= $cur == 'deposit.php' ? 'active' : '' ?>"><i class="bi bi-wallet2 me-2"></i> Deposit</a>
            <a href="monthly_report.php" class="<?= $cur == 'monthly_report.php' ? 'active' : '' ?>"><i class="bi bi-file-earmark-bar-graph me-2"></i> Monthly Report</a>
        </div>
    </div>
         <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- Header -->
        <div class="top-header">
            <button class="btn btn-light border" id="menu-toggle"><i class="bi bi-list fs-5"></i></button>
            
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php
                    $nav_img = "https://ui-avatars.com/api/?name=" . urlencode($_SESSION['full_name'] ?? 'Manager') . "&background=0D6EFD&color=fff";
                    if(isset($_SESSION['user_id']) && isset($pdo)) {
                        $stmt_nav = $pdo->prepare("SELECT photo FROM users WHERE id = ?");
                        $stmt_nav->execute([$_SESSION['user_id']]);
                        $user_nav = $stmt_nav->fetch(PDO::FETCH_ASSOC);
                        if ($user_nav && !empty($user_nav['photo'])) {
                            $nav_img = "../uploads/" . $user_nav['photo'];
                        }
                    }
                    ?>
                    <img src="<?= $nav_img ?>" class="rounded-circle me-2 shadow-sm" width="38" height="38" alt="User" style="object-fit: cover;">
                    <span class="fw-bold text-dark d-none d-sm-inline"><?= $_SESSION['full_name'] ?? 'Manager' ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="userDropdown" style="border-radius: 12px; min-width: 200px;">
                    <li><a class="dropdown-item py-2" href="account.php"><i class="bi bi-person-circle me-2 text-primary"></i> My Account</a></li>
                    <li><a class="dropdown-item py-2" href="../auth/change_password.php"><i class="bi bi-key me-2 text-warning"></i> Change Password</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2 text-danger" href="../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>
        <!-- Dynamic Content -->
        <div class="p-4">
            <?= isset($content) ? $content : '' ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('menu-toggle').addEventListener('click', function() {
            document.body.classList.toggle('toggled');
        });
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
