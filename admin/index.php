<?php
// Tableau de bord administrateur (Affichage des statistiques et liste des rendez-vous)

session_start();

// Vérifier si l'utilisateur est authentifié
if (!isset($_SESSION['logged']) || $_SESSION['logged'] !== true) {
    header("Location: login.php");
    exit;
}

require_once '../config/Database.php';
require_once '../models/Appointment.php';

$database = new DatabaseConnection();
$databaseConnection = $database->getConnection();

$appointmentRecord = new Appointment($databaseConnection);

// Configurer la langue
$language = $_COOKIE['lang'] ?? 'fr';
if ($language !== 'ar' && $language !== 'fr') {
    $language = 'fr';
}
$textDirection = ($language === 'ar') ? 'rtl' : 'ltr';

// Charger les traductions
$allTranslations = require __DIR__ . '/../lang/translations.php';
$translation = $allTranslations;

// Helper variables for translations that might not exist in translation.php
$t_dashboard = ($language === 'ar') ? 'لوحة التحكم' : 'Tableau de bord';
$t_appointments = ($language === 'ar') ? 'المواعيد' : 'Rendez-vous';
$t_schedule = ($language === 'ar') ? 'الجدولة والاستثناءات' : 'Horaires & Exceptions';
$t_logout = ($language === 'ar') ? 'تسجيل الخروج' : 'Déconnexion';
$t_today = ($language === 'ar') ? 'اليوم' : "Aujourd'hui";
$t_total = ($language === 'ar') ? 'الخدمات' : 'Services';
$t_new_appointment = ($language === 'ar') ? 'موعد جديد' : 'Nouveau rendez-vous';
$t_search_placeholder = ($language === 'ar') ? 'بحث عن اسم، هاتف، بريد...' : 'Rechercher par nom, email, téléphone...';
$t_all_services = ($language === 'ar') ? 'كل الخدمات' : 'Tous les services';
$t_filter = ($language === 'ar') ? 'تصفية' : 'Filtrer';
$t_patient = ($language === 'ar') ? 'المريض' : 'PATIENT';
$t_rendezvous = ($language === 'ar') ? 'الموعد' : 'RENDEZ-VOUS';
$t_reference = ($language === 'ar') ? 'المرجع' : 'RÉFÉRENCE';
$t_message = ($language === 'ar') ? 'رسالة' : 'MESSAGE';
$t_date = ($language === 'ar') ? 'تاريخ الإنشاء' : 'DATE';
$t_status = ($language === 'ar') ? 'الحالة' : 'STATUT';
$t_actions = ($language === 'ar') ? 'الإجراءات' : 'ACTIONS';
$t_no_appointments = ($language === 'ar') ? 'لا توجد مواعيد.' : 'Aucun rendez-vous trouvé.';
$t_confirm = ($language === 'ar') ? 'تأكيد' : 'Confirmer';
$t_pending = ($language === 'ar') ? 'في الانتظار' : 'En attente';
$t_cancel = ($language === 'ar') ? 'إلغاء' : 'Annuler';
$t_delete_permanent = ($language === 'ar') ? 'حذف نهائي' : 'Supprimer définitivement';
$t_confirm_delete = ($language === 'ar') ? 'هل أنت متأكد من الحذف النهائي لهذا الموعد؟' : 'Êtes-vous sûr de vouloir supprimer définitivement ce rendez-vous ?';
$t_tab_all = ($language === 'ar') ? 'الكل' : 'Tous';

// Récupérer les statistiques du tableau de bord
$dashboardStatistics = $appointmentRecord->getDashboardStatistics();

// Gérer les filtres de recherche
$searchQuery = $_GET['search'] ?? '';
$serviceFilter = $_GET['service'] ?? '';

// Fetch all services for the dropdown
$stmtServices = $databaseConnection->query("SELECT id, name FROM services ORDER BY name ASC");
$allServices = $stmtServices->fetchAll(PDO::FETCH_ASSOC);

