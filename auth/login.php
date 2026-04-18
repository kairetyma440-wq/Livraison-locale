<?php
require '../config.php';

if (isLoggedIn()) {
    $r = $_SESSION['role'];
    if ($r==='client')  redirect('/livraison_locale/client/restaurants.php');
    if ($r==='gerant')  redirect('/livraison_locale/gerant/dashboard.php');
    if ($r==='livreur') redirect('/livraison_locale/livreur/missions.php');
    if ($r==='admin')   redirect('/livraison_locale/admin/dashboard.php');
}

$role   = $_GET['role'] ?? 'client';
$erreur = '';

if (($_GET['erreur'] ?? '') === 'acces_refuse') {
    $erreur = 'Accès refusé pour ce rôle.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mdp   = trim($_POST['mot_de_passe'] ?? '');
    $role  = $_POST['role'] ?? 'client';

    if (!$email || !$mdp) {
        $erreur = 'Veuillez remplir tous les champs.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ? AND role = ?");
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch();

        if ($user && password_verify($mdp, $user['mot_de_passe'])) {
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['nom']           = $user['nom'];
            $_SESSION['email']         = $user['email'];
            $_SESSION['role']          = $user['role'];
            $_SESSION['restaurant_id'] = $user['restaurant_id'];

            if ($role === 'client')  redirect('/livraison_locale/client/restaurants.php');
            if ($role === 'gerant')  redirect('/livraison_locale/gerant/dashboard.php');
            if ($role === 'livreur') redirect('/livraison_locale/livreur/missions.php');
            if ($role === 'admin')   redirect('/livraison_locale/admin/dashboard.php');
        } else {
            $erreur = 'Email ou mot de passe incorrect.';
        }
    }
}

$roleLabels = [
    'client'  => ['emoji'=>'🧑‍🍳', 'label'=>'Client',        'color'=>'#FF6B35'],
    'gerant'  => ['emoji'=>'👨‍🍳', 'label'=>'Gérant',         'color'=>'#8B5CF6'],
    'livreur' => ['emoji'=>'🛵',   'label'=>'Livreur',        'color'=>'#0EA5E9'],
    'admin'   => ['emoji'=>'🏛️',  'label'=>'Administrateur', 'color'=>'#10B981'],
];
$rl = $roleLabels[$role] ?? $roleLabels['client'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — LivraisonLocale</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>body { background: <?= $rl['color'] ?>; }</style>
</head>
<body>
<div class="login-page">
    <div class="login-box">
        <div class="login-logo">
            <span class="emoji"><?= $rl['emoji'] ?></span>
            <h2>LivraisonLocale</h2>
            <p>Espace <?= $rl['label'] ?></p>
        </div>

        <?php if ($erreur): ?>
        <div class="alert alert-error"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" placeholder="votre@email.sn"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="mot_de_passe" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-full" style="background:<?= $rl['color'] ?>;border-color:<?= $rl['color'] ?>;margin-top:8px;">
                Se connecter
            </button>
        </form>

        <?php if ($role === 'client'): ?>
        <div class="login-switch">
            Pas encore de compte ? <a href="register.php">S'inscrire</a>
        </div>
        <?php endif; ?>

        <a href="/livraison_locale/index.php" style="display:block;text-align:center;margin-top:14px;font-size:13px;color:#9CA3AF;">
            ← Changer de rôle
        </a>
    </div>
</div>
</body>
</html>