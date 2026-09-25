<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$phpmailer_disponible = file_exists('/var/www/html/vendor/autoload.php');
if ($phpmailer_disponible) {
    require '/var/www/html/vendor/autoload.php';
}

// ── Configuration ─────────────────────────────────────────────
$db_host = '127.0.0.1';
$db_name = 'chaleurmatic';
$db_user = 'admin';
$db_pass = 'admin';
$email_destinataire = 'oddisafwan@gmail.com';
$smtp_user = 'oddisafwan@gmail.com';
$smtp_pass = 'hwuxjxggsqvjesgm';

// ── Nettoyage des données ─────────────────────────────────────
function clean($val) {
    return htmlspecialchars(strip_tags(trim($val ?? '')));
}

$service        = clean($_POST['service']        ?? '');
$prenom         = clean($_POST['prenom']         ?? '');
$nom            = clean($_POST['nom']            ?? '');
$tel            = clean($_POST['tel']            ?? '');
$email          = clean($_POST['email']          ?? '');
$adresse        = clean($_POST['adresse']        ?? '');
$date           = clean($_POST['date']           ?? '');
$creneau        = clean($_POST['creneau']        ?? '');
$message        = clean($_POST['message']        ?? '');
$type_chaudiere = clean($_POST['type_chaudiere'] ?? '');
$marque         = clean($_POST['marque']         ?? '');
$modele         = clean($_POST['modele']         ?? '');
$annee          = clean($_POST['annee']          ?? '');

// ── Validation ────────────────────────────────────────────────
if (!$service || !$prenom || !$tel || !$adresse) {
    echo json_encode(['success' => false, 'message' => 'Champs obligatoires manquants.']);
    exit;
}

