<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$db_host = '127.0.0.1';
$db_name = 'chaleurmatic';
$db_user = 'admin';
$db_pass = 'admin';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die('Erreur de connexion à la base de données.');
}

$phpmailer_disponible = file_exists('/var/www/html/vendor/autoload.php');
if ($phpmailer_disponible) {
    require '/var/www/html/vendor/autoload.php';
}

function envoyerConfirmation($rdv, $date_confirmee) {
    global $phpmailer_disponible;
    if (!$rdv['email']) return;
    if (!$phpmailer_disponible) return;

    $date_formatee = date('d/m/Y à H:i', strtotime($date_confirmee));

    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'oddisafwan@gmail.com';
        $mail->Password   = 'hwuxjxggsqvjesgm';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom('oddisafwan@gmail.com', 'ChaleurMatic');
        $mail->addAddress($rdv['email'], $rdv['prenom'] . ' ' . $rdv['nom']);
        $mail->Subject  = 'Votre RDV est confirmé – ChaleurMatic';
        $mail->isHTML(true);
        $mail->Body = '
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#0a0a0a;font-family:Arial,sans-serif">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#0a0a0a;padding:40px 20px">
    <tr><td align="center">
      <table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#111;border-radius:12px;overflow:hidden;border:1px solid #222">

        <!-- Header -->
        <tr>
          <td style="background:linear-gradient(135deg,#1a0a00,#2d0f00);padding:35px 30px;text-align:center;border-bottom:2px solid #22c55e">
            <div style="font-size:40px;margin-bottom:8px">✅</div>
            <div style="font-size:24px;font-weight:900;color:#f5f0eb">Chaleur<em style="color:#e8185a">Matic</em></div>
            <div style="font-size:12px;color:#7a7580;letter-spacing:.15em;text-transform:uppercase;margin-top:6px">Rendez-vous confirmé</div>
          </td>
        </tr>

        <!-- Message principal -->
        <tr>
          <td style="padding:30px 30px 10px;text-align:center">
            <p style="color:#f5f0eb;font-size:18px;font-weight:700;margin-bottom:8px">Bonjour ' . htmlspecialchars($rdv['prenom']) . ' !</p>
            <p style="color:#999;font-size:14px;margin-bottom:0">Votre rendez-vous a été confirmé par notre équipe.</p>
          </td>
        </tr>

        <!-- Date confirmée -->
        <tr>
          <td style="padding:20px 30px">
            <div style="background:#0d2010;border:1px solid rgba(34,197,94,.3);border-radius:10px;padding:20px;text-align:center">
              <div style="font-size:12px;color:#22c55e;letter-spacing:.1em;text-transform:uppercase;margin-bottom:8px">📅 Date de votre intervention</div>
              <div style="font-size:22px;font-weight:900;color:#f5f0eb">' . $date_formatee . '</div>
            </div>
          </td>
        </tr>

        <!-- Récapitulatif -->
        <tr>
          <td style="padding:0 30px 20px">
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#1a1a1a;border-radius:8px;overflow:hidden">
              <tr><td colspan="2" style="padding:12px 16px;font-size:11px;color:#777;letter-spacing:.08em;text-transform:uppercase;border-bottom:1px solid #222">Récapitulatif</td></tr>
              <tr>
                <td style="padding:10px 16px;color:#777;font-size:13px;width:130px">Service</td>
                <td style="padding:10px 16px;color:#f5f0eb;font-size:13px;font-weight:600">' . htmlspecialchars($rdv['service']) . '</td>
              </tr>
              <tr style="border-top:1px solid #222">
                <td style="padding:10px 16px;color:#777;font-size:13px">Adresse</td>
                <td style="padding:10px 16px;color:#f5f0eb;font-size:13px">' . htmlspecialchars($rdv['adresse']) . '</td>
              </tr>
              ' . ($rdv['type_chaudiere'] || $rdv['marque'] ? '
              <tr style="border-top:1px solid #222">
                <td style="padding:10px 16px;color:#777;font-size:13px">Chaudière</td>
                <td style="padding:10px 16px;color:#f5f0eb;font-size:13px">' . htmlspecialchars($rdv['type_chaudiere'] . ($rdv['marque'] ? ' · ' . $rdv['marque'] : '')) . '</td>
              </tr>' : '') . '
            </table>
          </td>
        </tr>

        <!-- Contact -->
        <tr>
          <td style="padding:0 30px 30px;text-align:center">
            <p style="color:#777;font-size:13px;margin-bottom:16px">Une question ? Contactez-nous directement :</p>
            <a href="tel:+32488274228" style="display:inline-block;background:#e8185a;color:#fff;text-decoration:none;font-size:14px;font-weight:700;padding:12px 28px;border-radius:6px">📞 +32 488 27 42 28</a>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background:#0d0d0d;padding:16px 30px;text-align:center;border-top:1px solid #222">
            <div style="font-size:12px;color:#555">© ChaleurMatic · Chauffagiste agréé Bruxelles</div>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>';
        $mail->send();
    } catch (Exception $e) {
        // Email échoue silencieusement
    }
}

