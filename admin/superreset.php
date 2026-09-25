<?php
// ── Clé secrète — ne partage jamais cette URL avec le client ──
define('SECRET_KEY', 'adminkey');

$db_host = '127.0.0.1';
$db_name = 'chaleurmatic';
$db_user = 'admin';
$db_pass = 'admin';

$key = $_GET['key'] ?? '';
if ($key !== SECRET_KEY) {
    http_response_code(404);
    die('Page introuvable.');
}

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die('Erreur de connexion.');
}

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nouveau_mdp   = $_POST['nouveau_mdp']   ?? '';
    $confirmer_mdp = $_POST['confirmer_mdp'] ?? '';

    if (strlen($nouveau_mdp) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif ($nouveau_mdp !== $confirmer_mdp) {
        $error = 'Les mots de passe ne correspondent pas.';
    } else {
        $pdo->prepare("UPDATE admin_config SET password = ?")->execute([password_hash($nouveau_mdp, PASSWORD_DEFAULT)]);
        $success = 'Mot de passe réinitialisé avec succès !';
    }
}

$config = $pdo->query("SELECT username FROM admin_config LIMIT 1")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Super Reset – ChaleurMatic</title>
  <meta name="robots" content="noindex, nofollow">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #0a0a0a; font-family: 'Segoe UI', Arial, sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    .box { background: #111; border: 1px solid #333; border-radius: 16px; padding: 50px 40px; width: 100%; max-width: 420px; text-align: center; }
    .logo { font-size: 26px; font-weight: 900; color: #f5f0eb; margin-bottom: 6px; }
    .logo em { color: #e8185a; font-style: normal; }
    .subtitle { font-size: 11px; color: #555; letter-spacing: .15em; text-transform: uppercase; margin-bottom: 8px; }
    .warning { background: rgba(249,115,22,.1); border: 1px solid rgba(249,115,22,.3); color: #f97316; border-radius: 8px; padding: 10px 16px; font-size: 13px; margin-bottom: 28px; }
    .current { font-size: 13px; color: #777; margin-bottom: 24px; }
    .current strong { color: #f5f0eb; }
    label { display: block; text-align: left; font-size: 12px; color: #777; margin-bottom: 6px; letter-spacing: .05em; text-transform: uppercase; }
    input { width: 100%; background: #1a1a1a; border: 1px solid #333; border-radius: 8px; padding: 12px 16px; color: #f5f0eb; font-size: 15px; margin-bottom: 18px; outline: none; transition: border-color .2s; }
    input:focus { border-color: #e8185a; }
    button { width: 100%; background: #e8185a; color: #fff; border: none; border-radius: 8px; padding: 14px; font-size: 15px; font-weight: 700; cursor: pointer; transition: background .2s; }
    button:hover { background: #c91450; }
    .success { background: rgba(34,197,94,.1); border: 1px solid rgba(34,197,94,.3); color: #22c55e; border-radius: 8px; padding: 12px 16px; font-size: 14px; margin-bottom: 20px; }
    .error   { background: rgba(232,24,90,.1);  border: 1px solid rgba(232,24,90,.3);  color: #e8185a;  border-radius: 8px; padding: 12px 16px; font-size: 14px; margin-bottom: 20px; }
  </style>
</head>
<body>
  <div class="box">
    <div class="logo">🔥 Chaleur<em>Matic</em></div>
    <div class="subtitle">Réinitialisation administrateur</div>
    <div class="warning">⚠️ Accès développeur uniquement</div>

    <?php if ($config): ?>
    <div class="current">Compte : <strong><?= htmlspecialchars($config['username']) ?></strong></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="success">✅ <?= $success ?></div>
      <a href="index.php" style="display:block;margin-top:16px;font-size:13px;color:#e8185a;text-decoration:none;font-weight:700">→ Page de connexion</a>
    <?php else: ?>
      <?php if ($error): ?><div class="error">❌ <?= $error ?></div><?php endif; ?>
      <form method="POST">
        <label>Nouveau mot de passe</label>
        <input type="password" name="nouveau_mdp" placeholder="Au moins 6 caractères" required autofocus>
        <label>Confirmer</label>
        <input type="password" name="confirmer_mdp" placeholder="Répéter le mot de passe" required>
        <button type="submit">Réinitialiser</button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
