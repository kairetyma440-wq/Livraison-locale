<?php
require '../config.php';
requireLogin('client');

$search = trim($_GET['q'] ?? '');

if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE nom LIKE ? OR cuisine LIKE ? ORDER BY note DESC");
    $stmt->execute(["%$search%", "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM restaurants ORDER BY note DESC");
}
$restaurants = $stmt->fetchAll();

$stmtCount = $pdo->prepare("SELECT COUNT(*) as nb FROM commandes WHERE client_id = ? AND statut NOT IN ('Livrée','Annulée')");
$stmtCount->execute([$_SESSION['user_id']]);
$enCours = $stmtCount->fetch()['nb'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurants — LivraisonLocale</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container">
    <div class="header" style="background:#FF6B35;">
        <div class="header-row">
            <div>
                <p>Bonjour 👋</p>
                <h2><?= htmlspecialchars($_SESSION['nom']) ?></h2>
            </div>
            <a href="mes_commandes.php" class="cart-badge">🛒 <?= $enCours ?> en cours</a>
        </div>
        <form method="GET" style="margin-top:14px;">
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                placeholder="🔍 Rechercher un restaurant ou cuisine..."
                class="form-control" style="background:rgba(255,255,255,0.95);">
        </form>
    </div>

    <div class="page-content">
        <h3 style="margin-bottom:14px;font-size:16px;font-weight:800;">
            <?= $search ? '🔍 Résultats pour "'.htmlspecialchars($search).'"' : '🍽️ Nos restaurants' ?>
        </h3>

        <?php if (empty($restaurants)): ?>
        <div class="empty-state">
            <span class="empty-icon">😕</span>
            <p>Aucun restaurant trouvé.</p>
        </div>
        <?php endif; ?>

        <?php foreach ($restaurants as $r): ?>
        <a href="menu.php?id=<?= $r['id'] ?>" class="resto-card">
            <div class="resto-row">
                <span class="resto-emoji"><?= $r['emoji'] ?></span>
                <div class="resto-info">
                    <h3><?= htmlspecialchars($r['nom']) ?></h3>
                    <p><?= htmlspecialchars($r['description']) ?></p>
                    <div class="resto-meta">
                        <span class="badge-sm">⭐ <?= $r['note'] ?></span>
                        <span class="badge-sm">📍 <?= htmlspecialchars($r['zone']) ?></span>
                        <span class="badge-sm">🍴 <?= htmlspecialchars($r['cuisine']) ?></span>
                    </div>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <nav class="bottom-nav">
        <a href="restaurants.php" class="active"><span class="icon">🏠</span><span class="label">Accueil</span></a>
        <a href="mes_commandes.php"><span class="icon">📦</span><span class="label">Mes commandes</span></a>
        <a href="profil.php"><span class="icon">👤</span><span class="label">Profil</span></a>
    </nav>
</div>
</body>
</html>