// Construire la requête SQL avec les filtres (Pas de pagination ici car le design groupe par service)
$sqlQuery = "SELECT a.*, s.name AS service_name, ts.start_time AS slot_start, ts.end_time AS slot_end 
             FROM appointments a 
             LEFT JOIN services s ON a.service_id = s.id 
             LEFT JOIN time_slots ts ON a.time_slot_id = ts.id 
             WHERE 1=1";
$queryParameters = [];

if ($searchQuery) {
    $sqlQuery .= " AND (a.name LIKE :search OR a.phone LIKE :search OR a.email LIKE :search OR a.cni LIKE :search)";
    $queryParameters[':search'] = "%$searchQuery%";
}
if ($serviceFilter) {
    $sqlQuery .= " AND a.service_id = :service_id";
    $queryParameters[':service_id'] = $serviceFilter;
}

$sqlQuery .= " ORDER BY s.name ASC, a.appointment_date DESC, ts.start_time DESC LIMIT 100";

$preparedStatement = $databaseConnection->prepare($sqlQuery);
$preparedStatement->execute($queryParameters);
$allAppointments = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);

function translateStatus($status, $language) {
    if ($language === 'ar') {
        $statusMap = [
            'pending' => 'في الانتظار',
            'confirmed' => 'مؤكد',
            'canceled' => 'ملغى'
        ];
    } else {
        $statusMap = [
            'pending' => 'En attente',
            'confirmed' => 'Confirmé',
            'canceled' => 'Annulé'
        ];
    }
    return $statusMap[$status] ?? $status;
}

function getStatusColorClass($status) {
    $colorMap = [
        'pending' => 'status-pending',
        'confirmed' => 'status-confirmed',
        'canceled' => 'status-canceled'
    ];
    return $colorMap[$status] ?? '';
}

// Group appointments by service name
$groupedAppointments = [];
foreach ($allAppointments as $app) {
    $service = $app['service_name'] ?? $app['service_type'];
    if (empty($service)) $service = ($language === 'ar') ? 'أخرى' : 'Autre';
    if (!isset($groupedAppointments[$service])) {
        $groupedAppointments[$service] = [];
    }
    $groupedAppointments[$service][] = $app;
}

