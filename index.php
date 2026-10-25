<?php
require_once 'includes/session.php';

// Redirect logged-in users to their dashboard
if (isLoggedIn()) {
    if (isAgriculteur()) header('Location: php/agriculteur/dashboard.php');
    else header('Location: php/ouvrier/dashboard.php');
    exit;
}

$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Uber-Cueillette — Connecter Agriculteurs & Ouvriers</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    .role-choice { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 2rem; }
    .role-card {
      background: var(--white);
      border: 2px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 2.5rem 2rem;
      text-align: center;
      cursor: pointer;
      text-decoration: none;
      color: var(--text);
      transition: all 0.3s;
      display: block;
    }
    .role-card:hover { border-color: var(--olive-light); transform: translateY(-6px); box-shadow: 0 16px 40px var(--shadow); }
    .role-card.farmer:hover { border-color: var(--earth); }
    .role-card .role-emoji { font-size: 3.5rem; margin-bottom: 1rem; display: block; }
    .role-card h3 { font-family: 'Playfair Display', serif; font-size: 1.3rem; color: var(--bark); margin-bottom: 0.5rem; }
    .role-card p { font-size: 0.88rem; color: var(--text-muted); }
    .role-card .role-links { margin-top: 1.2rem; display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap; }

    .how-it-works { max-width: 1100px; margin: 0 auto; }
    .step { display: flex; align-items: flex-start; gap: 1.5rem; }
    .step-num {
      width: 48px; height: 48px;
      background: var(--olive);
      border-radius: 50%;
      color: white;
      font-family: 'Playfair Display', serif;
      font-size: 1.2rem;
      font-weight: 700;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .step-content h4 { font-family: 'Playfair Display', serif; color: var(--bark); margin-bottom: 0.3rem; }
    .step-content p { color: var(--text-muted); font-size: 0.9rem; }
    .steps-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; max-width: 900px; margin: 0 auto; }

    .fruits-strip {
      background: var(--bark);
      padding: 1rem 0;
      overflow: hidden;
    }
    .fruits-marquee {
      display: flex;
      gap: 3rem;
      animation: marquee 20s linear infinite;
      white-space: nowrap;
    }
    .fruits-marquee span { color: var(--olive-pale); font-size: 0.85rem; font-weight: 500; letter-spacing: 0.1em; text-transform: uppercase; }
    .fruits-marquee span::before { margin-right: 1rem; }
    @keyframes marquee { from { transform: translateX(0); } to { transform: translateX(-50%); } }

    @media (max-width: 600px) {
      .role-choice { grid-template-columns: 1fr; }
      .steps-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <a href="index.php" class="navbar-brand">
    <div class="logo-icon">🫒</div>
    <div>
      <span class="brand-name">Uber-Cueillette</span>
      <span class="brand-sub">Récolte Connectée</span>
    </div>
  </a>
  <ul class="navbar-nav">
    <li><a href="#comment">Comment ça marche</a></li>
    <li><a href="php/auth/login.php">Connexion</a></li>
    <li><a href="#inscription" class="btn-nav">S'inscrire</a></li>
  </ul>
</nav>

<!-- FRUITS STRIP -->
<div class="fruits-strip">
  <div class="fruits-marquee">
    <span>🫒 Olives</span><span>🍊 Agrumes</span><span>🍅 Tomates</span>
    <span>🌴 Dattes</span><span>🍇 Raisins</span><span>🍎 Pommes</span>
    <span>🍐 Poires</span><span>🌿 Figues</span>
    <!-- repeat for seamless loop -->
    <span>🫒 Olives</span><span>🍊 Agrumes</span><span>🍅 Tomates</span>
    <span>🌴 Dattes</span><span>🍇 Raisins</span><span>🍎 Pommes</span>
    <span>🍐 Poires</span><span>🌿 Figues</span>
  </div>
</div>

<!-- HERO -->
<section class="hero">
  <div class="hero-content fade-in">
    <div class="hero-badge">🌾 Plateforme de Récolte Agricole</div>
    <h1>Connectez <em>Agriculteurs</em><br>& Ouvriers de Récolte</h1>
    <p>La plateforme qui met en relation les agriculteurs tunisiens avec des ouvriers qualifiés pour chaque saison de récolte — olives, agrumes, tomates et bien plus.</p>
    <div class="hero-actions">
      <a href="#inscription" class="btn btn-primary">🌿 Commencer maintenant</a>
      <a href="php/auth/login.php" class="btn btn-secondary">Se connecter</a>
    </div>
  </div>
</section>

<?php if ($error): ?>
<div class="page-wrapper">
  <div class="alert alert-error" data-dismiss>⚠️ Accès refusé. Veuillez vous connecter.</div>
</div>
<?php endif; ?>

<!-- HOW IT WORKS -->
<section class="section" id="comment">
  <div class="how-it-works">
    <h2 class="section-title">Comment ça marche ?</h2>
    <p class="section-sub">Simple, rapide, efficace — en 4 étapes.</p>
    <div class="steps-grid">
      <div class="step">
        <div class="step-num">1</div>
        <div class="step-content">
          <h4>Créez votre profil</h4>
          <p>Inscrivez-vous comme agriculteur ou ouvrier. Renseignez vos informations et votre profil devient visible de la communauté.</p>
        </div>
      </div>
      <div class="step">
        <div class="step-num">2</div>
        <div class="step-content">
          <h4>Publiez ou consultez les offres</h4>
          <p>Les agriculteurs publient leurs besoins de récolte. Les ouvriers consultent et filtrent les offres par fruit, région ou salaire.</p>
        </div>
      </div>
      <div class="step">
        <div class="step-num">3</div>
        <div class="step-content">
          <h4>Postulez en 1 clic</h4>
          <p>L'ouvrier postule, l'agriculteur consulte les profils et accepte ou refuse les candidatures selon ses besoins.</p>
        </div>
      </div>
      <div class="step">
        <div class="step-num">4</div>
        <div class="step-content">
          <h4>Évaluez après la récolte</h4>
          <p>Après le chantier, l'agriculteur note et commente le travail de chaque ouvrier pour enrichir leur profil.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FEATURES -->
<section class="section section-olive">
  <h2 class="section-title">Pourquoi choisir Uber-Cueillette ?</h2>
  <p class="section-sub">Des fonctionnalités pensées pour simplifier la saison de récolte.</p>
  <div style="max-width:1000px;margin:0 auto;" class="grid-3">
    <div class="feature-card">
      <div class="feature-icon">🗺️</div>
      <h4>Filtrage par Gouvernorat</h4>
      <p>Trouvez des offres près de chez vous en filtrant par gouvernorat ou type de fruit.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">⭐</div>
      <h4>Système de notation</h4>
      <p>Les profils d'ouvriers affichent leur moyenne de notes et les commentaires des anciens employeurs.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">📊</div>
      <h4>Suivi des gains</h4>
      <p>Les ouvriers peuvent consulter leur historique de chantiers et le total de leurs rémunérations.</p>
    </div>
  </div>
</section>

<!-- INSCRIPTION -->
<section class="section" id="inscription">
  <h2 class="section-title">Rejoignez la plateforme</h2>
  <p class="section-sub">Choisissez votre profil pour commencer.</p>
  <div class="role-choice" style="max-width:700px;margin:0 auto;">
    <div class="role-card farmer">
      <span class="role-emoji">👨‍🌾</span>
      <h3>Je suis Agriculteur</h3>
      <p>Publiez vos offres de récolte, gérez les candidatures et évaluez vos ouvriers.</p>
      <div class="role-links">
        <a href="php/auth/register_agriculteur.php" class="btn btn-earth btn-sm">S'inscrire</a>
        <a href="php/auth/login.php" class="btn btn-outline btn-sm">Se connecter</a>
      </div>
    </div>
    <div class="role-card">
      <span class="role-emoji">👷</span>
      <h3>Je suis Ouvrier</h3>
      <p>Consultez les offres, postulez en un clic et suivez vos chantiers et gains.</p>
      <div class="role-links">
        <a href="php/auth/register_ouvrier.php" class="btn btn-primary btn-sm">S'inscrire</a>
        <a href="php/auth/login.php" class="btn btn-outline btn-sm">Se connecter</a>
      </div>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <p>🫒 <strong>Uber-Cueillette</strong> — Institut Supérieur de Gestion de Tunis &nbsp;|&nbsp; Web2 2025-2026</p>
</footer>

<script src="js/main.js"></script>
</body>
</html>
