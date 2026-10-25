<?php
require_once '../../includes/session.php';
require_once '../../includes/db.php';
requireAgriculteur();

$id = $_SESSION['user_id'];

// Delete offer
if ($_GET['action'] ?? '' === 'supprimer') {
    $id_offre = intval($_GET['id'] ?? 0);
    // Only if no candidatures
    $check = $pdo->prepare("SELECT COUNT(*) FROM candidature WHERE id_offre=?");
    $check->execute([$id_offre]);
    if ($check->fetchColumn() == 0) {
        $stmt = $pdo->prepare("DELETE FROM offre WHERE id_offre=? AND id_agriculteur=?");
        $stmt->execute([$id_offre, $id]);
        $msg = 'Offre supprimée.';
    } else {
        $error = 'Impossible de supprimer : des candidats ont postulé.';
    }
}

$offres = $pdo->prepare("
    SELECT o.*, tf.libelle AS fruit, g.libelle AS gouvernorat,
           COUNT(c.id_candidature) AS nb_candidats,
           SUM(c.decision='accepte') AS nb_acceptes,
           SUM(c.decision='encours') AS nb_encours
    FROM offre o
    JOIN type_fruit tf ON o.id_type_fruit=tf.id_type_fruit
    JOIN gouvernorat g ON o.id_gouvernorat=g.id_gouvernorat
    LEFT JOIN candidature c ON o.id_offre=c.id_offre
    WHERE o.id_agriculteur=?
    GROUP BY o.id_offre
    ORDER BY o.id_offre DESC
");
$offres->execute([$id]);
$offres = $offres->fetchAll();

$fruitEmojis = ['Olives'=>'🫒','Agrumes'=>'🍊','Tomates'=>'🍅','Dattes'=>'🌴','Raisins'=>'🍇','Pommes'=>'🍎','Poires'=>'🍐','Figues'=>'🌿'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mes Offres — Uber-Cueillette</title>
  <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="dashboard-layout">
  <aside class="sidebar">
    <div class="sidebar-user">
      <div class="avatar">👨‍🌾</div>
      <div class="name"><?= htmlspecialchars($_SESSION['prenom'].' '.$_SESSION['nom']) ?></div>
      <div class="role">Agriculteur</div>
    </div>
    <ul class="sidebar-nav">
      <li><a href="dashboard.php"><span class="nav-icon">🏠</span> Tableau de bord</a></li>
      <li><a href="ajouter_offre.php"><span class="nav-icon">➕</span> Ajouter une offre</a></li>
      <li><a href="mes_offres.php" class="active"><span class="nav-icon">📋</span> Mes offres</a></li>
      <li><a href="profil.php"><span class="nav-icon">👤</span> Mon profil</a></li>
      <li><a href="../auth/logout.php"><span class="nav-icon">🚪</span> Déconnexion</a></li>
    </ul>
  </aside>

  <main class="dashboard-main">
    <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;">
      <div>
        <h2>📋 Mes offres de récolte</h2>
        <p><?= count($offres) ?> offre(s) publiée(s)</p>
      </div>
      <a href="ajouter_offre.php" class="btn btn-primary">+ Nouvelle offre</a>
    </div>

    <?php if (isset($_GET['success'])): ?><div class="alert alert-success" data-dismiss>✅ Offre publiée avec succès ! <a href="ajouter_offre.php">Publier une autre →</a></div><?php endif; ?>
    <?php if (isset($msg)): ?><div class="alert alert-success" data-dismiss>✅ <?= $msg ?></div><?php endif; ?>
    <?php if (isset($error)): ?><div class="alert alert-error" data-dismiss>❌ <?= $error ?></div><?php endif; ?>

    <?php if (empty($offres)): ?>
      <div class="card">
        <div class="card-body text-center" style="padding:3rem">
          <div style="font-size:3rem;margin-bottom:1rem">🌾</div>
          <h3 style="font-family:'Playfair Display',serif;color:var(--bark)">Aucune offre publiée</h3>
          <p class="text-muted mt-1">Commencez par publier votre premier besoin de récolte.</p>
          <a href="ajouter_offre.php" class="btn btn-primary mt-2">Publier une offre</a>
        </div>
      </div>
    <?php else: ?>
    <div class="grid-offers">
      <?php foreach ($offres as $o):
        $closed = $o['date_limite'] < date('Y-m-d') || $o['nb_acceptes'] >= $o['nombre_ouvriers'];
        $emoji = $fruitEmojis[$o['fruit']] ?? '🌾';
        $spots_left = $o['nombre_ouvriers'] - $o['nb_acceptes'];
      ?>
      <div class="offer-card">
        <div class="offer-card-header">
          <div class="offer-fruit-badge"><?= $emoji ?> <?= htmlspecialchars($o['fruit']) ?></div>
          <div class="offer-price"><?= number_format($o['prix_journee'], 2) ?> <span>TND/jour</span></div>
        </div>
        <div class="offer-card-body">
          <div class="offer-meta">
            <div class="offer-meta-item"><span class="icon">📍</span> <?= htmlspecialchars($o['gouvernorat']) ?></div>
            <div class="offer-meta-item"><span class="icon">👥</span> <?= $o['nombre_ouvriers'] ?> ouvriers</div>
            <div class="offer-meta-item"><span class="icon">📅</span> <?= date('d/m/Y', strtotime($o['date_debut'])) ?></div>
            <div class="offer-meta-item"><span class="icon">🏁</span> <?= date('d/m/Y', strtotime($o['date_fin'])) ?></div>
          </div>
          <div style="font-size:0.85rem;color:var(--text-muted);margin-bottom:0.5rem">
            📍 <?= htmlspecialchars($o['adresse']) ?>
          </div>
          <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-top:0.5rem">
            <?php if ($closed): ?>
              <span class="badge badge-closed">Clôturée</span>
            <?php else: ?>
              <span class="badge badge-success">Active</span>
              <span class="badge badge-warning">⏰ Limite: <?= date('d/m/Y', strtotime($o['date_limite'])) ?></span>
            <?php endif; ?>
            <?php if ($o['nb_encours'] > 0): ?>
              <span class="badge badge-olive">📨 <?= $o['nb_encours'] ?> en attente</span>
            <?php endif; ?>
          </div>
        </div>
        <div class="offer-card-footer">
          <span class="spots-badge">✅ <?= $o['nb_acceptes'] ?>/<?= $o['nombre_ouvriers'] ?> postes</span>
          <div style="display:flex;gap:0.5rem;">
            <a href="postulants.php?id=<?= $o['id_offre'] ?>" class="btn btn-outline btn-sm">Postulants (<?= $o['nb_candidats'] ?>)</a>
            <?php if ($o['nb_candidats'] == 0): ?>
              <a href="?action=supprimer&id=<?= $o['id_offre'] ?>" class="btn btn-danger btn-sm"
                onclick="return confirmDelete('Supprimer cette offre ?')">🗑️</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </main>
</div>

<script src="../../js/main.js"></script>
</body>
</html>
