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

       </div>
    </div>
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- Header -->
        <div class="top-header">
            <button class="btn btn-light border" id="menu-toggle"><i class="bi bi-list fs-5"></i></button>
            <div class="fw-bold text-secondary">Admin Panel</div>
        </div>
</body>
</html>
