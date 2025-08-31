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
$stmt = $conn->prepare("SELECT lastconnexion, combo FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$userInfo = $stmt->fetch(PDO::FETCH_ASSOC);

$lastConnexionDate = date('Y-m-d', strtotime($userInfo['lastconnexion']));
$combo = floatval($userInfo['combo']);

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
        $points_ajoutes = round((($nb_pompe / 2) * $combo),2);
        $sql = "UPDATE users SET 
                    pompejour = pompejour + $nb_pompe, 
                    totalpompe = totalpompe + $nb_pompe,
                    point = point + $points_ajoutes
                WHERE id = $user_id";
        $conn->query($sql);
    }

    if (isset($_POST['abdos'])) {
        $nb_abdos = intval($_POST['abdos']);
        $points_ajoutes = round((($nb_abdos / 2) * $combo),2);
        $sql = "UPDATE users SET 
                    abdosjour = abdosjour + $nb_abdos, 
                    totalabdos = totalabdos + $nb_abdos,
                    point = point + $points_ajoutes
                WHERE id = $user_id";
        $conn->query($sql);
    }

    if (isset($_POST['reset_pompe'])) {
        $result = $conn->query("SELECT pompejour FROM users WHERE id = $user_id");
        $data = $result->fetch(PDO::FETCH_ASSOC);
        $penalite = round((($data['pompejour'] / 2) * $combo),2);
        $sql = "UPDATE users SET 
                    point = GREATEST(0, point - $penalite), 
                    jour1 = GREATEST(0, jour1 - $penalite),
                    totalpompe = GREATEST(0, totalpompe - pompejour),
                    pompejour = 0 
                WHERE id = $user_id";
        $conn->query($sql);
    }

    if (isset($_POST['reset_abdos'])) {
        $result = $conn->query("SELECT abdosjour FROM users WHERE id = $user_id");
        $data = $result->fetch(PDO::FETCH_ASSOC);
        $penalite = round((($data['abdosjour'] / 2) * $combo),2);
        $sql = "UPDATE users SET 
                    point = GREATEST(0, point - $penalite), 
                    jour1 = GREATEST(0, jour1 - $penalite),
                    totalabdos = GREATEST(0, totalabdos - abdosjour),
                    abdosjour = 0 
                WHERE id = $user_id";
        $conn->query($sql);
    }

    // Mise à jour de jour1 uniquement pour l'affichage
    $result = $conn->query("SELECT pompejour, abdosjour FROM users WHERE id = $user_id");
    $data = $result->fetch(PDO::FETCH_ASSOC);
    $jour1 = round(((($data['pompejour'] + $data['abdosjour']) / 2) * $combo),2);
    $sql = "UPDATE users SET jour1 = $jour1 WHERE id = $user_id";
    $conn->query($sql);

    header("Location: objectifs.php");
    exit();
}

// Récupération des données pour affichage
$result = $conn->query("SELECT pompejour, abdosjour FROM users WHERE id = $user_id");
$data = $result->fetch(PDO::FETCH_ASSOC);

$pompeJour = intval($data['pompejour']);
$abdosJour = intval($data['abdosjour']);

$pompePourcent = min(100, ($pompeJour / 100) * 100);
$abdosPourcent = min(100, ($abdosJour / 100) * 100);

$pompeStatus = $pompePourcent >= 100 ? "Terminé" : "$pompeJour / 100";
$abdosStatus = $abdosPourcent >= 100 ? "Terminé" : "$abdosJour / 100";

$pompeColor = $pompePourcent >= 100 ? "bg-green-500" : "bg-white";
$abdosColor = $abdosPourcent >= 100 ? "bg-green-500" : "bg-white";

