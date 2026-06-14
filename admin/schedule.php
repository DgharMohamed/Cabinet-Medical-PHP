<?php

// Gestion de l'emploi du temps (créneaux horaires et jours de congés exceptionnels)

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

// Configurer la langue
$language = $_COOKIE['lang'] ?? 'fr';
if ($language !== 'ar' && $language !== 'fr') {
    $language = 'fr';
}

$textDirection = ($language === 'ar') ? 'rtl' : 'ltr';

// Charger les traductions
$allTranslations = require __DIR__ . '/../lang/translations.php';
$translation = $allTranslations;

// Traduire les jours de la semaine
$translatedDayNames = [
    'Monday' => $translation[$language]['monday'],
    'Tuesday' => $translation[$language]['tuesday'],
    'Wednesday' => $translation[$language]['wednesday'],
    'Thursday' => $translation[$language]['thursday'],
    'Friday' => $translation[$language]['friday'],
    'Saturday' => $translation[$language]['saturday'],
    'Sunday' => $translation[$language]['sunday']
];

// Gérer l'ajout d'un nouveau créneau horaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {

    $selectedDay = $_POST['day_of_week'] ?? '';
    $selectedTimeSlot = $_POST['time_slot'] ?? '';
    $maximumPatients = intval($_POST['max_patients'] ?? 5);

    if ($selectedDay && $selectedTimeSlot && $maximumPatients > 0) {

        $timeParts = explode('-', $selectedTimeSlot);
        if (count($timeParts) == 2) {
            $startTime = trim($timeParts[0]);
            $endTime = trim($timeParts[1]);

            // Vérifier si le créneau existe déjà pour ce jour
            $duplicateCheckQuery = $databaseConnection->prepare("SELECT COUNT(*) FROM time_slots WHERE day_of_week = ? AND start_time = ? AND end_time = ?");
            $duplicateCheckQuery->execute([$selectedDay, $startTime, $endTime]);
            $slotAlreadyExists = intval($duplicateCheckQuery->fetchColumn());

            if ($slotAlreadyExists == 0) {
                // Insérer le nouveau créneau dans la base de données
                $insertQuery = $databaseConnection->prepare("INSERT INTO time_slots (day_of_week, start_time, end_time, max_patients) VALUES (?, ?, ?, ?)");
                $insertQuery->execute([$selectedDay, $startTime, $endTime, $maximumPatients]);
            } else {
                $_SESSION['schedule_error'] = [
                    'ar' => 'هذه الحصة الزمنية موجودة بالفعل لهذا اليوم.',
                    'fr' => 'Ce créneau horaire existe déjà pour ce jour.'
                ];
            }
        }
    }

    header('Location: schedule.php');
    exit;
}

// Gérer la suppression d'un créneau horaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $slotIdToDelete = intval($_POST['delete_id'] ?? 0);

    if ($slotIdToDelete > 0) {
        $deleteQuery = $databaseConnection->prepare("DELETE FROM time_slots WHERE id = ?");
        $deleteQuery->execute([$slotIdToDelete]);
    }

    header('Location: schedule.php');
    exit;
}

// Récupérer tous les créneaux horaires enregistrés
$allTimeSlotsQuery = $databaseConnection->query("SELECT * FROM time_slots ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), start_time");
$allTimeSlots = $allTimeSlotsQuery->fetchAll(PDO::FETCH_ASSOC);

