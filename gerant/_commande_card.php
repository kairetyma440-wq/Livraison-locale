<?php // Partial : carte commande gérant ?>
<div class="cmd-card" style="margin-bottom:14px;">
    <div class="cmd-header">
        <div>
            <span style="font-weight:800;"><?= $cmd['id'] ?></span>
            <span style="font-size:12px;color:#9CA3AF;margin-left:8px;"><?= date('d/m H:i', strtotime($cmd['heure'])) ?></span>
        </div>
        <?= statutBadge($cmd['statut']) ?>
    </div>
    <div class="cmd-body">
        <div class="flex-between mb-1">
            <span style="font-weight:700;font-size:14px;">👤 <?= htmlspecialchars($cmd['client_nom']) ?></span>
            <span style="font-weight:800;color:#FF6B35;"><?= formatPrix($cmd['total']) ?></span>
        </div>
        <p style="font-size:12px;color:#9CA3AF;margin-bottom:6px;">📍 <?= htmlspecialchars($cmd['adresse']) ?> (<?= htmlspecialchars($cmd['zone']) ?>)</p>
        <p style="font-size:13px;margin-bottom:10px;">🍽️ <?= htmlspecialchars($cmd['plats_liste'] ?? '') ?></p>
        <?php if ($cmd['livreur_nom']): ?>
        <p style="font-size:12px;background:#F0F9FF;padding:6px 10px;border-radius:8px;margin-bottom:10px;">
            🛵 <strong><?= htmlspecialchars($cmd['livreur_nom']) ?></strong>
        </p>
        <?php endif; ?>
        <div class="cmd-actions">
            <?php if ($cmd['statut'] === 'En attente'): ?>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="cmd_id" value="<?= $cmd['id'] ?>">
                <input type="hidden" name="action" value="valider">
                <button type="submit" class="btn btn-sm btn-blue">✓ Valider</button>
            </form>
            <?php endif; ?>
            <?php if ($cmd['statut'] === 'Validée'): ?>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="cmd_id" value="<?= $cmd['id'] ?>">
                <input type="hidden" name="action" value="preparer">
                <button type="submit" class="btn btn-sm" style="background:#EAB308;border-color:#EAB308;">🍳 Préparer</button>
            </form>
            <?php endif; ?>
            <?php if (in_array($cmd['statut'], ['Validée','En préparation']) && !$cmd['livreur_id']): ?>
            <button class="btn btn-sm" onclick="ouvrirAssign('<?= $cmd['id'] ?>', 'Commande <?= $cmd['id'] ?> — <?= addslashes($cmd['adresse']) ?>')">
                🛵 Assigner livreur
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>
