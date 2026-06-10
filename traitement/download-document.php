<?php

// Gérer le téléchargement sécurisé des documents médicaux

session_start();

require_once '../config/Database.php';
require_once '../models/Appointment.php';

$appointmentId = intval($_GET['id'] ?? 0);
$accessToken = $_GET['token'] ?? '';

// Vérifier que l'identifiant du rendez-vous est valide
if ($appointmentId <= 0) {
    header('HTTP/1.1 400 Bad Request');
    die("Requête invalide.");
}

// Vérifier si l'utilisateur est connecté en tant qu'administrateur
$isAdministrator = isset($_SESSION['logged']) && $_SESSION['logged'] === true;

$database = new DatabaseConnection();
$databaseConnection = $database->getConnection();

if (!$databaseConnection) {
    header('HTTP/1.1 500 Internal Server Error');
    die("Erreur de connexion.");
}

$appointmentRecord = new Appointment($databaseConnection);
$appointmentRecord->id = $appointmentId;

// Valider l'accès : l'administrateur a un accès complet, le patient doit fournir un jeton valide
if (!$isAdministrator) {
    if (empty($accessToken)) {
        header('HTTP/1.1 403 Forbidden');
        die("Accès refusé.");
    }
    $appointmentRecord->public_token = $accessToken;
}

if (!$appointmentRecord->fetchAppointmentById()) {
    header('HTTP/1.1 404 Not Found');
    die("Document introuvable.");
}

$documentFilePath = $appointmentRecord->medical_document;

if (empty($documentFilePath)) {
    header('HTTP/1.1 404 Not Found');
    die("Aucun document associé à ce rendez-vous.");
}

// Empêcher la traversée de répertoires : s'assurer que le fichier se trouve bien dans le dossier uploads
$realUploadsDirectory = realpath(__DIR__ . '/../uploads');
$fullFilePath = realpath(__DIR__ . '/../' . $documentFilePath);

if (!$fullFilePath || !$realUploadsDirectory || strpos($fullFilePath, $realUploadsDirectory) !== 0) {
    header('HTTP/1.1 403 Forbidden');
    die("Accès non autorisé.");
}

// Vérifier que le fichier existe physiquement sur le serveur
if (!file_exists($fullFilePath)) {
    header('HTTP/1.1 404 Not Found');
    die("Le fichier n'existe pas sur le serveur.");
}

// Détecter le type MIME pour déterminer si le fichier doit être affiché ou téléchargé
$fileInformation = finfo_open(FILEINFO_MIME_TYPE);
$fileMimeType = finfo_file($fileInformation, $fullFilePath);
finfo_close($fileInformation);

$contentDisposition = (in_array($fileMimeType, ['application/pdf', 'image/jpeg', 'image/png'])) ? 'inline' : 'attachment';

if (ob_get_level()) {
    ob_end_clean();
}

// Envoyer les en-têtes HTTP appropriés pour le transfert du fichier
header('Content-Description: File Transfer');
header('Content-Type: ' . $fileMimeType);
header('Content-Disposition: ' . $contentDisposition . '; filename="' . basename($fullFilePath) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($fullFilePath));

readfile($fullFilePath);
exit;
