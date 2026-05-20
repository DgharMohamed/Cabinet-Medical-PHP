<?php

// Classe responsable de la connexion à la base de données MySQL via PDO
class DatabaseConnection {

    private $hostName = 'localhost';
    private $databaseName = 'cabinet_medical';
    private $userName = 'root';
    private $userPassword = '';
    private $databaseConnection = null;

    // Créer et retourner une connexion PDO sécurisée
    public function getConnection() {
        try {
            $dataSourceName = "mysql:host=" . $this->hostName . ";dbname=" . $this->databaseName . ";charset=utf8";
            $databaseConnection = new PDO($dataSourceName, $this->userName, $this->userPassword);

            // Activer le mode exception pour détecter les erreurs SQL
            $databaseConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $this->databaseConnection = $databaseConnection;
        } catch (PDOException $exception) {
            error_log("Connection failure: " . $exception->getMessage());
            die("Une erreur de connexion est survenue. Veuillez réessayer plus tard.");
        }

        return $this->databaseConnection;
    }
}
