<?php
// includes/session.php
session_start();

function isAgriculteur() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'agriculteur';
}

function isOuvrier() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'ouvrier';
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireAgriculteur() {
    if (!isAgriculteur()) {
        header('Location: ../index.php?error=access_denied');
        exit;
    }
}

function requireOuvrier() {
    if (!isOuvrier()) {
        header('Location: ../index.php?error=access_denied');
        exit;
    }
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../index.php?error=not_logged_in');
        exit;
    }
}
?>
