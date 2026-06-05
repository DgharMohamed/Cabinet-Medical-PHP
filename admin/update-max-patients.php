<?php

// Point d'entrée AJAX pour mettre à jour le nombre maximum de patients par créneau

// Vérifier l'authentification
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['logged']) || $_SESSION['logged'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// S'assurer que la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Récupérer les paramètres envoyés par le client
$timeSlotId = intval($_POST['slot_id'] ?? 0);
$maximumPatientCount = intval($_POST['max_patients'] ?? 0);
$submittedCsrfToken = $_POST['csrf_token'] ?? '';

// Valider le jeton CSRF
if (empty($submittedCsrfToken) || $submittedCsrfToken !== ($_SESSION['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

// Vérifier la validité des paramètres
if ($timeSlotId <= 0 || $maximumPatientCount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Connexion à la base de données
require_once __DIR__ . '/../config/Database.php';

try {
    $database = new DatabaseConnection();
    $databaseConnection = $database->getConnection();

    // Mettre à jour la capacité maximale du créneau
    $updateStatement = $databaseConnection->prepare("UPDATE time_slots SET max_patients = ? WHERE id = ?");
    $updateStatement->execute([$maximumPatientCount, $timeSlotId]);

    echo json_encode(['success' => true, 'message' => 'Successfully updated']);
} catch (Exception $exception) {
    echo json_encode(['success' => false, 'message' => $exception->getMessage()]);
}
