<?php

// Mettre à jour le statut d'un rendez-vous et envoyer un e-mail de notification

session_start();
if (!isset($_SESSION['logged']) || $_SESSION['logged'] !== true) {
    header("Location: login.php");
    exit;
}

require_once '../config/Database.php';
require_once '../models/Appointment.php';

// Fonction pour simuler l'envoi d'e-mails et les enregistrer dans un fichier de journalisation local
function simulateEmailSend($recipientEmail, $emailSubject, $emailBody) {
    $logDirectory = __DIR__ . '/../logs/';
    if (!is_dir($logDirectory)) {
        mkdir($logDirectory, 0755, true);
    }
    
    $logFilePath = $logDirectory . 'emails_log.html';
    
    $logEntry = "<div style='border:1px solid #ccc; margin:20px; padding:20px; font-family:sans-serif;'>";
    $logEntry .= "<h3 style='margin-top:0; color:#333;'>" . htmlspecialchars($emailSubject) . "</h3>";
    $logEntry .= "<p><strong>À:</strong> " . htmlspecialchars($recipientEmail) . "</p>";
    $logEntry .= "<p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>";
    $logEntry .= "<hr>";
    $logEntry .= "<div>" . $emailBody . "</div>";
    $logEntry .= "</div>\n";
    
    file_put_contents($logFilePath, $logEntry, FILE_APPEND);
    return true;
}

// Fonction pour envoyer un e-mail de confirmation au patient
function sendConfirmationEmail($patientName, $patientEmail, $appointmentDate, $appointmentTime, $appointmentReference, $publicAccessToken, $appointmentId) {
    $emailSubject = "Confirmation de votre rendez-vous - Dr. Dghar Mohamed";
    $trackingLink = "http://" . $_SERVER['HTTP_HOST'] . str_replace('/admin/update-status.php', '', $_SERVER['PHP_SELF']) . "/confirmation.php?id=" . $appointmentId . "&token=" . $publicAccessToken;
    
    $emailBody = "
    <h2>Bonjour " . htmlspecialchars($patientName) . ",</h2>
    <p>Votre rendez-vous a été <strong>confirmé</strong> avec succès.</p>
    <ul>
        <li><strong>Date :</strong> " . htmlspecialchars($appointmentDate) . "</li>
        <li><strong>Heure :</strong> " . htmlspecialchars($appointmentTime) . "</li>
        <li><strong>Référence :</strong> " . htmlspecialchars($appointmentReference) . "</li>
    </ul>
    <p>Vous pouvez consulter votre billet de rendez-vous en cliquant sur le lien ci-dessous :</p>
    <p><a href='" . htmlspecialchars($trackingLink) . "' style='padding: 10px 15px; background-color: #1B4D3E; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>Voir mon rendez-vous</a></p>
    <br>
    <p>Merci de vous présenter 10 minutes avant l'heure prévue.</p>
    <p>Cordialement,<br>Le cabinet du Dr. Dghar Mohamed</p>
    ";

    return simulateEmailSend($patientEmail, $emailSubject, $emailBody);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointmentId = intval($_POST['id'] ?? 0);
    
    if ($appointmentId <= 0) {
        $_SESSION['error'] = "ID de rendez-vous invalide.";
        header("Location: index.php");
        exit;
    }

    $database = new DatabaseConnection();
    $databaseConnection = $database->getConnection();
    
    // Supprimer le rendez-vous si demandé
    if (isset($_POST['delete']) && $_POST['delete'] == '1') {
        $appointmentRecord = new Appointment($databaseConnection);
        $appointmentRecord->id = $appointmentId;
        
        if ($appointmentRecord->deleteAppointment()) {
            $_SESSION['success'] = "Le rendez-vous a été supprimé définitivement.";
        } else {
            $_SESSION['error'] = "Erreur lors de la suppression.";
        }
        
        header("Location: index.php");
        exit;
    }
    
    // Mettre à jour le statut du rendez-vous
    $newStatus = $_POST['status'] ?? '';
    $allowedStatuses = ['pending', 'confirmed', 'canceled'];
    
    if (in_array($newStatus, $allowedStatuses)) {
        
        // Récupérer les détails du rendez-vous pour l'envoi de l'e-mail
        $detailsQuery = $databaseConnection->prepare("
            SELECT a.name, a.email, a.appointment_date, a.reference_number, a.public_token, a.id, ts.start_time 
            FROM appointments a 
            LEFT JOIN time_slots ts ON a.time_slot_id = ts.id 
            WHERE a.id = ?
        ");
        $detailsQuery->execute([$appointmentId]);
        $appointmentDetails = $detailsQuery->fetch(PDO::FETCH_ASSOC);

        $appointmentRecord = new Appointment($databaseConnection);
        $appointmentRecord->id = $appointmentId;
        $appointmentRecord->status = $newStatus;
        
        if ($appointmentRecord->updateAppointmentStatus()) {
            $_SESSION['success'] = "Le statut a été mis à jour avec succès.";
            
            // Envoyer un e-mail au patient si le rendez-vous est confirmé
            if ($newStatus === 'confirmed' && $appointmentDetails && !empty($appointmentDetails['email'])) {
                $formattedDate = date('d/m/Y', strtotime($appointmentDetails['appointment_date']));
                $formattedTime = $appointmentDetails['start_time'] ? substr($appointmentDetails['start_time'], 0, 5) : 'Non spécifiée';
                
                sendConfirmationEmail(
                    $appointmentDetails['name'],
                    $appointmentDetails['email'],
                    $formattedDate,
                    $formattedTime,
                    $appointmentDetails['reference_number'],
                    $appointmentDetails['public_token'],
                    $appointmentDetails['id']
                );
                
                $_SESSION['success'] .= " Un e-mail de confirmation a été envoyé au patient (simulé).";
            }
            
        } else {
            $_SESSION['error'] = "Erreur lors de la mise à jour du statut.";
        }
    } else {
        $_SESSION['error'] = "Statut invalide.";
    }
    
    header("Location: index.php");
    exit;
} else {
    header("Location: index.php");
    exit;
}
