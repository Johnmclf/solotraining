<?php
if (!isset($_GET['key']) || $_GET['key'] !== 'moncode123') {
    die("Accès refusé.");
}

require 'db.php';

$sqlFile = __DIR__ . '/solotraining.sql';

if (!file_exists($sqlFile)) {
    die("Fichier init.sql non trouvé.");
}

$sql = file_get_contents($sqlFile);

try {
    $pdo->exec($sql);
    echo "Script SQL exécuté avec succès.";
} catch (PDOException $e) {
    echo "Erreur lors de l’exécution SQL : " . $e->getMessage();
}
?>
