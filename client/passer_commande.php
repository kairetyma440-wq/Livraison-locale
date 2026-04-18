<?php
require '../config.php';
requireLogin('client');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/livraison_locale/client/restaurants.php');

$plat_ids = $_POST['plats']          ?? [];
$qtes     = $_POST['qtes']           ?? [];
$adresse  = trim($_POST['adresse']   ?? '');
$zone_id  = intval($_POST['zone_id'] ?? 1);
$resto_id = intval($_POST['restaurant_id'] ?? 0);

if (!$adresse || !$plat_ids || !$resto_id) {
    redirect("/livraison_locale/client/menu.php?id=$resto_id");
}

$stmtZone = $pdo->prepare("SELECT * FROM zones WHERE id = ?");
$stmtZone->execute([$zone_id]);
$zone = $stmtZone->fetch();

$stmtResto = $pdo->prepare("SELECT * FROM restaurants WHERE id = ?");
$stmtResto->execute([$resto_id]);
$resto = $stmtResto->fetch();

$sousTotal    = 0;
$platsDetails = [];

foreach ($plat_ids as $i => $pid) {
    $qte = intval($qtes[$i] ?? 1);
    $stmtPlat = $pdo->prepare("SELECT * FROM plats WHERE id = ? AND restaurant_id = ? AND disponible = 1");
    $stmtPlat->execute([$pid, $resto_id]);
    $plat = $stmtPlat->fetch();
    if ($plat) {
        $sousTotal += $plat['prix'] * $qte;
        $platsDetails[] = ['plat' => $plat, 'qte' => $qte];
    }
}

$frais  = $zone['tarif_livraison'] ?? 500;
$total  = $sousTotal + $frais;
$cmd_id = genererIdCommande($pdo);

$stmt = $pdo->prepare("INSERT INTO commandes (id,client_id,restaurant_id,restaurant_nom,adresse,zone,sous_total,frais_livraison,total,statut) VALUES (?,?,?,?,?,?,?,?,?,'En attente')");
$stmt->execute([$cmd_id, $_SESSION['user_id'], $resto_id, $resto['nom'], $adresse, $zone['nom'], $sousTotal, $frais, $total]);

foreach ($platsDetails as $d) {
    $stmtLigne = $pdo->prepare("INSERT INTO commande_plats (commande_id,plat_id,plat_nom,qte,prix_unitaire) VALUES (?,?,?,?,?)");
    $stmtLigne->execute([$cmd_id, $d['plat']['id'], $d['plat']['nom'], $d['qte'], $d['plat']['prix']]);
}

$_SESSION['panier']       = [];
$_SESSION['panier_resto'] = null;
$_SESSION['toast']        = "✓ Commande $cmd_id passée avec succès !";

redirect('/livraison_locale/client/mes_commandes.php');
