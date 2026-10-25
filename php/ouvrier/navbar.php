<?php
// includes/navbar_ouvrier.php
?>
<nav class="navbar">
  <a href="dashboard.php" class="navbar-brand">
    <div class="logo-icon">🫒</div>
    <div>
      <span class="brand-name">Uber-Cueillette</span>
      <span class="brand-sub">Espace Ouvrier</span>
    </div>
  </a>
  <ul class="navbar-nav">
    <li><a href="dashboard.php">Tableau de bord</a></li>
    <li><a href="offres.php">Offres</a></li>
    <li><a href="mes_candidatures.php">Mes candidatures</a></li>
    <li><a href="chantiers.php">Historique</a></li>
    <li><a href="profil.php">👤 <?= htmlspecialchars($_SESSION['pseudo']) ?></a></li>
    <li><a href="../auth/logout.php" class="btn-nav">Déconnexion</a></li>
  </ul>
</nav>