// ── Actions ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);

    if (isset($_POST['confirmer']) && $id) {
        $date_confirmee = $_POST['date_confirmee'] ?? '';
        // Récupérer les infos du RDV pour l'email
        $rdv = $pdo->prepare("SELECT * FROM demandes_rdv WHERE id = ?");
        $rdv->execute([$id]);
        $rdv_data = $rdv->fetch(PDO::FETCH_ASSOC);
        // Mettre à jour le statut
        $pdo->prepare("UPDATE demandes_rdv SET statut='confirme', date_confirmee=? WHERE id=?")->execute([$date_confirmee, $id]);
        // Envoyer l'email de confirmation au client
        if ($rdv_data) envoyerConfirmation($rdv_data, $date_confirmee);
    }
    if (isset($_POST['terminer']) && $id) {
        $pdo->prepare("UPDATE demandes_rdv SET statut='termine' WHERE id=?")->execute([$id]);
    }
    if (isset($_POST['annuler']) && $id) {
        $pdo->prepare("UPDATE demandes_rdv SET statut='annule' WHERE id=?")->execute([$id]);
    }
    if (isset($_POST['supprimer']) && $id) {
        $pdo->prepare("DELETE FROM demandes_rdv WHERE id=?")->execute([$id]);
    }
    header('Location: dashboard.php');
    exit;
}

// ── Filtre ────────────────────────────────────────────────────
$filtre = $_GET['filtre'] ?? 'en_attente';
if (!in_array($filtre, ['en_attente','confirme','termine','annule','tous'])) $filtre = 'en_attente';

$where = $filtre !== 'tous' ? "WHERE statut = '$filtre'" : '';
$rdvs = $pdo->query("SELECT * FROM demandes_rdv $where ORDER BY date_envoi DESC")->fetchAll(PDO::FETCH_ASSOC);

