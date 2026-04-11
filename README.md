# ThreadLux API — Système de Paiement & Escrow

ThreadLux API est le backend de la marketplace ThreadLux. Il expose une API REST sécurisée permettant la gestion des utilisateurs, des produits, des commandes et d'un système de séquestre (escrow) complet avec gestion des litiges — le tout alimenté par **FedaPay**.

---

## 🚀 Fonctionnalités clés

| Domaine            | Détail                                                                                   |
| ------------------ | ---------------------------------------------------------------------------------------- |
| **Auth**           | Inscription / Connexion (clients) & Login vendeur dédié — via Laravel Sanctum            |
| **Catalogue**      | CRUD produits (avec variantes & images) + catégories                                     |
| **Paiement**       | Vérification sécurisée des transactions FedaPay côté serveur                             |
| **Escrow**         | Fonds bloqués à la confirmation du paiement, libération manuelle par le vendeur          |
| **Auto-release**   | Libération automatique des fonds après N jours (configurable) via Artisan scheduler      |
| **Litiges**        | L'acheteur peut ouvrir un litige — bloque la libération tant que non résolu par un admin |
| **Journalisation** | Audit trail complet de chaque changement de statut de transaction                        |
| **Webhook**        | Réception & vérification HMAC des événements FedaPay en temps réel                       |
| **Emails**         | Notifications Mailable (vendeur & acheteur) à chaque libération                          |

---

## 🛠️ Installation & Configuration

### Prérequis

