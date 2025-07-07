<?php
session_start();

require("db.php");

try {
    $conn = new PDO("pgsql:host=$host;dbname=$db", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Récupère la dernière date de connexion
$stmt = $conn->prepare("SELECT lastConnexion FROM users WHERE id = ?");
$stmt->execute([$user_id]); // <-- ici, on utilise $user_id
$lastConnexion = $stmt->fetchColumn();

$lastConnexionDate = date('Y-m-d', strtotime($lastConnexion));
if ($lastConnexionDate !== $today) {
    session_unset();
    session_destroy();
    header('Location: login.php?deconnexion=1');
    exit();
}

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (isset($_POST['pompe'])) {
        $nb_pompe = intval($_POST['pompe']);
        $points_ajoutes = intval($nb_pompe / 2);
        $sql = "UPDATE users SET 
                    pompeJour = pompeJour + $nb_pompe, 
                    totalPompe = totalPompe + $nb_pompe,
                    point = point + $points_ajoutes
                WHERE id = $user_id";
        $conn->query($sql);
    }

    if (isset($_POST['abdos'])) {
        $nb_abdos = intval($_POST['abdos']);
        $points_ajoutes = intval($nb_abdos / 2);
        $sql = "UPDATE users SET 
                    abdosJour = abdosJour + $nb_abdos, 
                    totalAbdos = totalAbdos + $nb_abdos,
                    point = point + $points_ajoutes
                WHERE id = $user_id";
        $conn->query($sql);
    }
    
    if (isset($_POST['reset_pompe'])) {
        $result = $conn->query("SELECT pompeJour FROM users WHERE id = $user_id");
        $data = $result->fetch(PDO::FETCH_ASSOC);
        $penalite = intval($data['pompeJour'] / 2);
        $sql = "UPDATE users SET 
                    point = GREATEST(0, point - $penalite), 
                    jour1 = GREATEST(0, jour1 - $penalite),
                    totalPompe = GREATEST(0, totalPompe - pompeJour),
                    pompeJour = 0 
                WHERE id = $user_id";
        $conn->query($sql);
    }

    if (isset($_POST['reset_abdos'])) {
        $result = $conn->query("SELECT abdosJour FROM users WHERE id = $user_id");
        $data = $result->fetch(PDO::FETCH_ASSOC);
        $penalite = intval($data['abdosJour'] / 2);
        $sql = "UPDATE users SET 
                    point = GREATEST(0, point - $penalite), 
                    jour1 = GREATEST(0, jour1 - $penalite),
                    totalAbdos = GREATEST(0, totalAbdos - abdosJour),
                    abdosJour = 0 
                WHERE id = $user_id";
        $conn->query($sql);
    }


    // Mise à jour de jour1 uniquement pour l'affichage
    $result = $conn->query("SELECT pompeJour, abdosJour FROM users WHERE id = $user_id");
    $data = $result->fetch(PDO::FETCH_ASSOC);
    $jour1 = intval(($data['pompeJour'] + $data['abdosJour']) / 2);
    $sql = "UPDATE users SET jour1 = $jour1 WHERE id = $user_id";
    $conn->query($sql);

    header("Location: objectifs.php");
    exit();
}

// Récupération des données pour affichage
$result = $conn->query("SELECT pompeJour, abdosJour FROM users WHERE id = $user_id");
$data = $result->fetch(PDO::FETCH_ASSOC);

$pompeJour = intval($data['pompeJour']);
$abdosJour = intval($data['abdosJour']);

$pompePourcent = min(100, ($pompeJour / 100) * 100);
$abdosPourcent = min(100, ($abdosJour / 100) * 100);

$pompeStatus = $pompePourcent >= 100 ? "Terminé" : "$pompeJour / 100";
$abdosStatus = $abdosPourcent >= 100 ? "Terminé" : "$abdosJour / 100";

$pompeColor = $pompePourcent >= 100 ? "bg-green-500" : "bg-white";
$abdosColor = $abdosPourcent >= 100 ? "bg-green-500" : "bg-white";

// Récupération des points et définition du rang
$resultPoints = $conn->query("SELECT point FROM users WHERE id = $user_id");
$userData = $resultPoints->fetch(PDO::FETCH_ASSOC);
$points = intval($userData['point']);

// Détermination du rang et style en fonction des points
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
    <title>Objectifs - Solo Training</title>
    <script src="./asset/tailwind.js"></script>
    <link href="./asset/googleapis" rel="stylesheet">
    <link rel="stylesheet" href="asset/style.css">
    <link rel="shortcut icon" href="./asset/img/iconPage.jpg" type="image/x-icon">
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
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
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
                    <a href="dashboard.php" class="text-gray-300 hover:text-white transition">Statistiques</a>
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

    <!-- Formulaires + Objectifs -->
    <main class="container mx-auto px-4 py-12 grid md:grid-cols-2 gap-8">

        <!-- Objectif Pompes -->
        <div class="dashboard-card p-6 rounded-xl col-span-2">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Objectif Pompes</h2>
                <span class="text-lg font-semibold <?= $pompePourcent >= 100 ? 'text-green-400' : 'text-white' ?>">
                    <?= $pompeStatus ?>
                </span>
            </div>
            <div class="w-full bg-gray-700 rounded-full h-6 overflow-hidden">
                <div class="<?= $pompeColor ?> h-full transition-all" style="width: <?= $pompePourcent ?>%;"></div>
            </div>
        </div>

        <!-- Objectif Abdos -->
        <div class="dashboard-card p-6 rounded-xl col-span-2">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Objectif Abdos</h2>
                <span class="text-lg font-semibold <?= $abdosPourcent >= 100 ? 'text-green-400' : 'text-white' ?>">
                    <?= $abdosStatus ?>
                </span>
            </div>
            <div class="w-full bg-gray-700 rounded-full h-6 overflow-hidden">
                <div class="<?= $abdosColor ?> h-full transition-all" style="width: <?= $abdosPourcent ?>%;"></div>
            </div>
        </div>

        <!-- Formulaire Pompes -->
        <div class="dashboard-card p-8 rounded-xl col-span-2">
            <h2 class="text-xl font-bold mb-4 text-center">Ajouter des Pompes</h2>
            <form method="POST" class="space-y-4">
                <input type="number" name="pompe" min="1" placeholder="Nombre de pompes" required class="w-full p-3 rounded-lg bg-gray-800 text-white focus:outline-none">
                <button type="submit" class="w-full bg-purple-700 hover:bg-purple-900 text-white font-bold py-3 px-4 rounded-lg transition transform hover:scale-[1.02]">
                    Ajouter
                </button>
            </form>
        </div>

        <!-- Formulaire Abdos -->
        <div class="dashboard-card p-8 rounded-xl col-span-2">
            <h2 class="text-xl font-bold mb-4 text-center">Ajouter des Abdos</h2>
            <form method="POST" class="space-y-4">
                <input type="number" name="abdos" min="1" placeholder="Nombre d'abdos" required class="w-full p-3 rounded-lg bg-gray-800 text-white focus:outline-none">
                <button type="submit" class="w-full bg-purple-700 hover:bg-purple-900 text-white font-bold py-3 px-4 rounded-lg transition transform hover:scale-[1.02]">
                    Ajouter
                </button>
            </form>
        </div>

        <!-- Bloc Pompes -->
        <div class="dashboard-card p-6 rounded-xl col-span-2">
            <h2 class="text-xl font-bold mb-2 text-center">Pompes aujourd'hui : <?= $pompeJour ?></h2>
            <form method="POST">
                <button name="reset_pompe" type="submit"
                    class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-3 px-4 rounded-lg transition transform hover:scale-[1.02]">
                    Réinitialiser Pompes
                </button>
            </form>
        </div>

        <!-- Bloc Abdos -->
        <div class="dashboard-card p-6 rounded-xl col-span-2">
            <h2 class="text-xl font-bold mb-2 text-center">Abdos aujourd'hui : <?= $abdosJour ?></h2>
            <form method="POST">
                <button name="reset_abdos" type="submit"
                    class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-3 px-4 rounded-lg transition transform hover:scale-[1.02]">
                    Réinitialiser Abdos
                </button>
            </form>
        </div>
    </main>
</body>
</html>
