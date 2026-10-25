<?php
require_once '../../includes/session.php';
require_once '../../includes/db.php';
requireOuvrier();

$id_ouvrier = $_SESSION['user_id'];

// Handle postuler
$msg = $error = '';
if (isset($_GET['postuler'])) {
    $id_offre = intval($_GET['postuler']);
    // Check not already applied
    $check = $pdo->prepare("SELECT id_candidature FROM candidature WHERE id_offre=? AND id_ouvrier=?");
    $check->execute([$id_offre, $id_ouvrier]);
    if ($check->fetch()) {
        $error = 'Vous avez déjà postulé à cette offre.';
    } else {
        // Check offer still open
        $open = $pdo->prepare("SELECT o.id_offre FROM offre o LEFT JOIN candidature c ON o.id_offre=c.id_offre AND c.decision='accepte' WHERE o.id_offre=? AND o.date_limite>=CURDATE() GROUP BY o.id_offre HAVING COUNT(c.id_candidature)<o.nombre_ouvriers");
        $open->execute([$id_offre]);
        if ($open->fetch()) {
            $pdo->prepare("INSERT INTO candidature (id_offre,id_ouvrier) VALUES (?,?)")->execute([$id_offre, $id_ouvrier]);
            $msg = 'Candidature envoyée avec succès !';
        } else {
            $error = 'Cette offre est clôturée ou complète.';
        }
    }
}

// Filters
$fruit_filter = intval($_GET['fruit'] ?? 0);
$gouv_filter  = intval($_GET['gouv'] ?? 0);
$sort         = $_GET['sort'] ?? 'date';

$fruits      = $pdo->query("SELECT * FROM type_fruit ORDER BY libelle")->fetchAll();
$gouvernorats= $pdo->query("SELECT * FROM gouvernorat ORDER BY libelle")->fetchAll();

// Already applied
$applied = $pdo->prepare("SELECT id_offre FROM candidature WHERE id_ouvrier=?");
$applied->execute([$id_ouvrier]);
$applied_ids = array_column($applied->fetchAll(), 'id_offre');

// Build query
$where = ["o.date_limite >= CURDATE()"];
$params = [];

if ($fruit_filter) { $where[] = "o.id_type_fruit=?"; $params[] = $fruit_filter; }
if ($gouv_filter)  { $where[] = "o.id_gouvernorat=?"; $params[] = $gouv_filter; }

$order = $sort === 'prix_asc' ? 'o.prix_journee ASC' : ($sort === 'prix_desc' ? 'o.prix_journee DESC' : 'o.date_limite ASC');

$sql = "SELECT o.*, tf.libelle AS fruit, g.libelle AS gouvernorat,
               COUNT(c.id_candidature) AS nb_acceptes,
               ag.nom AS agric_nom, ag.prenom AS agric_prenom
        FROM offre o
        JOIN type_fruit tf ON o.id_type_fruit=tf.id_type_fruit
        JOIN gouvernorat g ON o.id_gouvernorat=g.id_gouvernorat
        JOIN agriculteur ag ON o.id_agriculteur=ag.id_agriculteur
        LEFT JOIN candidature c ON o.id_offre=c.id_offre AND c.decision='accepte'
        WHERE ".implode(' AND ', $where)."
        GROUP BY o.id_offre
        HAVING nb_acceptes < o.nombre_ouvriers
        ORDER BY $order";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$offres = $stmt->fetchAll();

