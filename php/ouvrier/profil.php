<?php
require_once '../../includes/session.php';
require_once '../../includes/db.php';
requireOuvrier();

$id = $_SESSION['user_id'];
$msg = ''; $errors = [];

$ouvrier = $pdo->prepare("SELECT * FROM ouvrier WHERE id_ouvrier=?");
$ouvrier->execute([$id]);
$ouvrier = $ouvrier->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom         = trim($_POST['nom'] ?? '');
    $prenom      = trim($_POST['prenom'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (!$nom)   $errors['nom'] = 'Nom requis.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email invalide.';

    $photoData = $ouvrier['photo'];
    if (!empty($_FILES['photo']['name'])) {
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        if (in_array($_FILES['photo']['type'], $allowed) && $_FILES['photo']['size'] <= 2*1024*1024) {
            $photoData = file_get_contents($_FILES['photo']['tmp_name']);
        } else {
            $errors['photo'] = 'Format invalide ou taille > 2MB.';
        }
    }

    if (empty($errors)) {
        $pdo->prepare("UPDATE ouvrier SET nom=?,prenom=?,email=?,description=?,photo=? WHERE id_ouvrier=?")->execute([$nom,$prenom,$email,$description,$photoData,$id]);
        $_SESSION['nom']    = $nom;
        $_SESSION['prenom'] = $prenom;
        $msg = 'Profil mis à jour !';
        $ouvrier = array_merge($ouvrier, ['nom'=>$nom,'prenom'=>$prenom,'email'=>$email,'description'=>$description,'photo'=>$photoData]);
    }
}

$photo_src = $ouvrier['photo'] ? 'data:image/jpeg;base64,'.base64_encode($ouvrier['photo']) : null;
$noteMoy = $pdo->prepare("SELECT AVG(note) FROM candidature WHERE id_ouvrier=? AND note IS NOT NULL");
$noteMoy->execute([$id]); $noteMoy = $noteMoy->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mon Profil — Uber-Cueillette</title>
  <link rel="stylesheet" href="../../css/style.css">
  <style>
    .photo-upload-area { border:2px dashed var(--border);border-radius:var(--radius);padding:1.5rem;text-align:center;cursor:pointer;transition:border-color 0.2s; }
    .photo-upload-area:hover { border-color:var(--olive-light); }
    #photo-preview { width:80px;height:80px;border-radius:50%;object-fit:cover;display:none;margin:0.5rem auto;border:3px solid var(--olive-pale); }
  </style>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="dashboard-layout">
  <aside class="sidebar">
    <div class="sidebar-user">
      <div class="avatar">
        <?php if ($photo_src): ?>
          <img src="<?= $photo_src ?>" alt="Photo">
        <?php else: ?>👷<?php endif; ?>
      </div>
      <div class="name"><?= htmlspecialchars($_SESSION['prenom'].' '.$_SESSION['nom']) ?></div>
      <div class="role">Ouvrier de récolte</div>
    </div>
    <ul class="sidebar-nav">
      <li><a href="dashboard.php"><span class="nav-icon">🏠</span> Tableau de bord</a></li>
      <li><a href="offres.php"><span class="nav-icon">🌾</span> Offres disponibles</a></li>
      <li><a href="mes_candidatures.php"><span class="nav-icon">📨</span> Mes candidatures</a></li>
      <li><a href="chantiers.php"><span class="nav-icon">📊</span> Historique & Gains</a></li>
      <li><a href="profil.php" class="active"><span class="nav-icon">👤</span> Mon profil</a></li>
      <li><a href="../auth/logout.php"><span class="nav-icon">🚪</span> Déconnexion</a></li>
    </ul>
  </aside>

  <main class="dashboard-main">
    <div class="profile-header">
      <div class="profile-avatar">
        <?php if ($photo_src): ?>
          <img src="<?= $photo_src ?>" alt="Photo">
        <?php else: ?>👷<?php endif; ?>
      </div>
      <div class="profile-info">
        <h3><?= htmlspecialchars($ouvrier['prenom'].' '.$ouvrier['nom']) ?></h3>
        <p>@<?= htmlspecialchars($ouvrier['pseudo']) ?> · Ouvrier de récolte</p>
        <p><?= htmlspecialchars($ouvrier['email']) ?></p>
        <?php if ($noteMoy !== null): ?>
        <div class="star-rating">
          <?php for ($i=1;$i<=10;$i++) echo $i<=round($noteMoy)?'⭐':'☆'; ?>
          <span style="font-size:0.85rem;margin-left:0.4rem"><?= number_format($noteMoy,1) ?>/10</span>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($msg): ?><div class="alert alert-success" data-dismiss>✅ <?= $msg ?></div><?php endif; ?>

    <div class="card">
      <div class="card-header"><h3 style="font-family:'Playfair Display',serif;">Modifier mon profil</h3></div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
          <div class="grid-2">
            <div class="form-group">
              <label class="form-label" for="nom">Nom</label>
              <input type="text" id="nom" name="nom" class="form-control <?= isset($errors['nom'])?'is-invalid':'' ?>" value="<?= htmlspecialchars($ouvrier['nom']) ?>">
              <?php if (isset($errors['nom'])): ?><div class="form-error"><?= $errors['nom'] ?></div><?php endif; ?>
            </div>
            <div class="form-group">
              <label class="form-label" for="prenom">Prénom</label>
              <input type="text" id="prenom" name="prenom" class="form-control" value="<?= htmlspecialchars($ouvrier['prenom']) ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label" for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control <?= isset($errors['email'])?'is-invalid':'' ?>" value="<?= htmlspecialchars($ouvrier['email']) ?>">
            <?php if (isset($errors['email'])): ?><div class="form-error"><?= $errors['email'] ?></div><?php endif; ?>
          </div>
          <div class="form-group">
            <label class="form-label" for="description">Description</label>
            <textarea id="description" name="description" class="form-control"><?= htmlspecialchars($ouvrier['description'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Photo d'identité</label>
            <div class="photo-upload-area" onclick="document.getElementById('photo').click()">
              <?php if ($photo_src): ?>
                <img src="<?= $photo_src ?>" id="photo-preview" style="display:block;">
              <?php else: ?>
                <img id="photo-preview" src="" alt="">
              <?php endif; ?>
              <div>📷 Cliquer pour changer la photo</div>
            </div>
            <input type="file" id="photo" name="photo" accept="image/*" style="display:none">
            <?php if (isset($errors['photo'])): ?><div class="form-error"><?= $errors['photo'] ?></div><?php endif; ?>
          </div>
          <div class="form-group">
            <label class="form-label">CIN <small>(non modifiable)</small></label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($ouvrier['CIN']) ?>" disabled>
          </div>
          <button type="submit" class="btn btn-primary">💾 Sauvegarder</button>
        </form>
      </div>
    </div>
  </main>
</div>
<script src="../../js/main.js"></script>
</body>
</html>
