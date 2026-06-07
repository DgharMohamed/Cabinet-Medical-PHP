<?php

// Traitement du suivi d'un rendez-vous par le patient via référence et CNI

session_start();
require_once 'config/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$referenceNumber = trim($_POST['reference_number'] ?? '');
$patientCni = trim($_POST['cni'] ?? '');

// Vérifier que les informations requises sont fournies
if ($referenceNumber === '' || $patientCni === '') {
    header('Location: index.php?track_status=missing#track-appointment');
    exit;
}

try {
    $database = new DatabaseConnection();
    $databaseConnection = $database->getConnection();

    // Rechercher le rendez-vous correspondant (insensible à la casse et sans espaces pour la CNI)
    $appointmentQuery = $databaseConnection->prepare(
        "SELECT id, status, public_token, reference_number
         FROM appointments
         WHERE UPPER(reference_number) = UPPER(:reference_number)
           AND REPLACE(UPPER(cni), ' ', '') = REPLACE(UPPER(:cni), ' ', '')
         LIMIT 1"
    );
    
    $appointmentQuery->execute([
        ':reference_number' => $referenceNumber,
        ':cni' => $patientCni,
    ]);

    $appointmentRecord = $appointmentQuery->fetch(PDO::FETCH_ASSOC);

    if (!$appointmentRecord) {
        header('Location: index.php?track_status=not_found#track-appointment');
        exit;
    }

    $appointmentId = (int) $appointmentRecord['id'];
    $appointmentStatus = $appointmentRecord['status'] ?? 'pending';
    $publicAccessToken = $appointmentRecord['public_token'] ?? '';

    // Générer un jeton public s'il n'existe pas encore pour ce rendez-vous
    if ($publicAccessToken === '') {
        $publicAccessToken = bin2hex(random_bytes(32));
        $tokenUpdateQuery = $databaseConnection->prepare("UPDATE appointments SET public_token = ? WHERE id = ?");
        $tokenUpdateQuery->execute([$publicAccessToken, $appointmentId]);
    }

    // Rediriger le patient selon le statut actuel de son rendez-vous
    if ($appointmentStatus === 'confirmed') {
        header('Location: confirmation.php?id=' . $appointmentId . '&token=' . urlencode($publicAccessToken));
        exit;
    }

    if ($appointmentStatus === 'pending') {
        header('Location: pending-confirmation.php?id=' . $appointmentId . '&token=' . urlencode($publicAccessToken));
        exit;
    }

    // Si le rendez-vous est annulé ou dans un autre état
    header('Location: index.php?track_status=canceled#track-appointment');
    exit;
    
} catch (Throwable $exception) {
    error_log('Track appointment error: ' . $exception->getMessage());
    header('Location: index.php?track_status=not_found#track-appointment');
    exit;
}
