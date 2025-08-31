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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Tableau de bord - Solo Training</title>
  <script src="./asset/tailwind.js"></script>
  <script src="./asset/chart.js"></script>
  <link href="./asset/googleapis" rel="stylesheet">
  <style>
    :root{
      --bg-1: #0f172a;
      --bg-2: #020617;
      --card-bg: rgba(30,41,59,0.75);
      --accent: #7c3aed;
      --accent-weak: rgba(124,58,237,0.2);
      --text: #e2e8f0;
    }
    html,body{
      height:100%;
      margin:0;
      font-family: Roboto, system-ui, -apple-system, "Segoe UI", "Helvetica Neue", Arial;
      background: radial-gradient(circle at center, var(--bg-1) 0%, var(--bg-2) 100%);
      color: var(--text);
    }

    /* Header */
    header{
      position:relative;
      z-index:40;
      border-bottom:1px solid rgba(124,58,237,0.08);
      background: linear-gradient(180deg, rgba(6,7,20,0.6), rgba(6,7,20,0.45));
      backdrop-filter: blur(6px);
    }
    .container{
      max-width:1200px;
      margin:0 auto;
      padding-left:1rem;
      padding-right:1rem;
    }
    nav { display:flex; align-items:center; justify-content:space-between; padding:1rem 0; gap:1rem; }

    .brand { display:flex; align-items:center; gap:0.5rem; }
    .brand .logo { width:28px; height:28px; display:inline-block; background: linear-gradient(90deg,#7c3aed,#4f46e5); border-radius:6px; box-shadow: 0 0 8px rgba(124,58,237,0.3); }
    .brand h1 { margin:0; font-weight:700; font-family: "Orbitron", sans-serif; font-size:1.15rem; text-shadow:0 0 6px rgba(124,58,237,0.6); }

    /* Desktop menu (hidden on mobile) */
    .menu-desktop { display:none; align-items:center; gap:1.25rem; }
    .menu-desktop a { color: #cbd5e1; text-decoration:none; transition: color .15s;}
    .menu-desktop a:hover{ color: #fff; }

    /* Mobile hamburger (visible on mobile) */
    .mobile-toggle {
      display:flex;
      align-items:center;
      justify-content:center;
      width:44px;
      height:44px;
      background: transparent;
      border-radius:8px;
      cursor:pointer;
      border: 1px solid rgba(255,255,255,0.06);
      z-index:50;
    }
    .mobile-toggle:focus { outline: 2px solid rgba(124,58,237,0.4); outline-offset:2px; }

    /* Mobile menu (slide) */
    .mobile-menu-wrapper {
      position: absolute;
      left:0;
      right:0;
      top:100%;
      z-index:45;
      display:flex;
      justify-content:center;
      pointer-events:none; /* pointer-events controlled inside .mobile-menu */
    }
    .mobile-menu {
      width:100%;
      max-width:1200px;
      background: linear-gradient(180deg, rgba(2,6,23,0.98), rgba(6,7,20,0.98));
      border-top: 1px solid rgba(124,58,237,0.06);
      box-shadow: 0 8px 30px rgba(2,6,23,0.6);
      overflow: hidden;
      max-height: 0;
      transition: max-height 300ms ease, padding 300ms ease;
      pointer-events: auto;
      padding: 0 1rem;
    }
    .mobile-menu.open { max-height: 420px; padding: 0.75rem 1rem 1rem 1rem; }

    .mobile-menu .row { display:flex; align-items:center; gap:0.75rem; padding:0.5rem 0; border-bottom:1px solid rgba(255,255,255,0.02); }
    .mobile-menu a { color:#cbd5e1; text-decoration:none; display:block; padding:0.25rem 0; }
    .mobile-menu .rank-wrap{ display:flex; align-items:center; gap:0.75rem; }

    /* Dashboard cards */
    .main {
      max-width:1200px;
      margin:0 auto;
      padding:2.5rem 1rem;
      box-sizing:border-box;
    }
    .grid-desktop {
      display:block;
    }
    .stack { display:flex; flex-direction:column; gap:1rem; }

    .dashboard-card {
      background: var(--card-bg);
      border: 1px solid rgba(124,58,237,0.12);
      backdrop-filter: blur(8px);
      padding:1rem;
      border-radius:12px;
      transition: transform .18s ease, box-shadow .18s ease;
    }
    .dashboard-card:hover { transform: translateY(-6px); box-shadow: 0 14px 30px rgba(10,8,30,0.45); }

    /* small screens: make sure cards breathe */
    @media (max-width:767px){
      .menu-desktop { display:none; }
      .mobile-toggle { display:flex; }
      .grid-desktop { display:block; }
    }

    /* desktop layout */
    @media (min-width:768px){
      .menu-desktop { display:flex; }
      .mobile-toggle { display:none; } /* hide mobile toggle on desktop */
      .mobile-menu { display:none !important; } /* force hide mobile menu on desktop */
      .grid-desktop {
        display:grid;
        grid-template-columns: 2fr 1fr;
        gap: 1.5rem;
      }
      .stack { gap:1.25rem; }
      .dashboard-card { margin:0; }
    }

    /* utility small tweaks */
    .muted { color:#94a3b8; font-size:0.95rem; }
    .title { font-weight:700; font-size:1.05rem; margin-bottom:0.35rem; }
    .big { font-size:1.8rem; font-weight:700; }
    .accent-pill { background: rgba(124,58,237,0.12); color: #c7b8ff; padding:6px 10px; border-radius:999px; font-size:0.9rem; }
    .circle-rank { width:40px; height:40px; border-radius:999px; display:flex; align-items:center; justify-content:center; font-weight:700; }
  </style>
</head>
<body>
  <!-- Header -->
  <header>
    <div class="container">
      <nav>
        <div class="brand" aria-hidden="false">
          <span class="logo" aria-hidden="true"></span>
          <h1>SOLO TRAINING</h1>
        </div>

        <!-- Desktop menu -->
        <div class="menu-desktop" role="navigation" aria-label="Menu principal">
          <a href="objectifs.php">Objectifs</a>

          <div style="display:flex;align-items:center;gap:0.8rem;">
            <div class="circle-rank <?= $rankColor ?>" title="<?= $points ?> points"><?= $rank ?></div>
            <a href="index.html" title="Quitter">
              <img src="./asset/img/iconExit.png" alt="Exit" style="width:44px;height:auto;opacity:0.85;">
            </a>
          </div>
        </div>

        <!-- Mobile toggle (div cliquable avec SVG -> garanti visible) -->
        <div id="mobileToggle" class="mobile-toggle" role="button" tabindex="0" aria-controls="mobileMenu" aria-expanded="false" aria-label="Ouvrir le menu">
          <!-- SVG hamburger (blanc) -->
          <svg id="hamburgerIcon" width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
            <rect y="4" width="24" height="2" rx="1" fill="#ffffff"/>
            <rect y="11" width="24" height="2" rx="1" fill="#ffffff"/>
            <rect y="18" width="24" height="2" rx="1" fill="#ffffff"/>
          </svg>
        </div>
      </nav>
    </div>

    <!-- Mobile menu (absolute beneath header) -->
    <div class="mobile-menu-wrapper">
      <div id="mobileMenu" class="mobile-menu" aria-hidden="true">
        <div class="row">
          <a href="objectifs.php">Objectifs</a>
        </div>

        <div class="row rank-wrap">
          <div class="circle-rank <?= $rankColor ?>" title="<?= $points ?> points"><?= $rank ?></div>
          <div style="display:flex;flex-direction:column;">
            <span class="muted">Points</span>
            <strong class="big"><?= $points ?></strong>
          </div>
          <div style="margin-left:auto;">
            <a href="index.html" title="Quitter">
              <img src="./asset/img/iconExit.png" alt="Exit" style="width:44px;height:auto;opacity:0.95;">
            </a>
          </div>
        </div>

        <!-- Optionally more mobile-only rows -->
        <div class="row" style="padding-bottom:0;">
          <a href="objectifs.php" class="muted">Voir mes objectifs</a>
        </div>
      </div>
    </div>
  </header>

  <!-- Main Dashboard -->
  <main class="main">
    <div class="grid-desktop">
      <!-- Left column -->
      <section class="stack">
        <div>
          <h2 style="margin:0;font-size:1.35rem;font-weight:700;text-shadow:0 0 6px rgba(124,58,237,0.5);">Bon retour, Chasseur</h2>
          <p class="muted" style="margin-top:6px;">Votre progression aujourd'hui</p>
        </div>

        <div class="dashboard-card">
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <div>
              <div class="title">Objectif Pompes</div>
              <div class="muted">Progression</div>
            </div>
            <div class="<?= $pompePourcent >= 100 ? 'text-green-400' : 'text-white' ?>"><?= $pompeStatus ?></div>
          </div>
          <div style="margin-top:12px;">
            <div style="background:#334155;height:12px;border-radius:20px;overflow:hidden;">
              <div class="<?= $pompeColor ?>" style="height:100%; width: <?= $pompePourcent ?>%; transition:width .4s;"></div>
            </div>
            <a href="objectifs.php" class="accent-pill" style="display:inline-block;margin-top:10px;text-decoration:none;">Voir les détails →</a>
          </div>
        </div>

        <div class="dashboard-card">
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <div>
              <div class="title">Objectif Abdos</div>
              <div class="muted">Progression</div>
            </div>
            <div class="<?= $abdosPourcent >= 100 ? 'text-green-400' : 'text-white' ?>"><?= $abdosStatus ?></div>
          </div>
          <div style="margin-top:12px;">
            <div style="background:#334155;height:12px;border-radius:20px;overflow:hidden;">
              <div class="<?= $abdosColor ?>" style="height:100%; width: <?= $abdosPourcent ?>%; transition:width .4s;"></div>
            </div>
            <a href="objectifs.php" class="accent-pill" style="display:inline-block;margin-top:10px;text-decoration:none;">Voir les détails →</a>
          </div>
        </div>

        <div class="dashboard-card">
          <h3 class="title" style="margin-bottom:12px;">Diagramme du total de points (7 derniers jours)</h3>
          <canvas id="pointsChart" style="max-width:100%;height:220px;"></canvas>
        </div>
      </section>

      <!-- Right column -->
      <aside class="stack" style="min-width:0;">
        <div class="dashboard-card" style="display:flex;flex-direction:column;gap:10px;">
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <div>
              <div class="title">POINTS</div>
              <div class="muted">Aujourd'hui</div>
            </div>
            <div class="accent-pill">+<?= $pointsToday ?></div>
          </div>
          <div class="big"><?= $points ?></div>
        </div>

        <div class="dashboard-card">
          <div class="title">COMBO</div>
          <div class="big">x <?= $combo ?></div>
        </div>

        <div class="dashboard-card">
          <div class="title">TOTAL DES EXERCICES</div>
          <div style="display:flex;gap:0.75rem;margin-top:8px;">
            <div style="flex:1;">
              <div class="muted">Pompes au total</div>
              <div style="font-weight:700;font-size:1.25rem;"><?= $totalPompe ?></div>
            </div>
            <div style="flex:1;">
              <div class="muted">Abdos au total</div>
              <div style="font-weight:700;font-size:1.25rem;"><?= $totalAbdos ?></div>
            </div>
          </div>
        </div>

        <div class="dashboard-card">
          <div class="title">PROCHAIN RANG</div>
          <div style="display:flex;align-items:center;gap:0.75rem;margin-top:8px;">
            <?php if ($nextRank): ?>
              <div class="circle-rank <?= $nextRank['color'] ?>"><?= $nextRank['name'] ?></div>
              <div>
                <div class="muted">Points restants</div>
                <div style="font-weight:700;font-size:1.1rem;"><?= $pointsRestants ?></div>
              </div>
            <?php else: ?>
              <div class="muted">Rang maximum atteint</div>
            <?php endif; ?>
          </div>
        </div>

        <div class="dashboard-card">
          <div class="title">Moyenne sur 7 jours</div>
          <div style="font-weight:700;font-size:1.1rem;margin-top:6px;"><?= $moyenne7jours ?> points / jour</div>
        </div>
      </aside>
    </div>
  </main>

  <script>
    // Elements
    const mobileToggle = document.getElementById('mobileToggle');
    const mobileMenu = document.getElementById('mobileMenu');

    // Toggle function
    function setMenu(open){
      if(open){
        mobileMenu.classList.add('open');
        mobileToggle.setAttribute('aria-expanded','true');
        mobileMenu.setAttribute('aria-hidden','false');
      } else {
        mobileMenu.classList.remove('open');
        mobileToggle.setAttribute('aria-expanded','false');
        mobileMenu.setAttribute('aria-hidden','true');
      }
    }

    // Click / keyboard handlers (div clickable fallback)
    mobileToggle.addEventListener('click', () => {
      setMenu(!mobileMenu.classList.contains('open'));
    });
    mobileToggle.addEventListener('keydown', (e) => {
      if(e.key === 'Enter' || e.key === ' '){
        e.preventDefault();
        setMenu(!mobileMenu.classList.contains('open'));
      }
    });

    // Close when clicking outside menu
    document.addEventListener('click', (e) => {
      const isClickInside = mobileMenu.contains(e.target) || mobileToggle.contains(e.target);
      if(!isClickInside && mobileMenu.classList.contains('open')){
        setMenu(false);
      }
    });

    // Hide mobile menu on resize to desktop
    window.addEventListener('resize', () => {
      if(window.innerWidth >= 768 && mobileMenu.classList.contains('open')){
        setMenu(false);
      }
    });

    // OPTIONAL: close menu when a link inside is clicked (good on mobile)
    const mobileLinks = mobileMenu.querySelectorAll('a');
    mobileLinks.forEach(a => a.addEventListener('click', () => setMenu(false)));

    // Chart.js init (keeps your original options)
    (function initChart(){
      const ctx = document.getElementById('pointsChart');
      if(!ctx) return;
      const chart = ctx.getContext('2d');
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
      const config = {
        type: 'line',
        data,
        options: {
          scales: {
            x: {
              grid: { display: false },
              ticks: { color: 'white' },
              border: { color: 'white' }
            },
            y: {
              grid: { display: false },
              ticks: { color: 'white' },
              border: { color: 'white' }
            }
          },
          plugins: {
            legend: { display: false }
          },
          maintainAspectRatio: false
        }
      };
      try {
        new Chart(chart, config);
      } catch(e){
        // Chart not available / error: fail silently
        console.warn('Chart.js: impossible d\'initialiser le graphique.', e);
      }
    })();
  </script>
</body>
</html>
