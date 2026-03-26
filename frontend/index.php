<?php
$pageTitle = 'CATMIN Frontend';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <h1>Frontend CATMIN</h1>
  <p>Projet frontend en PHP prêt à être relié au dashboard.</p>
  <div class="hero-actions">
    <a class="btn-primary" href="../dashboard/index.php?page=dashboard">Ouvrir le dashboard</a>
    <a class="btn-secondary" href="../dashboard/login.html">Connexion admin</a>
  </div>
</section>

<section class="card-grid">
  <article class="card">
    <h2>Structure PHP</h2>
    <p>Header et footer partagés via includes pour faciliter l'évolution du frontend.</p>
  </article>
  <article class="card">
    <h2>Assets synchronisés</h2>
    <p>Favicon, icône et logos copiés depuis dashboard/assets/img vers frontend/assets/img.</p>
  </article>
  <article class="card">
    <h2>Intégration simple</h2>
    <p>Navigation directe vers le dashboard pour relier les deux parties du projet.</p>
  </article>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
