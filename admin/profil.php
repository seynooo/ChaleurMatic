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
    die('Erreur de connexion.');
}

// Créer la table config si elle n'existe pas
$pdo->exec("CREATE TABLE IF NOT EXISTS admin_config (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL
)");

// Ajouter colonne email si elle n'existe pas (compatible toutes versions MySQL)
try {
    $cols = $pdo->query("SHOW COLUMNS FROM admin_config LIKE 'email'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE admin_config ADD COLUMN email VARCHAR(150) DEFAULT ''");
    }
} catch(Exception $e) {}

// Insérer les identifiants par défaut si la table est vide
$count = $pdo->query("SELECT COUNT(*) FROM admin_config")->fetchColumn();
if ($count == 0) {
    $pdo->prepare("INSERT INTO admin_config (username, password, email) VALUES (?, ?, ?)")
        ->execute(['admin', password_hash('admin', PASSWORD_DEFAULT), '']);
}

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ancien_mdp    = $_POST['ancien_mdp']    ?? '';
    $nouveau_mdp   = $_POST['nouveau_mdp']   ?? '';
    $confirmer_mdp = $_POST['confirmer_mdp'] ?? '';
    $nouveau_login = trim($_POST['nouveau_login'] ?? '');

    $config = $pdo->query("SELECT * FROM admin_config LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    $nouveau_email = trim($_POST['nouveau_email'] ?? '');

    if (!password_verify($ancien_mdp, $config['password'])) {
        $error = 'Ancien mot de passe incorrect.';
    } elseif ($nouveau_mdp && $nouveau_mdp !== $confirmer_mdp) {
        $error = 'Les nouveaux mots de passe ne correspondent pas.';
    } elseif ($nouveau_mdp && strlen($nouveau_mdp) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } else {
        $new_pass  = $nouveau_mdp    ? password_hash($nouveau_mdp, PASSWORD_DEFAULT) : $config['password'];
        $new_user  = $nouveau_login  ?: $config['username'];
        $new_email = $nouveau_email  ?: ($config['email'] ?? '');
        $pdo->prepare("UPDATE admin_config SET username=?, password=?, email=? WHERE id=?")
            ->execute([$new_user, $new_pass, $new_email, $config['id']]);
        $success = 'Identifiants mis à jour avec succès !';
    }
}

$config = $pdo->query("SELECT * FROM admin_config LIMIT 1")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profil – ChaleurMatic Admin</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #0a0a0a; font-family: 'Segoe UI', Arial, sans-serif; color: #f5f0eb; min-height: 100vh; }
    .header {
      background: #111; border-bottom: 1px solid #222;
      padding: 16px 30px; display: flex; align-items: center; justify-content: space-between;
    }
    .logo { font-size: 20px; font-weight: 900; }
    .logo em { color: #e8185a; font-style: normal; }
    .nav-links { display: flex; gap: 16px; align-items: center; }
    .nav-links a { color: #777; font-size: 13px; text-decoration: none; padding: 8px 16px; border: 1px solid #333; border-radius: 6px; transition: all .2s; }
    .nav-links a:hover { color: #f5f0eb; border-color: #555; }
    .nav-links a.logout:hover { color: #e8185a; border-color: #e8185a; }
    .container { max-width: 500px; margin: 60px auto; padding: 0 20px; }
    h1 { font-size: 22px; font-weight: 700; margin-bottom: 30px; }
    .card { background: #111; border: 1px solid #222; border-radius: 12px; padding: 30px; }
    label { display: block; font-size: 12px; color: #777; margin-bottom: 6px; letter-spacing: .05em; text-transform: uppercase; }
    input {
      width: 100%; background: #1a1a1a; border: 1px solid #333; border-radius: 8px;
      padding: 12px 16px; color: #f5f0eb; font-size: 15px; margin-bottom: 18px; outline: none; transition: border-color .2s;
    }
    input:focus { border-color: #e8185a; }
    .current-user { background: #1a1a1a; border: 1px solid #333; border-radius: 8px; padding: 12px 16px; font-size: 14px; color: #999; margin-bottom: 24px; }
    .current-user strong { color: #f5f0eb; }
    .divider { border: none; border-top: 1px solid #222; margin: 20px 0; }
    .section-title { font-size: 11px; color: #555; letter-spacing: .1em; text-transform: uppercase; margin-bottom: 16px; }
    button {
      width: 100%; background: #e8185a; color: #fff; border: none; border-radius: 8px;
      padding: 14px; font-size: 15px; font-weight: 700; cursor: pointer; margin-top: 5px; transition: background .2s;
    }
    button:hover { background: #c91450; }
    .success { background: rgba(34,197,94,.1); border: 1px solid rgba(34,197,94,.3); color: #22c55e; border-radius: 8px; padding: 12px 16px; font-size: 14px; margin-bottom: 20px; }
    .error   { background: rgba(232,24,90,.1);  border: 1px solid rgba(232,24,90,.3);  color: #e8185a;  border-radius: 8px; padding: 12px 16px; font-size: 14px; margin-bottom: 20px; }
    @media (max-width: 600px) {
      .header { padding: 12px 16px; flex-wrap: wrap; gap: 8px; }
      .nav-links { flex-wrap: wrap; gap: 6px; }
      .nav-links a { font-size: 12px; padding: 6px 10px; }
      .container { margin: 24px auto; padding: 0 14px; }
      .card { padding: 20px 16px; }
    }
  </style>
</head>
<body>

<div class="header">
  <div class="logo">🔥 Chaleur<em>Matic</em> <span style="font-size:13px;color:#555;font-weight:400;margin-left:8px">Admin</span></div>
  <div class="nav-links">
    <a href="dashboard.php">← Retour au dashboard</a>
    <a href="logout.php" class="logout">Déconnexion</a>
  </div>
</div>

<div class="container">
  <h1>Mon profil</h1>

  <div class="card">
    <div class="current-user">Connecté en tant que : <strong><?= htmlspecialchars($config['username']) ?></strong></div>

    <?php if ($success): ?><div class="success">✅ <?= $success ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="error">❌ <?= $error ?></div><?php endif; ?>

    <form method="POST">
      <div class="section-title">Changer l'identifiant</div>
      <label>Nouvel identifiant</label>
      <input type="text" name="nouveau_login" placeholder="<?= htmlspecialchars($config['username']) ?>">
      <label>Email (pour réinitialisation mot de passe)</label>
      <input type="email" name="nouveau_email" placeholder="<?= htmlspecialchars($config['email'] ?? '') ?: 'votre@email.com' ?>" value="<?= htmlspecialchars($config['email'] ?? '') ?>">

      <hr class="divider">

      <div class="section-title">Changer le mot de passe</div>
      <label>Nouveau mot de passe</label>
      <input type="password" name="nouveau_mdp" placeholder="Laisser vide pour ne pas changer">
      <label>Confirmer le nouveau mot de passe</label>
      <input type="password" name="confirmer_mdp" placeholder="Répéter le nouveau mot de passe">

      <hr class="divider">

      <label>Ancien mot de passe * (obligatoire)</label>
      <input type="password" name="ancien_mdp" placeholder="Votre mot de passe actuel" required>

      <button type="submit">Enregistrer les modifications</button>
    </form>
  </div>
</div>

</body>
</html>
