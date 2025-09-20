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

$user_id = $_SESSION['user_id'];

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT pompejour, recompence, recompence2, abdosjour FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$pompeJour   = $user['pompejour'];
$abdosJour   = $user['abdosjour'];
$recompense  = $user['recompence'];
$recompense2  = $user['recompence2'];

// --- Quête principale (pompes) ---
$canClaim = ($pompeJour >= 100 && $recompense == 1);
$canClaimSecondary = ($abdosJour >= 100 && $recompense2 == 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($canClaim && isset($_POST['claim_main'])) {
        $update = $conn->prepare("UPDATE users SET recompence = 0, point = point + 100 WHERE id = ?");
        $update->execute([$userId]);
        header("Location: home.php?success=1");
        exit();
    }

    if ($canClaimSecondary && isset($_POST['claim_secondary'])) {
        $update = $conn->prepare("UPDATE users SET recompence2 = 0, point = point + 100 WHERE id = ?");
        $update->execute([$userId]);
        header("Location: home.php?secondary=1");
        exit();
    }
}

$resultPoints = $conn->query("SELECT point FROM users WHERE id = $user_id");
$userData = $resultPoints->fetch(PDO::FETCH_ASSOC);
$points = round(($userData['point']),2);

// Détermination du rang et style en fonction des points
if ($points < 1000) {
    $rank = "E";
    $rankColor = "border-green-400 text-green-400 shadow-[0_0_15px_#4ade80]";
} elseif ($points < 3000) {
    $rank = "D";
    $rankColor = "border-blue-400 text-blue-400 shadow-[0_0_15px_#60a5fa]";
} elseif ($points < 5000) {
    $rank = "C";
    $rankColor = "border-blue-700 text-blue-700 shadow-[0_0_15px_#1e40af]";
} elseif ($points < 10000) {
    $rank = "B";
    $rankColor = "border-pink-400 text-pink-400 shadow-[0_0_15px_#f472b6]";
} elseif ($points < 30000) {
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
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Quêtes</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet"/>
<style>
    .perspective-container { perspective: 1000px; }
    .quest-card:hover {
    box-shadow: 0 0 20px #a855f7, 0 0 40px #a855f7;
    border-color: #a855f7;
    }
    .quest-card { transition: transform 0.4s ease; }
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
    body {
      background: radial-gradient(circle at center, #0f172a 0%, #020617 100%);
      color: #e2e8f0;
    }
    .glow-text { text-shadow: 0 0 5px rgba(124, 58, 237, 0.7); }
    .dashboard-card {
      background: rgba(30, 41, 59, 0.7);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(124, 58, 237, 0.3);
      transition: transform 0.2s ease, box-shadow 0.3s ease;
    }
    .rank-progress {
      background: linear-gradient(90deg, #7c3aed 0%, #4f46e5 100%);
      box-shadow: 0 0 10px rgba(124, 58, 237, 0.5);
    }
    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

    /* --- Nouveau menu mobile (slide) --- */
    .mobile-menu {
      background: rgba(2,6,23,0.96);
      border-top: 1px solid rgba(124,58,237,0.2);
      overflow: hidden;
      max-height: 0;
      opacity: 0;
      transition: max-height .3s ease, opacity .3s ease;
    }
    .mobile-menu.open {
      max-height: 300px;
      opacity: 1;
    }
    .mobile-menu a {
      display: block;
      padding: .75rem 1rem;
      color: #e2e8f0;
      font-weight: 500;
      text-decoration: none;
    }
    .mobile-menu a:hover { color: #fff; text-decoration: underline; }
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

<header class="bg-[#0a0c1c] border-b border-accent/20 w-full relative z-40">
  <nav class="max-w-6xl mx-auto px-6 py-4 flex justify-between items-center">
    <div class="flex items-center space-x-2">
      <h1 class="text-xl font-orbitron glow-text">SOLO TRAINING</h1>
    </div>

    <!-- Menu desktop -->
    <div class="hidden md:flex items-center space-x-6">
      <a href="dashboard.php" class="text-gray-300 hover:text-white transition">Statistiques</a>
      <a href="objectifs.php" class="text-gray-300 hover:text-white transition">Objectifs</a>
      <div class="flex items-center space-x-4">
        <div title="<?= $points ?> points"
             class="w-8 h-8 rounded-full flex items-center justify-center font-bold border <?= $rankColor ?>">
          <?= $rank ?>
        </div>
        <a href="index.html" class="text-gray-300 hover:text-white transition">
          <img src="./asset/img/iconExit.png" class="w-10 h-7 opacity-70 hover:opacity-100 transition duration-200" alt="EXIT">
        </a>
      </div>
    </div>

    <!-- Bouton mobile -->
    <button id="mobileToggle" class="md:hidden flex items-center space-x-2 text-gray-300 focus:outline-none">
      <span id="hambIcon" class="text-2xl">☰</span>
      <span id="closeIcon" class="hidden text-2xl">✖</span>
    </button>
  </nav>

  <!-- Menu mobile -->
  <div id="mobileMenu" class="mobile-menu md:hidden">
    <a href="dashboard.php">Quêtes</a>
    <a href="objectifs.php">Objectifs</a>
    <a href="index.html">Quitter</a>
  </div>
</header>


<!-- Quête principale -->
<div class="perspective-container w-full max-w-3xl mt-12 ">
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

<script>
    (function(){
        const toggle = document.getElementById('mobileToggle');
        const menu = document.getElementById('mobileMenu');
        const hamb = document.getElementById('hambIcon');
        const closeI = document.getElementById('closeIcon');

        if (!toggle || !menu) return;

        function openMenu(open){
            if(open){
            menu.classList.add('open');
            menu.setAttribute('aria-hidden','false');
            toggle.setAttribute('aria-expanded','true');
            hamb.style.display = 'none';
            closeI.style.display = 'inline';
            } else {
            menu.classList.remove('open');
            menu.setAttribute('aria-hidden','true');
            toggle.setAttribute('aria-expanded','false');
            hamb.style.display = 'inline';
            closeI.style.display = 'none';
            }
        }

        openMenu(false);
        toggle.addEventListener('click', ()=> openMenu(!menu.classList.contains('open')));
        document.addEventListener('click', (ev) => {
            if(menu.classList.contains('open') && !menu.contains(ev.target) && !toggle.contains(ev.target)){
            openMenu(false);
            }
        });
        document.addEventListener('keydown', (e)=> {
            if(e.key === 'Escape' && menu.classList.contains('open')) openMenu(false);
        });
    })();
</script>


</body>
</html>
