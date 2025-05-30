<?php
session_start();

require("db.php");

$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$error = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE pseudo = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['pseudo'] = $user['pseudo'];

            $lastConnexion = $user['lastConnexion'];
            $today = date('Y-m-d');

            // Si on s'est pas connecté aujourd'hui
            if ($lastConnexion !== $today) {
                $lastDate = new DateTime($lastConnexion);
                $currentDate = new DateTime($today);
                $interval = $lastDate->diff($currentDate);
                $daysPassed = $interval->days;

                if ($daysPassed >= 1) {
                    // Récupération des valeurs des 7 jours
                    $jours = [];
                    for ($i = 1; $i <= 7; $i++) {
                        $jours[] = $user["jour$i"];
                    }

                    // Copie des objectifs du dernier jour enregistré
                    $pompe = $user['pompeJour'];
                    $abdos = $user['abdosJour'];

                    // Calcul de la pénalité à appliquer
                    $penaliteTotale = 0;

                    // Appliquer la pénalité pour chaque jour manqué
                    for ($d = 0; $d < min($daysPassed, 7); $d++) {
                        if ($pompe < 100) $penaliteTotale += 50;
                        if ($abdos < 100) $penaliteTotale += 50;
                    }

                    // Application de la pénalité
                    $nouveauxPoints = max(0, $user['point'] - $penaliteTotale);

                    // Décalage des jours
                    for ($i = 6; $i >= 0; $i--) {
                        if ($i - $daysPassed >= 0) {
                            $jours[$i] = $jours[$i - $daysPassed];
                        } else {
                            $jours[$i] = 0;
                        }
                    }

                    // Mise à jour finale
                    $update = $pdo->prepare("UPDATE users SET jour1 = ?, jour2 = ?, jour3 = ?, jour4 = ?, jour5 = ?, jour6 = ?, jour7 = ?, pompeJour = 0, abdosJour = 0, lastConnexion = CURDATE(), point = ? WHERE id = ?");
                    $update->execute([...$jours, $nouveauxPoints, $user['id']]);
                }
            }


            // Fin classique
            header('Location: dashboard.php');
            exit;
        }

    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Solo Training</title>
    <script src="./asset/tailwind.js"></script>
    <link href="./asset/googleapis" rel="stylesheet">
    <link rel="shortcut icon" href="./asset/img/iconPage.jpg" type="image/x-icon">
    <style>
        body {
            background: radial-gradient(circle at center, #0f172a 0%, #020617 100%);
            color: #e2e8f0;
        }
        .glow-text {
            text-shadow: 0 0 5px rgba(124, 58, 237, 0.7);
        }
        .auth-card {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(124, 58, 237, 0.3);
        }
        .input-field {
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(124, 58, 237, 0.3);
        }
        .input-field:focus {
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.2);
        }
    </style>
</head>
<body class="font-roboto min-h-screen flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-[url('./asset/img/fondLogin.webp')] bg-cover bg-center opacity-20 -z-10"></div>

    <div class="auth-card rounded-xl p-8 w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-orbitron glow-text mb-2">CONNEXION</h1>
            <p class="text-gray-400">Accédez à votre compte de chasseur</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="text-red-500 text-center mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form class="space-y-6" method="POST">
            <div>
                <label for="email" class="block text-sm font-medium mb-1">Pseudo</label>
                <div class="relative">
                    <input type="text" name="email" id="email" required class="input-field w-full pl-4 pr-3 py-3 rounded-lg focus:outline-none" placeholder="Votre pseudo">
                </div>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium mb-1">Mot de passe</label>
                <div class="relative">
                    <input type="password" name="password" id="password" required class="input-field w-full pl-4 pr-3 py-3 rounded-lg focus:outline-none" placeholder="••••••••">
                </div>
            </div>

            <button type="submit" class="w-full bg-purple-700 hover:bg-purple-900 text-white font-bold py-3 px-4 rounded-lg transition transform hover:scale-[1.02]">
                ACCÉDER AU SYSTÈME
            </button>

            <div class="text-center text-sm text-gray-400">
                Nouveau chasseur? <a href="register.php" class="text-accent hover:underline">Créez un compte</a>
            </div>
        </form>
    </div>
</body>
</html>
