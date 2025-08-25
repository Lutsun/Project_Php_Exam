<?php
// Vérifier si les headers ont déjà été envoyés
if (headers_sent()) {
    die("Erreur: Les headers ont déjà été envoyés. Vérifiez les espaces avant <?php ou les echo avant session_start().");
}

// Configuration sécurisée des sessions AVANT session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
ini_set('session.sid_length', 128);
ini_set('session.sid_bits_per_character', 6);

// Démarrer la session seulement si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Protection contre le fixation de session
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
    $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $_SESSION['ua'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
}

// Vérification contre le hijacking de session
if ($_SESSION['ip'] !== ($_SERVER['REMOTE_ADDR'] ?? 'unknown') || 
    $_SESSION['ua'] !== ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown')) {
    session_unset();
    session_destroy();
    // Éviter la redirection pour ne pas créer de boucle
    die("Erreur de sécurité: Session invalide. Veuillez vous reconnecter.");
}