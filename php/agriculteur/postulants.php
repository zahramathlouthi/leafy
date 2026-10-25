<?php
require_once '../../includes/session.php';
require_once '../../includes/db.php';
requireAgriculteur();

$id_offre = intval($_GET['id'] ?? 0);
$id_agric = $_SESSION['user_id'];

// Verify ownership
$offre = $pdo->prepare("SELECT o.*, tf.libelle AS fruit, g.libelle AS gouvernorat FROM offre o JOIN type_fruit tf ON o.id_type_fruit=tf.id_type_fruit JOIN gouvernorat g ON o.id_gouvernorat=g.id_gouvernorat WHERE o.id_offre=? AND o.id_agriculteur=?");
$offre->execute([$id_offre, $id_agric]);
$offre = $offre->fetch();
if (!$offre) { header('Location: mes_offres.php'); exit; }

$nb_acceptes_current = $pdo->prepare("SELECT COUNT(*) FROM candidature WHERE id_offre=? AND decision='accepte'");
$nb_acceptes_current->execute([$id_offre]);
$nb_acceptes_current = $nb_acceptes_current->fetchColumn();
$is_full = $nb_acceptes_current >= $offre['nombre_ouvriers'];

// Handle accept/refuse via POST (prevents CSRF via GET links)
$msg = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['cid'])) {
    $action  = $_POST['action'];
    $id_cand = intval($_POST['cid']);
    if ($action === 'accepter' && !$is_full) {
        $pdo->prepare("UPDATE candidature SET decision='accepte' WHERE id_candidature=? AND id_offre=?")->execute([$id_cand, $id_offre]);
        $msg = 'Candidature acceptée !';
        // Re-check if now full
        $nb_acceptes_current++;
        $is_full = $nb_acceptes_current >= $offre['nombre_ouvriers'];
    } elseif ($action === 'refuser') {
        $pdo->prepare("UPDATE candidature SET decision='refuse' WHERE id_candidature=? AND id_offre=?")->execute([$id_cand, $id_offre]);
        $msg = 'Candidature refusée.';
    } elseif ($action === 'accepter' && $is_full) {
        $error = 'Quota atteint — impossible d\'accepter d\'autres ouvriers.';
    }
}

// Handle evaluation (after chantier)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['evaluer'])) {
    $id_cand = intval($_POST['id_candidature']);
    $note    = intval($_POST['note']);
    $comm    = trim($_POST['commentaire'] ?? '');
    $remun   = floatval($_POST['remuneration'] ?? 0);
    if ($note >= 0 && $note <= 10) {
        $pdo->prepare("UPDATE candidature SET note=?,commentaire=?,remuneration=? WHERE id_candidature=? AND id_offre=?")->execute([$note,$comm,$remun,$id_cand,$id_offre]);
        $msg = 'Évaluation enregistrée !';
    }
}

