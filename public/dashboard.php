<?php
session_start();
require_once('db.php');

try {
    $conn = new PDO("pgsql:host=$host;dbname=$db", $user, $pass);
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
$stmt = $conn->prepare("SELECT lastconnexion FROM users WHERE id = ?");
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
$sql = "SELECT point, pompejour, abdosjour,  totalpompe, totalabdos, jour1, jour2, jour3, jour4, jour5, jour6, jour7, combo FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$userId]);
$user = $stmt->fetch();

$points = $user['point'];
$pompeJour = $user['pompejour'];
$abdosJour = $user['abdosjour'];

$totalPompe = $user['totalpompe'] ?? 0;
$totalAbdos = $user['totalabdos'] ?? 0;

$jours = [$user['jour1'], $user['jour2'], $user['jour3'], $user['jour4'], $user['jour5'], $user['jour6'], $user['jour7']];
$moyenne7jours = round(array_sum($jours) / 7, 1);

$pompePourcent = min(100, ($pompeJour / 100) * 100);
$abdosPourcent = min(100, ($abdosJour / 100) * 100);

$pompeStatus = $pompePourcent >= 100 ? 'Terminé' : "$pompeJour / 100";
$abdosStatus = $abdosPourcent >= 100 ? 'Terminé' : "$abdosJour / 100";

$pompeColor = $pompePourcent >= 100 ? 'bg-green-500' : 'bg-white';
$abdosColor = $abdosPourcent >= 100 ? 'bg-green-500' : 'bg-white';

$combo = $user['combo'];

$pointsToday = intval((($pompeJour + $abdosJour) / 2) * $combo);


// Détermination du rang
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

$ranks = [
    ['name' => 'D', 'points' => 1000, 'color' => 'border-blue-400 text-blue-400 shadow-[0_0_15px_#60a5fa]'],
    ['name' => 'C', 'points' => 3000, 'color' => 'border-blue-700 text-blue-700 shadow-[0_0_15px_#1e40af]'],
    ['name' => 'B', 'points' => 5000, 'color' => 'border-pink-400 text-pink-400 shadow-[0_0_15px_#f472b6]'],
    ['name' => 'A', 'points' => 10000, 'color' => 'border-purple-500 text-purple-500 shadow-[0_0_15px_#a855f7]'],
    ['name' => 'S', 'points' => 30000, 'color' => 'border-yellow-300 text-yellow-300 shadow-[0_0_15px_#fde047]'],
    ['name' => 'NATION', 'points' => 50000, 'color' => 'border-orange-400 text-orange-400 shadow-[0_0_15px_#fb923c]'],
    ['name' => 'ERROR', 'points' => 100000, 'color' => 'border-red-600 text-red-600 shadow-[0_0_20px_#dc2626]'],
];

$nextRank = null;
foreach ($ranks as $r) {
    if ($points < $r['points']) {
        $nextRank = $r;
        break;
    }
}
$pointsRestants = $nextRank ? $nextRank['points'] - $points : 0;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Tableau de bord - Solo Training</title>
  <script src="./asset/tailwind.js"></script>
  <script src="./asset/chart.js"></script>
  <link href="./asset/googleapis" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
  <style>
    /* --- Base --- */
    :root{
      --bg: radial-gradient(circle at center, #0f172a 0%, #020617 100%);
      --card-bg: rgba(30,41,59,0.7);
      --accent-border: rgba(124,58,237,0.3);
      --header-bg: rgba(2,6,23,0.98);
      --menu-bg: rgba(2,6,23,0.96);
    }
    html,body{height:100%}
    body{
      margin:0;
      font-family: 'Orbitron', sans-serif;
      background: var(--bg);
      color: #e2e8f0;
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
    }

    .glow-text { text-shadow: 0 0 5px rgba(124,58,237,0.7); }

    .dashboard-card {
      background: var(--card-bg);
      backdrop-filter: blur(10px);
      border: 1px solid var(--accent-border);
      border-radius: 1rem;
      padding: 1.25rem;
      margin-bottom: 1rem; /* mobile spacing */
      transition: transform .22s ease, box-shadow .28s ease;
    }

    header { position: relative; z-index: 40; background: var(--header-bg); border-bottom: 1px solid rgba(124,58,237,0.08); }
    nav.container { display:flex; align-items:center; justify-content:space-between; gap:1rem; }

    /* Desktop / mobile helpers (robust even si tailwind manque) */
    .desktop-menu { display: none; }        /* hidden by default (mobile) */
    .mobile-toggle { display: flex; }       /* visible by default (mobile) */

    /* mobile menu (slide) */
    .mobile-menu {
      position: absolute;
      left: 0; right: 0;
      top: 100%;
      background: var(--menu-bg);
      border-top: 1px solid rgba(124,58,237,0.08);
      padding: 0.5rem 0;
      z-index: 45;

      /* slide animation */
      max-height: 0;
      overflow: hidden;
      opacity: 0;
      pointer-events: none;
      transition: max-height .32s ease, opacity .28s ease;
    }
    .mobile-menu.open {
      max-height: 420px; /* enough for menu content */
      opacity: 1;
      pointer-events: auto;
    }

    .mobile-menu .menu-inner { max-width: 1200px; margin: 0 auto; padding: .5rem 1rem; }
    .mobile-menu a.menu-link {
      display:block;
      padding: .6rem 0;
      color: #e2e8f0;
      text-decoration: none;
      font-weight: 500;
    }
    .mobile-menu a.menu-link:hover { color: #fff; text-decoration: underline; }

    .mobile-menu .row { display:flex; align-items:center; gap:.75rem; padding:.45rem 0; }

    /* Force icon/button visible on dark bg */
    .mobile-toggle { align-items:center; gap:.5rem; cursor:pointer; color: #fff; background: transparent; border: none; padding: .25rem .5rem; border-radius: .5rem; }
    .mobile-toggle:focus{ outline: 2px solid rgba(124,58,237,0.35); outline-offset:2px; }

    /* Hide mobile pieces on desktop and show desktop pieces */
    @media (min-width: 768px){
      .mobile-toggle { display: none !important; }
      .mobile-menu { display: none !important; }
      .desktop-menu { display: flex !important; align-items:center; gap:1rem; }
      .dashboard-card { margin-bottom:0; }
      main { display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; }
      aside { display:flex; flex-direction:column; gap:1.25rem; }
    }

    /* small UI niceties */
    .rank-circle { width: 40px; height:40px; border-radius:999px; display:flex; align-items:center; justify-content:center; font-weight:700; }
    .exit-img { width:44px; height:34px; object-fit:contain; opacity:.8; }
    .menu-label { font-weight:600; color:#e2e8f0; }
    .menu-hint { font-size:.9rem; color:#9ca3af; }
    /* safe fallback in case Tailwind 'hidden' class not present */
    .u-hidden{display:none}
  </style>
</head>
<body class="font-orbitron">
  <!-- Header -->
  <header class="bg-[#0a0c1c] border-b border-accent/20 w-full relative z-40">
    <nav class="max-w-6xl mx-auto px-6 py-4 flex justify-between items-center">
      <div class="flex items-center space-x-2">
        <h1 class="text-xl font-orbitron glow-text">SOLO TRAINING</h1>
      </div>

      <!-- Menu desktop -->
      <div class="hidden md:flex items-center space-x-6">
        <a href="home.php" class="text-gray-300 hover:text-white transition">Quêtes</a>
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
      <a href="home.php">Quêtes</a>
      <a href="objectifs.php">Objectifs</a>
      <a href="index.html">Quitter</a>
    </div>
  </header>


  <!-- Main -->
  <main class="container mx-auto px-4 max-w-7xl py-12">
    <!-- Left column -->
    <section>
      <div style="display:flex;flex-direction:column;gap:.5rem;margin-bottom:1.25rem;">
        <h2 class="glow-text" style="font-size:22px;margin:0;font-weight:700;">Bon retour, Chasseur</h2>
        <p style="color:#9ca3af;margin:0;">Votre progression aujourd'hui</p>
      </div>

      <!-- Objectives grid (2-cols on md) -->
      <div style="display:grid;grid-template-columns:1fr;gap:1rem;margin-bottom:1.5rem;" class="md:grid md:grid-cols-2 md:gap-6">
        <!-- Pompes -->
        <div class="dashboard-card">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.6rem;">
            <h3 style="margin:0;font-size:18px;font-weight:700;">Objectif Pompes</h3>
            <span class="<?= $pompePourcent >= 100 ? 'text-green-400' : 'text-white' ?>"><?= $pompeStatus ?></span>
          </div>
          <div style="width:100%;background:#374151;height:1.25rem;border-radius:999px;overflow:hidden;">
            <div class="<?= $pompeColor ?>" style="height:100%;width: <?= $pompePourcent ?>%;"></div>
          </div>
          <a href="objectifs.php" class="text-accent" style="display:inline-flex;align-items:center;margin-top:.5rem;color:#8b5cf6;font-weight:600;">Voir les détails <span style="margin-left:.5rem;font-size:12px;">→</span></a>
        </div>

        <!-- Abdos -->
        <div class="dashboard-card">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.6rem;">
            <h3 style="margin:0;font-size:18px;font-weight:700;">Objectif Abdos</h3>
            <span class="<?= $abdosPourcent >= 100 ? 'text-green-400' : 'text-white' ?>"><?= $abdosStatus ?></span>
          </div>
          <div style="width:100%;background:#374151;height:1.25rem;border-radius:999px;overflow:hidden;">
            <div class="<?= $abdosColor ?>" style="height:100%;width: <?= $abdosPourcent ?>%;"></div>
          </div>
          <a href="objectifs.php" class="text-accent" style="display:inline-flex;align-items:center;margin-top:.5rem;color:#8b5cf6;font-weight:600;">Voir les détails <span style="margin-left:.5rem;font-size:12px;">→</span></a>
        </div>
      </div>

      <!-- Chart card -->
      <div class="dashboard-card" style="margin-bottom:1rem;">
        <h3 style="margin:0 0 .75rem 0;font-weight:700;">Diagramme du total de points (7 derniers jours)</h3>
        <canvas id="pointsChart"></canvas>
      </div>
    </section>

    <!-- Right column (appears under main on mobile) -->
    <aside>
      <div class="dashboard-card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem;">
          <div style="display:flex;align-items:center;gap:.5rem;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 2v20M2 12h20" stroke="#8b5cf6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <strong>POINTS</strong>
          </div>
          <span style="background:rgba(139,92,246,0.12);color:#8b5cf6;padding:.25rem .6rem;border-radius:999px;font-weight:600;">+<?= $pointsToday ?> aujourd'hui</span>
        </div>
        <div style="font-size:28px;font-weight:800;"><?= $points ?></div>
      </div>

      <div class="dashboard-card">
        <h3 style="margin:0 0 .5rem 0;font-weight:700;">COMBO</h3>
        <div style="font-size:28px;font-weight:800;">x <?= $combo ?></div>
      </div>

      <div class="dashboard-card">
        <h3 style="margin:0 0 .5rem 0;font-weight:700;">TOTAL DES EXERCICES</h3>
        <div style="display:flex;gap:1rem;">
          <div>
            <div style="color:#9ca3af;font-size:.9rem;">Pompes au total</div>
            <div style="font-weight:700;font-size:18px;"><?= $totalPompe ?></div>
          </div>
          <div>
            <div style="color:#9ca3af;font-size:.9rem;">Abdos au total</div>
            <div style="font-weight:700;font-size:18px;"><?= $totalAbdos ?></div>
          </div>
        </div>
      </div>

      <div class="dashboard-card">
        <h3 style="margin:0 0 .5rem 0;font-weight:700;">PROCHAIN RANG</h3>
        <div style="display:flex;gap:1rem;align-items:center;">
          <?php if ($nextRank): ?>
            <div title="<?= $nextRank['name'] ?> (<?= $nextRank['points'] ?> pts)" class="rank-circle <?= $nextRank['color'] ?>"><?= $nextRank['name'] ?></div>
            <div>
              <div style="color:#9ca3af;font-size:.9rem;">Points restants</div>
              <div style="font-weight:700;font-size:18px;"><?= $pointsRestants ?></div>
            </div>
          <?php else: ?>
            <div style="color:#9ca3af;">Rang maximum atteint</div>
          <?php endif; ?>
        </div>
      </div>

      <div class="dashboard-card">
        <h3 style="margin:0 0 .5rem 0;font-weight:700;">Moyenne sur 7 jours</h3>
        <div style="font-weight:700;font-size:18px;"><?= $moyenne7jours ?> points / jour</div>
      </div>
    </aside>
  </main>

  <!-- Scripts -->
  <script>
    // Mobile menu toggle logic (robuste)
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

      // initial closed
      openMenu(false);

      toggle.addEventListener('click', ()=> openMenu(!menu.classList.contains('open')));

      // keyboard support
      toggle.addEventListener('keydown', (e) => {
        if(e.key === 'Enter' || e.key === ' '){ e.preventDefault(); openMenu(!menu.classList.contains('open')); }
      });

      // close if clicking outside
      document.addEventListener('click', (ev) => {
        const target = ev.target;
        if(menu.classList.contains('open')){
          if(!menu.contains(target) && !toggle.contains(target)){
            openMenu(false);
          }
        }
      });

      // optional: close on escape
      document.addEventListener('keydown', (e)=> {
        if(e.key === 'Escape' && menu.classList.contains('open')){
          openMenu(false);
        }
      });
    })();

    // ChartJS (unchanged)
    (function(){
      const ctx = document.getElementById('pointsChart');
      if (!ctx) return;
      const chartCtx = ctx.getContext('2d');
      const data = {
        labels: ['Jour 1','Jour 2','Jour 3','Jour 4','Jour 5','Jour 6','Jour 7'],
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

      new Chart(chartCtx, {
        type: 'line',
        data,
        options: {
          scales: {
            x: { grid:{ display:false }, ticks:{ color:'#e2e8f0' }, border:{ color:'#ffffff' } },
            y: { grid:{ display:false }, ticks:{ color:'#e2e8f0' }, border:{ color:'#ffffff' } }
          },
          plugins: { legend:{ display:false } }
        }
      });
    })();
  </script>
</body>
</html>
