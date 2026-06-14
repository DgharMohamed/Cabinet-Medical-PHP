<?php

// Traitement de la soumission d'un rendez-vous par le patient

session_start();

require_once __DIR__ . '/../config/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

// Nettoyer et extraire les données du formulaire
$patientName = trim($_POST['name'] ?? '');
$patientEmail = trim($_POST['email'] ?? '');
$patientPhone = trim($_POST['phone'] ?? '');
$patientCni = trim($_POST['cni'] ?? '');
$serviceId = intval($_POST['service_id'] ?? 0);
$appointmentDate = trim($_POST['appointment_date'] ?? '');
$timeSlotId = intval($_POST['time_slot_id'] ?? 0);
$patientMessage = trim($_POST['message'] ?? '');

$language = $_COOKIE['lang'] ?? 'fr';
if ($language !== 'ar' && $language !== 'fr') {
    $language = 'fr';
}

$validationErrors = [];

// Validation côté serveur des champs obligatoires
if (empty($patientName)) {
    $validationErrors[] = ($language === 'ar') ? "الاسم الكامل مطلوب." : "Le nom complet est obligatoire.";
}

if (empty($patientEmail)) {
    $validationErrors[] = ($language === 'ar') ? "البريد الإلكتروني مطلوب." : "L'adresse e-mail est obligatoire.";
} elseif (!filter_var($patientEmail, FILTER_VALIDATE_EMAIL)) {
    $validationErrors[] = ($language === 'ar') ? "البريد الإلكتروني غير صالح." : "L'adresse e-mail n'est pas valide.";
}

if (empty($patientPhone)) {
    $validationErrors[] = ($language === 'ar') ? "الهاتف مطلوب." : "Le numéro de téléphone est obligatoire.";
}

if (empty($patientCni)) {
    $validationErrors[] = ($language === 'ar') ? "رقم البطاقة الوطنية مطلوب." : "Le numéro de CNI est obligatoire.";
}

if (empty($serviceId)) {
    $validationErrors[] = ($language === 'ar') ? "نوع الخدمة مطلوب." : "Le type de service est obligatoire.";
}

if (empty($appointmentDate)) {
    $validationErrors[] = ($language === 'ar') ? "التاريخ مطلوب." : "La date de rendez-vous est obligatoire.";
}

if (empty($timeSlotId)) {
    $validationErrors[] = ($language === 'ar') ? "الوقت مطلوب." : "Le créneau horaire est obligatoire.";
}

// Vérifier que les champs ne dépassent pas la longueur maximale autorisée
if (strlen($patientName) > 100 || strlen($patientEmail) > 100 || strlen($patientPhone) > 50 || strlen($patientCni) > 20) {
    $validationErrors[] = ($language === 'ar') ? "يتجاوز حقل واحد أو أكثر الحد الأقصى للطول المسموح به." : "Un ou plusieurs champs dépassent la longueur maximale autorisée.";
}

if (strlen($patientMessage) > 500) {
    $validationErrors[] = ($language === 'ar') ? "يجب ألا تتجاوز الرسالة 500 حرف." : "Le message ne doit pas dépasser 500 caractères.";
}

// Traitement sécurisé du document médical téléversé (optionnel)
$uploadedDocumentPath = null;
if (isset($_FILES['medical_document']) && $_FILES['medical_document']['error'] === UPLOAD_ERR_OK) {
    $temporaryFilePath = $_FILES['medical_document']['tmp_name'];
    $originalFileName = $_FILES['medical_document']['name'];
    $fileSizeInBytes = $_FILES['medical_document']['size'];

    // Autoriser uniquement les fichiers PDF, JPG, JPEG et PNG de moins de 5 Mo
    $allowedFileExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
    $uploadedFileExtension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));

    if (in_array($uploadedFileExtension, $allowedFileExtensions) && $fileSizeInBytes < 5000000) {
        $uploadDirectory = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0755, true);
        }
        $uniqueFileName = uniqid() . '.' . $uploadedFileExtension;
        $destinationFilePath = $uploadDirectory . $uniqueFileName;

        if (move_uploaded_file($temporaryFilePath, $destinationFilePath)) {
            $uploadedDocumentPath = 'uploads/' . $uniqueFileName;
        }
    } else {
        $validationErrors[] = ($language === 'ar') ? "الملف غير صالح. يجب أن يكون PDF أو JPG أو PNG وأقل من 5 ميغابايت." : "Fichier invalide. Il doit être au format PDF, JPG ou PNG et faire moins de 5 Mo.";
    }
}

if (count($validationErrors) > 0) {
    $_SESSION['form_errors'] = $validationErrors;
    header('Location: ../index.php?status=error#appointment');
    exit;
}

