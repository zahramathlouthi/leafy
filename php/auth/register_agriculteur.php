<?php
require_once '../../includes/session.php';
require_once '../../includes/db.php';

if (isLoggedIn()) { header('Location: ../agriculteur/dashboard.php'); exit; }

$errors = []; $success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom     = trim($_POST['nom'] ?? '');
    $prenom  = trim($_POST['prenom'] ?? '');
    $cin     = trim($_POST['cin'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $pseudo  = trim($_POST['pseudo'] ?? '');
    $pwd     = $_POST['password'] ?? '';
    $cpwd    = $_POST['confirm_password'] ?? '';

    // Server-side validation
    if (!$nom)   $errors['nom']   = 'Nom obligatoire.';
    if (!$prenom)$errors['prenom']= 'Prénom obligatoire.';
    if (!preg_match('/^[0-9]{8}$/', $cin))   $errors['cin']   = 'CIN: 8 chiffres requis.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email invalide.';
    if (!preg_match('/^[a-zA-ZÀ-ÿ]+$/', $pseudo)) $errors['pseudo'] = 'Pseudo: lettres uniquement.';
    if (!preg_match('/^[a-zA-Z0-9À-ÿ]{7,}[\$#]$/', $pwd)) $errors['password'] = 'Mot de passe invalide.';
    if ($pwd !== $cpwd) $errors['confirm_password'] = 'Mots de passe différents.';

    if (empty($errors)) {
        // Check uniqueness
        $stmt = $pdo->prepare("SELECT id_agriculteur FROM agriculteur WHERE pseudo=? OR email=? OR CIN=?");
        $stmt->execute([$pseudo, $email, $cin]);
        if ($stmt->fetch()) {
            $errors['pseudo'] = 'Pseudo, email ou CIN déjà utilisé.';
        } else {
            $hash = password_hash($pwd, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO agriculteur (nom,prenom,CIN,email,adresse,pseudo,password) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([$nom, $prenom, $cin, $email, $adresse, $pseudo, $hash]);
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
  <title>Inscription Agriculteur — Uber-Cueillette</title>
  <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-container wide">
    <div class="auth-header" style="background: linear-gradient(135deg, var(--earth) 0%, var(--bark-mid) 100%);">
      <div style="font-size:2.5rem;margin-bottom:0.5rem">👨‍🌾</div>
      <h2>Inscription Agriculteur</h2>
      <p>Publiez vos offres de récolte et trouvez vos ouvriers</p>
    </div>
    <div class="auth-body">

      <?php if ($success): ?>
        <div class="alert alert-success">✅ Inscription réussie ! <a href="login.php">Connectez-vous maintenant</a></div>
      <?php endif; ?>

      <?php if (!$success): ?>
      <form method="POST" onsubmit="return validateRegisterAgriculteur()">
        <div class="grid-2">
          <div class="form-group">
            <label class="form-label" for="nom">Nom *</label>
            <input type="text" id="nom" name="nom" class="form-control <?= isset($errors['nom']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" placeholder="Votre nom">
            <?php if (isset($errors['nom'])): ?><div class="form-error"><?= $errors['nom'] ?></div><?php endif; ?>
          </div>
          <div class="form-group">
            <label class="form-label" for="prenom">Prénom *</label>
            <input type="text" id="prenom" name="prenom" class="form-control <?= isset($errors['prenom']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>" placeholder="Votre prénom">
            <?php if (isset($errors['prenom'])): ?><div class="form-error"><?= $errors['prenom'] ?></div><?php endif; ?>
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label class="form-label" for="cin">Numéro CIN * <small>(8 chiffres)</small></label>
            <input type="text" id="cin" name="cin" class="form-control <?= isset($errors['cin']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($_POST['cin'] ?? '') ?>" placeholder="12345678" maxlength="8">
            <?php if (isset($errors['cin'])): ?><div class="form-error"><?= $errors['cin'] ?></div><?php endif; ?>
          </div>
          <div class="form-group">
            <label class="form-label" for="email">Email *</label>
            <input type="email" id="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="vous@email.com">
            <?php if (isset($errors['email'])): ?><div class="form-error"><?= $errors['email'] ?></div><?php endif; ?>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="adresse">Adresse personnelle</label>
          <input type="text" id="adresse" name="adresse" class="form-control" value="<?= htmlspecialchars($_POST['adresse'] ?? '') ?>" placeholder="Votre adresse">
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label class="form-label" for="pseudo">Pseudo * <small>(lettres uniquement)</small></label>
            <input type="text" id="pseudo" name="pseudo" class="form-control <?= isset($errors['pseudo']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($_POST['pseudo'] ?? '') ?>" placeholder="MonPseudo">
            <?php if (isset($errors['pseudo'])): ?><div class="form-error"><?= $errors['pseudo'] ?></div><?php endif; ?>
          </div>
          <div></div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label class="form-label" for="password">Mot de passe * <small>(min 8 chars, finit par $ ou #)</small></label>
            <input type="password" id="password" name="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" placeholder="MotDePasse#">
            <?php if (isset($errors['password'])): ?><div class="form-error"><?= $errors['password'] ?></div><?php endif; ?>
          </div>
          <div class="form-group">
            <label class="form-label" for="confirm_password">Confirmer mot de passe *</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" placeholder="Répéter">
            <?php if (isset($errors['confirm_password'])): ?><div class="form-error"><?= $errors['confirm_password'] ?></div><?php endif; ?>
          </div>
        </div>

        <button type="submit" class="btn btn-earth w-100 mt-2">👨‍🌾 Créer mon compte Agriculteur</button>
      </form>
      <?php endif; ?>

      <div class="auth-toggle">
        Déjà inscrit ? <a href="login.php">Se connecter</a> &nbsp;·&nbsp;
        <a href="register_ouvrier.php">Inscription Ouvrier</a>
      </div>
      <div class="auth-toggle mt-1"><a href="../../index.php">← Retour à l'accueil</a></div>
    </div>
  </div>
</div>
<script src="../../js/main.js"></script>
</body>
</html>
