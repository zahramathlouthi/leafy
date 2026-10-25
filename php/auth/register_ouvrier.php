<?php
require_once '../../includes/session.php';
require_once '../../includes/db.php';

if (isLoggedIn()) { header('Location: ../ouvrier/dashboard.php'); exit; }

$errors = []; $success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom         = trim($_POST['nom'] ?? '');
    $prenom      = trim($_POST['prenom'] ?? '');
    $cin         = trim($_POST['cin'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $pseudo      = trim($_POST['pseudo'] ?? '');
    $pwd         = $_POST['password'] ?? '';
    $cpwd        = $_POST['confirm_password'] ?? '';

    if (!$nom)    $errors['nom']    = 'Nom obligatoire.';
    if (!$prenom) $errors['prenom'] = 'Prénom obligatoire.';
    if (!preg_match('/^[0-9]{8}$/', $cin))    $errors['cin']    = 'CIN: 8 chiffres requis.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email invalide.';
    if (!preg_match('/^[a-zA-ZÀ-ÿ]+$/', $pseudo)) $errors['pseudo']  = 'Pseudo: lettres uniquement.';
    if (!preg_match('/^[a-zA-Z0-9À-ÿ]{7,}[\$#]$/', $pwd)) $errors['password'] = 'Mot de passe invalide.';
    if ($pwd !== $cpwd) $errors['confirm_password'] = 'Mots de passe différents.';
    if (empty($_FILES['photo']['name'])) $errors['photo'] = 'Photo obligatoire.';

    $photoData = null;
    if (empty($errors['photo'])) {
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        if (!in_array($_FILES['photo']['type'], $allowed)) {
            $errors['photo'] = 'Format invalide (jpg, png, gif, webp).';
        } elseif ($_FILES['photo']['size'] > 2*1024*1024) {
            $errors['photo'] = 'Photo trop grande (max 2MB).';
        } else {
            $photoData = file_get_contents($_FILES['photo']['tmp_name']);
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id_ouvrier FROM ouvrier WHERE pseudo=? OR email=? OR CIN=?");
        $stmt->execute([$pseudo, $email, $cin]);
        if ($stmt->fetch()) {
            $errors['pseudo'] = 'Pseudo, email ou CIN déjà utilisé.';
        } else {
            $hash = password_hash($pwd, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO ouvrier (nom,prenom,CIN,email,photo,description,pseudo,password) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->execute([$nom, $prenom, $cin, $email, $photoData, $description, $pseudo, $hash]);
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inscription Ouvrier — Uber-Cueillette</title>
  <link rel="stylesheet" href="../../css/style.css">
  <style>
    .photo-upload-area {
      border: 2px dashed var(--border);
      border-radius: var(--radius);
      padding: 1.5rem;
      text-align: center;
      cursor: pointer;
      transition: border-color 0.2s;
    }
    .photo-upload-area:hover { border-color: var(--olive-light); }
    #photo-preview { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; display: none; margin: 0.5rem auto; border: 3px solid var(--olive-pale); }
  </style>
</head>
<body>
<div class="auth-page">
  <div class="auth-container wide">
    <div class="auth-header">
      <div style="font-size:2.5rem;margin-bottom:0.5rem">👷</div>
      <h2>Inscription Ouvrier</h2>
      <p>Trouvez des opportunités de récolte près de chez vous</p>
    </div>
    <div class="auth-body">

      <?php if ($success): ?>
        <div class="alert alert-success">✅ Inscription réussie ! <a href="login.php">Connectez-vous maintenant</a></div>
      <?php endif; ?>

      <?php if (!$success): ?>
      <form method="POST" enctype="multipart/form-data" onsubmit="return validateRegisterOuvrier()">
        <div class="grid-2">
          <div class="form-group">
            <label class="form-label" for="nom">Nom *</label>
            <input type="text" id="nom" name="nom" class="form-control <?= isset($errors['nom']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
            <?php if (isset($errors['nom'])): ?><div class="form-error"><?= $errors['nom'] ?></div><?php endif; ?>
          </div>
          <div class="form-group">
            <label class="form-label" for="prenom">Prénom *</label>
            <input type="text" id="prenom" name="prenom" class="form-control <?= isset($errors['prenom']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">
            <?php if (isset($errors['prenom'])): ?><div class="form-error"><?= $errors['prenom'] ?></div><?php endif; ?>
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label class="form-label" for="cin">CIN * <small>(8 chiffres)</small></label>
            <input type="text" id="cin" name="cin" class="form-control <?= isset($errors['cin']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($_POST['cin'] ?? '') ?>" maxlength="8">
            <?php if (isset($errors['cin'])): ?><div class="form-error"><?= $errors['cin'] ?></div><?php endif; ?>
          </div>
          <div class="form-group">
            <label class="form-label" for="email">Email *</label>
            <input type="email" id="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            <?php if (isset($errors['email'])): ?><div class="form-error"><?= $errors['email'] ?></div><?php endif; ?>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="description">Description <small>(expérience, niveau, etc.)</small></label>
          <textarea id="description" name="description" class="form-control" placeholder="Décrivez votre expérience, votre niveau d'éducation..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Photo d'identité *</label>
          <div class="photo-upload-area" onclick="document.getElementById('photo').click()">
            <img id="photo-preview" src="" alt="Aperçu">
            <div>📷 Cliquez pour choisir une photo</div>
            <div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.3rem">JPG, PNG — max 2MB</div>
          </div>
          <input type="file" id="photo" name="photo" accept="image/*" style="display:none" class="<?= isset($errors['photo']) ? 'is-invalid' : '' ?>">
          <?php if (isset($errors['photo'])): ?><div class="form-error"><?= $errors['photo'] ?></div><?php endif; ?>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label class="form-label" for="pseudo">Pseudo * <small>(lettres uniquement)</small></label>
            <input type="text" id="pseudo" name="pseudo" class="form-control <?= isset($errors['pseudo']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($_POST['pseudo'] ?? '') ?>">
            <?php if (isset($errors['pseudo'])): ?><div class="form-error"><?= $errors['pseudo'] ?></div><?php endif; ?>
          </div>
          <div></div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label class="form-label" for="password">Mot de passe * <small>(min 8 chars + $ ou #)</small></label>
            <input type="password" id="password" name="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>">
            <?php if (isset($errors['password'])): ?><div class="form-error"><?= $errors['password'] ?></div><?php endif; ?>
          </div>
          <div class="form-group">
            <label class="form-label" for="confirm_password">Confirmer *</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>">
            <?php if (isset($errors['confirm_password'])): ?><div class="form-error"><?= $errors['confirm_password'] ?></div><?php endif; ?>
          </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 mt-2">👷 Créer mon compte Ouvrier</button>
      </form>
      <?php endif; ?>

      <div class="auth-toggle">
        Déjà inscrit ? <a href="login.php">Se connecter</a> &nbsp;·&nbsp;
        <a href="register_agriculteur.php">Inscription Agriculteur</a>
      </div>
      <div class="auth-toggle mt-1"><a href="../../index.php">← Retour à l'accueil</a></div>
    </div>
  </div>
</div>
<script src="../../js/main.js"></script>
</body>
</html>
