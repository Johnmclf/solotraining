<?php
try {
    $pdo = new PDO("mysql:host=mysql;dbname=railway", "root", getenv('DB_PASSWORD'));

    // Modification de la colonne id
    $sql = "ALTER TABLE users 
            MODIFY id INT NOT NULL AUTO_INCREMENT, 
            ADD PRIMARY KEY (id)";
    
    $pdo->exec($sql);

    echo " La colonne 'id' a bien été modifiée en AUTO_INCREMENT avec PRIMARY KEY.";
} catch (PDOException $e) {
    echo " Erreur : " . $e->getMessage();
}
?>
