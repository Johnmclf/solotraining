<?php

#$host = 'mysql';
#$db = 'railway';
#$user = 'root';
#$port = '3306';
#$pass = getenv('DB_PASSWORD');
$host = 'aws-0-eu-west-3.pooler.supabase.com';
$db = 'postgres';
$user = 'postgres.vxztonwsxzzjrixgomjv';
$port = '6543';
$pass = 'W0u726ACdDYflPkv';

date_default_timezone_set('Europe/Paris');

$dsn = "pgsql:host=$host;port=$port;dbname=$db";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    echo "Connexion réussie à la base Supabase PostgreSQL !";
} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
}