// ── Sauvegarde en base de données ─────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8",
        $db_user,
        $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $pdo->prepare("
        INSERT INTO demandes_rdv
            (service, prenom, nom, telephone, email, adresse, type_chaudiere, marque, modele, annee, date_souhaitee, creneau, message)
        VALUES
            (:service, :prenom, :nom, :tel, :email, :adresse, :type_chaudiere, :marque, :modele, :annee, :date, :creneau, :message)
    ");

    $stmt->execute([
        ':service'        => $service,
        ':prenom'         => $prenom,
        ':nom'            => $nom,
        ':tel'            => $tel,
        ':email'          => $email,
        ':adresse'        => $adresse,
        ':type_chaudiere' => $type_chaudiere,
        ':marque'         => $marque,
        ':modele'         => $modele,
        ':annee'          => $annee,
        ':date'           => $date,
        ':creneau'        => $creneau,
        ':message'        => $message,
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur base de données.']);
    exit;
}

// ── Envoi email via SMTP Outlook ──────────────────────────────
if (!$phpmailer_disponible) {
    echo json_encode(['success' => true, 'message' => 'Demande enregistrée.']);
    exit;
}
try {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp_user;
    $mail->Password   = $smtp_pass;
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom('oddisafwan@gmail.com', 'ChaleurMatic RDV');
    $mail->addAddress($email_destinataire);
    if ($email) $mail->addReplyTo($email);

    $mail->Subject  = "Nouvelle demande RDV – $service – $prenom $nom";
    $mail->isHTML(true);

    $chaudiere_html = '';
    if ($type_chaudiere || $marque || $modele || $annee) {
        $chaudiere_html = '
        <tr><td colspan="2" style="padding:20px 30px 8px;font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#e8185a">🔧 Chaudière</td></tr>
        ' . ($type_chaudiere ? '<tr><td style="padding:4px 30px;color:#999;font-size:14px;width:140px">Type</td><td style="padding:4px 30px;color:#f5f0eb;font-size:14px">' . $type_chaudiere . '</td></tr>' : '') .
        ($marque  ? '<tr><td style="padding:4px 30px;color:#999;font-size:14px">Marque</td><td style="padding:4px 30px;color:#f5f0eb;font-size:14px">' . $marque . '</td></tr>' : '') .
        ($modele  ? '<tr><td style="padding:4px 30px;color:#999;font-size:14px">Modèle</td><td style="padding:4px 30px;color:#f5f0eb;font-size:14px">' . $modele . '</td></tr>' : '') .
        ($annee   ? '<tr><td style="padding:4px 30px;color:#999;font-size:14px">Année</td><td style="padding:4px 30px;color:#f5f0eb;font-size:14px">' . $annee . '</td></tr>' : '');
    }

    $dispo_html = '';
    if ($date || $creneau) {
        $dispo_html = '
        <tr><td colspan="2" style="padding:20px 30px 8px;font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#e8185a">📅 Disponibilité</td></tr>
        ' . ($date    ? '<tr><td style="padding:4px 30px;color:#999;font-size:14px;width:140px">Date souhaitée</td><td style="padding:4px 30px;color:#f5f0eb;font-size:14px">' . $date . '</td></tr>' : '') .
        ($creneau ? '<tr><td style="padding:4px 30px;color:#999;font-size:14px">Créneau</td><td style="padding:4px 30px;color:#f5f0eb;font-size:14px">' . $creneau . '</td></tr>' : '');
    }

    $message_html = '';
    if ($message) {
        $message_html = '
        <tr><td colspan="2" style="padding:20px 30px 8px;font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#e8185a">💬 Message</td></tr>
        <tr><td colspan="2" style="padding:4px 30px 20px;color:#f5f0eb;font-size:14px">' . $message . '</td></tr>';
    }

    $mail->Body = '
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#0a0a0a;font-family:Arial,sans-serif">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#0a0a0a;padding:40px 20px">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#111;border-radius:12px;overflow:hidden;border:1px solid #222">

        <!-- Header -->
        <tr>
          <td style="background:linear-gradient(135deg,#1a0a00,#2d0f00);padding:35px 30px;text-align:center;border-bottom:2px solid #e8185a">
            <div style="font-size:32px;margin-bottom:8px">🔥</div>
            <div style="font-size:26px;font-weight:900;color:#f5f0eb;letter-spacing:.05em">Chaleur<em style="color:#e8185a">Matic</em></div>
            <div style="font-size:12px;color:#7a7580;letter-spacing:.15em;text-transform:uppercase;margin-top:6px">Nouvelle demande de rendez-vous</div>
          </td>
        </tr>

        <!-- Badge service -->
        <tr>
          <td colspan="2" style="padding:25px 30px 5px;text-align:center">
            <span style="display:inline-block;background:#e8185a;color:#fff;font-size:13px;font-weight:700;padding:8px 20px;border-radius:20px;letter-spacing:.05em">
              ' . $service . '
            </span>
          </td>
        </tr>

        <!-- Contenu -->
        <table width="100%" cellpadding="0" cellspacing="0">

          <tr><td colspan="2" style="padding:25px 30px 8px;font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#e8185a">👤 Client</td></tr>
          <tr>
            <td style="padding:4px 30px;color:#999;font-size:14px;width:140px">Nom</td>
            <td style="padding:4px 30px;color:#f5f0eb;font-size:14px;font-weight:600">' . $prenom . ' ' . $nom . '</td>
          </tr>
          <tr>
            <td style="padding:4px 30px;color:#999;font-size:14px">Téléphone</td>
            <td style="padding:4px 30px;font-size:14px"><a href="tel:' . $tel . '" style="color:#e8185a;text-decoration:none;font-weight:700">' . $tel . '</a></td>
          </tr>
          ' . ($email ? '<tr><td style="padding:4px 30px;color:#999;font-size:14px">Email</td><td style="padding:4px 30px;font-size:14px"><a href="mailto:' . $email . '" style="color:#e8185a;text-decoration:none">' . $email . '</a></td></tr>' : '') . '
          <tr>
            <td style="padding:4px 30px 20px;color:#999;font-size:14px">Adresse</td>
            <td style="padding:4px 30px 20px;color:#f5f0eb;font-size:14px">' . $adresse . '</td>
          </tr>

          ' . $chaudiere_html . '
          ' . $dispo_html . '
          ' . $message_html . '

        </table>

        <!-- Footer -->
        <tr>
          <td style="background:#0d0d0d;padding:20px 30px;text-align:center;border-top:1px solid #222">
            <div style="font-size:12px;color:#555">Demande reçue via <strong style="color:#777">chaleurmatic.com</strong></div>
            <div style="margin-top:8px">
              <a href="tel:+32488274228" style="display:inline-block;background:#e8185a;color:#fff;text-decoration:none;font-size:13px;font-weight:700;padding:10px 24px;border-radius:6px">📞 Rappeler le client</a>
            </div>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>';
    $mail->send();

} catch (Exception $e) {
    // Email échoue mais on confirme quand même (données déjà sauvegardées)
}

echo json_encode(['success' => true]);
?>
