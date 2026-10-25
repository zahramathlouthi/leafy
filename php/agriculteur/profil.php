<?php
require_once '../../includes/session.php';
require_once '../../includes/db.php';
requireAgriculteur();

$id = $_SESSION['user_id'];
$msg = $errors = [];

$agric = $pdo->prepare("SELECT * FROM agriculteur WHERE id_agriculteur=?");
$agric->execute([$id]);
$agric = $agric->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom     = trim($_POST['nom'] ?? '');
    $prenom  = trim($_POST['prenom'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');

    if (!$nom)   $errors['nom']   = 'Nom requis.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email invalide.';

    if (empty($errors)) {
        $pdo->prepare("UPDATE agriculteur SET nom=?,prenom=?,email=?,adresse=? WHERE id_agriculteur=?")->execute([$nom,$prenom,$email,$adresse,$id]);
        $_SESSION['nom']    = $nom;
        $_SESSION['prenom'] = $prenom;
        $msg = 'Profil mis à jour !';
        $agric = array_merge($agric, ['nom'=>$nom,'prenom'=>$prenom,'email'=>$email,'adresse'=>$adresse]);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mon Profil — Uber-Cueillette</title>
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
      <li><a href="mes_offres.php"><span class="nav-icon">📋</span> Mes offres</a></li>
      <li><a href="profil.php" class="active"><span class="nav-icon">👤</span> Mon profil</a></li>
      <li><a href="../auth/logout.php"><span class="nav-icon">🚪</span> Déconnexion</a></li>
    </ul>
  </aside>

  <main class="dashboard-main">
    <div class="profile-header">
      <div class="profile-avatar">👨‍🌾</div>
      <div class="profile-info">
        <h3><?= htmlspecialchars($agric['prenom'].' '.$agric['nom']) ?></h3>
        <p>@<?= htmlspecialchars($agric['pseudo']) ?> · Agriculteur</p>
        <p><?= htmlspecialchars($agric['email']) ?></p>
      </div>
    </div>

    <?php if ($msg): ?><div class="alert alert-success" data-dismiss>✅ <?= $msg ?></div><?php endif; ?>

    <div class="card">
      <div class="card-header"><h3 style="font-family:'Playfair Display',serif;">Modifier mon profil</h3></div>
      <div class="card-body">
        <form method="POST">
          <div class="grid-2">
            <div class="form-group">
              <label class="form-label" for="nom">Nom</label>
              <input type="text" id="nom" name="nom" class="form-control <?= isset($errors['nom']) ? 'is-invalid':'' ?>" value="<?= htmlspecialchars($agric['nom']) ?>">
              <?php if (isset($errors['nom'])): ?><div class="form-error"><?= $errors['nom'] ?></div><?php endif; ?>
            </div>
            <div class="form-group">
              <label class="form-label" for="prenom">Prénom</label>
              <input type="text" id="prenom" name="prenom" class="form-control" value="<?= htmlspecialchars($agric['prenom']) ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label" for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid':'' ?>" value="<?= htmlspecialchars($agric['email']) ?>">
            <?php if (isset($errors['email'])): ?><div class="form-error"><?= $errors['email'] ?></div><?php endif; ?>
          </div>
          <div class="form-group">
            <label class="form-label" for="adresse">Adresse</label>
            <input type="text" id="adresse" name="adresse" class="form-control" value="<?= htmlspecialchars($agric['adresse'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">CIN <small>(non modifiable)</small></label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($agric['CIN']) ?>" disabled>
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
