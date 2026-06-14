<?php

// Mettre à jour le statut d'un rendez-vous

session_start();
if (!isset($_SESSION['logged']) || $_SESSION['logged'] !== true) {
    header("Location: login.php");
    exit;
}

require_once '../config/Database.php';
require_once '../models/Appointment.php';

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
        
        $appointmentRecord = new Appointment($databaseConnection);
        $appointmentRecord->id = $appointmentId;
        $appointmentRecord->status = $newStatus;
        
        if ($appointmentRecord->updateAppointmentStatus()) {
            $_SESSION['success'] = "Le statut a été mis à jour avec succès.";
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
