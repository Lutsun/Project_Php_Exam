<?php
// Informations de connexion
$host = 'localhost';
$dbname = 'gestion_stock';
$username = 'root';
$password = ''; // Laisse vide si aucun mot de passe, sinon mets le tien

try {
    // Connexion à la base de données avec PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

    // Définir les options PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // En cas d'erreur, afficher un message clair
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
