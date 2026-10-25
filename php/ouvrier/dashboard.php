<?php
require_once '../../includes/session.php';
require_once '../../includes/db.php';
requireOuvrier();

$id = $_SESSION['user_id'];

$totalCandidatures = $pdo->prepare("SELECT COUNT(*) FROM candidature WHERE id_ouvrier=?");
$totalCandidatures->execute([$id]); $totalCandidatures = $totalCandidatures->fetchColumn();

$acceptes = $pdo->prepare("SELECT COUNT(*) FROM candidature WHERE id_ouvrier=? AND decision='accepte'");
$acceptes->execute([$id]); $acceptes = $acceptes->fetchColumn();

$totalGains = $pdo->prepare("SELECT COALESCE(SUM(remuneration),0) FROM candidature WHERE id_ouvrier=? AND remuneration IS NOT NULL");
$totalGains->execute([$id]); $totalGains = $totalGains->fetchColumn();

$noteMoy = $pdo->prepare("SELECT AVG(note) FROM candidature WHERE id_ouvrier=? AND note IS NOT NULL");
$noteMoy->execute([$id]); $noteMoy = $noteMoy->fetchColumn();

// Recent offres
$recentOffres = $pdo->prepare("
    SELECT o.*, tf.libelle AS fruit, g.libelle AS gouvernorat,
           COUNT(c.id_candidature) AS nb_acceptes
    FROM offre o
    JOIN type_fruit tf ON o.id_type_fruit=tf.id_type_fruit
    JOIN gouvernorat g ON o.id_gouvernorat=g.id_gouvernorat
    LEFT JOIN candidature c ON o.id_offre=c.id_offre AND c.decision='accepte'
    WHERE o.date_limite >= CURDATE()
    GROUP BY o.id_offre
    HAVING nb_acceptes < o.nombre_ouvriers
    ORDER BY o.prix_journee DESC LIMIT 4
");
$recentOffres->execute();
$offres = $recentOffres->fetchAll();

$fruitEmojis = ['Olives'=>'🫒','Agrumes'=>'🍊','Tomates'=>'🍅','Dattes'=>'🌴','Raisins'=>'🍇','Pommes'=>'🍎','Poires'=>'🍐','Figues'=>'🌿'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tableau de Bord — Ouvrier</title>
  <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="dashboard-layout">
  <aside class="sidebar">
    <div class="sidebar-user">
      <div class="avatar">👷</div>
      <div class="name"><?= htmlspecialchars($_SESSION['prenom'].' '.$_SESSION['nom']) ?></div>
      <div class="role">Ouvrier de récolte</div>
    </div>
    <ul class="sidebar-nav">
      <li><a href="dashboard.php" class="active"><span class="nav-icon">🏠</span> Tableau de bord</a></li>
      <li><a href="offres.php"><span class="nav-icon">🌾</span> Offres disponibles</a></li>
      <li><a href="mes_candidatures.php"><span class="nav-icon">📨</span> Mes candidatures</a></li>
      <li><a href="chantiers.php"><span class="nav-icon">📊</span> Historique & Gains</a></li>
      <li><a href="profil.php"><span class="nav-icon">👤</span> Mon profil</a></li>
      <li><a href="../auth/logout.php"><span class="nav-icon">🚪</span> Déconnexion</a></li>
    </ul>
  </aside>

  <main class="dashboard-main">
    <div class="page-header">
      <h2>Bonjour, <?= htmlspecialchars($_SESSION['prenom']) ?> 👋</h2>
      <p>Bienvenue sur votre espace ouvrier. Trouvez votre prochain chantier !</p>
    </div>

    <!-- STATS -->
    <div class="grid-4 mb-2">
      <div class="stat-card">
        <div class="stat-icon">📨</div>
        <div class="stat-value"><?= $totalCandidatures ?></div>
        <div class="stat-label">Candidatures envoyées</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">✅</div>
        <div class="stat-value"><?= $acceptes ?></div>
        <div class="stat-label">Missions acceptées</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">💰</div>
        <div class="stat-value"><?= number_format($totalGains, 0) ?></div>
        <div class="stat-label">Gains totaux (TND)</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">⭐</div>
        <div class="stat-value"><?= $noteMoy ? number_format($noteMoy, 1) : '—' ?></div>
        <div class="stat-label">Note moyenne / 10</div>
      </div>
    </div>

    <!-- LATEST OFFERS -->
    <div class="card">
      <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <h3 style="font-family:'Playfair Display',serif;font-size:1.2rem;">🌾 Offres récentes</h3>
        <a href="offres.php" class="btn btn-primary btn-sm">Voir toutes</a>
      </div>
      <div class="card-body">
        <?php if (empty($offres)): ?>
          <p class="text-muted text-center">Aucune offre disponible.</p>
        <?php else: ?>
        <div class="grid-offers">
          <?php foreach ($offres as $o):
            $emoji = $fruitEmojis[$o['fruit']] ?? '🌾';
          ?>
          <div class="offer-card">
            <div class="offer-card-header">
              <div class="offer-fruit-badge"><?= $emoji ?> <?= htmlspecialchars($o['fruit']) ?></div>
              <div class="offer-price"><?= number_format($o['prix_journee'], 2) ?> <span>TND/jour</span></div>
            </div>
            <div class="offer-card-body">
              <div class="offer-meta">
                <div class="offer-meta-item"><span class="icon">📍</span><?= htmlspecialchars($o['gouvernorat']) ?></div>
                <div class="offer-meta-item"><span class="icon">📅</span><?= date('d/m/Y', strtotime($o['date_debut'])) ?></div>
              </div>
            </div>
            <div class="offer-card-footer">
              <span class="spots-badge">🗓️ Limite: <?= date('d/m/Y', strtotime($o['date_limite'])) ?></span>
              <a href="offres.php?postuler=<?= $o['id_offre'] ?>" class="btn btn-primary btn-sm">Postuler</a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<script src="../../js/main.js"></script>
</body>
</html>
