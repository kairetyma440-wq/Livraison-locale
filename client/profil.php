<?php
require '../config.php';
requireLogin('client');

$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$stmtStats = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN statut='Livrée' THEN 1 ELSE 0 END) as livrees FROM commandes WHERE client_id=?");
$stmtStats->execute([$_SESSION['user_id']]);
$stats = $stmtStats->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon profil — LivraisonLocale</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container">
    <div class="header" style="background:#FF6B35;"><h2>👤 Mon profil</h2></div>
    <div class="page-content">
        <div style="text-align:center;margin-bottom:20px;">
            <div style="width:72px;height:72px;border-radius:50%;background:#FF6B35;margin:0 auto 12px;display:flex;align-items:center;justify-content:center;font-size:30px;color:#fff;">
                <?= strtoupper(mb_substr($user['nom'], 0, 1)) ?>
            </div>
            <h3 style="font-size:18px;font-weight:800;"><?= htmlspecialchars($user['nom']) ?></h3>
            <p style="color:#9CA3AF;"><?= htmlspecialchars($user['email']) ?></p>
        </div>
        <div class="card">
            <div class="flex-between mb-1"><span>📦 Total commandes</span><span class="fw-bold text-orange"><?= $stats['total'] ?></span></div>
            <div class="flex-between mb-1"><span>✅ Commandes livrées</span><span class="fw-bold text-green"><?= $stats['livrees'] ?></span></div>
            <div class="flex-between"><span>📞 Téléphone</span><span class="fw-bold"><?= htmlspecialchars($user['telephone'] ?? 'Non renseigné') ?></span></div>
        </div>
        <a href="../auth/logout.php" class="btn btn-full btn-outline-red mt-2" onclick="return confirm('Se déconnecter ?')">🚪 Se déconnecter</a>
    </div>
    <nav class="bottom-nav">
        <a href="restaurants.php"><span class="icon">🏠</span><span class="label">Accueil</span></a>
        <a href="mes_commandes.php"><span class="icon">📦</span><span class="label">Mes commandes</span></a>
        <a href="profil.php" class="active"><span class="icon">👤</span><span class="label">Profil</span></a>
    </nav>
</div>
</body>
</html>
