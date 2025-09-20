<?php
session_start();

require("db.php");

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Traitement du formulaire
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($pseudo) || empty($password) || empty($confirm_password)) {
        $message = "Tous les champs sont requis.";
    } elseif ($password !== $confirm_password) {
        $message = "Les mots de passe ne correspondent pas.";
    } else {
        // Vérifie si le pseudo existe déjà
        $stmt = $pdo->prepare("SELECT id FROM users WHERE pseudo = :pseudo");
        $stmt->execute(['pseudo' => $pseudo]);
        if ($stmt->fetch()) {
            $message = "Ce pseudo est déjà utilisé.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO users (
                    pseudo, password, totalPompe, totalAbdos,
                    jour1, jour2, jour3, jour4, jour5, jour6, jour7,
                    point, lastConnexion
                ) VALUES (
                    :pseudo, :password, 0, 0,
                    0, 0, 0, 0, 0, 0, 0,
                    0, CURRENT_DATE
                )
            ");
            $stmt->execute([
                'pseudo' => $pseudo,
                'password' => $hashedPassword
            ]);

            header("Location: login.php?register=success");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Solo Training</title>
    <script src="./asset/tailwind.js"></script>
    <link href="./asset/googleapis" rel="stylesheet">
    <link rel="shortcut icon" href="./asset/img/iconPage.jpg" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
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
<body class="font-orbitron min-h-screen flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-[url('./asset/img/fondRegister.webp')] bg-cover bg-center opacity-20 -z-10"></div>
    
    <div class="auth-card rounded-xl p-8 w-full max-w-md">
        <div class="text-center mb-8">
            <div class="flex justify-center mb-4">
                <i class="fas fa-fire text-accent text-4xl"></i>
            </div>
            <h1 class="text-3xl font-orbitron glow-text mb-2">REJOINDRE L'AVENTURE</h1>
            <p class="text-gray-400">Créez votre compte de chasseur</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="mb-4 text-red-500 text-sm text-center">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form class="space-y-6" method="POST" action="register.php">
            <div>
                <label for="username" class="block text-sm font-medium mb-1">Nom d'utilisateur</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-user text-gray-500"></i>
                    </div>
                    <input type="text" id="username" name="username" class="input-field w-full pl-4 pr-3 py-3 rounded-lg focus:outline-none" placeholder="Votre pseudo">
                </div>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium mb-1">Mot de passe</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-lock text-gray-500"></i>
                    </div>
                    <input type="password" id="password" name="password" class="input-field w-full pl-4 pr-3 py-3 rounded-lg focus:outline-none" placeholder="••••••••">
                </div>
            </div>

            <div>
                <label for="confirm-password" class="block text-sm font-medium mb-1">Confirmer le mot de passe</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-lock text-gray-500"></i>
                    </div>
                    <input type="password" id="confirm-password" name="confirm_password" class="input-field w-full pl-4 pr-3 py-3 rounded-lg focus:outline-none" placeholder="••••••••">
                </div>
            </div>

            <button type="submit" class="w-full bg-purple-700 hover:bg-purple-900 text-white font-bold py-3 px-4 rounded-lg transition transform hover:scale-[1.02]">
                DEVENIR CHASSEUR
            </button>

            <div class="text-center text-sm text-gray-400">
                Déjà un compte? <a href="login.php" class="text-accent hover:underline">Connectez-vous</a>
            </div>
        </form>
    </div>
</body>
</html>
