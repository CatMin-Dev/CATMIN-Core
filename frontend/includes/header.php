<?php
if (!isset($pageTitle)) {
    $pageTitle = 'CATMIN Frontend';
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="icon" type="image/png" href="assets/img/icon.png">
  <link rel="apple-touch-icon" href="assets/img/icon.png">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="index.php">
        <img src="assets/img/logo_color.png" alt="CATMIN" class="brand-logo">
        <span>CATMIN Frontend</span>
      </a>
      <nav class="main-nav">
        <a href="index.php">Accueil</a>
        <a href="../dashboard/index.php?page=dashboard" class="btn-dashboard">Dashboard</a>
      </nav>
    </div>
  </header>
  <main class="container">
