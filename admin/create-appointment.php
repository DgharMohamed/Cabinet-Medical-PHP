<?php
// Page de l'administrateur pour créer manuellement un rendez-vous (patient, service, date, créneau horaire)

// Vérifier l'authentification
session_start();
if (!isset($_SESSION['logged']) || $_SESSION['logged'] !== true) {
    header('Location: login.php');
    exit;
}

// Connexion à la base de données
require_once __DIR__ . '/../config/Database.php';

$database = new DatabaseConnection();
$databaseConnection = $database->getConnection();

// Récupérer la liste des services et des créneaux horaires
$allServices = $databaseConnection->query("SELECT * FROM services")->fetchAll(PDO::FETCH_ASSOC);
$allTimeSlots = $databaseConnection->query("SELECT * FROM time_slots ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), start_time")->fetchAll(PDO::FETCH_ASSOC);

// Configurer la langue et charger les traductions
$language = $_COOKIE['lang'] ?? 'fr';
if ($language !== 'ar' && $language !== 'fr') {
    $language = 'fr';
}
$textDirection = ($language === 'ar') ? 'rtl' : 'ltr';

$allTranslations = require __DIR__ . '/../lang/translations.php';
$translation = $allTranslations;

$errorMessage = '';
$successMessage = '';

// Noms des jours pour l'affichage selon la langue
$daysFrench = [
    'Monday' => 'Lundi', 'Tuesday' => 'Mardi', 'Wednesday' => 'Mercredi',
    'Thursday' => 'Jeudi', 'Friday' => 'Vendredi', 'Saturday' => 'Samedi', 'Sunday' => 'Dimanche'
];
$daysArabic = [
    'Monday' => 'الإثنين', 'Tuesday' => 'الثلاثاء', 'Wednesday' => 'الأربعاء',
    'Thursday' => 'الخميس', 'Friday' => 'الجمعة', 'Saturday' => 'السبت', 'Sunday' => 'الأحد'
];
$dayNames = ($language === 'ar') ? $daysArabic : $daysFrench;