// Get candidatures
$candidats = $pdo->prepare("
    SELECT c.*, ov.nom, ov.prenom, ov.photo, ov.description, ov.CIN,
           AVG(c2.note) AS note_moy
    FROM candidature c
    JOIN ouvrier ov ON c.id_ouvrier=ov.id_ouvrier
    LEFT JOIN candidature c2 ON c2.id_ouvrier=ov.id_ouvrier AND c2.note IS NOT NULL
    WHERE c.id_offre=?
    GROUP BY c.id_candidature
    ORDER BY c.date_candidature ASC
");
$candidats->execute([$id_offre]);
$candidats = $candidats->fetchAll();

$chantier_termine = $offre['date_fin'] < date('Y-m-d');
$fruitEmojis = ['Olives'=>'🫒','Agrumes'=>'🍊','Tomates'=>'🍅','Dattes'=>'🌴','Raisins'=>'🍇','Pommes'=>'🍎','Poires'=>'🍐','Figues'=>'🌿'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Postulants — Uber-Cueillette</title>
  <link rel="stylesheet" href="../../css/style.css">
  <style>
    .candidat-card {
      background: var(--white);
      border-radius: var(--radius-lg);
      border: 1px solid var(--border);
      padding: 1.2rem;
      display: flex;
      gap: 1.2rem;
      align-items: flex-start;
      transition: box-shadow 0.2s;
    }
    .candidat-card:hover { box-shadow: 0 4px 20px var(--shadow); }
    .candidat-photo {
      width: 60px; height: 60px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid var(--olive-pale);
      flex-shrink: 0;
      background: var(--cream-dark);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.5rem;
    }
    .candidat-photo img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
    .eval-form { background: var(--cream); padding: 1rem; border-radius: var(--radius); margin-top: 1rem; }
    .star-input { display: flex; gap: 0.3rem; margin: 0.5rem 0; }
    .star { font-size: 1.5rem; cursor: pointer; color: var(--border); transition: color 0.1s; }
    .star.selected, .star.hovered { color: var(--harvest); }
  </style>
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
    <div class="page-header">
      <h2><?= ($fruitEmojis[$offre['fruit']] ?? '🌾') ?> Postulants — <?= htmlspecialchars($offre['fruit']) ?> à <?= htmlspecialchars($offre['gouvernorat']) ?></h2>
      <p>
        <?= date('d/m/Y', strtotime($offre['date_debut'])) ?> → <?= date('d/m/Y', strtotime($offre['date_fin'])) ?> &nbsp;·&nbsp;
        <?= number_format($offre['prix_journee'], 2) ?> TND/jour &nbsp;·&nbsp;
        <strong><?= $nb_acceptes_current ?>/<?= $offre['nombre_ouvriers'] ?></strong> postes pourvus
        <?php if ($is_full): ?><span class="badge badge-success" style="margin-left:0.5rem">Complet</span><?php endif; ?>
        <?php if ($chantier_termine): ?><span class="badge badge-warning" style="margin-left:0.5rem">Chantier terminé</span><?php endif; ?>
      </p>
    </div>

    <?php if ($msg): ?><div class="alert alert-success" data-dismiss>✅ <?= $msg ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error" data-dismiss>❌ <?= $error ?></div><?php endif; ?>

    <?php if (empty($candidats)): ?>
      <div class="card"><div class="card-body text-center" style="padding:3rem">
        <div style="font-size:3rem">📭</div>
        <p class="text-muted mt-2">Aucun postulant pour l'instant.</p>
      </div></div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:1rem;">
      <?php foreach ($candidats as $c):
        $photo_src = $c['photo'] ? 'data:image/jpeg;base64,'.base64_encode($c['photo']) : null;
        $stars_avg = round($c['note_moy'] ?? 0);
      ?>
      <div class="candidat-card">
        <!-- Photo -->
        <div class="candidat-photo">
          <?php if ($photo_src): ?>
            <img src="<?= $photo_src ?>" alt="photo">
          <?php else: ?>👤<?php endif; ?>
        </div>

        <!-- Info -->
        <div style="flex:1">
          <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;">
            <div>
              <strong><?= htmlspecialchars($c['prenom'].' '.$c['nom']) ?></strong>
              <span class="badge <?= $c['decision']=='accepte' ? 'badge-success' : ($c['decision']=='refuse' ? 'badge-danger' : 'badge-warning') ?>" style="margin-left:0.5rem">
                <?= $c['decision']=='accepte' ? '✅ Accepté' : ($c['decision']=='refuse' ? '❌ Refusé' : '⏳ En attente') ?>
              </span>
            </div>
            <span class="text-muted" style="font-size:0.82rem">Postulé le <?= date('d/m/Y', strtotime($c['date_candidature'])) ?></span>
          </div>

          <?php if ($c['description']): ?>
            <p style="font-size:0.87rem;color:var(--text-muted);margin-top:0.3rem"><?= htmlspecialchars($c['description']) ?></p>
          <?php endif; ?>

          <!-- Average rating -->
          <?php if ($c['note_moy'] !== null): ?>
          <div class="star-rating" style="margin-top:0.3rem;font-size:0.85rem">
            <?php for ($i=1;$i<=10;$i++) echo $i<=$stars_avg?'⭐':'☆'; ?>
            <span style="color:var(--text-muted)"> (<?= number_format($c['note_moy'],1) ?>/10)</span>
          </div>
          <?php else: ?>
            <p style="font-size:0.82rem;color:var(--text-muted);margin-top:0.3rem">Pas encore noté</p>
          <?php endif; ?>

          <!-- Actions: POST forms to prevent CSRF -->
          <?php if ($c['decision'] === 'encours'): ?>
          <div style="margin-top:0.8rem;display:flex;gap:0.5rem;flex-wrap:wrap">
            <?php if (!$is_full): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="accepter">
                <input type="hidden" name="cid" value="<?= $c['id_candidature'] ?>">
                <button type="submit" class="btn btn-success btn-sm">✅ Accepter</button>
              </form>
            <?php endif; ?>
            <form method="POST" style="display:inline" onsubmit="return confirmDelete('Refuser ce candidat ?')">
              <input type="hidden" name="action" value="refuser">
              <input type="hidden" name="cid" value="<?= $c['id_candidature'] ?>">
              <button type="submit" class="btn btn-danger btn-sm">❌ Refuser</button>
            </form>
          </div>
          <?php endif; ?>

          <!-- Evaluation form (after chantier ends, for accepted workers) -->
          <?php if ($chantier_termine && $c['decision'] === 'accepte'): ?>
          <div class="eval-form">
            <strong style="font-size:0.9rem">📝 Évaluation après chantier</strong>
            <form method="POST">
              <input type="hidden" name="evaluer" value="1">
              <input type="hidden" name="id_candidature" value="<?= $c['id_candidature'] ?>">
              <div class="form-group" style="margin-top:0.7rem">
                <label class="form-label">Note (0-10)</label>
                <div class="star-input">
                  <input type="hidden" name="note" value="<?= $c['note'] ?? 0 ?>">
                  <?php for ($i=1;$i<=10;$i++): ?>
                    <span class="star <?= ($c['note'] && $i<=$c['note']) ? 'selected' : '' ?>">★</span>
                  <?php endfor; ?>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Rémunération accordée (TND)</label>
                <input type="number" name="remuneration" step="0.5" class="form-control" value="<?= $c['remuneration'] ?? '' ?>" style="max-width:200px">
              </div>
              <div class="form-group">
                <label class="form-label">Commentaire</label>
                <textarea name="commentaire" class="form-control" rows="2"><?= htmlspecialchars($c['commentaire'] ?? '') ?></textarea>
              </div>
              <button type="submit" class="btn btn-primary btn-sm">💾 Enregistrer</button>
            </form>
          </div>
          <?php elseif ($c['note'] !== null): ?>
            <div style="margin-top:0.5rem;font-size:0.85rem;background:var(--harvest-pale);padding:0.5rem 0.8rem;border-radius:8px;">
              ⭐ Note: <?= $c['note'] ?>/10 — <?= htmlspecialchars($c['commentaire'] ?? '') ?>
              <?php if ($c['remuneration']): ?> — 💰 <?= number_format($c['remuneration'],2) ?> TND<?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="mt-3"><a href="mes_offres.php" class="btn btn-outline">← Retour aux offres</a></div>
  </main>
</div>

<script src="../../js/main.js"></script>
</body>
</html>
