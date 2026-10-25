<?php
require_once '../../includes/session.php';
require_once '../../includes/db.php';

if (isLoggedIn()) {
    header(isAgriculteur() ? 'Location: ../agriculteur/dashboard.php' : 'Location: ../ouvrier/dashboard.php');
    exit;
}

$error = ''; $role = $_POST['role'] ?? 'agriculteur';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = trim($_POST['pseudo'] ?? '');
    $pwd    = $_POST['password'] ?? '';
    $role   = $_POST['role'] ?? 'agriculteur';

    if ($role === 'agriculteur') {
        $stmt = $pdo->prepare("SELECT * FROM agriculteur WHERE pseudo = ?");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM ouvrier WHERE pseudo = ?");
    }
    $stmt->execute([$pseudo]);
    $user = $stmt->fetch();

    if ($user && password_verify($pwd, $user['password'])) {
        $_SESSION['user_id']   = $role === 'agriculteur' ? $user['id_agriculteur'] : $user['id_ouvrier'];
        $_SESSION['user_type'] = $role;
        $_SESSION['pseudo']    = $user['pseudo'];
        $_SESSION['nom']       = $user['nom'];
        $_SESSION['prenom']    = $user['prenom'];

        if ($role === 'agriculteur') {
            header('Location: ../agriculteur/dashboard.php');
        } else {
            $_SESSION['photo'] = $user['photo'] ? true : false;
            header('Location: ../ouvrier/dashboard.php');
        }
        exit;
    } else {
        $error = 'Pseudo ou mot de passe incorrect.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion — Uber-Cueillette</title>
  <link rel="stylesheet" href="../../css/style.css">
  <style>
    .role-tabs { display: flex; margin-bottom: 1.5rem; border-radius: var(--radius); overflow: hidden; border: 1.5px solid var(--border); }
    .role-tab {
      flex: 1;
      padding: 0.75rem;
      text-align: center;
      cursor: pointer;
      font-weight: 600;
      font-size: 0.9rem;
      transition: all 0.2s;
      background: var(--cream);
      color: var(--text-muted);
      border: none;
      font-family: 'DM Sans', sans-serif;
    }
    .role-tab.active { background: var(--olive); color: white; }
    .role-tab:first-child { border-right: 1.5px solid var(--border); }
  </style>
</head>
<body>
<div class="auth-page">
  <div class="auth-container">
    <div class="auth-header">
      <div style="font-size:2rem;margin-bottom:0.5rem">🌿</div>
      <h2>Bon retour !</h2>
      <p>Connectez-vous à votre espace</p>
    </div>
    <div class="auth-body">

      <?php if ($error): ?>
        <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" onsubmit="return validateLogin()">
        <!-- Role tabs -->
        <div class="role-tabs">
          <button type="button" class="role-tab <?= $role === 'agriculteur' ? 'active' : '' ?>"
            onclick="this.classList.add('active'); this.nextElementSibling.classList.remove('active'); document.getElementById('role').value='agriculteur'">
            👨‍🌾 Agriculteur
          </button>
          <button type="button" class="role-tab <?= $role === 'ouvrier' ? 'active' : '' ?>"
            onclick="this.classList.add('active'); this.previousElementSibling.classList.remove('active'); document.getElementById('role').value='ouvrier'">
            👷 Ouvrier
          </button>
        </div>
        <input type="hidden" id="role" name="role" value="<?= htmlspecialchars($role) ?>">

        <div class="form-group">
          <label class="form-label" for="pseudo">Pseudo</label>
          <input type="text" id="pseudo" name="pseudo" class="form-control" value="<?= htmlspecialchars($_POST['pseudo'] ?? '') ?>" placeholder="Votre pseudo" autofocus>
        </div>
        <div class="form-group">
          <label class="form-label" for="password">Mot de passe</label>
          <input type="password" id="password" name="password" class="form-control" placeholder="Votre mot de passe">
        </div>
        <button type="submit" class="btn btn-primary w-100 mt-2">🔐 Se connecter</button>
      </form>

      <div class="auth-toggle mt-2">
        Pas encore de compte ?
        <a href="register_agriculteur.php">Agriculteur</a> ou
        <a href="register_ouvrier.php">Ouvrier</a>
      </div>
      <div class="auth-toggle mt-1"><a href="../../index.php">← Retour à l'accueil</a></div>
    </div>
  </div>
</div>
<script src="../../js/main.js"></script>
</body>
</html>
