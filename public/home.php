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

// Récupération des données utilisateur (mêmes que dashboard + recompenses)
$sql = "SELECT point, pompejour, abdosjour, totalpompe, totalabdos, jour1, jour2, jour3, jour4, jour5, jour6, jour7, combo, recompence, recompence2 
        FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$points      = $user['point'];
$pompeJour   = $user['pompejour'];
$abdosJour   = $user['abdosjour'];
$recompense  = $user['recompence'];   // quête principale
$recompense2 = $user['recompence2']; // quête secondaire

$totalPompe = $user['totalpompe'] ?? 0;
$totalAbdos = $user['totalabdos'] ?? 0;
$jours = [$user['jour1'], $user['jour2'], $user['jour3'], $user['jour4'], $user['jour5'], $user['jour6'], $user['jour7']];
$moyenne7jours = round(array_sum($jours) / 7, 1);
$combo = $user['combo'];
$pointsToday = intval((($pompeJour + $abdosJour) / 2) * $combo);

// Détermination du rang
if ($points < 1000) {
    $rank = "E"; $rankColor = "border-green-400 text-green-400 shadow-[0_0_15px_#4ade80]";
} elseif ($points < 3000) {
    $rank = "D"; $rankColor = "border-blue-400 text-blue-400 shadow-[0_0_15px_#60a5fa]";
} elseif ($points < 5000) {
    $rank = "C"; $rankColor = "border-blue-700 text-blue-700 shadow-[0_0_15px_#1e40af]";
} elseif ($points < 10000) {
    $rank = "B"; $rankColor = "border-pink-400 text-pink-400 shadow-[0_0_15px_#f472b6]";
} elseif ($points < 30000) {
    $rank = "A"; $rankColor = "border-purple-500 text-purple-500 shadow-[0_0_15px_#a855f7]";
} elseif ($points < 50000) {
    $rank = "S"; $rankColor = "border-yellow-300 text-yellow-300 shadow-[0_0_15px_#fde047]";
} elseif ($points < 100000) {
    $rank = "NATION"; $rankColor = "border-orange-400 text-orange-400 shadow-[0_0_15px_#fb923c]";
} else {
    $rank = "ERROR"; $rankColor = "border-red-600 text-red-600 shadow-[0_0_20px_#dc2626]";
}

// --- Logique des quêtes ---
$canClaim = ($pompeJour >= 100 && $recompense == 1); // quête principale
$canShowSecondary = ($recompense == 0 && $pompeJour >= 100); // afficher secondaire si principale récupérée
$canClaimSecondary = ($abdosJour >= 100 && $canShowSecondary && $recompense2 == 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($canClaim && isset($_POST['claim_main'])) {
        $update = $conn->prepare("UPDATE users SET recompence = 0, point = point + 200 WHERE id = ?");
        $update->execute([$userId]);
        header("Location: home.php?success=1");
        exit();
    }
    if ($canClaimSecondary && isset($_POST['claim_secondary'])) {
        $update = $conn->prepare("UPDATE users SET recompense2 = 0, point = point + 100 WHERE id = ?");
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
<script src="./asset/tailwind.js"></script>
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
</head>
<body class="bg-black font-display text-gray-100 min-h-screen flex flex-col items-center justify-start p-4 gap-8">

<!-- HEADER -->
<header>
    <nav class="container mx-auto px-4 py-4" role="navigation" aria-label="Main navigation">
      <!-- left -->
      <div style="display:flex;align-items:center;gap:.6rem;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" style="color: #8b5cf6;">
          <path d="M12 2s1.5 2.5 1.5 4-1 2.5-1 4 1 2.5 1 4-2 3-2 3-4-3-4-6 2-5 2-7-1-4-1-4 3 1 3 3z" fill="currentColor"/>
        </svg>
        <h1 class="glow-text" style="font-size:18px;margin:0;font-weight:700;">SOLO TRAINING</h1>
      </div>

      <!-- right -->
      <div style="display:flex;align-items:center;gap:1rem;">
        <div class="desktop-menu">
          <a href="objectifs.php" class="text-gray-300 hover:text-white transition menu-link" style="color:#d1d5db;">Objectifs</a>
          <div style="display:flex;align-items:center;gap:.6rem;">
            <div title="<?= $points ?> points" class="rank-circle <?= $rankColor ?>"><?= $rank ?></div>
            <a href="index.html" aria-label="Exit" title="Quitter">
              <img src="./asset/img/iconExit.png" alt="exit" class="exit-img">
            </a>
          </div>
        </div>
        <div id="mobileToggle" class="mobile-toggle" role="button" aria-expanded="false" aria-controls="mobileMenu" tabindex="0">
          <span id="hambIcon" style="font-size:20px;line-height:1;">☰</span>
          <span id="closeIcon" style="display:none;font-size:20px;line-height:1;">✖</span>
          <span class="menu-label" style="font-size:14px;display:inline-block;">Menu</span>
        </div>
      </div>
    </nav>

    <!-- Mobile menu -->
    <div id="mobileMenu" class="mobile-menu" aria-hidden="true">
      <div class="menu-inner">
        <a href="objectifs.php" class="menu-link">Objectifs</a>
        <div class="row" style="justify-content:space-between;">
          <div style="display:flex;align-items:center;gap:.75rem;">
            <div title="<?= $points ?> points" class="rank-circle <?= $rankColor ?>"><?= $rank ?></div>
            <div>
              <div style="font-weight:600;color:#e2e8f0;">Points</div>
              <div style="font-size:.9rem;color:#9ca3af;">+<?= $pointsToday ?> aujourd'hui</div>
            </div>
          </div>
          <a href="index.html" class="menu-link" style="display:flex;align-items:center;gap:.5rem;">
            <img src="./asset/img/iconExit.png" alt="exit" class="exit-img" style="width:36px;height:28px;">
            <span class="menu-hint">Quitter</span>
          </a>
        </div>
      </div>
    </div>
</header>

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

<!-- Quête secondaire -->
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