// ── Compteurs ─────────────────────────────────────────────────
$counts = $pdo->query("SELECT statut, COUNT(*) as n FROM demandes_rdv GROUP BY statut")->fetchAll(PDO::FETCH_KEY_PAIR);
$total_attente  = $counts['en_attente'] ?? 0;
$total_confirme = $counts['confirme']   ?? 0;
$total_termine  = $counts['termine']    ?? 0;
$total_annule   = $counts['annule']     ?? 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard – ChaleurMatic</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #0a0a0a; font-family: 'Segoe UI', Arial, sans-serif; color: #f5f0eb; min-height: 100vh; }

    /* ── Header ── */
    .header {
      background: #111;
      border-bottom: 1px solid #222;
      padding: 16px 30px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .logo { font-size: 20px; font-weight: 900; }
    .logo em { color: #e8185a; font-style: normal; }
    .logout { color: #777; font-size: 13px; text-decoration: none; padding: 8px 16px; border: 1px solid #333; border-radius: 6px; transition: all .2s; }
    .logout:hover { color: #e8185a; border-color: #e8185a; }

    /* ── Layout ── */
    .container { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }

    /* ── Stats ── */
    .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 30px; }
    .stat-card { background: #111; border: 1px solid #222; border-radius: 12px; padding: 20px 24px; }
    .stat-card .num { font-size: 36px; font-weight: 900; line-height: 1; }
    .stat-card .lbl { font-size: 12px; color: #777; margin-top: 6px; letter-spacing: .05em; text-transform: uppercase; }
    .stat-card.attente .num  { color: #f97316; }
    .stat-card.confirme .num { color: #22c55e; }
    .stat-card.termine .num  { color: #6b7280; }
    .stat-card.annule .num   { color: #e8185a; }

    /* ── Filtres ── */
    .filtres { display: flex; gap: 10px; margin-bottom: 24px; flex-wrap: wrap; }
    .filtre-btn { padding: 8px 20px; border-radius: 20px; border: 1px solid #333; background: transparent; color: #777; font-size: 13px; cursor: pointer; text-decoration: none; transition: all .2s; }
    .filtre-btn:hover, .filtre-btn.active { background: #e8185a; border-color: #e8185a; color: #fff; }

    /* ── Cards RDV ── */
    .rdv-list { display: flex; flex-direction: column; gap: 16px; }
    .rdv-card { background: #111; border: 1px solid #222; border-radius: 12px; padding: 24px; transition: border-color .2s; }
    .rdv-card:hover { border-color: #333; }
    .rdv-card.statut-confirme  { border-left: 3px solid #22c55e; }
    .rdv-card.statut-termine   { border-left: 3px solid #444; opacity: .7; }
    .rdv-card.statut-en_attente { border-left: 3px solid #f97316; }
    .rdv-card.statut-annule    { border-left: 3px solid #e8185a; opacity: .6; }

    .rdv-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
    .rdv-client { flex: 1; }
    .rdv-client h3 { font-size: 18px; font-weight: 700; margin-bottom: 4px; }
    .rdv-client .service-badge { display: inline-block; background: rgba(232,24,90,.15); color: #e8185a; border: 1px solid rgba(232,24,90,.3); font-size: 12px; font-weight: 600; padding: 3px 12px; border-radius: 20px; margin-bottom: 10px; }
    .rdv-infos { display: flex; flex-wrap: wrap; gap: 12px; }
    .rdv-info { font-size: 13px; color: #999; }
    .rdv-info strong { color: #f5f0eb; }
    .rdv-info a { color: #e8185a; text-decoration: none; }

    .rdv-date-envoi { font-size: 12px; color: #555; white-space: nowrap; }

    .badge { display: inline-block; font-size: 11px; font-weight: 700; padding: 4px 12px; border-radius: 20px; letter-spacing: .05em; text-transform: uppercase; }
    .badge-attente  { background: rgba(249,115,22,.15); color: #f97316; border: 1px solid rgba(249,115,22,.3); }
    .badge-confirme { background: rgba(34,197,94,.15);  color: #22c55e; border: 1px solid rgba(34,197,94,.3); }
    .badge-termine  { background: rgba(107,114,128,.15);color: #6b7280; border: 1px solid rgba(107,114,128,.3); }

    /* ── Chaudière ── */
    .chaudiere-info { margin-top: 14px; padding: 12px 16px; background: #1a1a1a; border-radius: 8px; font-size: 13px; color: #999; }
    .chaudiere-info span { color: #f5f0eb; margin-left: 6px; }

    /* ── Message ── */
    .rdv-message { margin-top: 12px; font-size: 13px; color: #999; font-style: italic; padding-left: 12px; border-left: 2px solid #333; }

    /* ── Actions ── */
    .rdv-actions { margin-top: 18px; display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
    .rdv-actions form { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
    input[type="datetime-local"] {
      background: #1a1a1a; border: 1px solid #333; border-radius: 6px;
      padding: 8px 12px; color: #f5f0eb; font-size: 13px; outline: none;
    }
    input[type="datetime-local"]:focus { border-color: #e8185a; }
    .btn { padding: 8px 18px; border-radius: 6px; border: none; font-size: 13px; font-weight: 600; cursor: pointer; transition: all .2s; }
    .btn-confirmer { background: #22c55e; color: #000; }
    .btn-confirmer:hover { background: #16a34a; }
    .btn-terminer  { background: #374151; color: #f5f0eb; }
    .btn-terminer:hover  { background: #4b5563; }
    .btn-annuler   { background: transparent; color: #f97316; border: 1px solid rgba(249,115,22,.3); }
    .btn-annuler:hover   { background: rgba(249,115,22,.1); }
    .btn-supprimer { background: transparent; color: #e8185a; border: 1px solid rgba(232,24,90,.3); }
    .btn-supprimer:hover { background: rgba(232,24,90,.1); }

    .date-confirmee { font-size: 13px; color: #22c55e; }

    /* ── Empty ── */
    .empty { text-align: center; padding: 60px 20px; color: #555; }
    .empty .icon { font-size: 48px; margin-bottom: 16px; }

    @media (max-width: 768px) {
      .stats { grid-template-columns: repeat(2, 1fr); gap: 10px; }
      .header { padding: 12px 16px; flex-wrap: wrap; gap: 10px; }
      .header > div:last-child { flex-wrap: wrap; gap: 6px; }
      .logout { font-size: 12px; padding: 6px 10px; }
      .container { padding: 20px 12px; }
      .rdv-top { flex-direction: column; gap: 10px; }
      .rdv-card { padding: 16px; }
      .rdv-actions { flex-direction: column; align-items: stretch; }
      .rdv-actions form { flex-direction: column; align-items: stretch; }
      input[type="datetime-local"] { width: 100%; }
      .btn { width: 100%; text-align: center; padding: 10px; }
      .filtres { gap: 6px; }
      .filtre-btn { font-size: 12px; padding: 6px 14px; }
    }
    @media (max-width: 480px) {
      .stats { grid-template-columns: repeat(2, 1fr); }
      .stat-card .num { font-size: 28px; }
      .rdv-client h3 { font-size: 16px; }
      .logo span { display: none; }
    }
  </style>
</head>
<body>

<div class="header">
  <div class="logo">🔥 Chaleur<em>Matic</em> <span style="font-size:13px;color:#555;font-weight:400;margin-left:8px">Admin</span></div>
  <div style="display:flex;gap:10px">
    <a href="disponibilites.php" class="logout">📅 Disponibilités</a>
    <a href="profil.php" class="logout">⚙️ Mon profil</a>
    <a href="logout.php" class="logout">Déconnexion</a>
  </div>
</div>

<div class="container">

  <!-- Stats -->
  <div class="stats">
    <div class="stat-card attente">
      <div class="num"><?= $total_attente ?></div>
      <div class="lbl">En attente</div>
    </div>
    <div class="stat-card confirme">
      <div class="num"><?= $total_confirme ?></div>
      <div class="lbl">Confirmés</div>
    </div>
    <div class="stat-card termine">
      <div class="num"><?= $total_termine ?></div>
      <div class="lbl">Terminés</div>
    </div>
    <div class="stat-card annule">
      <div class="num"><?= $total_annule ?></div>
      <div class="lbl">Annulés</div>
    </div>
  </div>

  <!-- Filtres -->
  <div class="filtres">
    <a href="?filtre=en_attente" class="filtre-btn <?= $filtre === 'en_attente' ? 'active' : '' ?>">⏳ En attente (<?= $total_attente ?>)</a>
    <a href="?filtre=confirme"   class="filtre-btn <?= $filtre === 'confirme'   ? 'active' : '' ?>">✅ Confirmés (<?= $total_confirme ?>)</a>
    <a href="?filtre=termine"    class="filtre-btn <?= $filtre === 'termine'    ? 'active' : '' ?>">🏁 Terminés (<?= $total_termine ?>)</a>
    <a href="?filtre=annule"     class="filtre-btn <?= $filtre === 'annule'     ? 'active' : '' ?>">❌ Annulés (<?= $total_annule ?>)</a>
    <a href="?filtre=tous"       class="filtre-btn <?= $filtre === 'tous'       ? 'active' : '' ?>">📋 Tous</a>
  </div>

  <!-- Liste RDV -->
  <div class="rdv-list">
    <?php if (empty($rdvs)): ?>
      <div class="empty">
        <div class="icon">📭</div>
        <div>Aucune demande dans cette catégorie.</div>
      </div>
    <?php else: foreach ($rdvs as $r): ?>
      <div class="rdv-card statut-<?= $r['statut'] ?>">
        <div class="rdv-top">
          <div class="rdv-client">
            <div class="service-badge"><?= htmlspecialchars($r['service']) ?></div>
            <h3><?= htmlspecialchars($r['prenom']) ?> <?= htmlspecialchars($r['nom']) ?></h3>
            <div class="rdv-infos">
              <div class="rdv-info">📞 <a href="tel:<?= htmlspecialchars($r['telephone']) ?>"><?= htmlspecialchars($r['telephone']) ?></a></div>
              <?php if ($r['email']): ?><div class="rdv-info">✉️ <a href="mailto:<?= htmlspecialchars($r['email']) ?>"><?= htmlspecialchars($r['email']) ?></a></div><?php endif; ?>
              <div class="rdv-info">📍 <strong><?= htmlspecialchars($r['adresse']) ?></strong></div>
              <?php if ($r['date_souhaitee']): ?><div class="rdv-info">📅 Souhaité : <strong><?= date('d/m/Y', strtotime($r['date_souhaitee'])) ?><?= $r['creneau'] ? ' · ' . htmlspecialchars($r['creneau']) : '' ?></strong></div><?php endif; ?>
            </div>
          </div>
          <div style="text-align:right">
            <?php
              $badge_class = $r['statut'] === 'en_attente' ? 'badge-attente' : ($r['statut'] === 'confirme' ? 'badge-confirme' : ($r['statut'] === 'annule' ? 'badge-attente' : 'badge-termine'));
              $badge_label = $r['statut'] === 'en_attente' ? '⏳ En attente' : ($r['statut'] === 'confirme' ? '✅ Confirmé' : ($r['statut'] === 'annule' ? '❌ Annulé' : '🏁 Terminé'));
            ?>
            <span class="badge <?= $badge_class ?>"><?= $badge_label ?></span>
            <div class="rdv-date-envoi" style="margin-top:8px">Reçu le <?= date('d/m/Y à H:i', strtotime($r['date_envoi'])) ?></div>
            <?php if ($r['date_confirmee']): ?>
              <div class="date-confirmee" style="margin-top:6px">RDV : <?= date('d/m/Y à H:i', strtotime($r['date_confirmee'])) ?></div>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($r['type_chaudiere'] || $r['marque'] || $r['modele'] || $r['annee']): ?>
        <div class="chaudiere-info">
          🔧
          <?php if ($r['type_chaudiere']): ?><span><?= htmlspecialchars($r['type_chaudiere']) ?></span><?php endif; ?>
          <?php if ($r['marque']): ?> · <span><?= htmlspecialchars($r['marque']) ?></span><?php endif; ?>
          <?php if ($r['modele']): ?> · <span><?= htmlspecialchars($r['modele']) ?></span><?php endif; ?>
          <?php if ($r['annee']): ?> · <span><?= htmlspecialchars($r['annee']) ?></span><?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($r['message']): ?>
        <div class="rdv-message"><?= htmlspecialchars($r['message']) ?></div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="rdv-actions">
          <?php if ($r['statut'] === 'en_attente'): ?>
            <form method="POST">
              <input type="hidden" name="id" value="<?= $r['id'] ?>">
              <input type="datetime-local" name="date_confirmee" required
                value="<?= $r['date_souhaitee'] ? date('Y-m-d', strtotime($r['date_souhaitee'])) . 'T08:00' : '' ?>">
              <button type="submit" name="confirmer" class="btn btn-confirmer">✅ Confirmer le RDV</button>
            </form>
          <?php endif; ?>

          <?php if ($r['statut'] === 'confirme'): ?>
            <form method="POST">
              <input type="hidden" name="id" value="<?= $r['id'] ?>">
              <button type="submit" name="terminer" class="btn btn-terminer">🏁 Marquer comme terminé</button>
            </form>
          <?php endif; ?>

          <?php if (in_array($r['statut'], ['en_attente', 'confirme'])): ?>
            <form method="POST" onsubmit="return confirm('Annuler ce rendez-vous ?')">
              <input type="hidden" name="id" value="<?= $r['id'] ?>">
              <button type="submit" name="annuler" class="btn btn-annuler">❌ Annuler</button>
            </form>
          <?php endif; ?>

          <form method="POST" onsubmit="return confirm('Supprimer cette demande ?')">
            <input type="hidden" name="id" value="<?= $r['id'] ?>">
            <button type="submit" name="supprimer" class="btn btn-supprimer">🗑 Supprimer</button>
          </form>
        </div>

      </div>
    <?php endforeach; endif; ?>
  </div>

</div>
</body>
</html>
