<?php
session_start();

$db_host = '127.0.0.1';
$db_name = 'chaleurmatic';
$db_user = 'admin';
$db_pass = 'admin';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die('Erreur de connexion.');
}

$token = $_GET['token'] ?? '';
$error   = '';
$success = '';

// Vérifier que le token est valide et non expiré
$row = $pdo->prepare("SELECT * FROM reset_tokens WHERE token = ? AND expires_at > NOW() LIMIT 1");
$row->execute([$token]);
$token_data = $row->fetch(PDO::FETCH_ASSOC);

if (!$token_data) {
    $error = 'Ce lien est invalide ou a expiré. Faites une nouvelle demande.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_data) {
    $nouveau_mdp   = $_POST['nouveau_mdp']   ?? '';
    $confirmer_mdp = $_POST['confirmer_mdp'] ?? '';

    if (strlen($nouveau_mdp) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif ($nouveau_mdp !== $confirmer_mdp) {
        $error = 'Les mots de passe ne correspondent pas.';
    } else {
        $pdo->prepare("UPDATE admin_config SET password = ?")->execute([password_hash($nouveau_mdp, PASSWORD_DEFAULT)]);
        $pdo->exec("DELETE FROM reset_tokens");
        $success = 'Mot de passe mis à jour ! Vous pouvez vous connecter.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nouveau mot de passe – ChaleurMatic</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #0a0a0a; font-family: 'Segoe UI', Arial, sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    .box { background: #111; border: 1px solid #222; border-radius: 16px; padding: 50px 40px; width: 100%; max-width: 400px; text-align: center; }
    .logo { font-size: 28px; font-weight: 900; color: #f5f0eb; margin-bottom: 6px; }
    .logo em { color: #e8185a; font-style: normal; }
    .subtitle { font-size: 12px; color: #555; letter-spacing: .15em; text-transform: uppercase; margin-bottom: 30px; }
    label { display: block; text-align: left; font-size: 12px; color: #777; margin-bottom: 6px; letter-spacing: .05em; text-transform: uppercase; }
    input { width: 100%; background: #1a1a1a; border: 1px solid #333; border-radius: 8px; padding: 12px 16px; color: #f5f0eb; font-size: 15px; margin-bottom: 18px; outline: none; transition: border-color .2s; }
    input:focus { border-color: #e8185a; }
    button { width: 100%; background: #e8185a; color: #fff; border: none; border-radius: 8px; padding: 14px; font-size: 15px; font-weight: 700; cursor: pointer; transition: background .2s; }
    button:hover { background: #c91450; }
    .back { display: block; margin-top: 16px; font-size: 13px; color: #555; text-decoration: none; }
    .back:hover { color: #f5f0eb; }
    .success { background: rgba(34,197,94,.1); border: 1px solid rgba(34,197,94,.3); color: #22c55e; border-radius: 8px; padding: 12px 16px; font-size: 14px; margin-bottom: 20px; }
    .error   { background: rgba(232,24,90,.1);  border: 1px solid rgba(232,24,90,.3);  color: #e8185a;  border-radius: 8px; padding: 12px 16px; font-size: 14px; margin-bottom: 20px; }
  </style>
</head>
<body>
  <div class="box">
    <div class="logo">🔥 Chaleur<em>Matic</em></div>
    <div class="subtitle">Nouveau mot de passe</div>

    <?php if ($success): ?>
      <div class="success">✅ <?= $success ?></div>
      <a href="index.php" class="back" style="color:#e8185a;font-weight:700">→ Se connecter</a>
    <?php elseif ($error && !$token_data): ?>
      <div class="error">❌ <?= $error ?></div>
      <a href="forgot.php" class="back">← Nouvelle demande</a>
    <?php else: ?>
      <?php if ($error): ?><div class="error">❌ <?= $error ?></div><?php endif; ?>
      <form method="POST">
        <label>Nouveau mot de passe</label>
        <input type="password" name="nouveau_mdp" placeholder="Au moins 6 caractères" required autofocus>
        <label>Confirmer le mot de passe</label>
        <input type="password" name="confirmer_mdp" placeholder="Répéter le mot de passe" required>
        <button type="submit">Enregistrer</button>
      </form>
      <a href="index.php" class="back">← Retour à la connexion</a>
    <?php endif; ?>
  </div>
</body>
</html>
