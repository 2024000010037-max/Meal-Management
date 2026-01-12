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
