<?php
session_start();
require_once('db.php');

try {
    $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Vérifie que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit();
}

$userId = $_SESSION['user_id'];
$today = date('Y-m-d');

// Récupère la dernière date de connexion
$stmt = $conn->prepare("SELECT lastConnexion FROM users WHERE id = ?");
$stmt->execute([$userId]);
$lastConnexion = $stmt->fetchColumn();


$lastConnexionDate = date('Y-m-d', strtotime($lastConnexion));
if ($lastConnexionDate !== $today) {
    session_unset();
    session_destroy();
    header('Location: login.php?deconnexion=1');
    exit();
}


// Récupère les données de l'utilisateur
$sql = "SELECT point, pompeJour, abdosJour,  totalPompe, totalAbdos, jour1, jour2, jour3, jour4, jour5, jour6, jour7 FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$userId]);
$user = $stmt->fetch();

$points = $user['point'];
$pompeJour = $user['pompeJour'];
$abdosJour = $user['abdosJour'];

$totalPompe = $user['totalPompe'] ?? 0;
$totalAbdos = $user['totalAbdos'] ?? 0;

$jours = [$user['jour1'], $user['jour2'], $user['jour3'], $user['jour4'], $user['jour5'], $user['jour6'], $user['jour7']];
$moyenne7jours = round(array_sum($jours) / 7, 1);

$pompePourcent = min(100, ($pompeJour / 100) * 100);
$abdosPourcent = min(100, ($abdosJour / 100) * 100);

$pompeStatus = $pompePourcent >= 100 ? 'Terminé' : "$pompeJour / 100";
$abdosStatus = $abdosPourcent >= 100 ? 'Terminé' : "$abdosJour / 100";

$pompeColor = $pompePourcent >= 100 ? 'bg-green-500' : 'bg-white';
$abdosColor = $abdosPourcent >= 100 ? 'bg-green-500' : 'bg-white';

$pointsToday = intval(($pompeJour + $abdosJour) / 2);

