<?php
require '../config.php';
requireLogin('client');

$resto_id = intval($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM restaurants WHERE id = ?");
$stmt->execute([$resto_id]);
$resto = $stmt->fetch();
if (!$resto) redirect('/livraison_locale/client/restaurants.php');

$stmtPlats = $pdo->prepare("SELECT * FROM plats WHERE restaurant_id = ? ORDER BY disponible DESC");
$stmtPlats->execute([$resto_id]);
$plats = $stmtPlats->fetchAll();

if (!isset($_SESSION['panier']))       $_SESSION['panier']       = [];
if (!isset($_SESSION['panier_resto'])) $_SESSION['panier_resto'] = null;

if ($_SESSION['panier_resto'] !== $resto_id) {
    $_SESSION['panier']       = [];
    $_SESSION['panier_resto'] = $resto_id;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
    $plat_id = intval($_POST['plat_id']);
    $found = false;
    foreach ($_SESSION['panier'] as &$item) {
        if ($item['id'] === $plat_id) { $item['qte']++; $found = true; break; }
    }
    if (!$found) {
        foreach ($plats as $p) {
            if ($p['id'] === $plat_id && $p['disponible']) {
                $_SESSION['panier'][] = ['id'=>$p['id'],'nom'=>$p['nom'],'prix'=>$p['prix'],'qte'=>1];
                break;
            }
        }
    }
    redirect("menu.php?id=$resto_id");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['retirer'])) {
    $plat_id = intval($_POST['plat_id']);
    foreach ($_SESSION['panier'] as $k => &$item) {
        if ($item['id'] === $plat_id) {
            if ($item['qte'] > 1) $item['qte']--;
            else unset($_SESSION['panier'][$k]);
            break;
        }
    }
    $_SESSION['panier'] = array_values($_SESSION['panier']);
    redirect("menu.php?id=$resto_id");
}

$panier        = $_SESSION['panier'];
$totalArticles = array_sum(array_column($panier, 'qte'));
$sousTotal     = array_sum(array_map(fn($p) => $p['prix'] * $p['qte'], $panier));
$zones         = $pdo->query("SELECT * FROM zones ORDER BY tarif_livraison")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($resto['nom']) ?> — LivraisonLocale</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container">
    <div class="header" style="background:#1C1917;">
        <div class="header-row">
            <div>
                <p style="color:#9CA3AF;font-size:12px;margin-bottom:4px;">
                    <a href="restaurants.php" style="color:#FF6B35;text-decoration:none;">← Retour</a>
                </p>
                <h2><?= $resto['emoji'] ?> <?= htmlspecialchars($resto['nom']) ?></h2>
                <p style="color:#9CA3AF;">⭐ <?= $resto['note'] ?> · <?= htmlspecialchars($resto['cuisine']) ?></p>
            </div>
            <?php if ($totalArticles > 0): ?>
            <button onclick="document.getElementById('modal-commande').style.display='flex'" class="btn btn-sm" style="white-space:nowrap;">
                🛒 <?= $totalArticles ?> · <?= formatPrix($sousTotal) ?>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="page-content">
        <h3 style="margin-bottom:14px;font-size:15px;font-weight:800;">🍴 Menu</h3>

        <?php foreach ($plats as $p): ?>
        <div class="plat-card <?= !$p['disponible'] ? 'plat-indispo' : '' ?>">
            <div class="plat-info" style="flex:1;">
                <h4><?= htmlspecialchars($p['nom']) ?>
                    <?php if (!$p['disponible']): ?>
                    <span style="font-size:11px;background:#FEE2E2;color:#991B1B;padding:2px 6px;border-radius:8px;font-weight:600;">Indisponible</span>
                    <?php endif; ?>
                </h4>
                <p><?= htmlspecialchars($p['description']) ?></p>
                <span class="plat-prix"><?= formatPrix($p['prix']) ?></span>
            </div>
            <?php if ($p['disponible']): ?>
            <?php
            $qte = 0;
            foreach ($panier as $item) {
                if ($item['id'] === $p['id']) { $qte = $item['qte']; break; }
            }
            ?>
            <div style="margin-left:12px;">
                <?php if ($qte > 0): ?>
                <div class="qte-control">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="plat_id" value="<?= $p['id'] ?>">
                        <button type="submit" name="retirer" class="qte-btn minus">−</button>
                    </form>
                    <span class="qte-val"><?= $qte ?></span>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="plat_id" value="<?= $p['id'] ?>">
                        <button type="submit" name="ajouter" class="qte-btn">+</button>
                    </form>
                </div>
                <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="plat_id" value="<?= $p['id'] ?>">
                    <button type="submit" name="ajouter" class="qte-btn">+</button>
                </form>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($totalArticles > 0): ?>
    <div style="position:fixed;bottom:20px;left:50%;transform:translateX(-50%);width:90%;max-width:420px;z-index:50;">
        <button onclick="document.getElementById('modal-commande').style.display='flex'"
            class="btn btn-full" style="box-shadow:0 8px 20px rgba(255,107,53,0.4);">
            Voir le panier (<?= $totalArticles ?> article<?= $totalArticles > 1 ? 's' : '' ?>) — <?= formatPrix($sousTotal) ?>
        </button>
    </div>
    <?php endif; ?>
