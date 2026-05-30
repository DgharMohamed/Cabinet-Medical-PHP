<?php

// Page de déconnexion pour l'administrateur

session_start();

// Effacer toutes les variables de session
$_SESSION = array();

// Supprimer le cookie de session si existant
if (ini_get("session.use_cookies")) {
    $sessionParameters = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $sessionParameters["path"], $sessionParameters["domain"],
        $sessionParameters["secure"], $sessionParameters["httponly"]
    );
}

// Détruire la session
session_destroy();

header('Location: login.php');
exit;
