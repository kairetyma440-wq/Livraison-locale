<?php
require '../config.php';
requireLogin('gerant');

$resto_id = $_SESSION['restaurant_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cmd_id = $_POST['cmd_id'] ?? '';
    $action = $_POST['action'] ?? '';

    if ($action === 'valider') {
        $pdo->prepare("UPDATE commandes SET statut='Validée' WHERE id=? AND restaurant_id=?")->execute([$cmd_id, $resto_id]);
        $_SESSION['toast'] = '✓ Commande validée.';
    }
    if ($action === 'preparer') {
        $pdo->prepare("UPDATE commandes SET statut='En préparation' WHERE id=? AND restaurant_id=?")->execute([$cmd_id, $resto_id]);
        $_SESSION['toast'] = '🍳 Commande en préparation.';
    }
    if ($action === 'assigner') {
        $livreur_id = intval($_POST['livreur_id'] ?? 0);
        if ($livreur_id) {
            $pdo->prepare("UPDATE commandes SET statut='Assignée', livreur_id=? WHERE id=? AND restaurant_id=?")->execute([$livreur_id, $cmd_id, $resto_id]);
            $pdo->prepare("UPDATE utilisateurs SET statut_livreur='En livraison' WHERE id=?")->execute([$livreur_id]);
            $_SESSION['toast'] = '🛵 Livreur assigné !';
        }
    }
    redirect('/livraison_locale/gerant/dashboard.php');
}

$stmtResto = $pdo->prepare("SELECT * FROM restaurants WHERE id = ?");
$stmtResto->execute([$resto_id]);
$resto = $stmtResto->fetch();