$fruitEmojis = ['Olives'=>'🫒','Agrumes'=>'🍊','Tomates'=>'🍅','Dattes'=>'🌴','Raisins'=>'🍇','Pommes'=>'🍎','Poires'=>'🍐','Figues'=>'🌿'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Offres de récolte — Uber-Cueillette</title>
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
      <li><a href="offres.php" class="active"><span class="nav-icon">🌾</span> Offres disponibles</a></li>
      <li><a href="mes_candidatures.php"><span class="nav-icon">📨</span> Mes candidatures</a></li>
      <li><a href="chantiers.php"><span class="nav-icon">📊</span> Historique & Gains</a></li>
      <li><a href="profil.php"><span class="nav-icon">👤</span> Mon profil</a></li>
      <li><a href="../auth/logout.php"><span class="nav-icon">🚪</span> Déconnexion</a></li>
    </ul>
  </aside>

  <main class="dashboard-main">
    <div class="page-header">
      <h2>🌾 Offres de récolte disponibles</h2>
      <p><?= count($offres) ?> offre(s) ouverte(s)</p>
    </div>

    <?php if ($msg): ?><div class="alert alert-success" data-dismiss>✅ <?= $msg ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error" data-dismiss>❌ <?= $error ?></div><?php endif; ?>

    <!-- FILTERS -->
    <form method="GET" action="offres.php">
      <div class="filter-bar">
        <div class="form-group">
          <label class="form-label">Fruit</label>
          <select name="fruit" class="form-control" onchange="this.form.submit()">
            <option value="0">Tous les fruits</option>
            <?php foreach ($fruits as $f): ?>
              <option value="<?= $f['id_type_fruit'] ?>" <?= $fruit_filter==$f['id_type_fruit']?'selected':'' ?>>
                <?= ($fruitEmojis[$f['libelle']]??'') ?> <?= htmlspecialchars($f['libelle']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Gouvernorat</label>
          <select name="gouv" class="form-control" onchange="this.form.submit()">
            <option value="0">Tous les gouvernorats</option>
            <?php foreach ($gouvernorats as $g): ?>
              <option value="<?= $g['id_gouvernorat'] ?>" <?= $gouv_filter==$g['id_gouvernorat']?'selected':'' ?>>
                <?= htmlspecialchars($g['libelle']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Trier par</label>
          <select name="sort" class="form-control" onchange="this.form.submit()">
            <option value="date"      <?= $sort==='date'?'selected':'' ?>>Date limite</option>
            <option value="prix_desc" <?= $sort==='prix_desc'?'selected':'' ?>>Prix ↓ (le plus élevé)</option>
            <option value="prix_asc"  <?= $sort==='prix_asc'?'selected':'' ?>>Prix ↑ (le moins cher)</option>
          </select>
        </div>
        <?php if ($fruit_filter || $gouv_filter): ?>
          <a href="offres.php" class="btn btn-outline btn-sm" style="align-self:flex-end">🔄 Réinitialiser</a>
        <?php endif; ?>
      </div>
    </form>

    <!-- OFFERS GRID -->
    <?php if (empty($offres)): ?>
      <div class="card"><div class="card-body text-center" style="padding:3rem">
        <div style="font-size:3rem">🌾</div>
        <p class="text-muted mt-2">Aucune offre disponible avec ces filtres.</p>
      </div></div>
    <?php else: ?>
    <div class="grid-offers">
      <?php foreach ($offres as $o):
        $emoji = $fruitEmojis[$o['fruit']] ?? '🌾';
        $already_applied = in_array($o['id_offre'], $applied_ids);
        $spots_left = $o['nombre_ouvriers'] - $o['nb_acceptes'];
        $jours = (strtotime($o['date_fin']) - strtotime($o['date_debut'])) / 86400;
        $total_potential = round($jours * $o['prix_journee'], 2);
      ?>
      <div class="offer-card">
        <div class="offer-card-header">
          <div class="offer-fruit-badge"><?= $emoji ?> <?= htmlspecialchars($o['fruit']) ?></div>
          <div class="offer-price"><?= number_format($o['prix_journee'], 2) ?> <span>TND/jour</span></div>
        </div>
        <div class="offer-card-body">
          <div class="offer-meta">
            <div class="offer-meta-item"><span class="icon">📍</span><?= htmlspecialchars($o['gouvernorat']) ?></div>
            <div class="offer-meta-item"><span class="icon">👥</span><?= $spots_left ?> poste(s) libre(s)</div>
            <div class="offer-meta-item"><span class="icon">📅</span><?= date('d/m/Y', strtotime($o['date_debut'])) ?></div>
            <div class="offer-meta-item"><span class="icon">🏁</span><?= date('d/m/Y', strtotime($o['date_fin'])) ?></div>
          </div>
          <div style="font-size:0.82rem;color:var(--text-muted);margin-bottom:0.5rem">📍 <?= htmlspecialchars($o['adresse']) ?></div>
          <div style="background:var(--harvest-pale);border-radius:8px;padding:0.4rem 0.8rem;font-size:0.82rem;display:inline-block">
            💰 Gain potentiel: ~<?= number_format($total_potential, 2) ?> TND
          </div>
          <div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.3rem">
            par <?= htmlspecialchars($o['agric_prenom'].' '.$o['agric_nom']) ?>
          </div>
        </div>
        <div class="offer-card-footer">
          <span class="spots-badge">⏰ Limite: <?= date('d/m/Y', strtotime($o['date_limite'])) ?></span>
          <?php if ($already_applied): ?>
            <span class="badge badge-olive">✓ Déjà postulé</span>
          <?php else: ?>
            <a href="?postuler=<?= $o['id_offre'] ?>&fruit=<?= $fruit_filter ?>&gouv=<?= $gouv_filter ?>&sort=<?= $sort ?>"
               class="btn btn-primary btn-sm"
               onclick="return confirm('Postuler à cette offre ?')">
              🙋 Postuler
            </a>
          <?php endif; ?>
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
