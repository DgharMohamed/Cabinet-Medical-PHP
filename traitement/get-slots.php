<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/Database.php';

$selectedDate = $_GET['date'] ?? '';

// Valider que la date est fournie
if (!$selectedDate) {
    echo json_encode(['error' => 'Date is required']);
    exit;
}

try {
    $database = new DatabaseConnection();
    $databaseConnection = $database->getConnection();

    $selectedDayOfWeek = date('l', strtotime($selectedDate));

    // Vérifier si des créneaux horaires existent pour ce jour de la semaine
    $slotCountQuery = $databaseConnection->prepare("SELECT COUNT(*) FROM time_slots WHERE day_of_week = ?");
    $slotCountQuery->execute([$selectedDayOfWeek]);
    $totalTimeSlots = $slotCountQuery->fetchColumn();

    // Retourner une erreur si le cabinet est fermé ce jour-là
    if ($totalTimeSlots == 0) {
        echo json_encode([
            'error' => true,
            'message' => [
                'ar' => 'العيادة مغلقة في هذا اليوم.',
                'fr' => 'Le cabinet est fermé ce jour.'
            ]
        ]);
        exit;
    }

    // Vérifier si la date correspond à un jour de congé exceptionnel
    $exceptionQuery = $databaseConnection->prepare("SELECT reason FROM schedule_exceptions WHERE exception_date = ? AND time_slot_id IS NULL");
    $exceptionQuery->execute([$selectedDate]);
    $scheduleException = $exceptionQuery->fetch(PDO::FETCH_ASSOC);

    if ($scheduleException) {
        $exceptionReason = $scheduleException['reason'] ? $scheduleException['reason'] : 'عطلة';
        echo json_encode([
            'error' => true,
            'message' => [
                'ar' => 'العيادة مغلقة هذا اليوم: ' . $exceptionReason,
                'fr' => 'Cabinet fermé ce jour: ' . $exceptionReason
            ]
        ]);
        exit;
    }

    // Récupérer les créneaux disponibles pour le jour, en excluant les exceptions et en comptant les réservations actuelles
    $slotAvailabilityQuery = "
        SELECT ts.*,
               (SELECT COUNT(*) FROM appointments a WHERE a.time_slot_id = ts.id AND a.appointment_date = :date1 AND a.status != 'canceled') AS current_bookings
        FROM time_slots ts
        WHERE ts.day_of_week = :day
          AND ts.id NOT IN (SELECT time_slot_id FROM schedule_exceptions WHERE exception_date = :date2 AND time_slot_id IS NOT NULL)
        ORDER BY ts.start_time
    ";

    $preparedStatement = $databaseConnection->prepare($slotAvailabilityQuery);
    $preparedStatement->execute([
        'date1' => $selectedDate,
        'date2' => $selectedDate,
        'day' => $selectedDayOfWeek
    ]);

    $availableSlots = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'slots' => $availableSlots,
        'day' => $selectedDayOfWeek
    ]);

} catch (Exception $exception) {
    echo json_encode(['error' => $exception->getMessage()]);
}
