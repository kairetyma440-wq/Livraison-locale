<?php
require '../config.php';
requireLogin('client');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cmd_id = $_POST['cmd_id'] ?? '';
    $action = $_POST['action'] ?? '';

    if ($action === 'annuler') {
        $stmt = $pdo->prepare("UPDATE commandes SET statut='Annulée' WHERE id=? AND client_id=? AND statut IN ('En attente','Validée')");
        $stmt->execute([$cmd_id, $_SESSION['user_id']]);
        $_SESSION['toast'] = 'Commande annulée.';
    }
    if ($action === 'evaluer') {
        $note = intval($_POST['note'] ?? 0);
        $comm = trim($_POST['commentaire'] ?? '');
        if ($note >= 1 && $note <= 5) {
            $stmtCmd = $pdo->prepare("SELECT livreur_id FROM commandes WHERE id=? AND client_id=? AND statut='Livrée'");
            $stmtCmd->execute([$cmd_id, $_SESSION['user_id']]);
            $cmd = $stmtCmd->fetch();
            if ($cmd && $cmd['livreur_id']) {
                $stmtEval = $pdo->prepare("INSERT INTO evaluations (commande_id,client_id,livreur_id,note,commentaire) VALUES (?,?,?,?,?)");
                $stmtEval->execute([$cmd_id, $_SESSION['user_id'], $cmd['livreur_id'], $note, $comm]);
                $_SESSION['toast'] = '⭐ Évaluation envoyée, merci !';
            }
        }
    }
    if ($action === 'signaler') {
        $stmt = $pdo->prepare("UPDATE commandes SET statut='Incident signalé' WHERE id=? AND client_id=?");
        $stmt->execute([$cmd_id, $_SESSION['user_id']]);
        $_SESSION['toast'] = "⚠️ Incident signalé à l'équipe.";
    }
    redirect('/livraison_locale/client/mes_commandes.php');
}

