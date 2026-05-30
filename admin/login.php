<?php

// Page de connexion pour l'administrateur

// Démarrer la session
session_start();

// Gérer le changement de langue
$language = $_COOKIE['lang'] ?? 'fr';
if (isset($_GET['set_lang'])) {
    $language = $_GET['set_lang'] === 'ar' ? 'ar' : 'fr';
    setcookie('lang', $language, time() + 31536000, '/');
    header('Location: login.php');
    exit;
}

if ($language !== 'ar' && $language !== 'fr') {
    $language = 'fr';
}
$textDirection = ($language === 'ar') ? 'rtl' : 'ltr';

// Rediriger si l'administrateur est déjà connecté
if (isset($_SESSION['logged']) && $_SESSION['logged'] === true) {
    header('Location: index.php');
    exit;
}

// Charger les traductions et la configuration
$allTranslations = require __DIR__ . '/../lang/translations.php';
$translation = $allTranslations;

require_once __DIR__ . '/../config/admin-config.php';

$loginError = '';

// Traiter le formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedPassword = $_POST['pass'] ?? '';

    // Vérifier le mot de passe et sécuriser la session
    if (password_verify($submittedPassword, $hashedAdminPassword)) {
        session_regenerate_id(true);
        $_SESSION['logged'] = true;
        header('Location: index.php');
        exit;
    } else {
        $loginError = $translation[$language]['login_error'];
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $language; ?>" dir="<?php echo $textDirection; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($translation[$language]['login_title']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Cairo:wght@400;500;600;700;800&family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-login.css">
</head>
<body>
    <div class="login-box">
        <div class="lang-switch">
            <a href="?set_lang=<?php echo ($language === 'ar') ? 'fr' : 'ar'; ?>"><?php echo htmlspecialchars($translation[$language]['lang_toggle']); ?></a>
        </div>
        <div class="login-logo">
            <i class="fa-solid fa-heart-pulse" style="font-size: 28px; color: currentColor;"></i>
        </div>
        <h1><?php echo htmlspecialchars($translation[$language]['login_h1']); ?></h1>
        <p class="subtitle"><?php echo htmlspecialchars($translation[$language]['login_subtitle']); ?></p>

        <?php if (!empty($loginError)): ?>
            <p class="error"><?php echo htmlspecialchars($loginError); ?></p>
        <?php endif; ?>

        <form method="post">
            <label for="pass"><?php echo htmlspecialchars($translation[$language]['password']); ?></label>
            <input type="password" id="pass" name="pass" placeholder="<?php echo htmlspecialchars($translation[$language]['password_placeholder']); ?>" required>
            <button type="submit"><?php echo htmlspecialchars($translation[$language]['login_submit']); ?></button>
        </form>
    </div>

    <a href="../index.php" class="back-link">
        <?php if ($language === 'ar'): ?>
            <?php echo htmlspecialchars($translation[$language]['back']); ?> &larr;
        <?php else: ?>
            &larr; <?php echo htmlspecialchars($translation[$language]['back']); ?>
        <?php endif; ?>
    </a>
</body>
</html>
