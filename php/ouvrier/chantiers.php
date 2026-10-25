<?php
require_once '../../includes/session.php';
require_once '../../includes/db.php';
requireOuvrier();

$id = $_SESSION['user_id'];

$chantiers = $pdo->prepare("
    SELECT c.*, o.date_debut, o.date_fin, o.prix_journee, o.adresse AS adresse_chantier,
           tf.libelle AS fruit, g.libelle AS gouvernorat,
           ag.nom AS agric_nom, ag.prenom AS agric_prenom
    FROM candidature c
    JOIN offre o ON c.id_offre=o.id_offre
    JOIN type_fruit tf ON o.id_type_fruit=tf.id_type_fruit
    JOIN gouvernorat g ON o.id_gouvernorat=g.id_gouvernorat
    JOIN agriculteur ag ON o.id_agriculteur=ag.id_agriculteur
    WHERE c.id_ouvrier=? AND c.decision='accepte' AND o.date_fin < CURDATE()
    ORDER BY o.date_fin DESC
");
$chantiers->execute([$id]);
$chantiers = $chantiers->fetchAll();

$totalGains = array_sum(array_column($chantiers, 'remuneration'));
$notes = array_filter(array_column($chantiers, 'note'), fn($n) => $n !== null);
$noteMoy = count($notes) ? array_sum($notes)/count($notes) : null;

$fruitEmojis = ['Olives'=>'🫒','Agrumes'=>'🍊','Tomates'=>'🍅','Dattes'=>'🌴','Raisins'=>'🍇','Pommes'=>'🍎','Poires'=>'🍐','Figues'=>'🌿'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Historique & Gains — Uber-Cueillette</title>
  <link rel="stylesheet" href="../../css/style.css">
  <style>
    .chantier-card {
      background: var(--white);
      border-radius: var(--radius-lg);
      border: 1px solid var(--border);
      padding: 1.5rem;
      transition: box-shadow 0.2s;
    }
    .chantier-card:hover { box-shadow: 0 4px 20px var(--shadow); }
    .note-stars { color: var(--harvest); font-size: 1.2rem; }
    .gains-banner {
      background: linear-gradient(135deg, var(--olive) 0%, var(--olive-light) 100%);
      border-radius: var(--radius-lg);
      padding: 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
      color: white;
    }
    .gains-amount { font-family: 'Playfair Display', serif; font-size: 2.5rem; font-weight: 700; }
  </style>
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
      <li><a href="mes_candidatures.php"><span class="nav-icon">📨</span> Mes candidatures</a></li>
      <li><a href="chantiers.php" class="active"><span class="nav-icon">📊</span> Historique & Gains</a></li>
      <li><a href="profil.php"><span class="nav-icon">👤</span> Mon profil</a></li>
      <li><a href="../auth/logout.php"><span class="nav-icon">🚪</span> Déconnexion</a></li>
    </ul>
  </aside>

  <main class="dashboard-main">
    <div class="page-header">
      <h2>📊 Historique des chantiers & Gains</h2>
      <p><?= count($chantiers) ?> chantier(s) terminé(s)</p>
    </div>

    <!-- GAINS BANNER -->
    <div class="gains-banner">
      <div>
        <div style="opacity:0.85;font-size:0.9rem;letter-spacing:0.05em;text-transform:uppercase;">Total des gains</div>
        <div class="gains-amount"><?= number_format($totalGains, 2) ?> TND</div>
      </div>
      <div style="text-align:right">
        <div style="opacity:0.85;font-size:0.9rem;margin-bottom:0.3rem">Note moyenne</div>
        <div style="font-size:2rem">
          <?php if ($noteMoy !== null): ?>
            <?= number_format($noteMoy, 1) ?>/10
            <div class="note-stars" style="font-size:1.2rem">
              <?php for ($i=1;$i<=10;$i++) echo $i<=round($noteMoy)?'⭐':'☆'; ?>
            </div>
          <?php else: ?>—<?php endif; ?>
        </div>
      </div>
    </div>

    <?php if (empty($chantiers)): ?>
      <div class="card"><div class="card-body text-center" style="padding:3rem">
        <div style="font-size:3rem">🌾</div>
        <p class="text-muted mt-2">Aucun chantier terminé pour l'instant.</p>
      </div></div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:1rem;">
      <?php foreach ($chantiers as $c):
        $emoji = $fruitEmojis[$c['fruit']] ?? '🌾';
        $jours = max(1, round((strtotime($c['date_fin']) - strtotime($c['date_debut'])) / 86400));
      ?>
      <div class="chantier-card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;">
          <div>
            <h4 style="font-family:'Playfair Display',serif;font-size:1.1rem;">
              <?= $emoji ?> Récolte de <?= htmlspecialchars($c['fruit']) ?>
            </h4>
            <p style="color:var(--text-muted);font-size:0.87rem;margin-top:0.2rem">
              📍 <?= htmlspecialchars($c['gouvernorat']) ?> — <?= htmlspecialchars($c['adresse_chantier']) ?><br>
              📅 <?= date('d/m/Y', strtotime($c['date_debut'])) ?> → <?= date('d/m/Y', strtotime($c['date_fin'])) ?>
              <em>(<?= $jours ?> jour<?= $jours>1?'s':'' ?>)</em><br>
              👨‍🌾 Agriculteur: <?= htmlspecialchars($c['agric_prenom'].' '.$c['agric_nom']) ?>
            </p>
          </div>
          <div style="text-align:right">
            <?php if ($c['remuneration'] !== null): ?>
              <div style="font-family:'Playfair Display',serif;font-size:1.5rem;color:var(--olive);font-weight:700">
                <?= number_format($c['remuneration'], 2) ?> TND
              </div>
              <div style="font-size:0.8rem;color:var(--text-muted)">rémunération</div>
            <?php else: ?>
              <div style="background:var(--harvest-pale);padding:0.5rem 1rem;border-radius:8px;font-size:0.85rem;color:var(--bark-mid)">
                💰 <?= number_format($jours * $c['prix_journee'], 2) ?> TND estimé
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Note & commentaire -->
        <?php if ($c['note'] !== null): ?>
        <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border)">
          <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
            <strong style="font-size:0.9rem">Évaluation:</strong>
            <div class="note-stars">
              <?php for ($i=1;$i<=10;$i++) echo $i<=$c['note']?'⭐':'☆'; ?>
            </div>
            <span style="font-weight:700;color:var(--bark)"><?= $c['note'] ?>/10</span>
          </div>
          <?php if ($c['commentaire']): ?>
            <p style="margin-top:0.5rem;font-style:italic;color:var(--text-muted);font-size:0.88rem;background:var(--cream);padding:0.6rem;border-radius:8px;">
              💬 "<?= htmlspecialchars($c['commentaire']) ?>"
            </p>
          <?php endif; ?>
        </div>
        <?php else: ?>
        <div style="margin-top:0.8rem;padding-top:0.8rem;border-top:1px solid var(--border)">
          <span style="font-size:0.82rem;color:var(--text-muted)">⏳ Pas encore évalué</span>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </main>
</div>
<script src="../../js/main.js"></script>
</body>
</html>
