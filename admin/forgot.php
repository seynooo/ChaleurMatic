<?php
session_start();

$db_host = '127.0.0.1';
$db_name = 'chaleurmatic';
$db_user = 'admin';
$db_pass = 'admin';

require '/var/www/html/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die('Erreur de connexion.');
}

// Créer table tokens si elle n'existe pas
$pdo->exec("CREATE TABLE IF NOT EXISTS reset_tokens (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    token      VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL
)");

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_saisi = trim($_POST['email'] ?? '');

    // Vérifier si l'email correspond à celui enregistré
    $config = $pdo->query("SELECT * FROM admin_config LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    if ($config && isset($config['email']) && strtolower($config['email']) === strtolower($email_saisi)) {
        // Générer un token unique
        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Supprimer les anciens tokens et insérer le nouveau
        $pdo->exec("DELETE FROM reset_tokens");
        $pdo->prepare("INSERT INTO reset_tokens (token, expires_at) VALUES (?, ?)")->execute([$token, $expires]);

        // Envoyer l'email
        $reset_url = 'http://185.216.27.172/admin/reset.php?token=' . $token;

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'oddisafwan@gmail.com';
            $mail->Password   = 'hwuxjxggsqvjesgm';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom('oddisafwan@gmail.com', 'ChaleurMatic');
            $mail->addAddress($email_saisi);
            $mail->Subject = 'Réinitialisation de votre mot de passe – ChaleurMatic';
            $mail->isHTML(true);
            $mail->Body = '
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#0a0a0a;font-family:Arial,sans-serif">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#0a0a0a;padding:40px 20px">
    <tr><td align="center">
      <table width="500" cellpadding="0" cellspacing="0" style="max-width:500px;width:100%;background:#111;border-radius:12px;overflow:hidden;border:1px solid #222">
        <tr>
          <td style="background:linear-gradient(135deg,#1a0a00,#2d0f00);padding:35px 30px;text-align:center;border-bottom:2px solid #e8185a">
            <div style="font-size:32px;margin-bottom:8px">🔥</div>
            <div style="font-size:24px;font-weight:900;color:#f5f0eb">Chaleur<em style="color:#e8185a">Matic</em></div>
            <div style="font-size:12px;color:#7a7580;letter-spacing:.15em;text-transform:uppercase;margin-top:6px">Réinitialisation mot de passe</div>
          </td>
        </tr>
        <tr>
          <td style="padding:35px 30px;text-align:center">
            <p style="color:#f5f0eb;font-size:15px;margin-bottom:10px">Vous avez demandé à réinitialiser votre mot de passe.</p>
            <p style="color:#999;font-size:13px;margin-bottom:30px">Ce lien est valable <strong style="color:#f5f0eb">1 heure</strong>.</p>
            <a href="' . $reset_url . '" style="display:inline-block;background:#e8185a;color:#fff;text-decoration:none;font-size:15px;font-weight:700;padding:14px 32px;border-radius:8px">
              Réinitialiser mon mot de passe
            </a>
            <p style="color:#555;font-size:12px;margin-top:24px">Si vous n\'avez pas fait cette demande, ignorez cet email.</p>
          </td>
        </tr>
        <tr>
          <td style="background:#0d0d0d;padding:16px 30px;text-align:center;border-top:1px solid #222">
            <div style="font-size:12px;color:#555">ChaleurMatic · Administration</div>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>';
            $mail->send();
            $success = 'Un email de réinitialisation a été envoyé.';
        } catch (Exception $e) {
            $error = 'Erreur lors de l\'envoi de l\'email.';
        }
    } else {
        // On affiche le même message pour ne pas révéler si l'email existe
        $success = 'Si cet email est enregistré, vous recevrez un lien de réinitialisation.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mot de passe oublié – ChaleurMatic</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #0a0a0a; font-family: 'Segoe UI', Arial, sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    .box { background: #111; border: 1px solid #222; border-radius: 16px; padding: 50px 40px; width: 100%; max-width: 400px; text-align: center; }
    .logo { font-size: 28px; font-weight: 900; color: #f5f0eb; margin-bottom: 6px; }
    .logo em { color: #e8185a; font-style: normal; }
    .subtitle { font-size: 12px; color: #555; letter-spacing: .15em; text-transform: uppercase; margin-bottom: 30px; }
    p { font-size: 14px; color: #777; margin-bottom: 24px; }
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
    <div class="subtitle">Mot de passe oublié</div>
    <p>Entrez votre email pour recevoir un lien de réinitialisation.</p>
    <?php if ($success): ?><div class="success"><?= $success ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="error"><?= $error ?></div><?php endif; ?>
    <?php if (!$success): ?>
    <form method="POST">
      <label>Votre email</label>
      <input type="email" name="email" placeholder="votre@email.com" required autofocus>
      <button type="submit">Envoyer le lien</button>
    </form>
    <?php endif; ?>
    <a href="index.php" class="back">← Retour à la connexion</a>
  </div>
</body>
</html>
