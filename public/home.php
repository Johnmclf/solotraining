<?php
session_start();
require_once('db.php');

try {
    $conn = new PDO("pgsql:host=$host;dbname=$db", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT pompejour, recompence, abdosjour FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$pompeJour   = $user['pompejour'];
$abdosJour   = $user['abdosjour'];
$recompense  = $user['recompence'];

// --- Quête principale (pompes) ---
$canClaim = ($pompeJour >= 100 && $recompense == 1);

// --- Quête secondaire (abdos) ---
// Elle apparaît seulement si la récompense principale est déjà récupérée
// et si les pompes sont validées.
$canShowSecondary = ($recompense == 0 && $pompeJour >= 100);
$canClaimSecondary = ($abdosJour >= 100 && $canShowSecondary);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($canClaim && isset($_POST['claim_main'])) {
        $update = $conn->prepare("UPDATE users SET recompence = 0, point = point + 200 WHERE id = ?");
        $update->execute([$userId]);
        header("Location: home.php?success=1");
        exit();
    }

    if ($canClaimSecondary && isset($_POST['claim_secondary'])) {
        $update = $conn->prepare("UPDATE users SET point = point + 100 WHERE id = ?");
        $update->execute([$userId]);
        header("Location: home.php?secondary=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Quêtes</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet"/>
<style>
    .perspective-container { perspective: 1000px; }
    .quest-card { transition: transform 0.4s ease; }
    .quest-card:hover { transform: scale(1.02); }
    .glitch {
        text-shadow: 0.05em 0 0 rgba(255,0,0,.75),
                     -0.025em -0.05em 0 rgba(0,255,0,.75),
                     0.025em 0.05em 0 rgba(0,0,255,.75);
        animation: glitch 700ms infinite;
    }
    @keyframes glitch {
        0%,14% { text-shadow: 0.05em 0 0 rgba(255,0,0,.75), -0.05em -0.025em 0 rgba(0,255,0,.75), -0.025em 0.05em 0 rgba(0,0,255,.75);}
        15%,49%{ text-shadow:-0.05em -0.025em 0 rgba(255,0,0,.75),0.025em 0.025em 0 rgba(0,255,0,.75),-0.05em -0.05em 0 rgba(0,0,255,.75);}
        50%,99%{ text-shadow:0.025em 0.05em 0 rgba(255,0,0,.75),0.05em 0 0 rgba(0,255,0,.75),0 -0.05em 0 rgba(0,0,255,.75);}
        100%   { text-shadow:-0.025em 0 0 rgba(255,0,0,.75),-0.025em -0.025em 0 rgba(0,255,0,.75),-0.025em -0.05em 0 rgba(0,0,255,.75);}
    }
</style>
<script>
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    primary: "#a855f7", /* violet pour quête principale */
                    secondary: "#3b82f6", /* bleu pour quête secondaire */
                    "surface-dark": "#1a1a1a",
                },
                fontFamily: { display: ["Orbitron", "sans-serif"] },
                boxShadow: {
                    neon: "0 0 5px #a855f7, 0 0 15px #a855f7, 0 0 30px #a855f7",
                    neonBlue: "0 0 5px #3b82f6, 0 0 15px #3b82f6, 0 0 30px #3b82f6",
                }
            }
        }
    }
</script>
</head>
<body class="bg-black font-display text-gray-100 min-h-screen flex flex-col items-center justify-start p-4 gap-8">

<!-- Quête principale -->
<div class="perspective-container w-full max-w-3xl">
    <div class="quest-card bg-surface-dark/80 backdrop-blur-sm border-2 border-primary rounded-lg p-8 shadow-neon relative overflow-hidden">
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#a855f733_1px,transparent_1px),linear-gradient(to_bottom,#a855f733_1px,transparent_1px)] bg-[size:20px_20px] opacity-20"></div>
        <div class="relative z-10 text-center">
            <h1 class="text-3xl font-bold tracking-widest text-primary uppercase mb-6 glitch">Quête Principale</h1>
            <p class="text-xl text-gray-300 mb-2">Fais <span class="text-primary font-bold">100 pompes</span> aujourd’hui</p>
            <p class="text-lg text-gray-400 mb-8">Progression : <?= $pompeJour ?> / 100</p>
            <form method="POST">
                <button type="submit" name="claim_main"
                    class="px-8 py-3 rounded-md uppercase font-bold tracking-widest transition-all duration-300
                    <?php if ($canClaim): ?>
                        bg-primary/20 border border-primary text-primary hover:bg-primary hover:text-black shadow-neon
                    <?php else: ?>
                        bg-gray-800 border border-gray-600 text-gray-500 cursor-not-allowed
                    <?php endif; ?>">
                    <?= $canClaim ? "Récupérer la récompense" : "Récompense indisponible" ?>
                </button>
            </form>
            <?php if (isset($_GET['success'])): ?>
                <p class="mt-6 text-green-400 font-bold"> Récompense récupérée avec succès !</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quête secondaire (affichée seulement si conditions) -->
<?php if ($canShowSecondary): ?>
<div class="perspective-container w-full max-w-3xl">
    <div class="quest-card bg-surface-dark/80 backdrop-blur-sm border-2 border-secondary rounded-lg p-8 shadow-neonBlue relative overflow-hidden">
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#3b82f666_1px,transparent_1px),linear-gradient(to_bottom,#3b82f666_1px,transparent_1px)] bg-[size:20px_20px] opacity-20"></div>
        <div class="relative z-10 text-center">
            <h1 class="text-3xl font-bold tracking-widest text-secondary uppercase mb-6 glitch">Quête Secondaire</h1>
            <p class="text-xl text-gray-300 mb-2">Fais <span class="text-secondary font-bold">100 abdos</span> aujourd’hui</p>
            <p class="text-lg text-gray-400 mb-8">Progression : <?= $abdosJour ?> / 100</p>
            <form method="POST">
                <button type="submit" name="claim_secondary"
                    class="px-8 py-3 rounded-md uppercase font-bold tracking-widest transition-all duration-300
                    <?php if ($canClaimSecondary): ?>
                        bg-secondary/20 border border-secondary text-secondary hover:bg-secondary hover:text-black shadow-neonBlue
                    <?php else: ?>
                        bg-gray-800 border border-gray-600 text-gray-500 cursor-not-allowed
                    <?php endif; ?>">
                    <?= $canClaimSecondary ? "Récupérer la récompense" : "Récompense indisponible" ?>
                </button>
            </form>
            <?php if (isset($_GET['secondary'])): ?>
                <p class="mt-6 text-green-400 font-bold"> Récompense secondaire récupérée avec succès !</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

</body>
</html>
