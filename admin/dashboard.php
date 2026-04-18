<?php
require '../config.php';
requireLogin('admin');

$page = $_GET['page'] ?? 'dashboard';

$stats = [
    'commandes'  => $pdo->query("SELECT COUNT(*) FROM commandes")->fetchColumn(),
    'livrees'    => $pdo->query("SELECT COUNT(*) FROM commandes WHERE statut='Livrée'")->fetchColumn(),
    'en_attente' => $pdo->query("SELECT COUNT(*) FROM commandes WHERE statut='En attente'")->fetchColumn(),
    'clients'    => $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role='client'")->fetchColumn(),
    'livreurs'   => $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role='livreur'")->fetchColumn(),
    'ca'         => $pdo->query("SELECT COALESCE(SUM(total),0) FROM commandes WHERE statut='Livrée'")->fetchColumn(),
    'incidents'  => $pdo->query("SELECT COUNT(*) FROM commandes WHERE statut='Incident signalé'")->fetchColumn(),
];

$commandes = $pdo->query("
    SELECT c.*, u.nom as client_nom, liv.nom as livreur_nom, r.nom as resto_nom,
           GROUP_CONCAT(cp.plat_nom SEPARATOR ', ') as plats_liste
    FROM commandes c
    LEFT JOIN utilisateurs u ON c.client_id = u.id
    LEFT JOIN utilisateurs liv ON c.livreur_id = liv.id
    LEFT JOIN restaurants r ON c.restaurant_id = r.id
    LEFT JOIN commande_plats cp ON c.id = cp.commande_id
    GROUP BY c.id ORDER BY c.heure DESC
")->fetchAll();

$livreurs = $pdo->query("SELECT * FROM utilisateurs WHERE role='livreur' ORDER BY note DESC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'changer_statut') {
        $cmd_id  = $_POST['cmd_id'] ?? '';
        $statut  = $_POST['statut'] ?? '';
        $statuts = ['En attente','Validée','En préparation','Assignée','En cours de livraison','Livrée','Annulée','Incident signalé'];
        if (in_array($statut, $statuts)) {
            $pdo->prepare("UPDATE commandes SET statut=? WHERE id=?")->execute([$statut, $cmd_id]);
            $_SESSION['toast'] = "Statut mis à jour : $statut";
        }
    }
    if ($action === 'toggle_livreur') {
        $lv_id  = intval($_POST['livreur_id']);
        $statut = $_POST['statut_livreur'];
        if (in_array($statut, ['Disponible','En livraison','Hors ligne'])) {
            $pdo->prepare("UPDATE utilisateurs SET statut_livreur=? WHERE id=? AND role='livreur'")->execute([$statut, $lv_id]);
            $_SESSION['toast'] = "Statut livreur mis à jour.";
        }
    }
    redirect("/livraison_locale/admin/dashboard.php?page=$page");
}