</div>

<div id="modal-commande" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <h3>🛒 Finaliser la commande</h3>
            <button class="modal-close" onclick="document.getElementById('modal-commande').style.display='none'">✕</button>
        </div>
        <div style="margin-bottom:16px;">
            <?php foreach ($panier as $item): ?>
            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #E5E7EB;">
                <span style="font-size:14px;"><?= htmlspecialchars($item['nom']) ?> × <?= $item['qte'] ?></span>
                <span style="font-weight:700;"><?= formatPrix($item['prix'] * $item['qte']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <form method="POST" action="passer_commande.php">
            <?php foreach ($panier as $item): ?>
            <input type="hidden" name="plats[]" value="<?= $item['id'] ?>">
            <input type="hidden" name="qtes[]"  value="<?= $item['qte'] ?>">
            <?php endforeach; ?>
            <input type="hidden" name="restaurant_id" value="<?= $resto_id ?>">
            <div class="form-group">
                <label>Adresse de livraison</label>
                <input type="text" name="adresse" class="form-control" placeholder="Ex: 12 Rue des Baobabs, Dakar" required>
            </div>
            <div class="form-group">
                <label>Zone de livraison</label>
                <select name="zone_id" id="zone-select" class="form-control" onchange="updateTotal()">
                    <?php foreach ($zones as $z): ?>
                    <option value="<?= $z['id'] ?>" data-tarif="<?= $z['tarif_livraison'] ?>">
                        <?= htmlspecialchars($z['nom']) ?> — <?= formatPrix($z['tarif_livraison']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="background:#F9FAFB;border-radius:12px;padding:14px;margin-bottom:16px;">
                <div class="flex-between mb-1">
                    <span style="font-size:13px;color:#6B7280;">Sous-total</span>
                    <span style="font-size:13px;font-weight:600;"><?= formatPrix($sousTotal) ?></span>
                </div>
                <div class="flex-between mb-1">
                    <span style="font-size:13px;color:#6B7280;">Frais de livraison</span>
                    <span id="frais-livraison" style="font-size:13px;font-weight:600;"><?= formatPrix($zones[0]['tarif_livraison']) ?></span>
                </div>
                <div style="border-top:1px solid #E5E7EB;padding-top:8px;" class="flex-between">
                    <span style="font-weight:800;">Total</span>
                    <span id="total-final" style="font-weight:800;color:#FF6B35;font-size:16px;"><?= formatPrix($sousTotal + $zones[0]['tarif_livraison']) ?></span>
                </div>
            </div>
            <button type="submit" class="btn btn-full">✓ Confirmer la commande</button>
        </form>
    </div>
</div>
<script>
const sousTotal = <?= $sousTotal ?>;
function updateTotal() {
    const sel   = document.getElementById('zone-select');
    const tarif = parseInt(sel.options[sel.selectedIndex].dataset.tarif);
    document.getElementById('frais-livraison').textContent = tarif.toLocaleString('fr') + ' FCFA';
    document.getElementById('total-final').textContent = (sousTotal + tarif).toLocaleString('fr') + ' FCFA';
}
</script>
</body>
</html>
