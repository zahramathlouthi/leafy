<?php
// includes/navbar_agriculteur.php
$current = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar">
  <a href="dashboard.php" class="navbar-brand">
    <div class="logo-icon">🫒</div>
    <div>
      <span class="brand-name">Uber-Cueillette</span>
      <span class="brand-sub">Espace Agriculteur</span>
    </div>
  </a>
  <ul class="navbar-nav">
    <li><a href="dashboard.php">Tableau de bord</a></li>
    <li><a href="ajouter_offre.php">+ Offre</a></li>
    <li><a href="mes_offres.php">Mes offres</a></li>
    <li><a href="profil.php">👤 <?= htmlspecialchars($_SESSION['pseudo']) ?></a></li>
    <li><a href="../auth/logout.php" class="btn-nav">Déconnexion</a></li>
  </ul>
</nav>
