<?php
try {
    $pdo = new PDO("mysql:host=mysql;dbname=railway", "root", getenv('DB_PASSWORD'));

    // Modifier la colonne 'id' pour ajouter AUTO_INCREMENT
    $sql = "ALTER TABLE users 
            MODIFY id INT NOT NULL AUTO_INCREMENT";

    $pdo->exec($sql);

    echo " La colonne 'id' est maintenant en AUTO_INCREMENT.";
} catch (PDOException $e) {
    echo " Erreur : " . $e->getMessage();
}
?>

