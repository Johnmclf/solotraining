<?php
session_start();

// heure serveur (entière)
$currentHour = (int) date('H');

// --- BLOCK entre 22h et 04h ---
if ($currentHour >= 22 || $currentHour < 4) {
    // On renvoie une page "down" ET on affiche l'heure serveur dans la console.
    // Important : on quitte tout de suite (exit) => aucun header ne sera modifié ensuite.
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width,initial-scale=1">
      <title>Maintenance - Solo Training</title>
      <style>
        body{font-family:Arial,Helvetica,sans-serif;background:#070812;color:#fff;display:flex;
             align-items:center;justify-content:center;height:100vh;margin:0;padding:1rem;}
        .card{background:rgba(0,0,0,0.6);border:1px solid rgba(139,92,246,0.12);
              padding:2rem;border-radius:12px;text-align:center;max-width:720px;}
        .card h1{color:#ff6b6b;margin:0 0 .5rem 0;}
        .card p{color:#ddd;margin:0 0 1rem 0;}
      </style>
    </head>
    <body>
      <div class="card">
        <h1>Connexions désactivées</h1>
        <p>Les connexions sont temporairement fermées entre <strong>00h00</strong> et <strong>04h00</strong>.</p>
        <p>Merci de revenir plus tard.</p>
      </div>

      <script>
        // affiche l'heure serveur dans la console du navigateur
        console.log('Heure actuelle (serveur PHP) : <?= $currentHour ?>h');
      </script>
    </body>
    </html>
    <?php
    exit;
}

// --- Si on arrive ici, on n'a rien envoyé au client (pas d'echo), safe pour header() ---
// require DB et suite du script (identique à ton code existant)
require("db.php");

$pdo = new PDO("pgsql:host=$host;dbname=$db", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$error = '';

// Traitement du formulaire (POST)
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

            // ... ta logique de mise à jour / combo / jours ...
            $lastConnexion = $user['lastconnexion'];
            $today = date('Y-m-d');
            $combo = (float)$user['combo'];

            if ($lastConnexion !== $today) {
                $lastDate = new DateTime($lastConnexion);
                $currentDate = new DateTime($today);
                $interval = $lastDate->diff($currentDate);
                $daysPassed = $interval->days;

                $pompe = (int)$user['pompejour'];
                $abdos = (int)$user['abdosjour'];

                if ($daysPassed >= 2) {
                    $combo = 1.00;
                } else {
                    $comboGain = 0.00;
                    if ($pompe >= 100) $comboGain += 0.05;
                    if ($comboGain > 0) {
                        $combo = round($combo + $comboGain, 2);
                    } else {
                        $combo = 1.00;
                    }
                }

                $jours = [];
                for ($i = 1; $i <= 7; $i++) $jours[] = $user["jour$i"];

                $penaliteTotale = 0;
                for ($d = 0; $d < min($daysPassed, 7); $d++) {
                    if ($pompe < 100) $penaliteTotale += 100;
                }

                $nouveauxPoints = max(0, $user['point'] - $penaliteTotale);

                for ($i = 6; $i >= 0; $i--) {
                    if ($i - $daysPassed >= 0) {
                        $jours[$i] = $jours[$i - $daysPassed];
                    } else {
                        $jours[$i] = 0;
                    }
                }

                $update = $pdo->prepare("UPDATE users SET 
                    jour1 = ?, jour2 = ?, jour3 = ?, jour4 = ?, jour5 = ?, jour6 = ?, jour7 = ?, 
                    pompejour = 0, abdosjour = 0, recompence = 1, recompence2 = 1, lastconnexion = CURRENT_DATE, point = ?, combo = ? 
                    WHERE id = ?");
                $update->execute([...$jours, $nouveauxPoints, $combo, $user['id']]);
            }

            // redirection : safe car on n'a rien envoyé précédemment (on est hors période blocage)
            header('Location: home.php');
            exit;
        } else {
            $error = "Mot de passe ou identifiant incorrect.";
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Connexion - Solo Training</title>
    <script src="./asset/tailwind.js"></script>
    <link href="./asset/googleapis" rel="stylesheet">
    <link rel="shortcut icon" href="./asset/img/iconPage.jpg" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body{background:radial-gradient(circle at center,#0f172a 0%,#020617 100%);font-family:'Orbitron',sans-serif;color:#e2e8f0}
    .auth-card{background:rgba(30,41,59,0.8);backdrop-filter:blur(10px);border:1px solid rgba(124,58,237,0.3)}
    .input-field{background:rgba(15,23,42,0.5);border:1px solid rgba(124,58,237,0.3);color:#000}
  </style>
</head>
<body class="font-orbitron min-h-screen flex items-center justify-center p-4">
  <div class="fixed inset-0 bg-[url('./asset/img/fondLogin.webp')] bg-cover bg-center opacity-20 -z-10"></div>
  <div class="auth-card rounded-xl p-8 w-full max-w-md">
    <div class="text-center mb-8">
      <h1 class="text-3xl mb-2">CONNEXION</h1>
      <p class="text-gray-400">Accédez à votre compte de chasseur</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="text-red-500 text-center mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
      <div>
        <label for="email" class="block text-sm font-medium mb-1">Pseudo</label>
        <input type="text" name="email" id="email" required class="input-field w-full pl-4 pr-3 py-3 rounded-lg" placeholder="Votre pseudo">
      </div>
      <div>
        <label for="password" class="block text-sm font-medium mb-1">Mot de passe</label>
        <input type="password" name="password" id="password" required class="input-field w-full pl-4 pr-3 py-3 rounded-lg" placeholder="••••••••">
      </div>
      <button type="submit" class="w-full bg-purple-700 hover:bg-purple-900 text-white font-bold py-3 px-4 rounded-lg">
        ACCÉDER AU SYSTÈME
      </button>
      <div class="text-center text-sm text-gray-400">
        Nouveau chasseur? <a href="register.php" class="text-accent hover:underline">Créez un compte</a>
      </div>
    </form>
  </div>

</body>
</html>
