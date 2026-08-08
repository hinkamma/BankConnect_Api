BankConnect API

> API bancaire REST développée avec Laravel 12 — Gestion de comptes, transactions et opérations bancaires en XAF (Franc CFA).

*****Technologies*****

- Laravel 12 — Framework PHP
- PHP 8.2+
- MySQL 8.0 — Base de données
- Laravel Sanctum — Authentification par token
- Postman — Tests des endpoints


*****Fonctionnalités*****

- Inscription, connexion et déconnexion avec token Sanctum
- Gestion de comptes bancaires (courant, épargne, professionnel)
- Dépôt, retrait et virement entre comptes
- Historique complet des transactions
- Gestion des rôles : Client, Employé, Administrateur
- Protection des routes avec middleware auth:sanctum
- Pagination, rate limiting et cache


*****Installation*****

1__Prérequis

- PHP 8.2+
- Composer
- MySQL
- XAMPP

2__Étapes

a.Cloner le projet

git clone https://github.com/hinkamma/BankConnect.git
cd BankConnect

b.Installer les dépendances
composer install

c.Copier le fichier d'environnement
cp .env.example .env

d.Configurer la base de données dans .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bankconnect
DB_USERNAME=root
DB_PASSWORD=

e.Générer la clé d'application
php artisan key:generate

f.Installer Sanctum et lancer les migrations**

php artisan install:api
php artisan migrate

g. Lancer le serveur

php artisan serve


*******Endpoints API********

### Authentification (public)

| Méthode | URL | Description |
|---------|-----|-------------|
| POST | `/api/register` | Inscription |
| POST | `/api/login` | Connexion |

### Profil (protégé)

| Méthode | URL | Description |
|---------|-----|-------------|
| POST | `/api/logout` | Déconnexion |
| GET | `/api/me` | Voir son profil |

### Comptes (protégé)

| Méthode | URL | Description |
|---------|-----|-------------|
| GET | `/api/accounts` | Voir ses comptes |
| POST | `/api/open_account` | Ouvrir un compte |
| GET | `/api/accounts/{id}` | Voir un compte |
| DELETE | `/api/accounts/{id}` | Fermer un compte |

### Opérations bancaires (protégé)

| Méthode | URL | Description |
|---------|-----|-------------|
| POST | `/api/accounts/{id}/depot` | Déposer de l'argent |
| POST | `/api/accounts/{id}/retrait` | Retirer de l'argent |
| POST | `/api/accounts/{id}/virement` | Virer vers un autre compte |

### Transactions (protégé)

| Méthode | URL | Description |
|---------|-----|-------------|
| GET | `/api/transactions` | Historique des transactions |
| GET | `/api/transactions/{id}` | Détail d'une transaction |


********Exemple de requête********

### Inscription

POST /api/register
Content-Type: application/json

{
    "name": "Hinkamma Freddy",
    "email": "freddy@gmail.com",
    "phone": "699000000",
    "password": "password123",
    "password_confirmation": "password123"
}

### Réponse

{
    "user": {
        "id": 1,
        "name": "Hinkamma Freddy",
        "email": "freddy@gmail.com"
    },
    "access_token": "1|abc123xyz..."
}

### Ouvrir un compte

POST /api/open_account
Authorization: Bearer {token}
Content-Type: application/json

{
    "type": "courant"
}

### Dépôt

POST /api/accounts/{id}/depot
Authorization: Bearer {token}
Content-Type: application/json

{
    "amount": 50000,
    "description": "Dépôt initial"
}


## Structure du projet

app/
    Http/
        Controllers/
            Api/
                AuthController.php
                AccountController.php
                OperationBankController.php
             Requests/
                RegisterRequest.php
                LoginRequest.php
                DepositeRequest.php
            Resources/
                AccountResource.php
                TransactionResource.php
    Models/
        User.php
        Account.php
        Transaction.php
database/
    migrations/
    create_users_table.php
    create_accounts_table.php
    create_transactions_table.php
routes/
    api.php

---

## Règles métier

- Le solde d'un compte ne peut pas être négatif
- Un compte bloqué ne peut pas effectuer de transactions
- Un virement ne peut se faire qu'entre comptes actifs
- Montant minimum d'une transaction : **100 XAF**
- Les tokens expirent après **24 heures**
- Maximum **60 requêtes par minute** par utilisateur

---

## Auteur

**Hinkamma Freddy Roland**
Étudiant en Master 1 — Développement d'Applications a l'université de douala

## Licence

Ce projet est développé à des fins académiques et professionnelles.
