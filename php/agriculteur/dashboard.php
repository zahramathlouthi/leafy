<?php
require_once '../../includes/session.php';
require_once '../../includes/db.php';
requireAgriculteur();

$id = $_SESSION['user_id'];

// Stats
$totalOffres = $pdo->prepare("SELECT COUNT(*) FROM offre WHERE id_agriculteur=?");
$totalOffres->execute([$id]); $totalOffres = $totalOffres->fetchColumn();

$offresActives = $pdo->prepare("SELECT COUNT(*) FROM offre WHERE id_agriculteur=? AND date_limite >= CURDATE()");
$offresActives->execute([$id]); $offresActives = $offresActives->fetchColumn();

$totalCandidatures = $pdo->prepare("SELECT COUNT(*) FROM candidature c JOIN offre o ON c.id_offre=o.id_offre WHERE o.id_agriculteur=?");
$totalCandidatures->execute([$id]); $totalCandidatures = $totalCandidatures->fetchColumn();

$acceptes = $pdo->prepare("SELECT COUNT(*) FROM candidature c JOIN offre o ON c.id_offre=o.id_offre WHERE o.id_agriculteur=? AND c.decision='accepte'");
$acceptes->execute([$id]); $acceptes = $acceptes->fetchColumn();

// Recent offers
$recentOffres = $pdo->prepare("
    SELECT o.*, tf.libelle AS fruit, g.libelle AS gouvernorat,
           COUNT(c.id_candidature) AS nb_candidats,
           SUM(c.decision='accepte') AS nb_acceptes
    FROM offre o
    JOIN type_fruit tf ON o.id_type_fruit=tf.id_type_fruit
    JOIN gouvernorat g ON o.id_gouvernorat=g.id_gouvernorat
    LEFT JOIN candidature c ON o.id_offre=c.id_offre
    WHERE o.id_agriculteur=?
    GROUP BY o.id_offre
    ORDER BY o.id_offre DESC LIMIT 5
");
$recentOffres->execute([$id]);
$offres = $recentOffres->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tableau de Bord — Agriculteur</title>
  <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="dashboard-layout">
  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-user">
      <div class="avatar">👨‍🌾</div>
      <div class="name"><?= htmlspecialchars($_SESSION['prenom'].' '.$_SESSION['nom']) ?></div>
      <div class="role">Agriculteur</div>
    </div>
    <ul class="sidebar-nav">
      <li><a href="dashboard.php" class="active"><span class="nav-icon">🏠</span> Tableau de bord</a></li>
      <li><a href="ajouter_offre.php"><span class="nav-icon">➕</span> Ajouter une offre</a></li>
      <li><a href="mes_offres.php"><span class="nav-icon">📋</span> Mes offres</a></li>
      <li><a href="profil.php"><span class="nav-icon">👤</span> Mon profil</a></li>
      <li><a href="../auth/logout.php"><span class="nav-icon">🚪</span> Déconnexion</a></li>
    </ul>
  </aside>

  <!-- MAIN -->
  <main class="dashboard-main">
    <div class="page-header">
      <h2>Bonjour, <?= htmlspecialchars($_SESSION['prenom']) ?> 👋</h2>
      <p>Voici un aperçu de votre activité sur la plateforme.</p>
    </div>

    <!-- STATS -->
    <div class="grid-4 mb-2">
      <div class="stat-card">
        <div class="stat-icon">📋</div>
        <div class="stat-value"><?= $totalOffres ?></div>
        <div class="stat-label">Total offres publiées</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🟢</div>
        <div class="stat-value"><?= $offresActives ?></div>
        <div class="stat-label">Offres actives</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">📨</div>
        <div class="stat-value"><?= $totalCandidatures ?></div>
        <div class="stat-label">Candidatures reçues</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">✅</div>
        <div class="stat-value"><?= $acceptes ?></div>
        <div class="stat-label">Ouvriers acceptés</div>
      </div>
    </div>

    <!-- RECENT OFFERS -->
    <div class="card">
      <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <h3 style="font-family:'Playfair Display',serif;font-size:1.2rem;">Offres récentes</h3>
        <a href="ajouter_offre.php" class="btn btn-primary btn-sm">+ Nouvelle offre</a>
      </div>
      <div class="table-wrap">
        <?php if (empty($offres)): ?>
          <div style="padding:2rem;text-align:center;color:var(--text-muted);">
            Aucune offre publiée. <a href="ajouter_offre.php">Créer votre première offre →</a>
          </div>
        <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Fruit</th><th>Gouvernorat</th><th>Période</th>
              <th>Prix/jour</th><th>Postulants</th><th>Statut</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($offres as $o):
              $closed = $o['date_limite'] < date('Y-m-d') || $o['nb_acceptes'] >= $o['nombre_ouvriers'];
              $fruitEmoji = ['Olives'=>'🫒','Agrumes'=>'🍊','Tomates'=>'🍅','Dattes'=>'🌴','Raisins'=>'🍇','Pommes'=>'🍎','Poires'=>'🍐','Figues'=>'🌿'][$o['fruit']] ?? '🌾';
            ?>
            <tr>
              <td><?= $fruitEmoji ?> <?= htmlspecialchars($o['fruit']) ?></td>
              <td><?= htmlspecialchars($o['gouvernorat']) ?></td>
              <td><?= date('d/m/Y', strtotime($o['date_debut'])) ?> → <?= date('d/m/Y', strtotime($o['date_fin'])) ?></td>
              <td><strong><?= number_format($o['prix_journee'], 2) ?> TND</strong></td>
              <td>
                <span class="badge <?= $o['nb_candidats'] > 0 ? 'badge-olive' : 'badge-warning' ?>">
                  <?= $o['nb_candidats'] ?> candidat(s)
                </span>
              </td>
              <td>
                <?php if ($closed): ?>
                  <span class="badge badge-closed">Clôturée</span>
                <?php else: ?>
                  <span class="badge badge-success">Active</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="postulants.php?id=<?= $o['id_offre'] ?>" class="btn btn-outline btn-sm">Postulants</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
      <?php if (!empty($offres)): ?>
      <div class="card-footer">
        <a href="mes_offres.php">Voir toutes les offres →</a>
      </div>
      <?php endif; ?>
    </div>
  </main>
</div>

<script src="../../js/main.js"></script>
</body>
</html>