// Gérer l'ajout d'une exception de l'emploi du temps (jour de congé)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_exception') {
    $exceptionDate = $_POST['exception_date'] ?? '';
    $exceptionReason = $_POST['reason'] ?? '';
    $exceptionSlotId = $_POST['time_slot_id'] ?? '';
    $exceptionSlotId = ($exceptionSlotId === '') ? null : intval($exceptionSlotId);

    if ($exceptionDate) {
        // Vérifier s'il y a des rendez-vous existants pour cette date
        if ($exceptionSlotId !== null) {
            $appointmentCheckQuery = $databaseConnection->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = ? AND time_slot_id = ? AND status != 'canceled'");
            $appointmentCheckQuery->execute([$exceptionDate, $exceptionSlotId]);
        } else {
            $appointmentCheckQuery = $databaseConnection->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = ? AND status != 'canceled'");
            $appointmentCheckQuery->execute([$exceptionDate]);
        }
        $existingAppointmentCount = intval($appointmentCheckQuery->fetchColumn());

        if ($existingAppointmentCount > 0) {
            $_SESSION['schedule_warning'] = [
                'date' => $exceptionDate,
                'count' => $existingAppointmentCount
            ];
        }

        // Vérifier si cette exception existe déjà
        $duplicateExceptionQuery = $databaseConnection->prepare("SELECT COUNT(*) FROM schedule_exceptions WHERE exception_date = ? AND (time_slot_id = ? OR (time_slot_id IS NULL AND ? IS NULL))");
        $duplicateExceptionQuery->execute([$exceptionDate, $exceptionSlotId, $exceptionSlotId]);
        $duplicateExceptionCount = intval($duplicateExceptionQuery->fetchColumn());

        if ($duplicateExceptionCount == 0) {
            // Insérer la nouvelle exception dans la base de données
            $insertExceptionQuery = $databaseConnection->prepare("INSERT INTO schedule_exceptions (exception_date, time_slot_id, reason) VALUES (?, ?, ?)");
            $insertExceptionQuery->execute([$exceptionDate, $exceptionSlotId, $exceptionReason]);
        }
    }

    header('Location: schedule.php');
    exit;
}

// Gérer la suppression d'une exception (supprimer un jour de congé)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_exception') {
    $exceptionIdToDelete = intval($_POST['delete_id'] ?? 0);

    if ($exceptionIdToDelete > 0) {
        $deleteExceptionQuery = $databaseConnection->prepare("DELETE FROM schedule_exceptions WHERE id = ?");
        $deleteExceptionQuery->execute([$exceptionIdToDelete]);
    }

    header('Location: schedule.php');
    exit;
}