- PHP 8.2+
- Composer
- PostgreSQL (ou toute DB compatible Laravel)
- Compte [FedaPay](https://fedapay.com/) (sandbox ou live)
- Compte [Mailtrap](https://mailtrap.io/) (tests email) ou SMTP de production

### Installation

```bash
# 1. Installer les dépendances PHP
composer install

# 2. Copier et remplir le fichier d'environnement
cp .env.example .env

# 3. Générer la clé d'application
php artisan key:generate

# 4. Lancer les migrations + seeders
php artisan migrate --seed

# 5. Démarrer le serveur
php artisan serve
```

### Configuration `.env`

```ini
APP_URL=http://localhost:8000

# Base de données
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=threadlux
DB_USERNAME=postgres
DB_PASSWORD=secret

# FedaPay
FEDAPAY_SECRET_KEY=sk_sandbox_...
FEDAPAY_PUBLIC_KEY=pk_sandbox_...
FEDAPAY_ENVIRONMENT=sandbox           # 'sandbox' | 'live'
FEDAPAY_WEBHOOK_SECRET=...            # Clé secrète du webhook FedaPay

# Escrow
ESCROW_AUTO_RELEASE_DAYS=7            # Nombre de jours avant libération automatique

# Mail (Mailtrap — sandbox)
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_user
MAIL_PASSWORD=your_pass
MAIL_FROM_ADDRESS=noreply@threadlux.com
MAIL_FROM_NAME="ThreadLux"
```

---

## 🏗️ Architecture Technique

### Modèles

| Modèle           | Description                                                                      |
| ---------------- | -------------------------------------------------------------------------------- |
| `User`           | Acheteurs (`client`) et Vendeurs (`vendeur`) — rôle stocké en DB                 |
| `Product`        | Produits du catalogue avec `user_id` (vendeur propriétaire)                      |
| `ProductVariant` | Variantes (taille, couleur, SKU, stock)                                          |
| `ProductImage`   | Images d'un produit (url, `is_principal`)                                        |
| `Categorie`      | Catégories de produits                                                           |
| `Transaction`    | Paiement FedaPay + statut d'escrow (`held`, `released`, `en_litige`, `refunded`) |
| `Commande`       | Commande globale liée à une transaction                                          |
| `CommandeItem`   | Lignes de commande (produit, quantité, prix)                                     |
| `Litige`         | Dispute ouverte par un acheteur sur une transaction                              |
| `TransactionLog` | Journal d'audit de chaque événement de transaction                               |

### Contrôleurs

| Contrôleur              | Rôle                                                    |
| ----------------------- | ------------------------------------------------------- |
| `AuthController`        | Register + Login/Logout clients (Sanctum)               |
| `AdminAuthController`   | Login dédié vendeurs/admins — rejette les clients       |
| `ProductController`     | CRUD produits + listing public                          |
| `CategorieController`   | CRUD catégories + listing public                        |
| `TransactionController` | Vérification de paiement FedaPay + Payout escrow + logs |
| `LitigeController`      | Ouverture, listing, résolution des litiges              |
| `WebhookController`     | Réception des événements FedaPay (HMAC vérifié)         |

### Services

- **`TransactionLogger`** — Crée des entrées `TransactionLog` à chaque changement d'état.

### Middleware personnalisé

- **`role`** — Vérifie que l'utilisateur authentifié possède l'un des rôles spécifiés (ex: `role:vendeur,admin`).

---

## 💰 Flux Escrow complet

```
Acheteur                   Backend                     FedaPay
   |                          |                            |
   |-- Widget FedaPay ------->|                            |
   |                          |<--- Paiement initié ------>|
   |                          |                            |
   |-- POST /payment/verify ->|                            |
   |                          |-- Retrieve transaction --->|
   |                          |<-- status: 'approved' -----|
   |                          |                            |
   |                          | escrow_status = 'held'     |
   |                          | auto_release_at = now+7j   |
   |                          |                            |
   |                      (livraison)                      |
   |                          |                            |
Vendeur                       |                            |
   |-- POST /seller/escrow/release/{id} ->|               |
   |                          |-- Payout::create+start --->|
   |                          |                            |
   |                          | escrow_status = 'released' |
   |                          | email vendeur + acheteur   |
```

### Libération automatique (Auto-release)

Un job planifié vérifie toutes les heures les transactions dont `auto_release_at <= now()` et dont le statut est `held`. Ces transactions sont automatiquement libérées sans intervention manuelle.

```bash
# Activer le scheduler Laravel (en production via cron)
* * * * * php /path/to/project/artisan schedule:run >> /dev/null 2>&1
```

---

## ⚖️ Système de Litiges

```
Acheteur                Admin                    Résultat
   |                      |                          |
   |-- POST /litiges ----->|                          |
   |  (raison, description)|                          |
   |                       |                          |
   |            escrow = 'en_litige' (bloqué)         |
   |                       |                          |
   |               GET /admin/litiges                 |
   |               GET /admin/litiges/{id}            |
   |                       |                          |
   |       PATCH /admin/litiges/{id}/resolve          |
   |       decision: 'resolue_vendeur'  -> Payout     |
   |       decision: 'resolue_acheteur' -> Refund     |
```

**Raisons valides pour ouvrir un litige** : `non_recu` | `non_conforme` | `defectueux` | `autre`

---

## 🔒 Sécurité

- **Authentification** : Laravel Sanctum (Bearer Token)
- **Rôles** : `client`, `vendeur`, `admin` — contrôlés via middleware `role`
- **Séparation acheteur/vendeur** : Les comptes `vendeur` et `admin` ne peuvent pas passer commande (vérifié au niveau de `POST /payment/verify`)
- **Vérification côté serveur** : Le statut de paiement est toujours récupéré depuis FedaPay avec la clé secrète — jamais depuis le client
- **Webhook sécurisé** : Signature HMAC-SHA256 vérifiée avant tout traitement

---

## 📄 Documentation API

La documentation OpenAPI complète (Swagger) est disponible dans `public/swagger.yaml`.

Vous pouvez l'importer dans [Swagger Editor](https://editor.swagger.io) ou Postman, ou la consulter localement via :

```bash
# Via docker (optionnel)
docker run -p 8080:8080 -e SWAGGER_JSON=/app/swagger.yaml -v $(pwd)/public:/app swaggerapi/swagger-ui
```

---

## 🧪 Tests

```bash
php artisan test
```

Les tests se trouvent dans `tests/Feature/` et `tests/Unit/`.
