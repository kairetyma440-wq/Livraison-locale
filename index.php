<?php
require 'config.php';

if (isLoggedIn()) {
    $role = $_SESSION['role'];
    if ($role === 'client')  redirect('/livraison_locale/client/restaurants.php');
    if ($role === 'gerant')  redirect('/livraison_locale/gerant/dashboard.php');
    if ($role === 'livreur') redirect('/livraison_locale/livreur/missions.php');
    if ($role === 'admin')   redirect('/livraison_locale/admin/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LivraisonLocale</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body { background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .welcome-box { max-width: 480px; width: 100%; }
        .role-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .role-btn { border: none; border-radius: 20px; padding: 24px 16px; cursor: pointer; text-align: center; color: #fff; text-decoration: none; display: block; transition: transform 0.2s, box-shadow 0.2s; }
        .role-btn:hover { transform: scale(1.04); box-shadow: 0 15px 30px rgba(0,0,0,0.25); }
        .role-btn .role-icon  { font-size: 44px; display: block; margin-bottom: 12px; }
        .role-btn .role-title { font-size: 18px; font-weight: 800; display: block; margin-bottom: 4px; }
        .role-btn .role-desc  { font-size: 12px; opacity: 0.9; display: block; }
        .role-client  { background: linear-gradient(135deg, #FF6B35, #F7931E); }
        .role-gerant  { background: linear-gradient(135deg, #8B5CF6, #6D28D9); }
        .role-livreur { background: linear-gradient(135deg, #0EA5E9, #0284C7); }
        .role-admin   { background: linear-gradient(135deg, #10B981, #059669); }
    </style>
</head>
<body>
<div class="welcome-box">
    <div style="text-align:center;margin-bottom:40px;">
        <span style="font-size:64px;background:rgba(255,255,255,0.2);display:inline-block;padding:20px;border-radius:50%;">🍕🍔🍜</span>
        <h1 style="color:#fff;font-size:32px;font-weight:800;margin:16px 0 8px;">Livraison<span style="color:#FFD700;">Locale</span></h1>
        <p style="color:rgba(255,255,255,0.85);font-size:15px;">La livraison de repas à Dakar</p>
    </div>
    <div class="role-grid">
        <a href="auth/login.php?role=client"  class="role-btn role-client">
            <span class="role-icon">🧑‍🍳</span>
            <span class="role-title">Client</span>
            <span class="role-desc">Commandez vos plats préférés</span>
        </a>
        <a href="auth/login.php?role=gerant"  class="role-btn role-gerant">
            <span class="role-icon">👨‍🍳</span>
            <span class="role-title">Gérant</span>
            <span class="role-desc">Gérez votre restaurant</span>
        </a>
        <a href="auth/login.php?role=livreur" class="role-btn role-livreur">
            <span class="role-icon">🛵</span>
            <span class="role-title">Livreur</span>
            <span class="role-desc">Gérez vos livraisons</span>
        </a>
        <a href="auth/login.php?role=admin"   class="role-btn role-admin">
            <span class="role-icon">🏛️</span>
            <span class="role-title">Administrateur</span>
            <span class="role-desc">Supervision globale</span>
        </a>
    </div>
    <p style="text-align:center;margin-top:32px;color:rgba(255,255,255,0.7);font-size:11px;">
        🍽️ Plateforme de livraison de repas — Version 1.0
    </p>
</div>
</body>
</html>