$stmtCmds = $pdo->prepare("
    SELECT c.*, u.nom as client_nom,
           GROUP_CONCAT(cp.plat_nom ORDER BY cp.id SEPARATOR ', ') as plats_liste,
           liv.nom as livreur_nom
    FROM commandes c
    LEFT JOIN utilisateurs u ON c.client_id = u.id
    LEFT JOIN commande_plats cp ON c.id = cp.commande_id
    LEFT JOIN utilisateurs liv ON c.livreur_id = liv.id
    WHERE c.restaurant_id = ?
    GROUP BY c.id ORDER BY c.heure DESC
");
$stmtCmds->execute([$resto_id]);
$commandes = $stmtCmds->fetchAll();

$pending = count(array_filter($commandes, fn($c) => $c['statut'] === 'En attente'));
$enCours = count(array_filter($commandes, fn($c) => in_array($c['statut'], ['Validée','En préparation','Assignée','En cours de livraison'])));
$livrees = count(array_filter($commandes, fn($c) => $c['statut'] === 'Livrée'));
$ca      = array_sum(array_map(fn($c) => $c['statut'] === 'Livrée' ? $c['total'] : 0, $commandes));

$livreurs = $pdo->query("SELECT * FROM utilisateurs WHERE role='livreur' AND statut_livreur='Disponible'")->fetchAll();

$page  = $_GET['page'] ?? 'dashboard';
$toast = $_SESSION['toast'] ?? '';
unset($_SESSION['toast']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérant — <?= htmlspecialchars($resto['nom'] ?? '') ?></title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container-wide">
    <div class="header" style="background:#1C1917;padding:20px 24px;">
        <div class="header-row">
            <div>
                <p style="color:#A78BFA;font-size:12px;font-weight:600;letter-spacing:1px;">ESPACE GÉRANT</p>
                <h2><?= $resto['emoji'] ?? '🍽️' ?> <?= htmlspecialchars($resto['nom'] ?? '') ?></h2>
                <p style="color:#9CA3AF;font-size:12px;">Bonjour <?= htmlspecialchars($_SESSION['nom']) ?></p>
            </div>
            <div style="display:flex;gap:10px;align-items:center;">
                <?php if ($pending > 0): ?>
                <span style="background:#EF4444;color:#fff;border-radius:20px;padding:4px 12px;font-size:12px;font-weight:700;"><?= $pending ?> en attente</span>
                <?php endif; ?>
                <a href="../auth/logout.php" class="btn btn-sm" style="background:rgba(255,255,255,0.15);border-color:transparent;">🚪</a>
            </div>
        </div>
    </div>

    <nav class="tab-nav">
        <a href="?page=dashboard"  class="<?= $page==='dashboard'  ? 'active' : '' ?>">📊 Dashboard</a>
        <a href="?page=commandes"  class="<?= $page==='commandes'  ? 'active' : '' ?>">📋 Commandes</a>
        <a href="?page=historique" class="<?= $page==='historique' ? 'active' : '' ?>">🕓 Historique</a>
        <a href="?page=profil"     class="<?= $page==='profil'     ? 'active' : '' ?>">👤 Profil</a>
    </nav>

    <div style="padding:20px;">

    <?php if ($page === 'dashboard'): ?>
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-icon">⏳</div><div class="stat-value" style="color:#F97316;"><?= $pending ?></div><div class="stat-label">En attente</div></div>
            <div class="stat-card"><div class="stat-icon">🔄</div><div class="stat-value" style="color:#3B82F6;"><?= $enCours ?></div><div class="stat-label">En cours</div></div>
            <div class="stat-card"><div class="stat-icon">✅</div><div class="stat-value" style="color:#10B981;"><?= $livrees ?></div><div class="stat-label">Livrées</div></div>
            <div class="stat-card"><div class="stat-icon">💰</div><div class="stat-value" style="font-size:16px;color:#8B5CF6;"><?= number_format($ca,0,',',' ') ?> F</div><div class="stat-label">Chiffre d'affaires</div></div>
        </div>
        <h3 style="margin-bottom:14px;font-size:15px;font-weight:800;">📋 Nouvelles commandes</h3>
        <?php $nouvelles = array_filter($commandes, fn($c) => $c['statut'] === 'En attente'); ?>
        <?php if (empty($nouvelles)): ?><div class="card" style="text-align:center;color:#9CA3AF;">Aucune nouvelle commande</div><?php endif; ?>
        <?php foreach ($nouvelles as $cmd): ?><?php include '_commande_card.php'; ?><?php endforeach; ?>

    <?php elseif ($page === 'commandes'): ?>
        <h3 style="margin-bottom:14px;">Commandes actives</h3>
        <?php $actives = array_filter($commandes, fn($c) => !in_array($c['statut'], ['Livrée','Annulée'])); ?>
        <?php foreach ($actives as $cmd): include '_commande_card.php'; endforeach; ?>
        <?php if (empty($actives)): ?><div class="card" style="text-align:center;color:#9CA3AF;">Aucune commande active</div><?php endif; ?>

    <?php elseif ($page === 'historique'): ?>
        <h3 style="margin-bottom:14px;">Historique</h3>
        <?php $histo = array_filter($commandes, fn($c) => in_array($c['statut'], ['Livrée','Annulée'])); ?>
        <?php foreach ($histo as $cmd): include '_commande_card.php'; endforeach; ?>
        <?php if (empty($histo)): ?><div class="card" style="text-align:center;color:#9CA3AF;">Aucun historique</div><?php endif; ?>

    <?php elseif ($page === 'profil'): ?>
        <div class="card">
            <h4 style="margin-bottom:16px;font-size:16px;font-weight:700;">🏪 Informations du restaurant</h4>
            <div class="flex-between mb-1"><span style="color:#6B7280;">Nom</span><strong><?= htmlspecialchars($resto['nom'] ?? '') ?></strong></div>
            <div class="flex-between mb-1"><span style="color:#6B7280;">Cuisine</span><span><?= htmlspecialchars($resto['cuisine'] ?? '') ?></span></div>
            <div class="flex-between mb-1"><span style="color:#6B7280;">Zone</span><span><?= htmlspecialchars($resto['zone'] ?? '') ?></span></div>
            <div class="flex-between mb-1"><span style="color:#6B7280;">Note</span><span>⭐ <?= $resto['note'] ?></span></div>
            <div class="flex-between"><span style="color:#6B7280;">Gérant</span><strong><?= htmlspecialchars($_SESSION['nom']) ?></strong></div>
        </div>
        <div class="card">
            <h4 style="margin-bottom:16px;font-size:16px;font-weight:700;">📊 Statistiques</h4>
            <div class="flex-between mb-1"><span style="color:#6B7280;">Total commandes</span><span><?= count($commandes) ?></span></div>
            <div class="flex-between mb-1"><span style="color:#6B7280;">Commandes livrées</span><span style="color:#10B981;font-weight:700;"><?= $livrees ?></span></div>
            <div class="flex-between"><span style="color:#6B7280;">Chiffre d'affaires</span><span style="color:#8B5CF6;font-weight:700;"><?= number_format($ca,0,',',' ') ?> FCFA</span></div>
        </div>
    <?php endif; ?>

    </div>
</div>

<div id="modal-assign" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <h3>🛵 Assigner un livreur</h3>
            <button class="modal-close" onclick="document.getElementById('modal-assign').style.display='none'">✕</button>
        </div>
        <p id="assign-info" style="color:#6B7280;font-size:14px;margin-bottom:12px;"></p>
        <form method="POST">
            <input type="hidden" name="action" value="assigner">
            <input type="hidden" name="cmd_id" id="assign-cmd-id">
            <?php if (empty($livreurs)): ?>
            <p style="text-align:center;color:#9CA3AF;padding:20px 0;">Aucun livreur disponible</p>
            <?php else: ?>
            <?php foreach ($livreurs as $lv): ?>
            <label class="livreur-option">
                <input type="radio" name="livreur_id" value="<?= $lv['id'] ?>" required style="margin-right:10px;">
                <div>
                    <p style="margin:0;font-weight:700;">🛵 <?= htmlspecialchars($lv['nom']) ?></p>
                    <p style="margin:0;font-size:12px;color:#9CA3AF;">⭐ <?= $lv['note'] ?> · <?= $lv['missions'] ?> missions · <?= htmlspecialchars($lv['vehicule'] ?? '') ?></p>
                </div>
                <span style="font-size:12px;background:#D1FAE5;color:#065F46;padding:3px 10px;border-radius:20px;font-weight:600;"><?= htmlspecialchars($lv['statut_livreur']) ?></span>
            </label>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-full mt-1">✓ Confirmer l'assignation</button>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if ($toast): ?><div class="toast"><?= htmlspecialchars($toast) ?></div><?php endif; ?>
<script>
function ouvrirAssign(cmdId, cmdInfo) {
    document.getElementById('assign-cmd-id').value = cmdId;
    document.getElementById('assign-info').textContent = cmdInfo;
    document.getElementById('modal-assign').style.display = 'flex';
}
</script>
</body>
</html>
