<?php

// Page affichant l'état d'un rendez-vous en attente de confirmation

session_start();
require_once 'config/Database.php';
require_once 'models/Appointment.php';

$pageTitle = "Attente de confirmation";
$appointmentDetails = null;
$isNotFound = false;
$accessToken = $_GET['token'] ?? '';

// Récupérer et valider le rendez-vous
if (isset($_GET['id']) && is_numeric($_GET['id']) && !empty($accessToken)) {
    $appointmentId = (int)$_GET['id'];

    $database = new DatabaseConnection();
    $databaseConnection = $database->getConnection();

    $appointmentRecord = new Appointment($databaseConnection);
    $appointmentRecord->id = $appointmentId;
    $appointmentRecord->public_token = $accessToken;

    if ($appointmentRecord->fetchAppointmentById()) {
        $appointmentDetails = $appointmentRecord;
    } else {
        $isNotFound = true;
    }
} else {
    $isNotFound = true;
}

// Configurer la langue
$language = $_COOKIE['lang'] ?? 'fr';
if ($language !== 'ar' && $language !== 'fr') {
    $language = 'fr';
}
$textDirection = ($language === 'ar') ? 'rtl' : 'ltr';

$allTranslations = require __DIR__ . '/lang/translations.php';
$translation = $allTranslations[$language];
?><!DOCTYPE html>
<html lang="<?php echo $language; ?>" dir="<?php echo $textDirection; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $translation['pending_title']; ?></title>
    <link rel="icon" type="image/png" href="assets/images/DoctorLogo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php if ($language === 'ar'): ?>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', 'IBM Plex Sans Arabic', sans-serif !important; }
        .confirm-doctor-name { font-family: 'Cairo', sans-serif !important; font-weight: 700 !important; }
        .confirm-section-title { font-family: 'Cairo', sans-serif !important; }
        .btn-confirm { font-family: 'Cairo', sans-serif !important; }
    </style>
    <?php else: ?>
    <link href="https://fonts.googleapis.com/css2?family=Spectral:wght@400;500;600;700&family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php endif; ?>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .pending-banner {
            background: rgba(196, 160, 82, 0.08);
            border: 1px dashed rgba(196, 160, 82, 0.3);
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            text-align: <?php echo ($language === 'ar') ? 'right' : 'left'; ?>;
        }
        .pending-banner i {
            color: var(--color-accent, #C4A052);
            font-size: 24px;
            margin-top: 2px;
        }
        .pending-banner-text h3 {
            font-size: 16px;
            font-weight: 600;
            color: #7c6229;
            margin-bottom: 6px;
        }
        .pending-banner-text p {
            font-size: 14px;
            color: #8c733a;
            line-height: 1.5;
            margin: 0;
        }
    </style>
</head>
<body>

<?php if ($isNotFound || !$appointmentDetails): ?>

    <!-- Error: Not found -->
    <main class="confirm-main">
        <div class="confirm-card confirm-error">
            <div class="confirm-error-icon">
                <i class="fa-regular fa-circle-xmark" style="font-size: 48px;"></i>
            </div>
            <h1><?php echo $translation['not_found_title']; ?></h1>
            <p><?php echo $translation['not_found_desc']; ?></p>
            <a href="index.php" class="btn-confirm"><?php echo $translation['back_home']; ?></a>
        </div>
    </main>

<?php else: ?>

    <!-- Pending ticket -->
    <main class="confirm-main">
        <div class="confirm-card">

            <!-- Doctor header + pending badge -->
            <div class="confirm-header">
                <div class="confirm-doctor">
                    <img src="assets/images/DoctorLogo.png" alt="Logo" class="confirm-logo" width="56" height="56">
                    <div>
                        <h1 class="confirm-doctor-name">Dr. Dghar Mohamed</h1>
                        <p class="confirm-doctor-title"><?php echo $translation['doctor_title']; ?></p>
                    </div>
                </div>
                <div class="confirm-badge" style="background: rgba(196, 160, 82, 0.08); color: var(--color-accent, #C4A052); border-color: rgba(196, 160, 82, 0.15);">
                    <i class="fa-regular fa-clock"></i>
                    <?php echo $translation['pending']; ?>
                </div>
            </div>

            <!-- Pending banner -->
            <div class="pending-banner">
                <i class="fa-solid fa-circle-info"></i>
                <div class="pending-banner-text">
                    <h3><?php echo $translation['pending_heading']; ?></h3>
                    <p><?php echo $translation['pending_desc']; ?></p>
                </div>
            </div>

            <div class="confirm-divider"></div>

            <!-- Patient info -->
            <section class="confirm-section">
                <h2 class="confirm-section-title">
                    <i class="fa-regular fa-user"></i>
                    <?php echo $translation['patient_info']; ?>
                </h2>
                <div class="confirm-grid">
                    <div class="confirm-field">
                        <span class="confirm-label"><?php echo $translation['fullname']; ?></span>
                        <span class="confirm-value"><?php echo htmlspecialchars($appointmentDetails->name); ?></span>
                    </div>
                    <div class="confirm-field">
                        <span class="confirm-label"><?php echo $translation['email']; ?></span>
                        <span class="confirm-value"><?php echo htmlspecialchars($appointmentDetails->email); ?></span>
                    </div>
                    <div class="confirm-field">
                        <span class="confirm-label"><?php echo $translation['phone']; ?></span>
                        <span class="confirm-value"><?php echo htmlspecialchars($appointmentDetails->phone); ?></span>
                    </div>
                    <div class="confirm-field">
                        <span class="confirm-label"><?php echo $translation['cni']; ?></span>
                        <span class="confirm-value"><?php echo htmlspecialchars($appointmentDetails->cni); ?></span>
                    </div>
                </div>
            </section>

            <div class="confirm-divider"></div>

            <!-- Appointment details -->
            <section class="confirm-section">
                <h2 class="confirm-section-title">
                    <i class="fa-regular fa-calendar-check"></i>
                    <?php echo $translation['appointment_info']; ?>
                </h2>
                <div class="confirm-grid">
                    <div class="confirm-field">
                        <span class="confirm-label"><?php echo $translation['service']; ?></span>
                        <span class="confirm-value confirm-service">
                            <?php
                            $serviceLabel = $appointmentDetails->service_name ?? $appointmentDetails->service_type;
                            echo htmlspecialchars($serviceLabel);
                            ?>
                        </span>
                    </div>
                    <div class="confirm-field">
                        <span class="confirm-label"><?php echo $translation['date']; ?></span>
                        <span class="confirm-value">
                            <?php
                            $appointmentDateTime = new DateTime($appointmentDetails->appointment_date);
                            echo $appointmentDateTime->format('d/m/Y');
                            ?>
                        </span>
                    </div>
                    <?php if ($appointmentDetails->time_slot_id):
                        $slotQuery = $databaseConnection->prepare("SELECT start_time, end_time FROM time_slots WHERE id = ?");
                        $slotQuery->execute([$appointmentDetails->time_slot_id]);
                        $slotInformation = $slotQuery->fetch(PDO::FETCH_ASSOC);
                    ?>
                    <?php if ($slotInformation): ?>
                    <div class="confirm-field">
                        <span class="confirm-label"><?php echo $translation['time']; ?></span>
                        <span class="confirm-value">
                            <?php echo substr($slotInformation['start_time'], 0, 5) . ' - ' . substr($slotInformation['end_time'], 0, 5); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                    <div class="confirm-field">
                        <span class="confirm-label"><?php echo $translation['reference'] ?? 'Réf. Mouvement'; ?></span>
                        <span class="confirm-value" style="font-weight:600; color: var(--color-primary);"><?php echo htmlspecialchars($appointmentDetails->reference_number); ?></span>
                    </div>
                    <div class="confirm-field">
                        <span class="confirm-label"><?php echo $translation['status']; ?></span>
                        <span class="confirm-value confirm-status"><?php echo $translation['pending']; ?></span>
                    </div>
                </div>
            </section>

            <div class="confirm-divider"></div>

            <!-- Practical info -->
            <section class="confirm-section">
                <h2 class="confirm-section-title">
                    <i class="fa-solid fa-circle-info"></i>
                    <?php echo $translation['practical_info']; ?>
                </h2>
                <div class="confirm-info-list">
                    <div class="confirm-info-item">
                        <i class="fa-solid fa-phone"></i>
                        <span>06 15 50 13 39</span>
                    </div>
                    <div class="confirm-info-item">
                        <i class="fa-solid fa-location-dot"></i>
                        <span><?php echo $translation['address_label']; ?></span>
                    </div>
                </div>
            </section>

            <!-- Footer -->
            <div class="confirm-footer">
                <p class="confirm-issued">
                    <?php echo $translation['issued_on']; ?> <?php echo (new DateTime($appointmentDetails->created_at))->format('d/m/Y H:i'); ?>
                </p>
                <div class="confirm-actions">
                    <a href="index.php" class="btn-confirm"><?php echo $translation['back_home']; ?></a>
                </div>
            </div>

        </div>
    </main>

<?php endif; ?>

</body>
</html>