?>
<!DOCTYPE html>
<html lang="<?php echo $language; ?>" dir="<?php echo $textDirection; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t_dashboard; ?> - <?php echo $translation[$language]['brand'] ?? 'Cabinet Médical'; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Cairo:wght@400;500;600;700;800&family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        /* Small overrides to perfectly match the image */
        body { background: #f4f6f2; }
        
        .stat-card.stat-rdv .stat-icon { background: #e8f5e9; color: #16a34a; }
        .stat-card.stat-avenir .stat-icon { background: #e0f2fe; color: #3b82f6; }
        .stat-card.stat-today .stat-icon { background: #fdf2f8; color: #db2777; }
        .stat-card.stat-services-count .stat-icon { background: #fef3c7; color: #d97706; }
        
        .btn-nav-primary {
            background: #C4A052; /* Gold/Orange color from mockup */
            color: #fff;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-nav-primary:hover { background: #AD8A3A; color: #fff; }

        .service-card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            margin-bottom: 30px;
        }
        .service-card .card-header {
            border-bottom: none;
            padding: 20px 24px;
            background: #fff;
            border-radius: 12px 12px 0 0;
        }
        .service-card table th {
            background: #1B4D3E; /* Dark green header */
            color: white;
            font-size: 11px;
            padding: 14px 24px;
            border-bottom: none;
        }
        .service-card table td {
            background: #fff;
            padding: 20px 24px;
            border-bottom: 1px solid #f1f5f1;
        }
        .service-card table tr:last-child td {
            border-bottom: none;
            border-radius: 0 0 12px 12px; /* if it's the last row */
        }
    </style>
</head>
<body>
    
    <div class="header">
        <div class="header-brand">
            <i class="fa-solid fa-heart-pulse" style="color:#C4A052; font-size: 22px;"></i>
            <h1 style="margin:0; font-size:20px;"><?php echo htmlspecialchars($translation[$language]['brand'] ?? 'Cabinet Dr. Dghar Mohamed'); ?></h1>
        </div>
        <div class="header-actions">
            <!-- Gérer les horaires -->
            <a href="schedule.php" class="btn-nav" style="background:#fff; color:#1B4D3E; border:none; padding:10px 20px; font-weight:700; border-radius:8px; display:inline-flex; align-items:center; gap:8px; text-decoration:none;">
                <i class="fa-solid fa-calendar-days"></i>
                <?php echo ($language === 'ar') ? 'إدارة المواعيد' : 'Gérer les horaires'; ?>
            </a>

            <!-- Switch Language (AR/FR) -->
            <a href="#" onclick="document.cookie='lang=<?php echo ($language==='fr')?'ar':'fr'; ?>; path=/'; window.location.reload(); return false;" class="btn-nav" style="background:transparent; color:#fff; border:1px solid rgba(255,255,255,0.25); padding:10px 16px; border-radius:8px; font-weight:700; text-decoration:none;">
                <?php echo ($language === 'fr') ? 'AR' : 'FR'; ?>
            </a>

            <!-- Change Password -->
            <a href="change-password.php" class="btn-nav" style="background:transparent; color:#fff; border:1px solid rgba(255,255,255,0.25); padding:10px 16px; border-radius:8px; text-decoration:none;">
                <i class="fa-solid fa-key"></i>
            </a>

            <!-- Déconnexion -->
            <a href="logout.php" class="btn-nav" style="background:transparent; color:#fff; border:1px solid rgba(255,255,255,0.25); padding:10px 20px; border-radius:8px; font-weight:600; text-decoration:none;">
                <?php echo $t_logout; ?>
            </a>
        </div>
    </div>

    <div class="container">
        
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success" style="background:#dcfce7;color:#166534;border:1px solid #bbf7d0;">
                <i class="fa-solid fa-circle-check"></i>
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats">
            <div class="stat-card stat-rdv" style="border:none; box-shadow:0 4px 15px rgba(0,0,0,0.03); border-radius:12px;">
                <div class="stat-info">
                    <span class="stat-value"><?php echo $dashboardStatistics['total']; ?></span>
                    <span class="stat-label"><?php echo $t_appointments; ?></span>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            
            <div class="stat-card stat-avenir" style="border:none; box-shadow:0 4px 15px rgba(0,0,0,0.03); border-radius:12px;">
                <div class="stat-info">
                    <span class="stat-value"><?php echo $dashboardStatistics['pending']; ?></span>
                    <span class="stat-label"><?php echo ($language === 'ar') ? 'القادمة' : 'À venir'; ?></span>
                </div>
                <div class="stat-icon">
                    <i class="fa-regular fa-calendar-check"></i>
                </div>
            </div>
            
            <div class="stat-card stat-today" style="border:none; box-shadow:0 4px 15px rgba(0,0,0,0.03); border-radius:12px;">
                <div class="stat-info">
                    <span class="stat-value"><?php echo $dashboardStatistics['today']; ?></span>
                    <span class="stat-label"><?php echo $t_today; ?></span>
                </div>
                <div class="stat-icon">
                    <i class="fa-regular fa-clock"></i>
                </div>
            </div>
            
            <div class="stat-card stat-services-count" style="border:none; box-shadow:0 4px 15px rgba(0,0,0,0.03); border-radius:12px;">
                <div class="stat-info">
                    <span class="stat-value"><?php echo count($allServices); ?></span>
                    <span class="stat-label"><?php echo $t_total; ?></span>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-briefcase-medical"></i>
                </div>
            </div>
        </div>

        <!-- Filters & Toolbar -->
        <div class="toolbar-wrap" style="margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center; gap:16px;">
            <form method="GET" class="toolbar" style="flex:1; display:flex; margin-bottom:0; box-shadow:0 4px 15px rgba(0,0,0,0.03); border:none; padding:10px 16px;">
                <div class="search-box" style="flex:1; min-width:280px; display:flex;">
                    <i class="fa-solid fa-magnifying-glass" style="margin-top:10px;"></i>
                    <input type="text" name="search" placeholder="<?php echo $t_search_placeholder; ?>" value="<?php echo htmlspecialchars($searchQuery); ?>" style="border:1px solid #e2e8f0; width:100%;">
                </div>
                
                <select name="service" class="filter-select" style="min-width:220px; border:1px solid #e2e8f0;">
                    <option value=""><?php echo $t_all_services; ?></option>
                    <?php foreach($allServices as $srv): ?>
                        <option value="<?php echo $srv['id']; ?>" <?php echo ((string)$serviceFilter === (string)$srv['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($srv['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit" class="btn-filter" style="padding:12px 28px;"><i class="fa-solid fa-filter" style="display:none;"></i> <?php echo $t_filter; ?></button>
            </form>

            <a href="create-appointment.php" class="btn-nav-primary" style="flex-shrink:0;">
                <i class="fa-solid fa-plus"></i> <?php echo $t_new_appointment; ?>
            </a>
        </div>

        <!-- Tab Bar -->
        <div class="tab-bar" style="border-bottom:none; margin-bottom: 24px;">
            <button type="button" class="tab-btn active" onclick="filterByTab('all', this)">
                <i class="fa-solid fa-list" style="margin-right:6px;"></i> <?php echo $t_tab_all; ?>
            </button>
            <?php foreach (array_keys($groupedAppointments) as $serviceName): ?>
                <button type="button" class="tab-btn" onclick="filterByTab('<?php echo md5($serviceName); ?>', this)" style="background:#fff;">
                    <i class="fa-solid fa-briefcase-medical" style="color:#64748b; margin-right:6px;"></i> <?php echo htmlspecialchars($serviceName); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Grouped Data Tables -->
        <div id="services-container">
            <?php if(empty($groupedAppointments)): ?>
                <div class="card service-card">
                    <div class="empty">
                        <i class="fa-solid fa-folder-open empty-icon"></i>
                        <p><?php echo $t_no_appointments; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php foreach ($groupedAppointments as $serviceName => $appointments): 
                $groupId = md5($serviceName);
            ?>
            <div class="card service-card service-group-card" id="group-<?php echo $groupId; ?>">
                <div class="card-header">
                    <h3 class="card-title" style="margin:0; font-size:16px;">
                        <i class="fa-solid fa-briefcase-medical" style="color:#1B4D3E; margin-right:8px;"></i> 
                        <?php echo htmlspecialchars($serviceName); ?> 
                        <span style="color:#859485; font-weight:400; font-size:14px;">(<?php echo count($appointments); ?>)</span>
                    </h3>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 25%;"><?php echo $t_patient; ?></th>
                                <th style="width: 15%;"><?php echo $t_rendezvous; ?></th>
                                <th style="width: 12%;"><?php echo $t_reference; ?></th>
                                <th style="width: 15%;"><?php echo $t_message; ?></th>
                                <th style="width: 10%;"><?php echo $t_date; ?></th>
                                <th style="width: 10%;"><?php echo $t_status; ?></th>
                                <th style="width: 13%;" class="actions-col"><?php echo $t_actions; ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($appointments as $appointmentData): ?>
                            <tr class="table-row">
                                <td>
                                    <div class="patient-name" style="font-size:15px; margin-bottom:4px;"><?php echo htmlspecialchars($appointmentData['name']); ?></div>
                                    <div class="patient-meta" style="margin-bottom:2px;">
                                        <span class="patient-meta-item"><i class="fa-regular fa-id-card"></i> <?php echo htmlspecialchars($appointmentData['cni']); ?></span> • 
                                        <span class="patient-meta-item"><i class="fa-solid fa-phone" style="font-size:10px;"></i> <?php echo htmlspecialchars($appointmentData['phone']); ?></span>
                                    </div>
                                    <div class="patient-email">
                                        <i class="fa-regular fa-envelope"></i> <?php echo htmlspecialchars($appointmentData['email']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="rdv-info" style="gap:8px;">
                                        <div class="badge badge-date" style="background:#f8fafc; border:1px solid #e2e8f0; color:#1e293b; padding:6px 10px; font-weight:500;">
                                            <i class="fa-regular fa-calendar-check" style="color:#1B4D3E;"></i> <?php echo date('d/m/Y', strtotime($appointmentData['appointment_date'])); ?>
                                        </div>
                                        <?php if($appointmentData['slot_start']): ?>
                                        <div class="badge badge-date" style="background:transparent; border:none; padding:0; color:#0f172a; font-weight:600; padding-left:2px;">
                                            <i class="fa-regular fa-clock" style="color:#1B4D3E;"></i> <?php echo substr($appointmentData['slot_start'], 0, 5) . ' - ' . substr($appointmentData['slot_end'], 0, 5); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="ref-number" style="font-size:11px; letter-spacing:0.5px;"><?php echo htmlspecialchars($appointmentData['reference_number'] ?? 'N/A'); ?></div>
                                </td>
                                <td>
                                    <?php if(!empty($appointmentData['message'])): ?>
                                        <div class="message-text" style="background:#f8fafc; border:1px solid #f1f5f9; color:#475569; font-size:12px; padding:8px 12px; border-radius:10px;">
                                            <i class="fa-solid fa-comment-dots" style="color:#94a3b8; margin-right:6px;"></i><?php echo htmlspecialchars($appointmentData['message']); ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="no-msg">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="background:#f8fafc; display:inline-block; padding:6px 10px; border-radius:6px; font-size:12px; font-weight:500; color:#334155;">
                                        <?php echo date('d/m/Y H:i', strtotime($appointmentData['created_at'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <!-- Interactive Status Dropdown -->
                                    <form action="update-status.php" method="POST" style="margin:0;">
                                        <input type="hidden" name="id" value="<?php echo $appointmentData['id']; ?>">
                                        <select name="status" class="status-badge <?php echo getStatusColorClass($appointmentData['status']); ?>" onchange="this.form.submit()">
                                            <option value="pending" <?php echo $appointmentData['status']==='pending' ? 'selected':''; ?>><?php echo translateStatus('pending', $language); ?></option>
                                            <option value="confirmed" <?php echo $appointmentData['status']==='confirmed' ? 'selected':''; ?>><?php echo translateStatus('confirmed', $language); ?></option>
                                            <option value="canceled" <?php echo $appointmentData['status']==='canceled' ? 'selected':''; ?>><?php echo translateStatus('canceled', $language); ?></option>
                                        </select>
                                    </form>
                                </td>
                                <td class="actions-col">
                                    <div class="action-group">
                                        <!-- Inline Action Buttons -->
                                        <?php if($appointmentData['status'] === 'pending'): ?>
                                            <form action="update-status.php" method="POST" style="margin:0;">
                                                <input type="hidden" name="id" value="<?php echo $appointmentData['id']; ?>">
                                                <button type="submit" name="status" value="confirmed" class="btn-action-confirm"><i class="fa-solid fa-check"></i> <?php echo $t_confirm; ?></button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if($appointmentData['status'] !== 'canceled'): ?>
                                            <form action="update-status.php" method="POST" style="margin:0;">
                                                <input type="hidden" name="id" value="<?php echo $appointmentData['id']; ?>">
                                                <button type="submit" name="status" value="canceled" class="btn-action-cancel"><i class="fa-solid fa-xmark"></i> <?php echo $t_cancel; ?></button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form action="update-status.php" method="POST" style="margin:0;" onsubmit="return confirm('<?php echo addslashes($t_confirm_delete); ?>');">
                                            <input type="hidden" name="id" value="<?php echo $appointmentData['id']; ?>">
                                            <button type="submit" name="delete" value="1" class="btn-action-delete" title="<?php echo $t_delete_permanent; ?>"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Script for tab filtering -->
    <script>
        function filterByTab(groupId, btnElement) {
            // Update active state of buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
                btn.style.background = '#fff';
            });
            btnElement.classList.add('active');
            btnElement.style.background = ''; // restore css default for active

            // Show/Hide service groups
            const allGroups = document.querySelectorAll('.service-group-card');
            
            if (groupId === 'all') {
                allGroups.forEach(group => group.style.display = 'block');
            } else {
                allGroups.forEach(group => {
                    if (group.id === 'group-' + groupId) {
                        group.style.display = 'block';
                    } else {
                        group.style.display = 'none';
                    }
                });
            }
        }
    </script>
</body>
</html>
