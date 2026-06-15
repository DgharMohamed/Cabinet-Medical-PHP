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
