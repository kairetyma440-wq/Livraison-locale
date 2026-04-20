# 🍕 LivraisonLocale

> Plateforme de livraison de repas à Dakar — Application PHP/MySQL

---

## 📋 Présentation

LivraisonLocale est une application web de livraison de repas développée en PHP natif avec MySQL. Elle met en relation **clients**, **gérants de restaurants**, **livreurs** et **administrateurs** au travers d'interfaces dédiées à chaque rôle.

---

## ✨ Fonctionnalités

### 🧑‍🍳 Client
- Parcourir les restaurants disponibles avec recherche
- Consulter les menus et ajouter des plats au panier
- Passer une commande avec choix de zone de livraison
- Suivre ses commandes en temps réel (statuts colorés)

### 👨‍🍳 Gérant de restaurant
- Tableau de bord des commandes de son restaurant
- Valider, mettre en préparation et assigner un livreur
- Visualiser le chiffre d'affaires et les statistiques

### 🛵 Livreur
- Voir les missions qui lui sont assignées
- Mettre à jour le statut : en route → livré
- Signaler un incident
- Suivre ses gains et son historique

### 🏛 Administrateur
- Supervision globale de toutes les commandes
- Gestion manuelle des statuts
- Gestion de la disponibilité des livreurs

---

## 🗂 Structure du projet

```
livraison_locale/
├── config.php              # Connexion BDD, sessions, fonctions globales
├── database.sql            # Schéma MySQL + données de démonstration
├── index.php               # Page d'accueil — choix du rôle
│
├── auth/
│   ├── login.php           # Connexion (tous rôles)
│   ├── register.php        # Inscription (clients uniquement)
│   └── logout.php          # Déconnexion
│
├── client/
│   ├── restaurants.php     # Liste des restaurants
│   ├── menu.php            # Menu + gestion du panier
│   ├── passer_commande.php # Validation de commande
│   ├── mes_commandes.php   # Suivi des commandes
│   └── profil.php          # Profil client
│
├── gerant/
│   ├── dashboard.php       # Tableau de bord gérant
│   └── _commande_card.php  # Composant carte commande
│
├── livreur/
│   └── missions.php        # Missions du livreur
│
├── admin/
│   └── dashboard.php       # Supervision globale
│
└── assets/
    └── style.css           # Feuille de style commune
```

---

## 🗃 Base de données

### Tables principales

| Table | Description |
|---|---|
| `utilisateurs` | Tous les comptes (client, gérant, livreur, admin) |
| `restaurants` | Restaurants disponibles |
| `plats` | Plats par restaurant |
| `zones` | Zones de livraison avec tarifs |
| `commandes` | Commandes passées |
| `commande_plats` | Détail des plats par commande |
| `evaluations` | Notes et commentaires après livraison |

### Statuts d'une commande

```
En attente → Validée → En préparation → Assignée → En cours de livraison → Livrée
                                                                         ↘ Incident signalé
                                    ↘ Annulée
```

---

## ⚙️ Installation

### Prérequis
- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL + PHP 8+)
- Navigateur web

### Étapes

1. **Cloner ou déposer** le dossier dans `htdocs/` de XAMPP :
   ```
   C:/xampp/htdocs/livraison_locale/
   ```

2. **Démarrer** Apache et MySQL depuis le panneau XAMPP.

3. **Importer la base de données** dans phpMyAdmin :
   - Ouvrir `http://localhost/phpmyadmin`
   - Créer une base `livraison_locale` (ou laisser le script le faire)
   - Importer le fichier `database.sql`

4. **Accéder à l'application** :
   ```
   http://localhost/livraison_locale/
   ```

---

## 🔑 Comptes de démonstration

> Mot de passe universel : **`password`**

| Rôle | Email |
|---|---|
| Client | `fatou@email.sn` |
| Client | `moussa@email.sn` |
| Gérant (Le Petit Dakar) | `gerant@petitdakar.sn` |
| Gérant (Pizza Royale) | `gerant@pizzaroyale.sn` |
| Gérant (Wok Express) | `gerant@wokexpress.sn` |
| Livreur | `ibrahim.seck@livreur.sn` |
| Livreur | `cheikh.fall@livreur.sn` |
| Livreur | `abdou.diop@livreur.sn` |
| Administrateur | `admin@livraison.sn` |

---

## 🔧 Configuration

La connexion à la base de données se configure dans `config.php` :

```php
$host   = 'localhost';
$dbname = 'livraison_locale';
$user   = 'root';
$pass   = '';
```

---

## 🛡 Sécurité

- Mots de passe hashés avec `password_hash()` (bcrypt)
- Requêtes préparées PDO contre les injections SQL
- Contrôle d'accès par rôle via `requireLogin($role)` sur chaque page
- Échappement des sorties HTML avec `htmlspecialchars()`

---

## 💰 Zones de livraison

| Zone | Tarif |
|---|---|
| Centre | 500 FCFA |
| Nord | 750 FCFA |
| Sud | 750 FCFA |
| Est | 1 000 FCFA |
| Périphérie | 1 500 FCFA |

---

## 🍽 Restaurants de démonstration

| Restaurant | Cuisine | Zone |
|---|---|---|
| 🍲 Le Petit Dakar | Sénégalaise | Centre |
| 🍕 Pizza Royale | Italienne | Nord |
| 🥡 Wok Express | Asiatique | Sud |

---

## 📄 Licence

Projet éducatif — libre d'utilisation et de modification.
