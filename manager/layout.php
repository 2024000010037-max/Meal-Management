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
</body>
</html>
