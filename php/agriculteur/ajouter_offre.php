<?php
require_once '../../includes/session.php';
require_once '../../includes/db.php';
requireAgriculteur();

$fruits = $pdo->query("SELECT * FROM type_fruit ORDER BY libelle")->fetchAll();
$gouvernorats = $pdo->query("SELECT * FROM gouvernorat ORDER BY libelle")->fetchAll();

$errors = []; $success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_fruit = intval($_POST['id_type_fruit'] ?? 0);
    $id_gouv  = intval($_POST['id_gouvernorat'] ?? 0);
    $adresse  = trim($_POST['adresse'] ?? '');
    $debut    = $_POST['date_debut'] ?? '';
    $fin      = $_POST['date_fin'] ?? '';
    $nb       = intval($_POST['nombre_ouvriers'] ?? 0);
    $prix     = floatval($_POST['prix_journee'] ?? 0);
    $limite   = $_POST['date_limite'] ?? '';

    if (!$id_fruit) $errors['id_type_fruit'] = 'Sélectionnez un type de fruit.';
    if (!$id_gouv)  $errors['id_gouvernorat'] = 'Sélectionnez un gouvernorat.';
    if (!$adresse)  $errors['adresse'] = 'Adresse requise.';
    if (!$debut)    $errors['date_debut'] = 'Date de début requise.';
    if (!$fin)      $errors['date_fin'] = 'Date de fin requise.';
    if ($debut && $fin && $debut >= $fin) $errors['date_fin'] = 'Date fin doit être après date début.';
    if ($debut && $debut < date('Y-m-d')) $errors['date_debut'] = 'Date dans le passé.';
    if ($nb < 1)    $errors['nombre_ouvriers'] = 'Min 1 ouvrier.';
    if ($prix <= 0) $errors['prix_journee'] = 'Prix doit être positif.';
    if (!$limite)   $errors['date_limite'] = 'Date limite requise.';
    if ($limite && $fin && $limite >= $fin) $errors['date_limite'] = 'Date limite doit être avant la fin.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO offre (id_type_fruit,id_gouvernorat,adresse,date_debut,date_fin,nombre_ouvriers,prix_journee,date_limite,id_agriculteur) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$id_fruit,$id_gouv,$adresse,$debut,$fin,$nb,$prix,$limite,$_SESSION['user_id']]);
        // PRG pattern: redirect so the form isn't shown again and browser refresh won't re-submit
        header('Location: mes_offres.php?success=1');
        exit;
    }
}