// Récupération des points et définition du rang
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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Objectifs - Solo Training</title>
  <link rel="shortcut icon" href="./asset/img/iconPage.jpg" type="image/x-icon">
  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const toggle = document.getElementById("mobileToggle");
      const menu = document.getElementById("mobileMenu");
      const hamb = document.getElementById("hambIcon");
      const close = document.getElementById("closeIcon");

      toggle.addEventListener("click", () => {
        const open = menu.classList.toggle("open");
        toggle.setAttribute("aria-expanded", open);
        menu.setAttribute("aria-hidden", !open);
        hamb.style.display = open ? "none" : "inline";
        close.style.display = open ? "inline" : "none";
      });
    });
  </script>

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
      font-family: Roboto, system-ui, -apple-system, "Segoe UI", "Helvetica Neue", Arial;
      background: var(--bg);
      color: #e2e8f0;
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
    .dashboard-card:hover { transform: translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,.25); }

    header { position: relative; z-index: 40; background: var(--header-bg); border-bottom: 1px solid rgba(124,58,237,0.08); }
    nav.container { display:flex; align-items:center; justify-content:space-between; gap:1rem; }

    /* Desktop / mobile helpers */
    .desktop-menu { display: none; }
    .mobile-toggle { display: flex; }

    /* mobile menu (slide) */
    .mobile-menu {
      position: absolute; left: 0; right: 0; top: 100%;
      background: var(--menu-bg);
      border-top: 1px solid rgba(124,58,237,0.08);
      padding: 0.5rem 0;
      z-index: 45;
      max-height: 0; overflow: hidden; opacity: 0; pointer-events: none;
      transition: max-height .32s ease, opacity .28s ease;
    }
    .mobile-menu.open {
      max-height: 420px;
      opacity: 1;
      pointer-events: auto;
    }

    .mobile-menu .menu-inner { max-width: 1200px; margin: 0 auto; padding: .5rem 1rem; }
    .mobile-menu a.menu-link {
      display:block; padding: .6rem 0;
      color: #e2e8f0; text-decoration: none; font-weight: 500;
    }
    .mobile-menu a.menu-link:hover { color: #fff; text-decoration: underline; }

    .mobile-menu .row { display:flex; align-items:center; gap:.75rem; padding:.45rem 0; }

    .mobile-toggle {
      align-items:center; gap:.5rem; cursor:pointer;
      color: #fff; background: transparent; border: none;
      padding: .25rem .5rem; border-radius: .5rem;
    }
    .mobile-toggle:focus{ outline: 2px solid rgba(124,58,237,0.35); outline-offset:2px; }

    @media (min-width: 768px){
      .mobile-toggle { display: none !important; }
      .mobile-menu { display: none !important; }
      .desktop-menu { display: flex !important; align-items:center; gap:1.5rem; }
      main {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 2rem;
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 1rem;
      }
      aside { display:flex; flex-direction:column; gap:1.25rem; }
    }

    /* small UI niceties */
    .rank-circle { width: 40px; height:40px; border-radius:999px; display:flex; align-items:center; justify-content:center; font-weight:700; border:2px solid #8b5cf6; }
    .exit-img { width:44px; height:34px; object-fit:contain; opacity:.8; }
    .exit-img:hover { opacity:1; }
    .menu-label { font-weight:600; color:#e2e8f0; }
    .menu-hint { font-size:.9rem; color:#9ca3af; }
  </style>
</head>
<body>
  <!-- Header -->
  <header>
    <nav class="container mx-auto px-4 py-4" role="navigation" aria-label="Main navigation">
      <!-- left -->
      <div style="display:flex;align-items:center;gap:.6rem;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" style="color: #8b5cf6;"><path d="M12 2s1.5 2.5 1.5 4-1 2.5-1 4 1 2.5 1 4-2 3-2 3-4-3-4-6 2-5 2-7-1-4-1-4 3 1 3 3z" fill="currentColor"/></svg>
        <h1 class="glow-text" style="font-size:18px;margin:0;font-weight:700;">SOLO TRAINING</h1>
      </div>

      <!-- right -->
      <div style="display:flex;align-items:center;gap:1rem;">
        <!-- Desktop menu -->
        <div class="desktop-menu">
          <a href="dashboard.php" class="menu-link">Statistiques</a>
          <a href="objectifs.php" class="menu-link">Objectifs</a>
          <div style="display:flex;align-items:center;gap:.6rem;">
            <div title="<?= $points ?> points" class="rank-circle <?= $rankColor ?>"><?= $rank ?></div>
            <a href="index.html" aria-label="Exit" title="Quitter"><img src="./asset/img/iconExit.png" alt="exit" class="exit-img"></a>
          </div>
        </div>

        <!-- Mobile toggle -->
        <div id="mobileToggle" class="mobile-toggle" role="button" aria-expanded="false" aria-controls="mobileMenu" tabindex="0">
          <span id="hambIcon" style="font-size:20px;line-height:1;">☰</span>
          <span id="closeIcon" style="display:none;font-size:20px;line-height:1;">✖</span>
          <span class="menu-label" style="font-size:14px;">Menu</span>
        </div>
      </div>
    </nav>

    <!-- Mobile menu -->
    <div id="mobileMenu" class="mobile-menu" aria-hidden="true">
      <div class="menu-inner">
        <a href="dashboard.php" class="menu-link">Statistiques</a>
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

  <!-- Content -->
  <main>
    <!-- Colonne principale -->
    <section>
      <!-- Objectifs -->
      <div class="dashboard-card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem;">
          <h2>Objectif Pompes</h2>
          <span class="<?= $pompePourcent >= 100 ? 'text-green-400' : 'text-white' ?>"><?= $pompeStatus ?></span>
        </div>
        <div style="background:#374151;border-radius:9999px;height:20px;overflow:hidden;">
          <div class="<?= $pompeColor ?>" style="width:<?= $pompePourcent ?>%;height:100%;"></div>
        </div>
      </div>

      <div class="dashboard-card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem;">
          <h2>Objectif Abdos</h2>
          <span class="<?= $abdosPourcent >= 100 ? 'text-green-400' : 'text-white' ?>"><?= $abdosStatus ?></span>
        </div>
        <div style="background:#374151;border-radius:9999px;height:20px;overflow:hidden;">
          <div class="<?= $abdosColor ?>" style="width:<?= $abdosPourcent ?>%;height:100%;"></div>
        </div>
      </div>

      <!-- Formulaires -->
      <div class="dashboard-card">
        <h2 style="text-align:center;margin-bottom:.75rem;">Ajouter des Pompes</h2>
        <form method="POST" class="space-y-4">
          <input type="number" name="pompe" min="1" placeholder="Nombre de pompes" required class="w-full p-3 rounded-lg bg-gray-800 text-white focus:outline-none">
          <button type="submit" class="w-full bg-purple-700 hover:bg-purple-900 text-white font-bold py-3 px-4 rounded-lg">Ajouter</button>
        </form>
      </div>

      <div class="dashboard-card">
        <h2 style="text-align:center;margin-bottom:.75rem;">Ajouter des Abdos</h2>
        <form method="POST" class="space-y-4">
          <input type="number" name="abdos" min="1" placeholder="Nombre d'abdos" required class="w-full p-3 rounded-lg bg-gray-800 text-white focus:outline-none">
          <button type="submit" class="w-full bg-purple-700 hover:bg-purple-900 text-white font-bold py-3 px-4 rounded-lg">Ajouter</button>
        </form>
      </div>
    </section>

    <!-- Colonne aside (desktop) -->
    <aside>
      <div class="dashboard-card">
        <h2 style="text-align:center;margin-bottom:.5rem;">Pompes aujourd'hui : <?= $pompeJour ?></h2>
        <form method="POST">
          <button name="reset_pompe" type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg">Réinitialiser Pompes</button>
        </form>
      </div>

      <div class="dashboard-card">
        <h2 style="text-align:center;margin-bottom:.5rem;">Abdos aujourd'hui : <?= $abdosJour ?></h2>
        <form method="POST">
          <button name="reset_abdos" type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg">Réinitialiser Abdos</button>
        </form>
      </div>
    </aside>
  </main>
</body>
</html>