$stmt = $pdo->prepare("
    SELECT c.*, GROUP_CONCAT(cp.plat_nom ORDER BY cp.id SEPARATOR ', ') as plats_liste, u.nom as livreur_nom
    FROM commandes c
    LEFT JOIN commande_plats cp ON c.id = cp.commande_id
    LEFT JOIN utilisateurs u ON c.livreur_id = u.id
    WHERE c.client_id = ?
    GROUP BY c.id ORDER BY c.heure DESC
");
$stmt->execute([$_SESSION['user_id']]);
$commandes = $stmt->fetchAll();

$evalsDone = [];
$stmtEvals = $pdo->prepare("SELECT commande_id FROM evaluations WHERE client_id = ?");
$stmtEvals->execute([$_SESSION['user_id']]);
foreach ($stmtEvals->fetchAll() as $e) { $evalsDone[$e['commande_id']] = true; }

$toast = $_SESSION['toast'] ?? '';
unset($_SESSION['toast']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes commandes — LivraisonLocale</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container">
    <div class="header" style="background:#FF6B35;"><h2>📦 Mes commandes</h2></div>

    <div class="page-content">
        <?php if (empty($commandes)): ?>
        <div class="empty-state">
            <span class="empty-icon">📦</span>
            <p>Aucune commande pour le moment.</p>
            <a href="restaurants.php" class="btn mt-2">Commander maintenant</a>
        </div>
        <?php endif; ?>

        <?php foreach ($commandes as $cmd): ?>
        <div class="cmd-card">
            <div class="cmd-header">
                <span style="font-weight:800;"><?= $cmd['id'] ?></span>
                <?= statutBadge($cmd['statut']) ?>
            </div>
            <div class="cmd-body">
                <p style="font-weight:700;font-size:14px;margin-bottom:3px;"><?= htmlspecialchars($cmd['restaurant_nom']) ?></p>
                <p style="font-size:12px;color:#9CA3AF;margin-bottom:8px;"><?= htmlspecialchars($cmd['plats_liste'] ?? '') ?></p>
                <div class="flex-between">
                    <span style="font-weight:800;color:#FF6B35;"><?= formatPrix($cmd['total']) ?></span>
                    <span style="font-size:12px;color:#9CA3AF;"><?= date('d/m H:i', strtotime($cmd['heure'])) ?></span>
                </div>
                <?php if ($cmd['livreur_nom']): ?>
                <p style="margin-top:8px;font-size:12px;color:#6B7280;background:#F9FAFB;padding:6px 10px;border-radius:8px;">
                    🛵 Livreur : <strong><?= htmlspecialchars($cmd['livreur_nom']) ?></strong>
                </p>
                <?php endif; ?>
                <div class="cmd-actions mt-1">
                    <?php if (in_array($cmd['statut'], ['En attente','Validée'])): ?>
                    <form method="POST">
                        <input type="hidden" name="cmd_id" value="<?= $cmd['id'] ?>">
                        <input type="hidden" name="action" value="annuler">
                        <button type="submit" class="btn btn-sm btn-outline-red" onclick="return confirm('Annuler cette commande ?')">Annuler</button>
                    </form>
                    <?php endif; ?>
                    <?php if ($cmd['statut'] === 'Livrée' && !isset($evalsDone[$cmd['id']])): ?>
                    <button class="btn btn-sm btn-green" onclick="ouvrirEval('<?= $cmd['id'] ?>')">⭐ Évaluer</button>
                    <?php endif; ?>
                    <?php if (in_array($cmd['statut'], ['En cours de livraison','En préparation'])): ?>
                    <button class="btn btn-sm" style="background:#F97316;border-color:#F97316;" onclick="ouvrirSignal('<?= $cmd['id'] ?>')">⚠️ Signaler</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <nav class="bottom-nav">
        <a href="restaurants.php"><span class="icon">🏠</span><span class="label">Accueil</span></a>
        <a href="mes_commandes.php" class="active"><span class="icon">📦</span><span class="label">Mes commandes</span></a>
        <a href="profil.php"><span class="icon">👤</span><span class="label">Profil</span></a>
    </nav>
</div>

<div id="modal-eval" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <h3>⭐ Évaluer le livreur</h3>
            <button class="modal-close" onclick="document.getElementById('modal-eval').style.display='none'">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="evaluer">
            <input type="hidden" name="cmd_id" id="eval-cmd-id">
            <div class="form-group">
                <label>Note (1 à 5 étoiles)</label>
                <div id="stars" style="display:flex;gap:8px;font-size:32px;">
                    <?php for ($i=1;$i<=5;$i++): ?>
                    <span onclick="setNote(<?= $i ?>)" style="cursor:pointer;color:#D1D5DB;" data-val="<?= $i ?>">★</span>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="note" id="note-val" required>
            </div>
            <div class="form-group">
                <label>Commentaire (optionnel)</label>
                <input type="text" name="commentaire" class="form-control" placeholder="Rapide, ponctuel...">
            </div>
            <button type="submit" class="btn btn-green btn-full">Envoyer l'évaluation</button>
        </form>
    </div>
</div>

<div id="modal-signal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <h3>⚠️ Signaler un problème</h3>
            <button class="modal-close" onclick="document.getElementById('modal-signal').style.display='none'">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="signaler">
            <input type="hidden" name="cmd_id" id="signal-cmd-id">
            <?php foreach (["Retard excessif","Commande incorrecte","Livreur injoignable","Autre"] as $p): ?>
            <label style="display:block;padding:10px 14px;border-radius:10px;border:2px solid #E5E7EB;margin-bottom:8px;cursor:pointer;">
                <input type="radio" name="probleme" value="<?= $p ?>" required style="margin-right:8px;"><?= $p ?>
            </label>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-red btn-full mt-1">Envoyer le signalement</button>
        </form>
    </div>
</div>

<?php if ($toast): ?><div class="toast"><?= htmlspecialchars($toast) ?></div><?php endif; ?>

<script>
function ouvrirEval(id) { document.getElementById('eval-cmd-id').value=id; document.getElementById('modal-eval').style.display='flex'; }
function ouvrirSignal(id) { document.getElementById('signal-cmd-id').value=id; document.getElementById('modal-signal').style.display='flex'; }
function setNote(n) {
    document.getElementById('note-val').value=n;
    document.querySelectorAll('#stars span').forEach(s => { s.style.color = parseInt(s.dataset.val)<=n ? '#F59E0B' : '#D1D5DB'; });
}
</script>
</body>
</html>