// Traiter la soumission du formulaire de création de rendez-vous
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Extraire les données saisies
    $patientName = trim($_POST['name'] ?? '');
    $patientCni = trim($_POST['cni'] ?? '');
    $patientEmail = trim($_POST['email'] ?? '');
    $patientPhone = trim($_POST['phone'] ?? '');
    $selectedServiceId = intval($_POST['service_id'] ?? 0);
    $selectedDate = trim($_POST['appointment_date'] ?? '');
    $selectedSlotId = intval($_POST['time_slot_id'] ?? 0);
    $patientMessage = trim($_POST['message'] ?? '');

    // Valider les champs obligatoires
    if (empty($patientName) || empty($patientPhone) || empty($selectedServiceId) || empty($selectedDate) || empty($selectedSlotId)) {
        $errorMessage = $translation[$language]['create_error'];
    } else {
        // Valider le créneau horaire sélectionné
        $slotDetailQuery = $databaseConnection->prepare("SELECT day_of_week, max_patients FROM time_slots WHERE id = :id");
        $slotDetailQuery->execute(['id' => $selectedSlotId]);
        $slotDetailsRow = $slotDetailQuery->fetch(PDO::FETCH_ASSOC);

        if (!$slotDetailsRow) {
            $errorMessage = ($language === 'ar') ? "الوقت المحدد غير صالح." : "Le créneau horaire sélectionné est invalide.";
        } else {
            // S'assurer que le jour de la semaine correspond au créneau
            $selectedDayOfWeek = date('l', strtotime($selectedDate));
            if (strcasecmp($selectedDayOfWeek, $slotDetailsRow['day_of_week']) !== 0) {
                $errorMessage = ($language === 'ar')
                    ? "تاريخ الموعد لا يتطابق مع اليوم المحدد لهذا التوقيت."
                    : "La date choisie ne correspond pas au jour de la semaine de ce créneau.";
            } else {
                try {
                    // Vérifier la capacité maximale du créneau
                    $bookingCheckQuery = $databaseConnection->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = :date AND time_slot_id = :slot_id AND status != 'canceled'");
                    $bookingCheckQuery->execute(['date' => $selectedDate, 'slot_id' => $selectedSlotId]);
                    $currentBookingCount = $bookingCheckQuery->fetchColumn();

                    $maximumCapacity = $slotDetailsRow['max_patients'];

                    if ($currentBookingCount >= $maximumCapacity) {
                        $errorMessage = $translation[$language]['slot_full'];
                    } else {
                        // Générer un numéro de référence unique et sécurisé
                        $referenceNumber = 'APT-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

                        // Générer un jeton d'accès public
                        $publicAccessToken = bin2hex(random_bytes(32));

                        $serviceName = '';
                        foreach ($allServices as $serviceItem) {
                            if ($serviceItem['id'] == $selectedServiceId) {
                                $serviceName = $serviceItem['name'];
                                break;
                            }
                        }

                        // Insérer le nouveau rendez-vous confirmé
                        $insertQuery = $databaseConnection->prepare("INSERT INTO appointments (name, cni, email, phone, service_type, appointment_date, service_id, time_slot_id, message, reference_number, public_token, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', NOW())");
                        $insertQuery->execute([$patientName, $patientCni, $patientEmail, $patientPhone, $serviceName, $selectedDate, $selectedServiceId, $selectedSlotId, $patientMessage, $referenceNumber, $publicAccessToken]);
                        $newAppointmentId = $databaseConnection->lastInsertId();

                        // Rediriger vers le tableau de bord
                        header('Location: index.php');
                        exit;
                    }
                } catch (Exception $exception) {
                    // Gérer les erreurs
                    error_log("Error creating manual appointment: " . $exception->getMessage());
                    $errorMessage = $translation[$language]['create_error'];
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $language; ?>" dir="<?php echo $textDirection; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($translation[$language]['create_title']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Cairo:wght@400;500;600;700;800&family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="form-wrap">
        <h1><?php echo htmlspecialchars($translation[$language]['create_heading']); ?></h1>

        <?php if ($errorMessage): ?>
            <div class="error-msg"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="name"><?php echo htmlspecialchars($translation[$language]['create_name']); ?></label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="cni"><?php echo htmlspecialchars($translation[$language]['create_cni']); ?></label>
                <input type="text" id="cni" name="cni">
            </div>
            <div class="form-group">
                <label for="email"><?php echo htmlspecialchars($translation[$language]['create_email']); ?></label>
                <input type="email" id="email" name="email">
            </div>
            <div class="form-group">
                <label for="phone"><?php echo htmlspecialchars($translation[$language]['create_phone']); ?></label>
                <input type="tel" id="phone" name="phone" required>
            </div>
            <div class="form-group">
                <label for="service_id"><?php echo htmlspecialchars($translation[$language]['create_service']); ?></label>
                <select id="service_id" name="service_id" required>
                    <option value="">--</option>
                    <?php foreach ($allServices as $serviceItem): ?>
                        <?php 
                            $srvName = $serviceItem['name'];
                            $tKey = 'srv_' . $srvName;
                            $displayName = isset($translation[$language][$tKey]) ? $translation[$language][$tKey] : $srvName;
                        ?>
                        <option value="<?php echo $serviceItem['id']; ?>"><?php echo htmlspecialchars($displayName); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="appointment_date"><?php echo htmlspecialchars($translation[$language]['create_date']); ?></label>
                <input type="date" id="appointment_date" name="appointment_date" required>
            </div>
            <div class="form-group">
                <label for="time_slot_id"><?php echo htmlspecialchars($translation[$language]['create_time_slot']); ?></label>
                <select id="time_slot_id" name="time_slot_id" required>
                    <option value=""><?php echo ($language === 'ar') ? "الرجاء اختيار التاريخ أولاً" : "Veuillez d'abord choisir une date"; ?></option>
                </select>
            </div>
            <div class="form-group">
                <label for="message"><?php echo htmlspecialchars($translation[$language]['create_message']); ?></label>
                <textarea id="message" name="message" rows="3"></textarea>
            </div>
            <button type="submit" class="btn-submit"><?php echo htmlspecialchars($translation[$language]['create_submit']); ?></button>
        </form>

        <a href="index.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> <?php echo htmlspecialchars($translation[$language]['create_back']); ?></a>
    </div>

    <script src="../assets/js/admin.js"></script>
</body>
</html>