$fruitEmojis = ['Olives'=>'🫒','Agrumes'=>'🍊','Tomates'=>'🍅','Dattes'=>'🌴','Raisins'=>'🍇','Pommes'=>'🍎','Poires'=>'🍐','Figues'=>'🌿'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ajouter une offre — Uber-Cueillette</title>
  <link rel="stylesheet" href="../../css/style.css">
  <style>
    .fruit-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.7rem; margin-bottom: 1rem; }
    .fruit-btn {
      border: 2px solid var(--border);
      border-radius: var(--radius);
      padding: 0.8rem 0.5rem;
      text-align: center;
      cursor: pointer;
      transition: all 0.2s;
      background: var(--white);
      font-size: 0.85rem;
      font-family: 'DM Sans', sans-serif;
    }
    .fruit-btn:hover, .fruit-btn.selected { border-color: var(--olive-light); background: rgba(122,140,58,0.1); }
    .fruit-btn .emoji { font-size: 1.6rem; display: block; margin-bottom: 0.3rem; }
    @media(max-width:600px){ .fruit-grid { grid-template-columns: repeat(2,1fr); } }
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
      <li><a href="ajouter_offre.php" class="active"><span class="nav-icon">➕</span> Ajouter une offre</a></li>
      <li><a href="mes_offres.php"><span class="nav-icon">📋</span> Mes offres</a></li>
      <li><a href="profil.php"><span class="nav-icon">👤</span> Mon profil</a></li>
      <li><a href="../auth/logout.php"><span class="nav-icon">🚪</span> Déconnexion</a></li>
    </ul>
  </aside>

  <main class="dashboard-main">
    <div class="page-header">
      <h2>➕ Ajouter une offre de récolte</h2>
      <p>Publiez votre besoin d'ouvriers pour la prochaine saison.</p>
    </div>

    <?php if ($success): ?>
      <div class="alert alert-success" data-dismiss>✅ Offre publiée avec succès ! <a href="mes_offres.php">Voir mes offres →</a></div>
    <?php endif; ?>

    <div class="card">
      <div class="card-body">
        <form method="POST" onsubmit="return validateOffre()">

          <!-- Fruit Selection -->
          <div class="form-group">
            <label class="form-label">Type de fruit *</label>
            <div class="fruit-grid" id="fruit-grid">
              <?php foreach ($fruits as $f):
                $emoji = $fruitEmojis[$f['libelle']] ?? '🌾';
                $selected = (isset($_POST['id_type_fruit']) && $_POST['id_type_fruit'] == $f['id_type_fruit']) ? 'selected' : '';
              ?>
              <div class="fruit-btn <?= $selected ?>"
                onclick="selectFruit(<?= $f['id_type_fruit'] ?>, this)">
                <span class="emoji"><?= $emoji ?></span>
                <?= htmlspecialchars($f['libelle']) ?>
              </div>
              <?php endforeach; ?>
            </div>
            <input type="hidden" id="id_type_fruit" name="id_type_fruit" value="<?= htmlspecialchars($_POST['id_type_fruit'] ?? '') ?>">
            <?php if (isset($errors['id_type_fruit'])): ?><div class="form-error"><?= $errors['id_type_fruit'] ?></div><?php endif; ?>
          </div>

          <div class="grid-2">
            <div class="form-group">
              <label class="form-label" for="id_gouvernorat">Gouvernorat *</label>
              <select id="id_gouvernorat" name="id_gouvernorat" class="form-control <?= isset($errors['id_gouvernorat']) ? 'is-invalid' : '' ?>">
                <option value="">-- Choisir --</option>
                <?php foreach ($gouvernorats as $g): ?>
                <option value="<?= $g['id_gouvernorat'] ?>" <?= (isset($_POST['id_gouvernorat']) && $_POST['id_gouvernorat'] == $g['id_gouvernorat']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($g['libelle']) ?>
                </option>
                <?php endforeach; ?>
              </select>
              <?php if (isset($errors['id_gouvernorat'])): ?><div class="form-error"><?= $errors['id_gouvernorat'] ?></div><?php endif; ?>
            </div>
            <div class="form-group">
              <label class="form-label" for="adresse">Adresse du site de récolte *</label>
              <input type="text" id="adresse" name="adresse" class="form-control <?= isset($errors['adresse']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($_POST['adresse'] ?? '') ?>" placeholder="Ex: Route de Nabeul Km 5">
              <?php if (isset($errors['adresse'])): ?><div class="form-error"><?= $errors['adresse'] ?></div><?php endif; ?>
            </div>
          </div>

          <div class="grid-2">
            <div class="form-group">
              <label class="form-label" for="date_debut">Date de début *</label>
              <input type="date" id="date_debut" name="date_debut" class="form-control <?= isset($errors['date_debut']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($_POST['date_debut'] ?? '') ?>" min="<?= date('Y-m-d') ?>">
              <?php if (isset($errors['date_debut'])): ?><div class="form-error"><?= $errors['date_debut'] ?></div><?php endif; ?>
            </div>
            <div class="form-group">
              <label class="form-label" for="date_fin">Date de fin *</label>
              <input type="date" id="date_fin" name="date_fin" class="form-control <?= isset($errors['date_fin']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($_POST['date_fin'] ?? '') ?>">
              <?php if (isset($errors['date_fin'])): ?><div class="form-error"><?= $errors['date_fin'] ?></div><?php endif; ?>
            </div>
          </div>

          <div class="grid-2">
            <div class="form-group">
              <label class="form-label" for="nombre_ouvriers">Nombre d'ouvriers *</label>
              <input type="number" id="nombre_ouvriers" name="nombre_ouvriers" class="form-control <?= isset($errors['nombre_ouvriers']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($_POST['nombre_ouvriers'] ?? '') ?>" min="1" placeholder="Ex: 5">
              <?php if (isset($errors['nombre_ouvriers'])): ?><div class="form-error"><?= $errors['nombre_ouvriers'] ?></div><?php endif; ?>
            </div>
            <div class="form-group">
              <label class="form-label" for="prix_journee">Prix à la journée (TND) *</label>
              <input type="number" id="prix_journee" name="prix_journee" step="0.5" class="form-control <?= isset($errors['prix_journee']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($_POST['prix_journee'] ?? '') ?>" min="0" placeholder="Ex: 25.00">
              <?php if (isset($errors['prix_journee'])): ?><div class="form-error"><?= $errors['prix_journee'] ?></div><?php endif; ?>
            </div>
          </div>

          <div class="form-group" style="max-width:340px">
            <label class="form-label" for="date_limite">Date limite de candidature *</label>
            <input type="date" id="date_limite" name="date_limite" class="form-control <?= isset($errors['date_limite']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($_POST['date_limite'] ?? '') ?>">
            <?php if (isset($errors['date_limite'])): ?><div class="form-error"><?= $errors['date_limite'] ?></div><?php endif; ?>
          </div>

          <div style="display:flex;gap:1rem;margin-top:1.5rem;">
            <button type="submit" class="btn btn-primary">🌾 Publier l'offre</button>
            <a href="mes_offres.php" class="btn btn-outline">Annuler</a>
          </div>
        </form>
      </div>
    </div>
  </main>
</div>

<script src="../../js/main.js"></script>
<script>
function selectFruit(id, el) {
  document.querySelectorAll('.fruit-btn').forEach(b => b.classList.remove('selected'));
  el.classList.add('selected');
  document.getElementById('id_type_fruit').value = id;
}
</script>
</body>
</html>
