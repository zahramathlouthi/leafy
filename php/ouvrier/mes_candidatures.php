<?php
require_once '../../includes/session.php';
require_once '../../includes/db.php';
requireOuvrier();

$id = $_SESSION['user_id'];

$candidatures = $pdo->prepare("
    SELECT c.*, o.date_debut, o.date_fin, o.date_limite, o.prix_journee, o.adresse AS adresse_chantier,
           tf.libelle AS fruit, g.libelle AS gouvernorat,
           ag.nom AS agric_nom, ag.prenom AS agric_prenom
    FROM candidature c
    JOIN offre o ON c.id_offre=o.id_offre
    JOIN type_fruit tf ON o.id_type_fruit=tf.id_type_fruit
    JOIN gouvernorat g ON o.id_gouvernorat=g.id_gouvernorat
    JOIN agriculteur ag ON o.id_agriculteur=ag.id_agriculteur
    WHERE c.id_ouvrier=?
    ORDER BY c.date_candidature DESC
");
$candidatures->execute([$id]);
$candidatures = $candidatures->fetchAll();

$fruitEmojis = ['Olives'=>'🫒','Agrumes'=>'🍊','Tomates'=>'🍅','Dattes'=>'🌴','Raisins'=>'🍇','Pommes'=>'🍎','Poires'=>'🍐','Figues'=>'🌿'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mes Candidatures — Uber-Cueillette</title>
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
      <li><a href="dashboard.php"><span class="nav-icon">🏠</span> Tableau de bord</a></li>
      <li><a href="offres.php"><span class="nav-icon">🌾</span> Offres disponibles</a></li>
      <li><a href="mes_candidatures.php" class="active"><span class="nav-icon">📨</span> Mes candidatures</a></li>
      <li><a href="chantiers.php"><span class="nav-icon">📊</span> Historique & Gains</a></li>
      <li><a href="profil.php"><span class="nav-icon">👤</span> Mon profil</a></li>
      <li><a href="../auth/logout.php"><span class="nav-icon">🚪</span> Déconnexion</a></li>
    </ul>
  </aside>

  <main class="dashboard-main">
    <div class="page-header">
      <h2>📨 Mes candidatures</h2>
      <p><?= count($candidatures) ?> candidature(s) envoyée(s)</p>
    </div>

    <?php if (empty($candidatures)): ?>
      <div class="card"><div class="card-body text-center" style="padding:3rem">
        <div style="font-size:3rem">📭</div>
        <h3 style="font-family:'Playfair Display',serif;color:var(--bark);margin-top:1rem">Aucune candidature</h3>
        <p class="text-muted mt-1">Consultez les offres disponibles et postulez !</p>
        <a href="offres.php" class="btn btn-primary mt-2">Voir les offres</a>
      </div></div>
    <?php else: ?>
    <div class="card">
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Fruit</th><th>Lieu</th><th>Période</th><th>Prix/jour</th>
              <th>Agriculteur</th><th>Date candidature</th><th>Décision</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($candidatures as $c):
              $emoji = $fruitEmojis[$c['fruit']] ?? '🌾';
            ?>
            <tr>
              <td><?= $emoji ?> <?= htmlspecialchars($c['fruit']) ?></td>
              <td><?= htmlspecialchars($c['gouvernorat']) ?><br><small class="text-muted"><?= htmlspecialchars($c['adresse_chantier']) ?></small></td>
              <td><?= date('d/m/Y', strtotime($c['date_debut'])) ?><br>→ <?= date('d/m/Y', strtotime($c['date_fin'])) ?></td>
              <td><strong><?= number_format($c['prix_journee'], 2) ?> TND</strong></td>
              <td><?= htmlspecialchars($c['agric_prenom'].' '.$c['agric_nom']) ?></td>
              <td><?= date('d/m/Y H:i', strtotime($c['date_candidature'])) ?></td>
              <td>
                <?php if ($c['decision'] === 'accepte'): ?>
                  <span class="badge badge-success">✅ Accepté</span>
                <?php elseif ($c['decision'] === 'refuse'): ?>
                  <span class="badge badge-danger">❌ Refusé</span>
                <?php else: ?>
                  <span class="badge badge-warning">⏳ En attente</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
  </main>
</div>
<script src="../../js/main.js"></script>
</body>
</html>
