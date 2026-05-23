<?php

// En-tête partagé : initialisation de la session, protection CSRF, gestion des langues (FR/AR) et barre de navigation

// Démarrer la session si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Générer un jeton CSRF pour protéger les formulaires
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Gérer le changement de langue
$language = $_COOKIE['lang'] ?? 'fr';
if (isset($_GET['set_lang'])) {
    $language = $_GET['set_lang'] === 'ar' ? 'ar' : 'fr';
    setcookie('lang', $language, time() + 31536000, '/');
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}
if ($language !== 'ar' && $language !== 'fr') {
    $language = 'fr';
}
$textDirection = ($language === 'ar') ? 'rtl' : 'ltr';

// Charger les traductions
$translations = require __DIR__ . '/../lang/translations.php';
$translation = [$language => $translations[$language]];
?>
<!DOCTYPE html>
<html lang="<?php echo $language; ?>" dir="<?php echo $textDirection; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo ($language === 'ar') ? 'عيادة الدكتور ادغار محمد، طبيب عام في طنجة' : 'Cabinet médical du Dr. Dghar Mohamed, médecin généraliste à Tanger'; ?>">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Dr. Dghar Mohamed - <?php echo ($language === 'ar') ? 'طبيب عام في طنجة' : 'Médecin Généraliste à Tanger'; ?></title>
    <link rel="icon" type="image/png" href="assets/images/DoctorLogo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php if ($language === 'ar'): ?>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <?php else: ?>
    <link href="https://fonts.googleapis.com/css2?family=Spectral:wght@400;500;600;700&family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>

    <!-- Scroll progress bar -->
    <div class="scroll-progress" id="scrollProgress"></div>

    <!-- Nav bar -->
    <header class="header" id="header">
        <div class="container">
            <nav class="nav">
                <a href="index.php" class="nav-brand">
                    <img src="assets/images/DoctorLogo.png" alt="Logo Dr. Dghar Mohamed" width="36" height="36">
                    <span><?php echo $translation[$language]['brand']; ?></span>
                </a>

                <ul class="nav-links" id="navLinks">
                    <li><a href="#about"><?php echo $translation[$language]['nav_about']; ?></a></li>
                    <li><a href="#services"><?php echo $translation[$language]['nav_services']; ?></a></li>
                    <li><a href="#testimonials"><?php echo $translation[$language]['nav_testimonials']; ?></a></li>
                    <li><a href="#contact"><?php echo $translation[$language]['nav_contact']; ?></a></li>
                </ul>

                <div class="nav-actions">
                    <a href="?set_lang=<?php echo ($language === 'ar') ? 'fr' : 'ar'; ?>" class="lang-btn" id="langToggle">
                        <span><?php echo $translation[$language]['lang_toggle']; ?></span>
                    </a>
                    <a href="#appointment" class="btn btn-primary btn-sm"><?php echo $translation[$language]['nav_book']; ?></a>
                    <button class="hamburger" id="hamburger" aria-label="Menu" aria-expanded="false">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            </nav>
        </div>
    </header>

    <!-- Mobile menu -->
    <div class="mobile-menu" id="mobileMenu">
        <div class="mobile-menu-inner">
            <a href="#about" class="mobile-link"><?php echo $translation[$language]['nav_about']; ?></a>
            <a href="#services" class="mobile-link"><?php echo $translation[$language]['nav_services']; ?></a>
            <a href="#testimonials" class="mobile-link"><?php echo $translation[$language]['nav_testimonials']; ?></a>
            <a href="#contact" class="mobile-link"><?php echo $translation[$language]['nav_contact']; ?></a>
            <a href="#appointment" class="btn btn-primary"><?php echo $translation[$language]['nav_book']; ?></a>
        </div>
    </div>