$database = new DatabaseConnection();
$databaseConnection = $database->getConnection();

// Vérifier que le service sélectionné existe dans la base de données
$serviceQuery = $databaseConnection->prepare("SELECT name FROM services WHERE id = :id");
$serviceQuery->execute(['id' => $serviceId]);
$serviceTypeName = $serviceQuery->fetchColumn();

if (!$serviceTypeName) {
    $_SESSION['form_errors'][] = ($language === 'ar') ? "الخدمة المطلوبة غير صالحة." : "Le service sélectionné est invalide.";
    header("Location: ../index.php?status=error#appointment");
    exit;
}

// Vérifier que le créneau horaire existe et récupérer ses détails
$slotQuery = $databaseConnection->prepare("SELECT day_of_week, max_patients FROM time_slots WHERE id = :slot_id");
$slotQuery->execute(['slot_id' => $timeSlotId]);
$slotDetails = $slotQuery->fetch(PDO::FETCH_ASSOC);

if (!$slotDetails) {
    $_SESSION['form_errors'][] = ($language === 'ar') ? "الوقت المحدد غير صالح." : "Le créneau horaire sélectionné est invalide.";
    header("Location: ../index.php?status=error#appointment");
    exit;
}

// Vérifier que le jour de la date choisie correspond au jour du créneau
$appointmentDayOfWeek = date('l', strtotime($appointmentDate));
if (strcasecmp($appointmentDayOfWeek, $slotDetails['day_of_week']) !== 0) {
    $_SESSION['form_errors'][] = ($language === 'ar') ? "تاريخ الموعد لا يتطابق مع اليوم المحدد لهذا التوقيت." : "La date choisie ne correspond pas au jour de la semaine de ce créneau.";
    header("Location: ../index.php?status=error#appointment");
    exit;
}

// Démarrer pour garantir la cohérence
try {

    // Vérifier si une exception d'emploi du temps (jour férié) bloque ce créneau
    $scheduleExceptionQuery = $databaseConnection->prepare("SELECT COUNT(*) FROM schedule_exceptions WHERE exception_date = ? AND (time_slot_id = ? OR time_slot_id IS NULL)");
    $scheduleExceptionQuery->execute([$appointmentDate, $timeSlotId]);
    $isSlotUnavailable = intval($scheduleExceptionQuery->fetchColumn());

    if ($isSlotUnavailable > 0) {
        $_SESSION['form_errors'][] = ($language === 'ar') ? "هذا الوقت غير متاح للحجز." : "Ce créneau horaire n'est pas disponible.";
        header("Location: ../index.php?status=error#appointment");
        exit;
    }

    // Vérifier la capacité du créneau pour éviter les conflits concurrents
    $bookingCountQuery = $databaseConnection->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = :date AND time_slot_id = :slot_id AND status != 'canceled'");
    $bookingCountQuery->execute(['date' => $appointmentDate, 'slot_id' => $timeSlotId]);
    $currentBookingCount = $bookingCountQuery->fetchColumn();

    $maximumCapacity = $slotDetails['max_patients'];

    if ($currentBookingCount >= $maximumCapacity) {
        $_SESSION['form_errors'][] = ($language === 'ar') ? "هذا الموعد محجوز بالكامل. الرجاء اختيار موعد آخر." : "Ce créneau est complet. Veuillez choisir un autre horaire.";
        header("Location: ../index.php?status=error#appointment");
        exit;
    }

    // Générer un numéro de référence unique et sécurisé
    $referenceNumber = 'APT-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

    // Générer un jeton sécurisé pour l'accès public du patient à son rendez-vous
    $publicAccessToken = bin2hex(random_bytes(32));

    // Insérer le rendez-vous dans la base de données
    $insertQuery = $databaseConnection->prepare("INSERT INTO appointments (name, email, phone, cni, service_type, appointment_date, service_id, time_slot_id, medical_document, reference_number, public_token, message, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $insertQuery->execute([$patientName, $patientEmail, $patientPhone, $patientCni, $serviceTypeName, $appointmentDate, $serviceId, $timeSlotId, $uploadedDocumentPath, $referenceNumber, $publicAccessToken, $patientMessage]);

    $newAppointmentId = $databaseConnection->lastInsertId();

    header('Location: ../pending-confirmation.php?id=' . $newAppointmentId . '&token=' . $publicAccessToken);
    
} catch (Exception $exception) {
    error_log("Database submission error: " . $exception->getMessage());
    $_SESSION['form_errors'][] = ($language === 'ar') ? "حدث خطأ في قاعدة البيانات. الرجاء المحاولة مرة أخرى." : "Une erreur de base de données est survenue. Veuillez réessayer.";
    header("Location: ../index.php?status=error#appointment");
}
exit;
