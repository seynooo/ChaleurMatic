<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php'); exit;
}

$pdo = new PDO("mysql:host=localhost;dbname=chaleurmatic;charset=utf8", 'admin', 'admin', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$pdo->exec("CREATE TABLE IF NOT EXISTS blocked_slots (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    type         ENUM('date','weekday','creneau_global') NOT NULL,
    date_value   VARCHAR(10) DEFAULT NULL,
    weekday      TINYINT    DEFAULT NULL,
    creneau      VARCHAR(50) DEFAULT NULL
)");

// ── Actions AJAX ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_weekday') {
        $day = (int)$_POST['day'];
        $exists = $pdo->prepare("SELECT id FROM blocked_slots WHERE type='weekday' AND weekday=?");
        $exists->execute([$day]);
        if ($exists->fetch()) {
            $pdo->prepare("DELETE FROM blocked_slots WHERE type='weekday' AND weekday=?")->execute([$day]);
            echo json_encode(['status' => 'unblocked']);
        } else {
            $pdo->prepare("INSERT INTO blocked_slots (type, weekday) VALUES ('weekday', ?)")->execute([$day]);
            echo json_encode(['status' => 'blocked']);
        }
        exit;
    }

    if ($action === 'toggle_creneau_global') {
        $creneau = $_POST['creneau'];
        $exists = $pdo->prepare("SELECT id FROM blocked_slots WHERE type='creneau_global' AND creneau=?");
        $exists->execute([$creneau]);
        if ($exists->fetch()) {
            $pdo->prepare("DELETE FROM blocked_slots WHERE type='creneau_global' AND creneau=?")->execute([$creneau]);
            echo json_encode(['status' => 'unblocked']);
        } else {
            $pdo->prepare("INSERT INTO blocked_slots (type, creneau) VALUES ('creneau_global', ?)")->execute([$creneau]);
            echo json_encode(['status' => 'blocked']);
        }
        exit;
    }

    if ($action === 'block_date') {
        $date    = $_POST['date']    ?? '';
        $creneau = $_POST['creneau'] ?? ''; // '' = journée entière, 'Matin' ou 'Après-midi'

        if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            echo json_encode(['error' => 'Date invalide']); exit;
        }

        $creneauVal = $creneau ?: null;

        // Vérifier si ce blocage exact existe déjà
        $check = $pdo->prepare("SELECT id FROM blocked_slots WHERE type='date' AND date_value=? AND creneau<=>?");
        $check->execute([$date, $creneauVal]);
        if ($check->fetch()) {
            echo json_encode(['error' => 'Déjà bloqué']); exit;
        }

        // Si on bloque toute la journée, supprimer les blocages partiels existants
        if (!$creneauVal) {
            $pdo->prepare("DELETE FROM blocked_slots WHERE type='date' AND date_value=?")->execute([$date]);
        }

        $stmt = $pdo->prepare("INSERT INTO blocked_slots (type, date_value, creneau) VALUES ('date', ?, ?)");
        $stmt->execute([$date, $creneauVal]);
        echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
        exit;
    }

    if ($action === 'unblock') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM blocked_slots WHERE id=?")->execute([$id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    echo json_encode(['error' => 'Action inconnue']); exit;
}

// ── Lecture des données ──
$rows = $pdo->query("SELECT * FROM blocked_slots ORDER BY type, weekday, date_value")->fetchAll(PDO::FETCH_ASSOC);

$blockedWeekdays  = [];
$blockedCreneaux  = [];
$blockedDates     = [];

foreach ($rows as $r) {
    if ($r['type'] === 'weekday')        $blockedWeekdays[]  = (int)$r['weekday'];
    if ($r['type'] === 'creneau_global') $blockedCreneaux[]  = $r['creneau'];
    if ($r['type'] === 'date')           $blockedDates[]     = $r;
}

