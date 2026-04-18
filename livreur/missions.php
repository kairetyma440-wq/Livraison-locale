<?php
require '../config.php';
requireLogin('livreur');

$livreur_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cmd_id = $_POST['cmd_id'] ?? '';
    $action = $_POST['action'] ?? '';

    if ($action === 'en_route') {
        $pdo->prepare("UPDATE commandes SET statut='En cours de livraison' WHERE id=? AND livreur_id=?")->execute([$cmd_id, $livreur_id]);
        $_SESSION['toast'] = '🛵 Vous êtes en route !';
    }
    if ($action === 'livree') {
        $pdo->prepare("UPDATE commandes SET statut='Livrée' WHERE id=? AND livreur_id=?")->execute([$cmd_id, $livreur_id]);
        $pdo->prepare("UPDATE utilisateurs SET missions=missions+1, gains=gains+1500, statut_livreur='Disponible' WHERE id=?")->execute([$livreur_id]);
        $_SESSION['toast'] = '✅ Livraison confirmée ! +1500 FCFA';
    }
    if ($action === 'incident') {
        $pdo->prepare("UPDATE commandes SET statut='Incident signalé' WHERE id=? AND livreur_id=?")->execute([$cmd_id, $livreur_id]);
        $_SESSION['toast'] = '⚠️ Incident signalé.';
    }
    redirect('/livraison_locale/livreur/missions.php');
}

$stmtLiv = $pdo->prepare("SELECT * FROM utilisateurs WHERE id=?");
$stmtLiv->execute([$livreur_id]);
$livreur = $stmtLiv->fetch();