// Détermination du rang
if ($points < 1000) {
    $rank = "E";
    $rankColor = "border-green-400 text-green-400 shadow-[0_0_15px_#4ade80]";
} elseif ($points < 2000) {
    $rank = "D";
    $rankColor = "border-blue-400 text-blue-400 shadow-[0_0_15px_#60a5fa]";
} elseif ($points < 5000) {
    $rank = "C";
    $rankColor = "border-blue-700 text-blue-700 shadow-[0_0_15px_#1e40af]";
} elseif ($points < 10000) {
    $rank = "B";
    $rankColor = "border-pink-400 text-pink-400 shadow-[0_0_15px_#f472b6]";
} elseif ($points < 20000) {
    $rank = "A";
    $rankColor = "border-purple-500 text-purple-500 shadow-[0_0_15px_#a855f7]";
} elseif ($points < 50000) {
    $rank = "S";
    $rankColor = "border-yellow-300 text-yellow-300 shadow-[0_0_15px_#fde047]";
} elseif ($points < 100000) {
    $rank = "NATION";
    $rankColor = "border-orange-400 text-orange-400 shadow-[0_0_15px_#fb923c]";
} else {
    $rank = "ERROR";
    $rankColor = "border-red-600 text-red-600 shadow-[0_0_20px_#dc2626]";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Solo Training</title>
    <script src="./asset/tailwind.js"></script>
    <script src="./asset/chart.js"></script>
    <link href="./asset/googleapis" rel="stylesheet">
    <style>
        body {
            background: radial-gradient(circle at center, #0f172a 0%, #020617 100%);
            color: #e2e8f0;
        }
        .glow-text {
            text-shadow: 0 0 5px rgba(124, 58, 237, 0.7);
        }
        .rank-progress {
            background: linear-gradient(90deg, #7c3aed 0%, #4f46e5 100%);
            box-shadow: 0 0 10px rgba(124, 58, 237, 0.5);
        }
        .dashboard-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(124, 58, 237, 0.3);
        }
        .user-rank-C {
            border: 2px solid #4ade80;
            box-shadow: 0 0 15px rgba(74, 222, 128, 0.5);
        }
    </style>
</head>
<body class="font-roboto min-h-screen">
    <!-- Header -->
    <header class="bg-primary border-b border-accent/20">
        <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <i class="fas fa-fire text-accent text-2xl"></i>
                <h1 class="text-2xl font-orbitron glow-text">SOLO TRAINING</h1>
            </div>
            
            <div class="flex items-center space-x-6">
                <div class="md:flex space-x-6">
                    <a href="objectifs.php" class="text-gray-300 hover:text-white transition">Objectifs</a>
                </div>
                <div class="flex items-center space-x-4">
                    <div title="<?= $points ?> points" class="w-10 h-10 rounded-full flex items-center justify-center font-bold border-2 <?= $rankColor ?>">
                        <?= $rank ?>
                    </div>
                    <a href="index.html" class="text-gray-300 hover:text-white transition">
                        <img src="./asset/img/iconExit.png"  class="w-12 h-9 opacity-70 hover:opacity-100 transition duration-200 px-2" alt="EXIT">
                    </a>
                </div>
            </div>
            <button class="md:hidden text-gray-300">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </nav>
    </header>

    <!-- Dashboard Content -->
    <main class="container mx-auto px-4 py-8">
        <!-- Welcome Section -->
        <section class="mb-12">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8">
                <div>
                    <h2 class="text-2xl font-orbitron glow-text mb-2">Bon retour, Chasseur</h2>
                    <p class="text-gray-400">Votre progression aujourd'hui</p>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid md:grid-cols-3 gap-6 mb-8">
                <!-- Objectif Pompes -->
                <div class="dashboard-card p-6 rounded-xl col-span-2 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold">Objectif Pompes</h2>
                        <span class="text-lg font-semibold <?= $pompePourcent >= 100 ? 'text-green-400' : 'text-white' ?>">
                            <?= $pompeStatus ?>
                        </span>
                    </div>
                    <div class="w-full bg-gray-700 rounded-full h-6 overflow-hidden">
                        <div class="<?= $pompeColor ?> h-full transition-all" style="width: <?= $pompePourcent ?>%;"></div>
                    </div>
                    <a href="objectifs.php" class="text-accent hover:underline text-sm flex items-center mt-2">
                        Voir les détails <i class="fas fa-arrow-right ml-2 text-xs"></i>
                    </a>
                </div>

                <!-- Objectif Abdos -->
                <div class="dashboard-card p-6 rounded-xl col-span-2 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold">Objectif Abdos</h2>
                        <span class="text-lg font-semibold <?= $abdosPourcent >= 100 ? 'text-green-400' : 'text-white' ?>">
                            <?= $abdosStatus ?>
                        </span>
                    </div>
                    <div class="w-full bg-gray-700 rounded-full h-6 overflow-hidden">
                        <div class="<?= $abdosColor ?> h-full transition-all" style="width: <?= $abdosPourcent ?>%;"></div>
                    </div>
                    <a href="objectifs.php" class="text-accent hover:underline text-sm flex items-center mt-2">
                        Voir les détails <i class="fas fa-arrow-right ml-2 text-xs"></i>
                    </a>
                </div>

                <!-- Carte Points -->
                <div class="dashboard-card rounded-xl p-6 col-span-2 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold flex items-center">
                            <i class="fas fa-coins text-accent mr-2"></i>
                            POINTS
                        </h3>
                        <span class="text-sm bg-accent/20 text-accent px-3 py-1 rounded-full">
                            +<?= $pointsToday ?> aujourd'hui
                        </span>
                    </div>
                    <div class="text-3xl font-bold mb-2"><?= $points ?></div>
                    <a href="statistiques.php" class="text-accent hover:underline text-sm flex items-center">
                        Voir l'historique <i class="fas fa-arrow-right ml-2 text-xs"></i>
                    </a>
                </div>

                <!-- Carte Totaux -->
                <div class="dashboard-card rounded-xl p-6 col-span-2 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold flex items-center">
                            <i class="fas fa-dumbbell text-accent mr-2"></i>
                            TOTAL DES EXERCICES
                        </h3>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-gray-400 text-sm">Pompes au total</div>
                            <div class="text-2xl font-semibold"><?= $totalPompe ?></div>
                        </div>
                        <div>
                            <div class="text-gray-400 text-sm">Abdos au total</div>
                            <div class="text-2xl font-semibold"><?= $totalAbdos ?></div>
                        </div>
                    </div>
                </div>

                <!-- Moyenne des 7 derniers jours -->
                <div class="dashboard-card rounded-xl p-6 col-span-2 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold flex items-center">
                            <i class="fas fa-calendar-week text-accent mr-2"></i>
                            Moyenne sur 7 jours
                        </h3>
                    </div>
                    <div class="text-2xl font-semibold text-white">
                        <?= $moyenne7jours ?> points par jour
                    </div>
                </div>

                <!-- Pompes Chart -->
                <div class="dashboard-card rounded-xl p-6 col-span-2 mb-6">
                    <h3 class="text-lg font-bold mb-4 flex items-center">
                        <i class="fas fa-chart-bar text-accent mr-2"></i>
                        Diagramme du total de points (7 derniers jours)
                    </h3>
                    <canvas id="pointsChart"></canvas>
                </div>

            </div>
        </section>
    </main>

    <script>
        const ctx = document.getElementById('pointsChart').getContext('2d');

        const data = {
            labels: ['Jour 1', 'Jour 2', 'Jour 3', 'Jour 4', 'Jour 5', 'Jour 6', 'Jour 7'],
            datasets: [{
                label: 'Points',
                data: [
                    <?= $user['jour1'] ?>,
                    <?= $user['jour2'] ?>,
                    <?= $user['jour3'] ?>,
                    <?= $user['jour4'] ?>,
                    <?= $user['jour5'] ?>,
                    <?= $user['jour6'] ?>,
                    <?= $user['jour7'] ?>
                ],
                pointStyle: 'circle',
                pointRadius: 6,
                pointBackgroundColor: 'white',
                pointBorderColor: 'rgb(255, 255, 255)',
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.3
            }]
        };

        const config = {
            type: 'line',
            data: data,
            options: {
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: 'white'
                        },
                        border: {
                            color: 'white'
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: 'white'
                        },
                        border: {
                            color: 'white'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        };

        new Chart(ctx, config);
    </script>
</body>
