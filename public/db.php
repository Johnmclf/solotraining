<?php

#$host = 'mysql';
#$db = 'railway';
#$user = 'root';
#$port = '3306';
#$pass = getenv('DB_PASSWORD');
$host = 'db.vxztonwsxzzjrixgomjv.supabase.co';
$db = 'postgres';
$user = 'postgres';
$port = '5432';
$pass = 'W0u726ACdDYflPkv';

date_default_timezone_set('Europe/Paris');

$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    echo "Connexion réussie à la base Supabase PostgreSQL !";
} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
}