// Récupérer toutes les exceptions programmées
$exceptionsQuery = $databaseConnection->query("SELECT se.*, ts.start_time, ts.end_time, ts.day_of_week FROM schedule_exceptions se LEFT JOIN time_slots ts ON se.time_slot_id = ts.id ORDER BY se.exception_date ASC");
$allExceptions = $exceptionsQuery->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="<?php echo $language; ?>" dir="<?php echo $textDirection; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($translation[$language]['schedule_title']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Cairo:wght@400;500;600;700;800&family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="header">
        <div class="header-brand">
            <i class="fa-solid fa-calendar-alt"></i>
            <h1><?php echo htmlspecialchars($translation[$language]['schedule_management']); ?></h1>
        </div>
        <div class="header-actions">
            <a href="index.php" class="btn-nav">
                <i class="fa-solid fa-arrow-<?php echo $textDirection === 'rtl' ? 'right' : 'left'; ?>"></i>
                <?php echo htmlspecialchars($translation[$language]['dashboard']); ?>
            </a>
            <a href="logout.php" class="logout-btn"><?php echo htmlspecialchars($translation[$language]['logout']); ?></a>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_SESSION['schedule_error'])): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i>
                <div>
                    <?php echo htmlspecialchars($_SESSION['schedule_error'][$language]); ?>
                </div>
            </div>
            <?php unset($_SESSION['schedule_error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['schedule_warning'])): ?>
            <div class="alert alert-warning">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div>
                    <?php
                    $warningData = $_SESSION['schedule_warning'];
                    if ($language === 'ar') {
                        echo "تنبيه: لقد قمت بإضافة عطلة في تاريخ <strong>" . htmlspecialchars($warningData['date']) . "</strong>، ولكن هناك <strong>" . $warningData['count'] . "</strong> موعد محجوز في هذا اليوم. يرجى مراجعة المواعيد.";
                    } else {
                        echo "Attention : Vous avez ajouté un jour de congé le <strong>" . htmlspecialchars($warningData['date']) . "</strong> alors qu'il y a déjà <strong>" . $warningData['count'] . "</strong> rendez-vous réservé(s) ce jour-là. Veuillez vérifier vos rendez-vous.";
                    }
                    ?>
                </div>
            </div>
            <?php unset($_SESSION['schedule_warning']); ?>
        <?php endif; ?>

        <div class="schedule-grid">
            <div class="card" style="padding: 20px;">
                <h2 style="font-size:18px;margin-bottom:20px;color:#1B4D3E;">
                    <i class="fa-solid fa-plus-circle"></i> <?php echo htmlspecialchars($translation[$language]['add_slot']); ?>
                </h2>
                <form method="post" action="schedule.php">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label><?php echo htmlspecialchars($translation[$language]['day']); ?></label>
                        <select name="day_of_week" id="day_of_week" required>
                            <option value="Monday"><?php echo htmlspecialchars($translation[$language]['monday']); ?></option>
                            <option value="Tuesday"><?php echo htmlspecialchars($translation[$language]['tuesday']); ?></option>
                            <option value="Wednesday"><?php echo htmlspecialchars($translation[$language]['wednesday']); ?></option>
                            <option value="Thursday"><?php echo htmlspecialchars($translation[$language]['thursday']); ?></option>
                            <option value="Friday"><?php echo htmlspecialchars($translation[$language]['friday']); ?></option>
                            <option value="Saturday"><?php echo htmlspecialchars($translation[$language]['saturday']); ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo htmlspecialchars($translation[$language]['time_slot']); ?></label>
                        <select name="time_slot" id="time_slot" required>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo htmlspecialchars($translation[$language]['max_patients']); ?></label>
                        <input type="number" name="max_patients" min="1" value="5" required>
                    </div>
                    <button type="submit" class="btn-submit"><?php echo htmlspecialchars($translation[$language]['add_slot']); ?></button>
                </form>
            </div>

            <div class="card" style="padding: 20px;">
                <div class="schedule-tabs" style="display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px;">
                    <?php
                    $workingDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                    foreach ($workingDays as $workingDay):
                    ?>
                        <button type="button" class="tab-btn" data-day="<?php echo $workingDay; ?>" onclick="switchDayTab('<?php echo $workingDay; ?>')">
                            <i class="fa-regular fa-calendar-days"></i>
                            <?php echo htmlspecialchars($translatedDayNames[$workingDay]); ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th><?php echo htmlspecialchars($translation[$language]['day']); ?></th>
                                <th><?php echo htmlspecialchars($translation[$language]['start_time']); ?></th>
                                <th><?php echo htmlspecialchars($translation[$language]['end_time']); ?></th>
                                <th><?php echo htmlspecialchars($translation[$language]['max_patients']); ?></th>
                                <th style="text-align:center;"><?php echo htmlspecialchars($translation[$language]['actions']); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr id="empty-day-row" style="display: none;">
                                <td colspan="5" style="text-align:center;color:#6b806b;padding:25px;">
                                    <i class="fa-solid fa-calendar-xmark" style="font-size:1.5rem;margin-bottom:10px;display:block;color:#cbd5e1;"></i>
                                    <?php echo htmlspecialchars($translation[$language]['empty']); ?>
                                </td>
                            </tr>
                            <?php foreach ($allTimeSlots as $timeSlot): ?>
                                <tr class="table-row slot-row" data-day-of-week="<?php echo $timeSlot['day_of_week']; ?>">
                                    <td><strong><?php echo htmlspecialchars($translatedDayNames[$timeSlot['day_of_week']] ?? $timeSlot['day_of_week']); ?></strong></td>
                                    <td><span class="badge badge-date"><i class="fa-regular fa-clock"></i> <?php echo date('H:i', strtotime($timeSlot['start_time'])); ?></span></td>
                                    <td><span class="badge badge-date"><i class="fa-regular fa-clock"></i> <?php echo date('H:i', strtotime($timeSlot['end_time'])); ?></span></td>
                                     <td>
                                         <select id="max-patients-<?php echo $timeSlot['id']; ?>"
                                                 onchange="updateMaxPatients(<?php echo $timeSlot['id']; ?>, this.value)"
                                                 style="background: rgba(22, 163, 74, 0.08); border: 1px solid rgba(22, 163, 74, 0.15); color: #1B4D3E; font-weight: 600; padding: 4px 8px; border-radius: 6px; cursor: pointer; outline: none; font-size: 13px; text-align: center; width: 65px;">
                                             <?php for ($patientIndex = 1; $patientIndex <= 15; $patientIndex++): ?>
                                                 <option value="<?php echo $patientIndex; ?>" <?php echo ($patientIndex === intval($timeSlot['max_patients'])) ? 'selected' : ''; ?>>
                                                     <?php echo $patientIndex; ?>
                                                 </option>
                                             <?php endfor; ?>
                                         </select>
                                     </td>
                                    <td style="text-align:center;">
                                        <button type="button" class="btn-delete" onclick="confirmDelete(<?php echo $timeSlot['id']; ?>)" style="background:#dc2626;color:white;border:none;padding:6px 12px;border-radius:6px;font-size:12px;cursor:pointer;">
                                            <i class="fa-solid fa-trash-can"></i> <?php echo htmlspecialchars($translation[$language]['delete']); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="schedule-grid" style="margin-top: 30px;">
            <div class="card" style="padding: 20px;">
                <h2 style="font-size:18px;margin-bottom:20px;color:#1B4D3E;">
                    <i class="fa-solid fa-calendar-xmark"></i> <?php echo htmlspecialchars($translation[$language]['add_exception']); ?>
                </h2>
                <form method="post" action="schedule.php">
                    <input type="hidden" name="action" value="add_exception">
                    <div class="form-group">
                        <label><?php echo htmlspecialchars($translation[$language]['date']); ?></label>
                        <input type="date" name="exception_date" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo ($language === 'ar') ? "الحصة الزمنية (اختياري)" : "Créneau (Optionnel)"; ?></label>
                        <select name="time_slot_id">
                            <option value=""><?php echo ($language === 'ar') ? "اليوم بأكمله" : "Toute la journée"; ?></option>
                            <?php foreach ($allTimeSlots as $timeSlotItem): ?>
                                <option value="<?php echo $timeSlotItem['id']; ?>">
                                    <?php
                                    $translatedDay = $translation[$language][strtolower($timeSlotItem['day_of_week'])] ?? $timeSlotItem['day_of_week'];
                                    echo htmlspecialchars($translatedDay . " (" . substr($timeSlotItem['start_time'], 0, 5) . " - " . substr($timeSlotItem['end_time'], 0, 5) . ")");
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo htmlspecialchars($translation[$language]['reason']); ?></label>
                        <input type="text" name="reason" placeholder="...">
                    </div>
                    <button type="submit" class="btn-submit" style="background:#b91c1c;"><?php echo htmlspecialchars($translation[$language]['add_exception']); ?></button>
                </form>
            </div>

            <div class="card" style="padding: 20px;">
                <h2 style="font-size:18px;margin-bottom:20px;color:#1B4D3E;">
                    <i class="fa-solid fa-list"></i> <?php echo htmlspecialchars($translation[$language]['exceptions_title']); ?>
                </h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th><?php echo htmlspecialchars($translation[$language]['date']); ?></th>
                                <th><?php echo ($language === 'ar') ? "الحصة" : "Créneau"; ?></th>
                                <th><?php echo htmlspecialchars($translation[$language]['reason']); ?></th>
                                <th style="text-align:center;"><?php echo htmlspecialchars($translation[$language]['actions']); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allExceptions)): ?>
                                <tr>
                                    <td colspan="4" style="text-align:center;color:#6b806b;">
                                        <?php echo htmlspecialchars($translation[$language]['no_exceptions']); ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($allExceptions as $exceptionRecord): ?>
                                    <tr class="table-row">
                                        <td><strong><?php echo date('Y-m-d', strtotime($exceptionRecord['exception_date'])); ?></strong></td>
                                        <td>
                                            <?php if ($exceptionRecord['time_slot_id']): ?>
                                                <span class="badge" style="background:#e0f2fe;color:#0369a1;">
                                                    <?php echo substr($exceptionRecord['start_time'], 0, 5) . ' - ' . substr($exceptionRecord['end_time'], 0, 5); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge" style="background:#f3f4f6;color:#374151;">
                                                    <?php echo ($language === 'ar') ? "اليوم بأكمله" : "Toute la journée"; ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge" style="background:#fee2e2;color:#b91c1c;"><?php echo htmlspecialchars($exceptionRecord['reason'] ? $exceptionRecord['reason'] : '...'); ?></span></td>
                                        <td style="text-align:center;">
                                            <button type="button" class="btn-delete" onclick="confirmDeleteException(<?php echo $exceptionRecord['id']; ?>)" style="background:#dc2626;color:white;border:none;padding:6px 12px;border-radius:6px;font-size:12px;cursor:pointer;">
                                                <i class="fa-solid fa-trash-can"></i> <?php echo htmlspecialchars($translation[$language]['delete']); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <form id="deleteForm" method="post" action="schedule.php" style="display:none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="delete_id" id="deleteIdInput" value="">
    </form>

    <form id="deleteExcForm" method="post" action="schedule.php" style="display:none;">
        <input type="hidden" name="action" value="delete_exception">
        <input type="hidden" name="delete_id" id="deleteExcIdInput" value="">
    </form>

    <script src="../assets/js/admin.js"></script>
</body>
</html>
