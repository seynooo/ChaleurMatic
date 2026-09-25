<?php
session_start();
if (isset($_SESSION['admin_logged_in'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    try {
        $pdo = new PDO("mysql:host=127.0.0.1;dbname=chaleurmatic;charset=utf8", 'admin', 'admin', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        // Créer table + identifiants par défaut si besoin
        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_config (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(100) NOT NULL, password VARCHAR(255) NOT NULL, email VARCHAR(150) DEFAULT '')");
        $count = $pdo->query("SELECT COUNT(*) FROM admin_config")->fetchColumn();
        if ($count == 0) {
            $pdo->prepare("INSERT INTO admin_config (username, password, email) VALUES (?, ?, ?)")->execute(['admin', password_hash('admin', PASSWORD_DEFAULT), '']);
        }

        $config = $pdo->prepare("SELECT * FROM admin_config WHERE username = ? LIMIT 1");
        $config->execute([$user]);
        $row = $config->fetch(PDO::FETCH_ASSOC);

        if ($row && password_verify($pass, $row['password'])) {
            $_SESSION['admin_logged_in'] = true;
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Identifiants incorrects.';
        }
    } catch (PDOException $e) {
        $error = 'Erreur de connexion à la base de données.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin – ChaleurMatic</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background: #0a0a0a;
      font-family: 'Segoe UI', Arial, sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .login-box {
      background: #111;
      border: 1px solid #222;
      border-radius: 16px;
      padding: 50px 40px;
      width: 100%;
      max-width: 400px;
      text-align: center;
    }
    .logo {
      font-size: 28px;
      font-weight: 900;
      color: #f5f0eb;
      margin-bottom: 6px;
    }
    .logo em { color: #e8185a; font-style: normal; }
    .subtitle {
      font-size: 12px;
      color: #555;
      letter-spacing: .15em;
      text-transform: uppercase;
      margin-bottom: 35px;
    }
    label {
      display: block;
      text-align: left;
      font-size: 12px;
      color: #777;
      margin-bottom: 6px;
      letter-spacing: .05em;
      text-transform: uppercase;
    }
    input {
      width: 100%;
      background: #1a1a1a;
      border: 1px solid #333;
      border-radius: 8px;
      padding: 12px 16px;
      color: #f5f0eb;
      font-size: 15px;
      margin-bottom: 18px;
      outline: none;
      transition: border-color .2s;
    }
    input:focus { border-color: #e8185a; }
    button {
      width: 100%;
      background: #e8185a;
      color: #fff;
      border: none;
      border-radius: 8px;
      padding: 14px;
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
      margin-top: 5px;
      transition: background .2s;
    }
    button:hover { background: #c91450; }
    .error {
      background: rgba(232,24,90,.1);
      border: 1px solid rgba(232,24,90,.3);
      color: #e8185a;
      border-radius: 8px;
      padding: 10px 16px;
      font-size: 14px;
      margin-bottom: 18px;
    }
  </style>
</head>
<body>
  <div class="login-box">
    <div class="logo">🔥 Chaleur<em>Matic</em></div>
    <div class="subtitle">Espace administration</div>
    <?php if ($error): ?>
      <div class="error"><?= $error ?></div>
    <?php endif; ?>
    <form method="POST">
      <label>Identifiant</label>
      <input type="text" name="username" placeholder="admin" autofocus>
      <label>Mot de passe</label>
      <input type="password" name="password" placeholder="••••••••">
      <button type="submit">Se connecter</button>
    </form>
    <a href="forgot.php" style="display:block;margin-top:16px;font-size:13px;color:#555;text-decoration:none;">Mot de passe oublié ?</a>
  </div>
</body>
</html>