$toast = $_SESSION['toast'] ?? '';
unset($_SESSION['toast']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — LivraisonLocale</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container-wide">
    <div class="header" style="background:linear-gradient(135deg,#064E3B,#065F46);padding:20px 24px;">
        <div class="header-row">
            <div>
                <p style="color:#6EE7B7;font-size:12px;font-weight:600;letter-spacing:1px;">ADMINISTRATION</p>
                <h2>🏛️ Tableau de bord</h2>
                <p style="color:#A7F3D0;font-size:12px;">Bonjour <?= htmlspecialchars($_SESSION['nom']) ?></p>
            </div>
            <a href="../auth/logout.php" class="btn btn-sm" style="background:rgba(255,255,255,0.15);border-color:transparent;color:#fff;">🚪</a>
        </div>
    </div>

    <nav class="tab-nav">
        <a href="?page=dashboard" class="<?= $page==='dashboard' ? 'active' : '' ?>">📊 Vue globale</a>
        <a href="?page=commandes" class="<?= $page==='commandes' ? 'active' : '' ?>">📋 Commandes</a>
        <a href="?page=livreurs"  class="<?= $page==='livreurs'  ? 'active' : '' ?>">🛵 Livreurs</a>
    </nav>

    <div style="padding:20px;">

    <?php if ($page === 'dashboard'): ?>
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-icon">📦</div><div class="stat-value"><?= $stats['commandes'] ?></div><div class="stat-label">Total commandes</div></div>
            <div class="stat-card"><div class="stat-icon">✅</div><div class="stat-value" style="color:#10B981;"><?= $stats['livrees'] ?></div><div class="stat-label">Livrées</div></div>
            <div class="stat-card"><div class="stat-icon">⏳</div><div class="stat-value" style="color:#F97316;"><?= $stats['en_attente'] ?></div><div class="stat-label">En attente</div></div>
            <div class="stat-card"><div class="stat-icon">👤</div><div class="stat-value"><?= $stats['clients'] ?></div><div class="stat-label">Clients</div></div>
            <div class="stat-card"><div class="stat-icon">🛵</div><div class="stat-value"><?= $stats['livreurs'] ?></div><div class="stat-label">Livreurs</div></div>
            <div class="stat-card"><div class="stat-icon">💰</div><div class="stat-value" style="font-size:14px;color:#8B5CF6;"><?= number_format($stats['ca'],0,',',' ') ?> F</div><div class="stat-label">Chiffre d'affaires</div></div>
            <div class="stat-card"><div class="stat-icon">⚠️</div><div class="stat-value" style="color:#EF4444;"><?= $stats['incidents'] ?></div><div class="stat-label">Incidents</div></div>
        </div>
        <h3 style="margin-bottom:14px;">Dernières commandes</h3>
        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;background:#fff;border-radius:12px;overflow:hidden;">
            <thead><tr style="background:#F8F7F5;text-align:left;">
                <th style="padding:12px;">ID</th>
                <th style="padding:12px;">Client</th>
                <th style="padding:12px;">Restaurant</th>
                <th style="padding:12px;">Total</th>
                <th style="padding:12px;">Statut</th>
                <th style="padding:12px;">Heure</th>
            </tr></thead>
            <tbody>
            <?php foreach (array_slice($commandes, 0, 10) as $cmd): ?>
            <tr style="border-top:1px solid #E5E7EB;">
                <td style="padding:10px;font-weight:700;"><?= $cmd['id'] ?></td>
                <td style="padding:10px;"><?= htmlspecialchars($cmd['client_nom'] ?? '') ?></td>
                <td style="padding:10px;"><?= htmlspecialchars($cmd['resto_nom'] ?? '') ?></td>
                <td style="padding:10px;font-weight:700;color:#FF6B35;"><?= formatPrix($cmd['total']) ?></td>
                <td style="padding:10px;"><?= statutBadge($cmd['statut']) ?></td>
                <td style="padding:10px;color:#9CA3AF;"><?= date('d/m H:i', strtotime($cmd['heure'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>

    <?php elseif ($page === 'commandes'): ?>
        <h3 style="margin-bottom:14px;">Toutes les commandes (<?= count($commandes) ?>)</h3>
        <?php foreach ($commandes as $cmd): ?>
        <div class="cmd-card">
            <div class="cmd-header">
                <div>
                    <span style="font-weight:800;"><?= $cmd['id'] ?></span>
                    <span style="font-size:12px;color:#9CA3AF;margin-left:8px;"><?= date('d/m H:i', strtotime($cmd['heure'])) ?></span>
                </div>
                <?= statutBadge($cmd['statut']) ?>
            </div>
            <div class="cmd-body">
                <div class="flex-between mb-1">
                    <span>👤 <?= htmlspecialchars($cmd['client_nom'] ?? '') ?></span>
                    <span style="font-weight:800;color:#FF6B35;"><?= formatPrix($cmd['total']) ?></span>
                </div>
                <p style="font-size:13px;color:#6B7280;margin-bottom:6px;">🍽️ <?= htmlspecialchars($cmd['resto_nom'] ?? '') ?> · <?= htmlspecialchars($cmd['plats_liste'] ?? '') ?></p>
                <p style="font-size:13px;color:#6B7280;margin-bottom:10px;">📍 <?= htmlspecialchars($cmd['adresse']) ?></p>
                <?php if ($cmd['livreur_nom']): ?>
                <p style="font-size:12px;background:#F0F9FF;padding:6px 10px;border-radius:8px;margin-bottom:10px;">🛵 <?= htmlspecialchars($cmd['livreur_nom']) ?></p>
                <?php endif; ?>
                <form method="POST" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <input type="hidden" name="action" value="changer_statut">
                    <input type="hidden" name="cmd_id" value="<?= $cmd['id'] ?>">
                    <select name="statut" class="form-control" style="flex:1;min-width:180px;">
                        <?php foreach (['En attente','Validée','En préparation','Assignée','En cours de livraison','Livrée','Annulée','Incident signalé'] as $s): ?>
                        <option value="<?= $s ?>" <?= $cmd['statut']===$s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-sm btn-purple">Mettre à jour</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>

    <?php elseif ($page === 'livreurs'): ?>
        <h3 style="margin-bottom:14px;">Gestion des livreurs (<?= count($livreurs) ?>)</h3>
        <?php foreach ($livreurs as $lv): ?>
        <div class="card">
            <div class="flex-between mb-1">
                <div>
                    <span style="font-weight:800;font-size:15px;">🛵 <?= htmlspecialchars($lv['nom']) ?></span>
                    <span style="font-size:12px;color:#9CA3AF;margin-left:8px;"><?= htmlspecialchars($lv['email']) ?></span>
                </div>
                <span style="font-weight:700;color:#10B981;">⭐ <?= $lv['note'] ?></span>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:12px;font-size:13px;">
                <div style="text-align:center;background:#F3F4F6;padding:8px;border-radius:8px;">
                    <div style="font-weight:700;"><?= $lv['missions'] ?></div>
                    <div style="color:#9CA3AF;font-size:11px;">Missions</div>
                </div>
                <div style="text-align:center;background:#F3F4F6;padding:8px;border-radius:8px;">
                    <div style="font-weight:700;color:#10B981;"><?= number_format($lv['gains'],0,',',' ') ?></div>
                    <div style="color:#9CA3AF;font-size:11px;">FCFA gagnés</div>
                </div>
                <div style="text-align:center;background:#F3F4F6;padding:8px;border-radius:8px;">
                    <div style="font-weight:700;"><?= htmlspecialchars($lv['vehicule'] ?? '-') ?></div>
                    <div style="color:#9CA3AF;font-size:11px;">Véhicule</div>
                </div>
            </div>
            <form method="POST" style="display:flex;gap:8px;align-items:center;">
                <input type="hidden" name="action" value="toggle_livreur">
                <input type="hidden" name="livreur_id" value="<?= $lv['id'] ?>">
                <select name="statut_livreur" class="form-control" style="flex:1;">
                    <?php foreach (['Disponible','En livraison','Hors ligne'] as $s): ?>
                    <option value="<?= $s ?>" <?= $lv['statut_livreur']===$s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sm btn-green">Mettre à jour</button>
            </form>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    </div>
</div>
<?php if ($toast): ?><div class="toast"><?= htmlspecialchars($toast) ?></div><?php endif; ?>
</body>
</html>
