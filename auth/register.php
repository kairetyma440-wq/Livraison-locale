<?php
require '../config.php';

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom   = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mdp   = trim($_POST['mot_de_passe'] ?? '');
    $tel   = trim($_POST['telephone'] ?? '');

    if (!$nom || !$email || !$mdp) {
        $erreur = 'Veuillez remplir tous les champs obligatoires.';
    } elseif (strlen($mdp) < 6) {
        $erreur = 'Le mot de passe doit contenir au moins 6 caractères.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $erreur = 'Cet email est déjà utilisé.';
        } else {
            $hash = password_hash($mdp, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe, role, telephone) VALUES (?, ?, ?, 'client', ?)");
            $stmt->execute([$nom, $email, $hash, $tel]);

            $id = $pdo->lastInsertId();
            $_SESSION['user_id'] = $id;
            $_SESSION['nom']     = $nom;
            $_SESSION['email']   = $email;
            $_SESSION['role']    = 'client';
            redirect('/livraison_locale/client/restaurants.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription — LivraisonLocale</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>body { background: #FF6B35; }</style>
</head>
<body>
<div class="login-page">
    <div class="login-box">
        <div class="login-logo">
            <span class="emoji">🍽️</span>
            <h2>LivraisonLocale</h2>
            <p>Créez votre compte client</p>
        </div>

        <?php if ($erreur): ?>
        <div class="alert alert-error"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Nom complet *</label>
                <input type="text" name="nom" class="form-control" placeholder="Jean Dupont" required
                    value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" class="form-control" placeholder="exemple@email.sn" required
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Téléphone</label>
                <input type="text" name="telephone" class="form-control" placeholder="77 000 00 00"
                    value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Mot de passe *</label>
                <input type="password" name="mot_de_passe" class="form-control" placeholder="Min. 6 caractères" required>
            </div>
            <button type="submit" class="btn btn-full" style="margin-top:16px;">S'inscrire</button>
        </form>

        <div class="login-switch">
            Déjà un compte ? <a href="login.php?role=client">Se connecter</a>
        </div>
    </div>
</div>
</body>
</html>
