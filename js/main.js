const RULES = {
  pseudo:   /^[a-zA-ZÀ-ÿ]+$/,
  password: /^[a-zA-Z0-9À-ÿ]{7,}[\$#]$/,
  cin:      /^[0-9]{8}$/,
  email:    /^[^\s@]+@[^\s@]+\.[^\s@]+$/
};

function showError(inputId, message) {
  const input = document.getElementById(inputId);
  if (!input) return;
  input.classList.add('is-invalid');
  let err = input.parentNode.querySelector('.form-error');
  if (!err) { err = document.createElement('div'); err.className = 'form-error'; input.parentNode.appendChild(err); }
  err.textContent = message;
}

function clearError(inputId) {
  const input = document.getElementById(inputId);
  if (!input) return;
  input.classList.remove('is-invalid');
  const err = input.parentNode.querySelector('.form-error');
  if (err) err.textContent = '';
}

function validateField(id, rule, msg) {
  const val = document.getElementById(id)?.value.trim();
  if (!val) { showError(id, 'Ce champ est obligatoire.'); return false; }
  if (rule && !rule.test(val)) { showError(id, msg); return false; }
  clearError(id); return true;
}

// ---- REGISTRATION VALIDATION ----
function validateRegisterAgriculteur() {
  let valid = true;
  valid &= validateField('nom', null, '');
  valid &= validateField('prenom', null, '');
  valid &= validateField('cin', RULES.cin, 'Le CIN doit contenir exactement 8 chiffres.');
  valid &= validateField('email', RULES.email, 'Email invalide.');
  valid &= validateField('pseudo', RULES.pseudo, 'Le pseudo doit contenir uniquement des lettres.');
  valid &= validateField('password', RULES.password, 'Mot de passe: min 8 caractères (lettres/chiffres) + finit par $ ou #');

  const pwd = document.getElementById('password')?.value;
  const cpwd = document.getElementById('confirm_password')?.value;
  if (pwd && cpwd && pwd !== cpwd) {
    showError('confirm_password', 'Les mots de passe ne correspondent pas.');
    valid = false;
  } else clearError('confirm_password');

  return !!valid;
}

function validateRegisterOuvrier() {
  let valid = validateRegisterAgriculteur();
  // photo validation
  const photo = document.getElementById('photo');
  if (photo && photo.files.length === 0) {
    showError('photo', 'Veuillez sélectionner une photo.');
    valid = false;
  } else clearError('photo');
  return valid;
}

// ---- LOGIN VALIDATION ----
function validateLogin() {
  let valid = true;
  valid &= validateField('pseudo', null, '');
  valid &= validateField('password', null, '');
  return !!valid;
}

// ---- OFFER VALIDATION ----
function validateOffre() {
  let valid = true;
  valid &= validateField('id_type_fruit', null, '');
  valid &= validateField('id_gouvernorat', null, '');
  valid &= validateField('adresse', null, '');
  valid &= validateField('date_debut', null, '');
  valid &= validateField('date_fin', null, '');
  valid &= validateField('nombre_ouvriers', null, '');
  valid &= validateField('prix_journee', null, '');
  valid &= validateField('date_limite', null, '');

  const debut = document.getElementById('date_debut')?.value;
  const fin   = document.getElementById('date_fin')?.value;
  const limite = document.getElementById('date_limite')?.value;
  const today  = new Date().toISOString().split('T')[0];

  if (debut && fin && debut >= fin) { showError('date_fin', 'La date de fin doit être après la date de début.'); valid = false; }
  if (debut && debut < today) { showError('date_debut', 'La date de début ne peut pas être dans le passé.'); valid = false; }
  if (limite && fin && limite >= fin) { showError('date_limite', 'La date limite doit être avant la fin du chantier.'); valid = false; }

  const nb = parseInt(document.getElementById('nombre_ouvriers')?.value);
  if (nb < 1) { showError('nombre_ouvriers', 'Le nombre d\'ouvriers doit être au moins 1.'); valid = false; }

  const prix = parseFloat(document.getElementById('prix_journee')?.value);
  if (prix <= 0) { showError('prix_journee', 'Le prix doit être positif.'); valid = false; }

  return !!valid;
}

// ---- STAR RATING ----
function initStarRatings() {
  document.querySelectorAll('.star-input').forEach(container => {
    const input = container.querySelector('input[type="hidden"]');
    const stars = container.querySelectorAll('.star');
    stars.forEach((star, i) => {
      star.addEventListener('mouseover', () => stars.forEach((s, j) => s.classList.toggle('hovered', j <= i)));
      star.addEventListener('mouseleave', () => stars.forEach(s => s.classList.remove('hovered')));
      star.addEventListener('click', () => {
        const val = i + 1;
        input.value = val;
        stars.forEach((s, j) => s.classList.toggle('selected', j < val));
      });
    });
  });
}

// ---- PHOTO PREVIEW ----
function initPhotoPreview() {
  const photoInput = document.getElementById('photo');
  const preview = document.getElementById('photo-preview');
  if (photoInput && preview) {
    photoInput.addEventListener('change', function() {
      if (this.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(this.files[0]);
      }
    });
  }
}

// ---- ALERT AUTO DISMISS ----
function initAlerts() {
  document.querySelectorAll('.alert[data-dismiss]').forEach(alert => {
    setTimeout(() => {
      alert.style.opacity = '0';
      alert.style.transition = 'opacity 0.5s';
      setTimeout(() => alert.remove(), 500);
    }, 4000);
  });
}

// ---- CONFIRM DELETE ----
function confirmDelete(msg) {
  return confirm(msg || 'Êtes-vous sûr de vouloir supprimer ?');
}

// ---- INIT ----
document.addEventListener('DOMContentLoaded', function() {
  initStarRatings();
  initPhotoPreview();
  initAlerts();

  // Real-time validation
  ['pseudo', 'cin', 'email', 'password'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('blur', function() {
      const ruleMap = { pseudo: [RULES.pseudo, 'Uniquement des lettres.'], cin: [RULES.cin, '8 chiffres requis.'], email: [RULES.email, 'Email invalide.'], password: [RULES.password, 'Min 8 chars + finit par $ ou #'] };
      const [rule, msg] = ruleMap[id] || [];
      if (this.value.trim()) {
        if (rule && !rule.test(this.value.trim())) showError(id, msg);
        else clearError(id);
      }
    });
  });
});
