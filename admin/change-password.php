<?php

// Page pour changer le mot de passe administrateur

// Vérifier l'authentification
session_start();
if (!isset($_SESSION['logged']) || $_SESSION['logged'] !== true) {
    header('Location: login.php');
    exit;
}

// Configurer la langue
$language = $_COOKIE['lang'] ?? 'fr';
if ($language !== 'ar' && $language !== 'fr') {
    $language = 'fr';
}
$textDirection = ($language === 'ar') ? 'rtl' : 'ltr';

// Charger les traductions et la configuration
$allTranslations = require __DIR__ . '/../lang/translations.php';
$translation = $allTranslations;

require_once __DIR__ . '/../config/Database.php';

$successMessage = '';
$errorMessage = '';

// Gérer la soumission du formulaire de changement de mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmNewPassword = $_POST['confirm_password'] ?? '';

    try {
        $database = new DatabaseConnection();
        $db = $database->getConnection();
        
        // ID de l'administrateur, on suppose 1 s'il n'est pas encore dans la session
        $adminId = $_SESSION['admin_id'] ?? 1;
        
        $stmt = $db->prepare("SELECT password FROM admins WHERE id = :id");
        $stmt->execute(['id' => $adminId]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        // Validation des mots de passe
        if (!$admin || !password_verify($currentPassword, $admin['password'])) {
            $errorMessage = $translation[$language]['pass_err_current'];
        } elseif ($newPassword !== $confirmNewPassword) {
            $errorMessage = $translation[$language]['pass_err_match'];
        } elseif (strlen($newPassword) < 6) {
            $errorMessage = $translation[$language]['pass_err_short'];
        } elseif ($currentPassword === $newPassword) {
            $errorMessage = $translation[$language]['pass_err_same'];
        } else {
            // Hasher le nouveau mot de passe
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

            // Mettre à jour la base de données
            $updateStmt = $db->prepare("UPDATE admins SET password = :password WHERE id = :id");
            if ($updateStmt->execute(['password' => $newPasswordHash, 'id' => $adminId])) {
                $successMessage = $translation[$language]['pass_success'];
            }
        }
    } catch (PDOException $e) {
        $errorMessage = "Erreur de base de données.";
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $language; ?>" dir="<?php echo $textDirection; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($translation[$language]['pass_title']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Cairo:wght@400;500;600;700;800&family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-login.css">
</head>
<body>
    <div class="login-box">
        <div class="login-logo">
            <i class="fa-solid fa-key" style="font-size: 24px; color: #1B4D3E;"></i>
        </div>
        <h1><?php echo htmlspecialchars($translation[$language]['pass_h1']); ?></h1>
        <p class="subtitle" style="margin-bottom: 24px;">
            <?php echo htmlspecialchars($translation[$language]['pass_subtitle']); ?>
        </p>

        <?php if ($successMessage): ?>
            <p class="success"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($successMessage); ?></p>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <p class="error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($errorMessage); ?></p>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="current_password"><?php echo htmlspecialchars($translation[$language]['pass_current']); ?></label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            <div class="form-group">
                <label for="new_password"><?php echo htmlspecialchars($translation[$language]['pass_new']); ?></label>
                <input type="password" id="new_password" name="new_password" required minlength="6">
            </div>
            <div class="form-group">
                <label for="confirm_password"><?php echo htmlspecialchars($translation[$language]['pass_confirm']); ?></label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
            </div>
            <button type="submit"><?php echo htmlspecialchars($translation[$language]['pass_submit']); ?></button>
        </form>
    </div>

    <a href="index.php" class="back-link">
        <?php if ($language === 'ar'): ?>
            <?php echo htmlspecialchars($translation[$language]['pass_back']); ?> &larr;
        <?php else: ?>
            &larr; <?php echo htmlspecialchars($translation[$language]['pass_back']); ?>
        <?php endif; ?>
    </a>
</body>
</html>
