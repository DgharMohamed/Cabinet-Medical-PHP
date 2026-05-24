<?php

// Modèle représentant un rendez-vous médical avec les opérations CRUD
class Appointment {
    
    private $databaseConnection;
    private $tableName = "appointments";

    public $id;
    public $name;
    public $email;
    public $phone;
    public $cni;
    public $service_type;
    public $appointment_date;
    public $message;
    public $status;
    public $created_at;
    public $service_id;
    public $time_slot_id;
    public $medical_document;
    public $reference_number;
    public $public_token;
    public $service_name;

    public function __construct($database) {
        $this->databaseConnection = $database;
    }

    // Insérer un nouveau rendez-vous dans la base de données
    public function createAppointment() {
        $sqlQuery = "INSERT INTO " . $this->tableName . " 
                    (name, email, phone, cni, service_type, appointment_date, message, service_id, time_slot_id, medical_document, reference_number, public_token, created_at)
                    VALUES (:name, :email, :phone, :cni, :service_type, :appointment_date, :message, :service_id, :time_slot_id, :medical_document, :reference_number, :public_token, NOW())";

        $preparedStatement = $this->databaseConnection->prepare($sqlQuery);

        return $preparedStatement->execute([
            ':name' => $this->name,
            ':email' => $this->email,
            ':phone' => $this->phone,
            ':cni' => $this->cni,
            ':service_type' => $this->service_type,
            ':appointment_date' => $this->appointment_date,
            ':message' => $this->message,
            ':service_id' => $this->service_id,
            ':time_slot_id' => $this->time_slot_id,
            ':medical_document' => $this->medical_document,
            ':reference_number' => $this->reference_number,
            ':public_token' => $this->public_token
        ]);
    }

    // Récupérer tous les rendez-vous avec les détails du service et du créneau horaire
    public function fetchAllAppointments() {

        // Jointure avec les tables services et time_slots pour obtenir les informations complètes
        $sqlQuery = "SELECT a.*, s.name AS service_name, ts.start_time AS slot_start, ts.end_time AS slot_end 
                     FROM " . $this->tableName . " a 
                     LEFT JOIN services s ON a.service_id = s.id 
                     LEFT JOIN time_slots ts ON a.time_slot_id = ts.id 
                     ORDER BY a.created_at DESC";
                     
        $preparedStatement = $this->databaseConnection->query($sqlQuery);
        
        return $preparedStatement->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer un rendez-vous par son identifiant, avec vérification optionnelle du jeton d'accès
    public function fetchAppointmentById() {
        $sqlQuery = "SELECT a.*, s.name AS service_name 
                     FROM " . $this->tableName . " a 
                     LEFT JOIN services s ON a.service_id = s.id 
                     WHERE a.id = :id";

        // Ajouter la vérification du jeton pour empêcher l'accès non autorisé (protection IDOR)
        if ($this->public_token !== null) {
            $sqlQuery .= " AND a.public_token = :public_token";
        }
        
        $preparedStatement = $this->databaseConnection->prepare($sqlQuery);

        $queryParameters = [':id' => $this->id];
        
        if ($this->public_token !== null) {
            $queryParameters[':public_token'] = $this->public_token;
        }

        $preparedStatement->execute($queryParameters);
        $resultRow = $preparedStatement->fetch(PDO::FETCH_ASSOC);

        if ($resultRow) {
            $this->name = $resultRow['name'];
            $this->email = $resultRow['email'];
            $this->phone = $resultRow['phone'];
            $this->cni = $resultRow['cni'];
            $this->service_type = $resultRow['service_type'];
            $this->appointment_date = $resultRow['appointment_date'];
            $this->message = $resultRow['message'];
            $this->status = $resultRow['status'];
            $this->created_at = $resultRow['created_at'];
            $this->service_id = $resultRow['service_id'];
            $this->time_slot_id = $resultRow['time_slot_id'];
            $this->medical_document = $resultRow['medical_document'];
            $this->reference_number = $resultRow['reference_number'];
            $this->public_token = $resultRow['public_token'] ?? null;
            $this->service_name = $resultRow['service_name'] ?? null;
            
            return true;
        }
        
        return false;
    }

    // Mettre à jour uniquement le statut d'un rendez-vous (confirmé, annulé, en attente)
    public function updateAppointmentStatus() {
        $sqlQuery = "UPDATE " . $this->tableName . " SET status = :status WHERE id = :id";
        
        $preparedStatement = $this->databaseConnection->prepare($sqlQuery);
        
        return $preparedStatement->execute([
            ':status' => $this->status,
            ':id' => $this->id
        ]);
    }

    // Mettre à jour toutes les informations d'un rendez-vous existant
    public function updateAppointment() {
        $sqlQuery = "UPDATE " . $this->tableName . "
                     SET name = :name, email = :email, phone = :phone, cni = :cni,
                         service_type = :service_type, appointment_date = :appointment_date,
                         message = :message,
                         service_id = :service_id, time_slot_id = :time_slot_id,
                         medical_document = :medical_document, reference_number = :reference_number
                     WHERE id = :id";

        $preparedStatement = $this->databaseConnection->prepare($sqlQuery);

        return $preparedStatement->execute([
            ':name' => $this->name,
            ':email' => $this->email,
            ':phone' => $this->phone,
            ':cni' => $this->cni,
            ':service_type' => $this->service_type,
            ':appointment_date' => $this->appointment_date,
            ':message' => $this->message,
            ':service_id' => $this->service_id,
            ':time_slot_id' => $this->time_slot_id,
            ':medical_document' => $this->medical_document,
            ':reference_number' => $this->reference_number,
            ':id' => $this->id
        ]);
    }

    // Supprimer un rendez-vous par son identifiant
    public function deleteAppointment() {
        $sqlQuery = "DELETE FROM " . $this->tableName . " WHERE id = :id";
        
        $preparedStatement = $this->databaseConnection->prepare($sqlQuery);
        
        return $preparedStatement->execute([':id' => $this->id]);
    }

    // Calculer les statistiques du tableau de bord (total, en attente, confirmés, annulés, aujourd'hui)
    public function getDashboardStatistics() {
        $statistics = [
            'total' => 0,
            'pending' => 0,
            'confirmed' => 0,
            'canceled' => 0,
            'today' => 0
        ];

        // Compter les rendez-vous regroupés par statut
        $sqlQuery = "SELECT status, COUNT(*) as count FROM " . $this->tableName . " GROUP BY status";
        $preparedStatement = $this->databaseConnection->query($sqlQuery);
        $results = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $resultRow) {
            $statistics['total'] += $resultRow['count'];
            
            if (isset($statistics[$resultRow['status']])) {
                $statistics[$resultRow['status']] = $resultRow['count'];
            }
        }

        // Compter les rendez-vous créés aujourd'hui
        $sqlTodayQuery = "SELECT COUNT(*) FROM " . $this->tableName . " WHERE DATE(created_at) = CURDATE()";
        $todayStatement = $this->databaseConnection->query($sqlTodayQuery);
        $statistics['today'] = $todayStatement->fetchColumn();

        return $statistics;
    }
}