$JOURS   = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'];
$JOURS_S = ['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Disponibilités – ChaleurMatic Admin</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #0a0a0a; font-family: 'Segoe UI', Arial, sans-serif; color: #f5f0eb; min-height: 100vh; }

    .header {
      background: #111; border-bottom: 1px solid #222;
      padding: 16px 30px; display: flex; align-items: center; justify-content: space-between;
    }
    .logo { font-size: 20px; font-weight: 900; }
    .logo em { color: #e8185a; font-style: normal; }
    .nav-links { display: flex; gap: 10px; align-items: center; }
    .nav-links a { color: #777; font-size: 13px; text-decoration: none; padding: 8px 14px; border: 1px solid #333; border-radius: 6px; transition: all .2s; }
    .nav-links a:hover { color: #f5f0eb; border-color: #555; }
    .nav-links a.logout:hover { color: #e8185a; border-color: #e8185a; }

    .container { max-width: 860px; margin: 0 auto; padding: 40px 20px 80px; }
    h1 { font-size: 22px; font-weight: 700; margin-bottom: 8px; }
    .subtitle { font-size: 13px; color: #555; margin-bottom: 36px; }

    .section { background: #111; border: 1px solid #222; border-radius: 12px; padding: 26px 28px; margin-bottom: 24px; }
    .section-title { font-size: 11px; color: #555; letter-spacing: .12em; text-transform: uppercase; margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }
    .section-title span { font-size: 15px; }

    /* ── Jours de la semaine ── */
    .days-grid { display: flex; gap: 10px; flex-wrap: wrap; }
    .day-btn {
      display: flex; flex-direction: column; align-items: center; gap: 4px;
      background: #1a1a1a; border: 1px solid #333; border-radius: 10px;
      padding: 14px 18px; cursor: pointer; transition: all .2s; min-width: 70px;
      font-family: inherit;
    }
    .day-btn .day-short { font-size: 13px; font-weight: 700; color: #f5f0eb; }
    .day-btn .day-full  { font-size: 10px; color: #555; }
    .day-btn:hover { border-color: #555; }
    .day-btn.blocked {
      background: rgba(232,24,90,.1); border-color: #e8185a;
    }
    .day-btn.blocked .day-short { color: #e8185a; }
    .day-btn.blocked .day-full  { color: rgba(232,24,90,.6); }
    .day-btn .status-dot {
      width: 6px; height: 6px; border-radius: 50%;
      background: #2a2a2a; margin-top: 2px; transition: background .2s;
    }
    .day-btn.blocked .status-dot { background: #e8185a; }

    /* ── Créneaux globaux ── */
    .creneau-btns { display: flex; gap: 14px; }
    .cr-btn {
      display: flex; align-items: center; gap: 12px;
      background: #1a1a1a; border: 1px solid #333; border-radius: 10px;
      padding: 16px 22px; cursor: pointer; transition: all .2s; flex: 1;
      font-family: inherit;
    }
    .cr-btn .cr-icon { font-size: 22px; }
    .cr-btn .cr-info { text-align: left; }
    .cr-btn .cr-info strong { display: block; font-size: 14px; color: #f5f0eb; }
    .cr-btn .cr-info small  { font-size: 11px; color: #555; }
    .cr-btn .cr-toggle {
      margin-left: auto;
      width: 40px; height: 22px; border-radius: 11px;
      background: #2a2a2a; border: 1px solid #333; position: relative; transition: background .2s;
    }
    .cr-toggle::after {
      content: ''; position: absolute; top: 3px; left: 3px;
      width: 14px; height: 14px; border-radius: 50%; background: #555; transition: all .2s;
    }
    .cr-btn.blocked { background: rgba(232,24,90,.08); border-color: #e8185a; }
    .cr-btn.blocked .cr-info strong { color: #e8185a; }
    .cr-btn.blocked .cr-toggle { background: #e8185a; border-color: #e8185a; }
    .cr-btn.blocked .cr-toggle::after { left: 22px; background: #fff; }

    /* ── Dates spécifiques — layout ── */
    .date-section-grid { display: grid; grid-template-columns: auto 1fr; gap: 28px; align-items: start; }
    @media(max-width:700px) { .date-section-grid { grid-template-columns: 1fr; } }

    /* Calendrier admin */
    .admin-cal {
      background: #161616; border: 1px solid #2a2a2a; border-radius: 12px;
      padding: 16px; user-select: none; width: 280px;
    }
    .admin-cal-header {
      display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;
    }
    .admin-cal-month {
      font-size: 14px; font-weight: 700; color: #f5f0eb; letter-spacing: .04em; text-transform: capitalize;
    }
    .admin-cal-nav {
      background: #1a1a1a; border: 1px solid #333; color: #777;
      border-radius: 6px; width: 28px; height: 28px; font-size: 15px;
      cursor: pointer; display: flex; align-items: center; justify-content: center;
      transition: border-color .2s, color .2s; padding: 0; font-family: inherit;
    }
    .admin-cal-nav:hover { border-color: #e8185a; color: #f5f0eb; }
    .admin-cal-days-hdr {
      display: grid; grid-template-columns: repeat(7,1fr);
      text-align: center; font-size: 10px; letter-spacing: .06em; text-transform: uppercase;
      color: #444; margin-bottom: 6px; gap: 2px;
    }
    .admin-cal-grid { display: grid; grid-template-columns: repeat(7,1fr); gap: 3px; }
    .admin-cal-cell {
      aspect-ratio: 1; display: flex; align-items: center; justify-content: center;
      border-radius: 6px; font-size: 12px; cursor: pointer; color: #888;
      border: 1px solid transparent; transition: all .15s;
    }
    .admin-cal-cell:hover:not(.acc-past):not(.acc-empty) {
      background: rgba(232,24,90,.15); border-color: #e8185a; color: #f5f0eb;
    }
    .admin-cal-cell.acc-past   { color: #2a2a2a; cursor: default; }
    .admin-cal-cell.acc-empty  { cursor: default; }
    .admin-cal-cell.acc-today  { border-color: rgba(232,24,90,.5); color: #e8185a; font-weight: 700; }
    .admin-cal-cell.acc-selected {
      background: #e8185a; color: #fff; font-weight: 700;
      border-color: #e8185a; box-shadow: 0 2px 10px rgba(232,24,90,.5);
    }
    .admin-cal-cell.acc-already-blocked {
      background: rgba(232,24,90,.08); border-color: rgba(232,24,90,.2);
      color: #e8185a; text-decoration: line-through; cursor: default; font-size: 11px;
    }
    .admin-cal-selected-label {
      text-align: center; font-size: 11px; color: #555; margin-top: 10px; min-height: 1em;
    }
    .admin-cal-selected-label.has-date { color: #e8185a; font-weight: 600; }

    /* Panneau droite */
    .date-panel-right {}
    .date-panel-right .dp-label {
      font-size: 11px; color: #555; text-transform: uppercase; letter-spacing: .1em; margin-bottom: 10px;
    }
    .creneau-type-grid { display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px; }
    .creneau-type-btn {
      display: flex; align-items: center; gap: 12px;
      background: #1a1a1a; border: 1px solid #2a2a2a; border-radius: 8px;
      padding: 12px 16px; cursor: pointer; transition: all .2s; font-family: inherit; text-align: left;
    }
    .creneau-type-btn .ct-icon { font-size: 18px; }
    .creneau-type-btn .ct-info strong { display: block; font-size: 13px; color: #ccc; }
    .creneau-type-btn .ct-info small { font-size: 11px; color: #555; }
    .creneau-type-btn:hover { border-color: #e8185a; }
    .creneau-type-btn.ct-selected { background: rgba(232,24,90,.1); border-color: #e8185a; }
    .creneau-type-btn.ct-selected .ct-info strong { color: #f5f0eb; }
    .ct-check { margin-left: auto; width: 18px; height: 18px; border-radius: 50%; border: 2px solid #333; transition: all .2s; }
    .creneau-type-btn.ct-selected .ct-check { background: #e8185a; border-color: #e8185a; }
    .btn-add {
      width: 100%; background: #e8185a; color: #fff; border: none; border-radius: 8px;
      padding: 12px; font-size: 13px; font-weight: 700; cursor: pointer; transition: background .2s;
    }
    .btn-add:hover { background: #c91450; }
    .btn-add:disabled { background: #2a2a2a; color: #444; cursor: not-allowed; }

    /* ── Liste des dates bloquées ── */
    .blocked-list { display: flex; flex-direction: column; gap: 8px; }
    .blocked-item {
      display: flex; align-items: center; justify-content: space-between;
      background: #1a1a1a; border: 1px solid #2a2a2a; border-radius: 8px;
      padding: 12px 16px; font-size: 13px;
    }
    .blocked-item .bi-date { color: #f5f0eb; font-weight: 600; }
    .blocked-item .bi-type {
      font-size: 11px; padding: 3px 10px; border-radius: 20px;
      background: rgba(232,24,90,.12); color: #e8185a; border: 1px solid rgba(232,24,90,.3);
    }
    .btn-unblock {
      background: transparent; border: 1px solid #333; color: #666; border-radius: 6px;
      padding: 5px 12px; font-size: 12px; cursor: pointer; transition: all .2s; font-family: inherit;
    }
    .btn-unblock:hover { border-color: #e8185a; color: #e8185a; }

    .empty-state { color: #333; font-size: 13px; padding: 16px 0; text-align: center; }

    .toast {
      position: fixed; bottom: 30px; right: 30px;
      background: #111; border: 1px solid #333; border-radius: 10px;
      padding: 14px 20px; font-size: 13px; color: #f5f0eb;
      transform: translateY(80px); opacity: 0; transition: all .3s; z-index: 9999;
    }
    .toast.show { transform: translateY(0); opacity: 1; }
    .toast.success { border-color: rgba(34,197,94,.4); color: #22c55e; }

    @media (max-width: 768px) {
      .container { padding: 24px 14px 60px; }
      .header { padding: 12px 16px; flex-wrap: wrap; gap: 8px; }
      .nav-links { flex-wrap: wrap; gap: 6px; }
      .nav-links a { font-size: 12px; padding: 6px 10px; }
      .section { padding: 18px 16px; }
      .days-grid { gap: 6px; }
      .day-btn { min-width: 56px; padding: 10px 12px; }
      .creneau-btns { flex-direction: column; }
      .date-section-grid { grid-template-columns: 1fr; }
      .admin-cal { width: 100%; }
      .creneau-type-grid { gap: 6px; }
      .blocked-item { flex-wrap: wrap; gap: 8px; }
      .blocked-item .bi-date { flex: 1 1 100%; }
    }
    @media (max-width: 480px) {
      .days-grid { justify-content: space-between; }
      .day-btn { min-width: calc(25% - 6px); flex: 1; }
      .section-title { font-size: 10px; }
    }
  </style>
</head>
<body>

<div class="header">
  <div class="logo">🔥 Chaleur<em>Matic</em> <span style="font-size:13px;color:#555;font-weight:400;margin-left:8px">Admin</span></div>
  <div class="nav-links">
    <a href="dashboard.php">← Dashboard</a>
    <a href="profil.php">⚙️ Mon profil</a>
    <a href="logout.php" class="logout">Déconnexion</a>
  </div>
</div>

<div class="container">
  <h1>📅 Disponibilités</h1>
  <p class="subtitle">Gérez les jours, créneaux et dates indisponibles. Les clients ne pourront pas sélectionner ces plages.</p>

  <!-- ── Jours de la semaine ── -->
  <div class="section">
    <div class="section-title"><span>📆</span> Jours récurrents indisponibles</div>
    <div class="days-grid">
      <?php foreach ($JOURS as $i => $jour): ?>
        <button class="day-btn <?= in_array($i, $blockedWeekdays) ? 'blocked' : '' ?>"
                data-day="<?= $i ?>" onclick="toggleWeekday(this)">
          <span class="day-short"><?= $JOURS_S[$i] ?></span>
          <span class="day-full"><?= $jour ?></span>
          <span class="status-dot"></span>
        </button>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ── Créneaux globaux ── -->
  <div class="section">
    <div class="section-title"><span>🕐</span> Créneaux globalement indisponibles</div>
    <div class="creneau-btns">
      <button class="cr-btn <?= in_array('Matin', $blockedCreneaux) ? 'blocked' : '' ?>"
              data-creneau="Matin" onclick="toggleCreneau(this)">
        <span class="cr-icon">🌅</span>
        <div class="cr-info">
          <strong>Matin</strong>
          <small>8h – 12h · Tous les jours</small>
        </div>
        <div class="cr-toggle"></div>
      </button>
      <button class="cr-btn <?= in_array('Après-midi', $blockedCreneaux) ? 'blocked' : '' ?>"
              data-creneau="Après-midi" onclick="toggleCreneau(this)">
        <span class="cr-icon">☀️</span>
        <div class="cr-info">
          <strong>Après-midi</strong>
          <small>12h – 18h · Tous les jours</small>
        </div>
        <div class="cr-toggle"></div>
      </button>
    </div>
  </div>

  <!-- ── Dates spécifiques ── -->
  <div class="section">
    <div class="section-title"><span>🚫</span> Bloquer une date spécifique</div>

    <div class="date-section-grid">
      <!-- Calendrier visuel -->
      <div class="admin-cal">
        <div class="admin-cal-header">
          <button class="admin-cal-nav" id="adm-prev">‹</button>
          <span class="admin-cal-month" id="adm-month-label"></span>
          <button class="admin-cal-nav" id="adm-next">›</button>
        </div>
        <div class="admin-cal-days-hdr">
          <span>Lun</span><span>Mar</span><span>Mer</span><span>Jeu</span><span>Ven</span><span>Sam</span><span>Dim</span>
        </div>
        <div class="admin-cal-grid" id="adm-cal-grid"></div>
        <input type="hidden" id="input-date">
        <div class="admin-cal-selected-label" id="adm-selected-label">Cliquez sur une date</div>
      </div>

      <!-- Options + liste -->
      <div class="date-panel-right">
        <div class="dp-label">Que souhaitez-vous bloquer ?</div>
        <div class="creneau-type-grid">
          <button class="creneau-type-btn ct-selected" data-val="" onclick="selectType(this)">
            <span class="ct-icon">🚫</span>
            <div class="ct-info"><strong>Journée entière</strong><small>Aucun rendez-vous ce jour</small></div>
            <div class="ct-check"></div>
          </button>
          <button class="creneau-type-btn" data-val="Matin" onclick="selectType(this)">
            <span class="ct-icon">🌅</span>
            <div class="ct-info"><strong>Matin seulement</strong><small>8h – 12h indisponible</small></div>
            <div class="ct-check"></div>
          </button>
          <button class="creneau-type-btn" data-val="Après-midi" onclick="selectType(this)">
            <span class="ct-icon">☀️</span>
            <div class="ct-info"><strong>Après-midi seulement</strong><small>12h – 18h indisponible</small></div>
            <div class="ct-check"></div>
          </button>
        </div>
        <button class="btn-add" id="btn-add-date" onclick="blockDate()" disabled>Choisir une date d'abord</button>
      </div>
    </div>

    <hr style="border:none;border-top:1px solid #1e1e1e;margin:24px 0">

    <div class="section-title" style="margin-bottom:14px"><span>📋</span> Dates bloquées</div>
    <div class="blocked-list" id="blocked-list">
      <?php if (empty($blockedDates)): ?>
        <div class="empty-state" id="empty-state">Aucune date spécifique bloquée</div>
      <?php else: ?>
        <?php foreach ($blockedDates as $bd): ?>
          <?php
            $ts = strtotime($bd['date_value']);
            $dateLabel = date('d/m/Y', $ts);
            $jourLabel = $JOURS[date('N', $ts) - 1];
            $typeLabel = $bd['creneau'] ?: 'Journée entière';
          ?>
          <div class="blocked-item" id="bi-<?= $bd['id'] ?>">
            <span class="bi-date">📅 <?= $jourLabel ?> <?= $dateLabel ?></span>
            <span class="bi-type"><?= htmlspecialchars($typeLabel) ?></span>
            <button class="btn-unblock" onclick="unblock(<?= $bd['id'] ?>, this)">Débloquer</button>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
const JOURS_FR  = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'];
const MOIS_FR   = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];

/* ── Calendrier admin ── */
const todayAdm  = new Date(); todayAdm.setHours(0,0,0,0);
let admYear     = todayAdm.getFullYear();
let admMonth    = todayAdm.getMonth();
let admSelected = null; // ISO string 'YYYY-MM-DD'

// Dates déjà bloquées (pour les marquer visuellement)
const alreadyBlocked = <?= json_encode(array_column($blockedDates, 'date_value')) ?>;

function pad2(n) { return String(n).padStart(2,'0'); }

function renderAdmCal() {
  const grid  = document.getElementById('adm-cal-grid');
  const label = document.getElementById('adm-month-label');
  if (!grid || !label) return;

  label.textContent = MOIS_FR[admMonth] + ' ' + admYear;

  const firstDay    = new Date(admYear, admMonth, 1).getDay();
  const startCol    = firstDay === 0 ? 6 : firstDay - 1;
  const daysInMonth = new Date(admYear, admMonth + 1, 0).getDate();

  grid.innerHTML = '';

  for (let i = 0; i < startCol; i++) {
    const e = document.createElement('div');
    e.className = 'admin-cal-cell acc-empty';
    grid.appendChild(e);
  }

  for (let d = 1; d <= daysInMonth; d++) {
    const cell     = document.createElement('div');
    cell.className = 'admin-cal-cell';
    cell.textContent = d;

    const cellDate = new Date(admYear, admMonth, d);
    cellDate.setHours(0,0,0,0);
    const iso = admYear + '-' + pad2(admMonth + 1) + '-' + pad2(d);

    if (cellDate < todayAdm) {
      cell.classList.add('acc-past');
    } else if (alreadyBlocked.includes(iso)) {
      cell.classList.add('acc-already-blocked');
      cell.title = 'Déjà bloqué';
    } else {
      if (cellDate.getTime() === todayAdm.getTime()) cell.classList.add('acc-today');
      if (admSelected === iso) cell.classList.add('acc-selected');

      cell.addEventListener('click', function () {
        admSelected = iso;
        document.getElementById('input-date').value = iso;

        const lbl = document.getElementById('adm-selected-label');
        const jourIdx = (cellDate.getDay() + 6) % 7;
        lbl.textContent = JOURS_FR[jourIdx] + ' ' + pad2(d) + '/' + pad2(admMonth+1) + '/' + admYear;
        lbl.classList.add('has-date');

        const btn = document.getElementById('btn-add-date');
        btn.disabled = false;
        btn.textContent = '🚫 Bloquer cette date';

        renderAdmCal();
      });
    }

    grid.appendChild(cell);
  }
}

document.getElementById('adm-prev').addEventListener('click', function () {
  admMonth--;
  if (admMonth < 0) { admMonth = 11; admYear--; }
  const ny = todayAdm.getFullYear(), nm = todayAdm.getMonth();
  if (admYear < ny || (admYear === ny && admMonth < nm)) { admYear = ny; admMonth = nm; }
  renderAdmCal();
});
document.getElementById('adm-next').addEventListener('click', function () {
  admMonth++;
  if (admMonth > 11) { admMonth = 0; admYear++; }
  renderAdmCal();
});

renderAdmCal();

/* ── Sélection du type de blocage ── */
let selectedType = '';

function selectType(btn) {
  document.querySelectorAll('.creneau-type-btn').forEach(b => b.classList.remove('ct-selected'));
  btn.classList.add('ct-selected');
  selectedType = btn.dataset.val;
}

/* ── Toast ── */
function showToast(msg, type = 'success') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = 'toast show ' + type;
  setTimeout(() => t.className = 'toast', 2800);
}

function post(data) {
  return fetch('disponibilites.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams(data)
  }).then(r => r.json());
}

function toggleWeekday(btn) {
  const day = btn.dataset.day;
  post({action: 'toggle_weekday', day})
    .then(res => {
      const blocked = res.status === 'blocked';
      btn.classList.toggle('blocked', blocked);
      showToast(blocked
        ? '🚫 ' + JOURS_FR[day] + ' bloqué'
        : '✅ ' + JOURS_FR[day] + ' débloqué'
      );
    });
}

function toggleCreneau(btn) {
  const creneau = btn.dataset.creneau;
  post({action: 'toggle_creneau_global', creneau})
    .then(res => {
      const blocked = res.status === 'blocked';
      btn.classList.toggle('blocked', blocked);
      showToast(blocked
        ? '🚫 Créneau ' + creneau + ' bloqué globalement'
        : '✅ Créneau ' + creneau + ' débloqué'
      );
    });
}

function blockDate() {
  const date    = document.getElementById('input-date').value;
  const creneau = selectedType;

  if (!date) { showToast('⚠️ Choisissez une date', ''); return; }

  post({action: 'block_date', date, creneau})
    .then(res => {
      if (res.error) { showToast('⚠️ ' + res.error, ''); return; }

      const list = document.getElementById('blocked-list');
      const empty = document.getElementById('empty-state');
      if (empty) empty.remove();

      const [y, m, d] = date.split('-');
      const jsDate  = new Date(y, m - 1, d);
      const jourIdx = (jsDate.getDay() + 6) % 7;
      const dateLabel = d + '/' + m + '/' + y;
      const typeLabel = creneau || 'Journée entière';

      const newId = res.id;
      const div = document.createElement('div');
      div.className = 'blocked-item';
      div.id = 'bi-' + newId;
      div.innerHTML =
        '<span class="bi-date">📅 ' + JOURS_FR[jourIdx] + ' ' + dateLabel + '</span>' +
        '<span class="bi-type">' + typeLabel + '</span>' +
        '<button class="btn-unblock" onclick="unblock(' + newId + ', this)">Débloquer</button>';
      list.appendChild(div);

      // Marquer dans le calendrier comme déjà bloqué
      if (!alreadyBlocked.includes(date)) alreadyBlocked.push(date);
      admSelected = null;
      document.getElementById('input-date').value = '';
      document.getElementById('adm-selected-label').textContent = 'Cliquez sur une date';
      document.getElementById('adm-selected-label').classList.remove('has-date');
      const btnAdd = document.getElementById('btn-add-date');
      btnAdd.disabled = true;
      btnAdd.textContent = 'Choisir une date d\'abord';
      renderAdmCal();
      showToast('🚫 ' + dateLabel + ' — ' + typeLabel);
    });
}

function unblock(id, btn) {
  post({action: 'unblock', id})
    .then(() => {
      const item = document.getElementById('bi-' + id);
      if (item) item.remove();
      const list = document.getElementById('blocked-list');
      if (!list.querySelector('.blocked-item')) {
        const empty = document.createElement('div');
        empty.className = 'empty-state';
        empty.id = 'empty-state';
        empty.textContent = 'Aucune date spécifique bloquée';
        list.appendChild(empty);
      }
      showToast('✅ Date débloquée');
    });
}
</script>
</body>
</html>
