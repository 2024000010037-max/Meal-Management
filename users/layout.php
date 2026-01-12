<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
<title><?= isset($pageTitle) ? $pageTitle : 'User Panel' ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
 <style>
        body {
font-family: 'Segoe UI', sans-serif;
background: #f1f5f9;
  overflow-x: hidden;
        }
/* SIDEBAR STYLES */
.sidebar {
width: 250px;
 height: 100vh;
            background: #020617;
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
/* TOP HEADER */
        .top-header {
 background: #fff;
            padding: 15px 25px;
box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
align-items: center;
            justify-content: space-between;
        }
   /* TOGGLED STATE */
body.toggled .sidebar {
            left: -250px;
        }
body.toggled .main-content {
 margin-left: 0;
        }
/* MOBILE RESPONSIVE */
        @media (max-width: 768px) {
            .sidebar {
                left: -250px;
            }
    .main-content {
                margin-left: 0;
            }
   body.toggled .sidebar {
                left: 0;
            }
  body.toggled::before {
                content: '';
                position: fixed; top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 999;
            }
        }
    </style>
</head>
<body>
<!-- SIDEBAR -->
    <div class="sidebar">
div class="sidebar-header">
            <h4>My Portal</h4>
        </div>
        <div class="sidebar-menu">
 ass="<?= $cur == 'dashboard.php' ? 'active' : '' ?>"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>            
 <a href="meals.php" class="<?= $cur == 'meals.php' ? 'active' : '' ?>"><i class="bi bi-egg-fried me-2"></i> My Meals</a>    
<a href="bazar.php" class="<?= $cur == 'bazar.php' ? 'active' : '' ?>"><i class="bi bi-cart me-2"></i> Bazar List</a>