$stmtMissions = $pdo->prepare("
    SELECT c.*, r.nom as resto_nom, r.emoji as resto_emoji,
           GROUP_CONCAT(cp.plat_nom SEPARATOR ', ') as plats_liste, cli.nom as client_nom
    FROM commandes c
    LEFT JOIN restaurants r ON c.restaurant_id = r.id
    LEFT JOIN commande_plats cp ON c.id = cp.commande_id
    LEFT JOIN utilisateurs cli ON c.client_id = cli.id
    WHERE c.livreur_id=? AND c.statut NOT IN ('Livrée','Annulée','Incident signalé')
    GROUP BY c.id ORDER BY c.heure ASC
");
$stmtMissions->execute([$livreur_id]);
$missions = $stmtMissions->fetchAll();

$stmtHisto = $pdo->prepare("
    SELECT c.*, r.nom as resto_nom, cli.nom as client_nom
    FROM commandes c
    LEFT JOIN restaurants r ON c.restaurant_id = r.id
    LEFT JOIN utilisateurs cli ON c.client_id = cli.id
    WHERE c.livreur_id=? AND c.statut IN ('Livrée','Incident signalé')
    ORDER BY c.heure DESC LIMIT 20
");
$stmtHisto->execute([$livreur_id]);
$historique = $stmtHisto->fetchAll();

$page  = $_GET['page'] ?? 'missions';
$toast = $_SESSION['toast'] ?? '';
unset($_SESSION['toast']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Livreur — LivraisonLocale</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container">
    <div class="header" style="background:linear-gradient(135deg,#0F172A,#1E293B);">
        <div class="header-row">
            <div>
                <p style="font-size:12px;opacity:0.7;">ESPACE LIVREUR</p>
                <h2><?= htmlspecialchars($livreur['nom']) ?></h2>
                <div style="display:flex;gap:8px;margin-top:6px;flex-wrap:wrap;">
                    <span style="background:#10B981;color:#fff;border-radius:20px;padding:2px 10px;font-size:11px;font-weight:600;">● Disponible</span>
                    <span style="background:rgba(255,255,255,0.2);border-radius:20px;padding:2px 10px;font-size:11px;">⭐ <?= $livreur['note'] ?></span>
                </div>
            </div>
            <a href="../auth/logout.php" class="btn btn-sm" style="background:rgba(255,255,255,0.15);border-color:transparent;color:#fff;">🚪</a>
        </div>
    </div>

    <nav class="tab-nav">
        <a href="?page=missions"   class="<?= $page==='missions'   ? 'active' : '' ?>" style="<?= $page==='missions'   ? 'color:#0EA5E9;border-bottom-color:#0EA5E9' : '' ?>">🛵 Missions</a>
        <a href="?page=historique" class="<?= $page==='historique' ? 'active' : '' ?>" style="<?= $page==='historique' ? 'color:#0EA5E9;border-bottom-color:#0EA5E9' : '' ?>">📋 Historique</a>
        <a href="?page=gains"      class="<?= $page==='gains'      ? 'active' : '' ?>" style="<?= $page==='gains'      ? 'color:#0EA5E9;border-bottom-color:#0EA5E9' : '' ?>">💰 Gains</a>
        <a href="?page=profil"     class="<?= $page==='profil'     ? 'active' : '' ?>" style="<?= $page==='profil'     ? 'color:#0EA5E9;border-bottom-color:#0EA5E9' : '' ?>">👤 Profil</a>
    </nav>

    <div class="page-content" style="padding-bottom:30px;">

    <?php if ($page === 'missions'): ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;">
            <div style="background:#E0F2FE;border-radius:12px;padding:12px;text-align:center;">
                <div style="font-size:24px;">📦</div>
                <p style="margin:4px 0 0;font-size:18px;font-weight:800;"><?= count($missions) ?></p>
                <p style="margin:0;font-size:11px;color:#0369A1;">Missions en cours</p>
            </div>
            <div style="background:#D1FAE5;border-radius:12px;padding:12px;text-align:center;">
                <div style="font-size:24px;">✅</div>
                <p style="margin:4px 0 0;font-size:18px;font-weight:800;"><?= $livreur['missions'] ?></p>
                <p style="margin:0;font-size:11px;color:#065F46;">Livraisons effectuées</p>
            </div>
        </div>
        <h3 style="margin-bottom:12px;font-size:16px;font-weight:700;">🛵 Missions actives</h3>
        <?php if (empty($missions)): ?>
        <div class="empty-state">
            <span class="empty-icon">🛵</span>
            <p>Aucune mission pour le moment.</p>
            <p style="font-size:12px;">Les commandes vous seront assignées par le gérant.</p>
        </div>
        <?php endif; ?>
        <?php foreach ($missions as $cmd): ?>
        <div style="background:#fff;border-radius:16px;margin-bottom:12px;overflow:hidden;border:<?= $cmd['statut']==='En cours de livraison' ? '2px solid #0EA5E9' : '1px solid #E5E7EB' ?>;">
            <div style="background:<?= $cmd['statut']==='En cours de livraison' ? '#F0F9FF' : '#F8F7F5' ?>;padding:12px 16px;border-bottom:1px solid #E5E7EB;display:flex;justify-content:space-between;align-items:center;">
                <span style="font-weight:800;"><?= $cmd['id'] ?></span>
                <?= statutBadge($cmd['statut']) ?>
            </div>
            <div style="padding:16px;">
                <div style="display:flex;gap:12px;margin-bottom:12px;">
                    <div style="font-size:32px;"><?= $cmd['resto_emoji'] ?></div>
                    <div>
                        <p style="margin:0 0 4px;font-weight:600;"><?= htmlspecialchars($cmd['resto_nom']) ?></p>
                        <p style="margin:0;font-size:13px;color:#6B7280;">📦 <?= htmlspecialchars($cmd['plats_liste']) ?></p>
                    </div>
                </div>
                <div style="background:#F3F4F6;border-radius:12px;padding:12px;margin-bottom:12px;">
                    <p style="margin:0 0 4px;font-size:13px;font-weight:600;">📍 Adresse de livraison</p>
                    <p style="margin:0;font-size:13px;color:#374151;"><?= htmlspecialchars($cmd['adresse']) ?></p>
                    <p style="margin:4px 0 0;font-size:12px;color:#6B7280;">👤 Client : <?= htmlspecialchars($cmd['client_nom']) ?></p>
                </div>
                <div class="cmd-actions">
                    <?php if ($cmd['statut'] === 'Assignée'): ?>
                    <form method="POST">
                        <input type="hidden" name="cmd_id" value="<?= $cmd['id'] ?>">
                        <input type="hidden" name="action" value="en_route">
                        <button type="submit" class="btn btn-sm" style="background:#0EA5E9;border-color:#0EA5E9;">🛵 Je pars en livraison</button>
                    </form>
                    <?php endif; ?>
                    <?php if ($cmd['statut'] === 'En cours de livraison'): ?>
                    <form method="POST">
                        <input type="hidden" name="cmd_id" value="<?= $cmd['id'] ?>">
                        <input type="hidden" name="action" value="livree">
                        <button type="submit" class="btn btn-sm btn-green" onclick="return confirm('Confirmer la livraison ?')">✅ Livraison effectuée</button>
                    </form>
                    <form method="POST">
                        <input type="hidden" name="cmd_id" value="<?= $cmd['id'] ?>">
                        <input type="hidden" name="action" value="incident">
                        <button type="submit" class="btn btn-sm btn-red">⚠️ Incident</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

    <?php elseif ($page === 'historique'): ?>
        <h3 style="margin-bottom:12px;">Historique des livraisons</h3>
        <?php if (empty($historique)): ?><div class="empty-state"><span class="empty-icon">📋</span><p>Aucune livraison effectuée.</p></div><?php endif; ?>
        <?php foreach ($historique as $cmd): ?>
        <div class="card">
            <div class="flex-between mb-1"><span style="font-weight:800;"><?= $cmd['id'] ?></span><?= statutBadge($cmd['statut']) ?></div>
            <p style="font-size:13px;margin-bottom:4px;">🍽️ <?= htmlspecialchars($cmd['resto_nom']) ?></p>
            <p style="font-size:13px;color:#6B7280;margin-bottom:4px;">📍 <?= htmlspecialchars($cmd['adresse']) ?></p>
            <div class="flex-between">
                <span style="font-size:12px;color:#9CA3AF;"><?= date('d/m H:i', strtotime($cmd['heure'])) ?></span>
                <?php if ($cmd['statut'] === 'Livrée'): ?><span style="font-size:12px;color:#10B981;font-weight:600;">+1 500 FCFA</span><?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

    <?php elseif ($page === 'gains'): ?>
        <div style="background:linear-gradient(135deg,#0F172A,#1E293B);border-radius:20px;padding:24px;margin-bottom:16px;text-align:center;color:#fff;">
            <p style="margin:0;font-size:13px;opacity:0.7;">Gains totaux</p>
            <p style="margin:8px 0 4px;font-size:42px;font-weight:900;"><?= number_format($livreur['gains'],0,',',' ') ?> FCFA</p>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="stat-card"><div class="stat-icon">📦</div><div class="stat-value"><?= $livreur['missions'] ?></div><div class="stat-label">Livraisons</div></div>
            <div class="stat-card"><div class="stat-icon">⭐</div><div class="stat-value"><?= $livreur['note'] ?></div><div class="stat-label">Note moyenne</div></div>
            <div class="stat-card"><div class="stat-icon">🛵</div><div class="stat-value"><?= $livreur['missions'] * 8 ?> km</div><div class="stat-label">Km parcourus</div></div>
            <div class="stat-card"><div class="stat-icon">💰</div><div class="stat-value" style="font-size:15px;">1500 F</div><div class="stat-label">Par livraison</div></div>
        </div>

    <?php elseif ($page === 'profil'): ?>
        <div class="card" style="text-align:center;margin-bottom:14px;">
            <div style="width:80px;height:80px;border-radius:50%;background:#0EA5E9;margin:0 auto 16px;display:flex;align-items:center;justify-content:center;font-size:36px;color:#fff;">
                <?= strtoupper(mb_substr($livreur['nom'], 0, 1)) ?>
            </div>
            <h3 style="font-size:20px;font-weight:800;"><?= htmlspecialchars($livreur['nom']) ?></h3>
            <p style="color:#9CA3AF;"><?= htmlspecialchars($livreur['email']) ?></p>
        </div>
        <div class="card">
            <div class="flex-between mb-1"><span style="color:#6B7280;">Téléphone</span><span><?= htmlspecialchars($livreur['telephone']) ?></span></div>
            <div class="flex-between mb-1"><span style="color:#6B7280;">Véhicule</span><span><?= htmlspecialchars($livreur['vehicule'] ?? '') ?></span></div>
            <div class="flex-between"><span style="color:#6B7280;">Statut</span><span><?= htmlspecialchars($livreur['statut_livreur']) ?></span></div>
        </div>
    <?php endif; ?>

    </div>
</div>
<?php if ($toast): ?><div class="toast"><?= htmlspecialchars($toast) ?></div><?php endif; ?>
</body>
</html>
