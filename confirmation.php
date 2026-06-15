<?php

// Page affichant les détails d'un rendez-vous confirmé avec un code QR

session_start();
require_once 'config/Database.php';
require_once 'models/Appointment.php';

$pageTitle = "Confirmation";
$appointmentDetails = null;
$isNotFound = false;
$databaseConnection = null;
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
        // Rediriger si le statut n'est pas "confirmé"
        if ($appointmentRecord->status !== 'confirmed') {
            header('Location: pending-confirmation.php?id=' . $appointmentId . '&token=' . urlencode($accessToken));
            exit;
        }
        $appointmentDetails = $appointmentRecord;
    } else {
        $isNotFound = true;
    }
} else {
    $isNotFound = true;
}

// Configurer la langue de la page
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
    <title><?php echo $translation['confirm_title']; ?></title>
    <link rel="icon" type="image/png" href="assets/images/DoctorLogo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php if ($language === 'ar'): ?>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <?php else: ?>
    <link href="https://fonts.googleapis.com/css2?family=Spectral:wght@400;500;600;700&family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php endif; ?>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body class="confirmation-body">

<?php if ($isNotFound || !$appointmentDetails): ?>

    <!-- Error: Not found -->
    <main class="confirm-main">
        <div class="confirm-card confirm-error">
            <div class="confirm-error-icon">
                <i class="fa-regular fa-circle-xmark error-icon-large"></i>
            </div>
            <h1><?php echo $translation['not_found_title']; ?></h1>
            <p><?php echo $translation['not_found_desc']; ?></p>
            <a href="index.php" class="btn-confirm"><?php echo $translation['back_home']; ?></a>
        </div>
    </main>

<?php else: ?>

    <!-- Confirmed ticket -->
    <main class="confirm-main">
        <div class="confirm-card">

            <!-- Doctor header + confirmed badge -->
            <div class="confirm-header">
                <div class="confirm-doctor">
                    <img src="assets/images/DoctorLogo.png" alt="Logo" class="confirm-logo" width="56" height="56">
                    <div>
                        <h1 class="confirm-doctor-name">Dr. Dghar Mohamed</h1>
                        <p class="confirm-doctor-title"><?php echo $translation['doctor_title']; ?></p>
                    </div>
                </div>
                <div class="confirm-badge">
                    <i class="fa-solid fa-circle-check"></i>
                    <?php echo $translation['confirmed']; ?>
                </div>
            </div>

            <!-- QR code + reference -->
            <?php if ($appointmentDetails->reference_number): ?>
            <div class="confirm-ref-banner confirm-ref-banner-style">
                <div style="flex-shrink:0;">
                    <?php $qrCodeImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=' . urlencode($appointmentDetails->reference_number); ?>
                    <img src="<?php echo htmlspecialchars($qrCodeImageUrl); ?>" alt="QR Code" width="100" height="100" class="qr-code-image">
                </div>
                <div>
                    <div class="qr-code-text-container">
                        <i class="fa-solid fa-qrcode qr-code-icon"></i>
                        <span><?php echo $translation['ref_number']; ?>: <?php echo htmlspecialchars($appointmentDetails->reference_number); ?></span>
                    </div>
                    <p class="qr-code-instructions">
                        <?php echo ($language === 'ar') ? 'يرجى الاحتفاظ برمز الاستجابة السريعة (QR Code) لتقديمه عند وصولك إلى العيادة.' : 'Veuillez conserver ce QR Code pour le présenter à votre arrivée au cabinet.'; ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>

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
                        <span class="confirm-label"><?php echo $translation['status']; ?></span>
                        <span class="confirm-value <?php echo ($appointmentDetails->status === 'confirmed') ? 'confirm-status confirm-status-confirmed' : 'confirm-status confirm-status-pending'; ?>">
                            <?php echo ($appointmentDetails->status === 'confirmed') ? $translation['confirmed'] : $translation['pending']; ?>
                        </span>
                    </div>
                    <?php if (!empty($appointmentDetails->message)): ?>
                    <div class="confirm-field confirm-field-full">
                        <span class="confirm-label"><?php echo $translation['message']; ?></span>
                        <span class="confirm-value confirm-message"><?php echo nl2br(htmlspecialchars($appointmentDetails->message)); ?></span>
                    </div>
                    <?php endif; ?>
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
                    <div class="confirm-info-item">
                        <i class="fa-solid fa-bell"></i>
                        <span><?php echo $translation['info_note']; ?></span>
                    </div>
                </div>
            </section>

            <!-- Footer + print -->
            <div class="confirm-footer">
                <p class="confirm-issued">
                    <?php echo $translation['issued_on']; ?> <?php echo (new DateTime($appointmentDetails->created_at))->format('d/m/Y H:i'); ?>
                    &bull; <?php echo $translation['rdv_no']; ?><?php echo htmlspecialchars($appointmentDetails->reference_number ?? ('RDV-' . str_pad($appointmentDetails->id, 4, '0', STR_PAD_LEFT))); ?>
                </p>
                <div class="confirm-actions">
                    <button class="btn-confirm btn-confirm-outline" onclick="window.print()">
                        <i class="fa-solid fa-print"></i>
                        <?php echo $translation['print']; ?>
                    </button>
                    <a href="index.php" class="btn-confirm"><?php echo $translation['back_home']; ?></a>
                </div>
            </div>

        </div>
    </main>

<?php endif; ?>

</body>
</html>
