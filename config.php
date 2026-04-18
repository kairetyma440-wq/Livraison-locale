<?php
session_start();

$host   = 'localhost';
$dbname = 'livraison_locale';
$user   = 'root';
$pass   = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('<div style="font-family:sans-serif;background:#FEE2E2;color:#991B1B;padding:20px;border-radius:8px;margin:20px;">
        <strong>Erreur de connexion :</strong><br>' . $e->getMessage() . '<br><br>
        Vérifiez que XAMPP est démarré et que la base <strong>livraison_locale</strong> existe.
    </div>');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin($role = null) {
    if (!isLoggedIn()) {
        header('Location: /livraison_locale/auth/login.php');
        exit;
    }
    if ($role && $_SESSION['role'] !== $role) {
        header('Location: /livraison_locale/auth/login.php?erreur=acces_refuse');
        exit;
    }
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function genererIdCommande($pdo) {
    $stmt  = $pdo->query("SELECT COUNT(*) as total FROM commandes");
    $count = $stmt->fetch()['total'];
    return 'CMD-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
}

function formatPrix($prix) {
    return number_format($prix, 0, ',', ' ') . ' FCFA';
}

function statutBadge($statut) {
    $colors = [
        'En attente'            => ['bg'=>'#FFF7ED','text'=>'#C2410C','dot'=>'#F97316'],
        'Validée'               => ['bg'=>'#EFF6FF','text'=>'#1D4ED8','dot'=>'#3B82F6'],
        'En préparation'        => ['bg'=>'#FEFCE8','text'=>'#A16207','dot'=>'#EAB308'],
        'Assignée'              => ['bg'=>'#F0FDF4','text'=>'#166534','dot'=>'#22C55E'],
        'En cours de livraison' => ['bg'=>'#F0F9FF','text'=>'#0C4A6E','dot'=>'#0EA5E9'],
        'Livrée'                => ['bg'=>'#F0FDF4','text'=>'#14532D','dot'=>'#16A34A'],
        'Annulée'               => ['bg'=>'#FFF1F2','text'=>'#9F1239','dot'=>'#F43F5E'],
        'Incident signalé'      => ['bg'=>'#FFF1F2','text'=>'#7F1D1D','dot'=>'#EF4444'],
    ];
    $c = $colors[$statut] ?? ['bg'=>'#F3F4F6','text'=>'#374151','dot'=>'#9CA3AF'];
    return "<span style='background:{$c['bg']};color:{$c['text']};padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:5px;'>
        <span style='width:7px;height:7px;border-radius:50%;background:{$c['dot']};display:inline-block;'></span>
        " . htmlspecialchars($statut) . "
    </span>";
}
