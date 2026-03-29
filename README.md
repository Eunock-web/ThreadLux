# ThreadLux API - Système de Paiement et d'Escrow

ThreadLux est la plateforme backend robuste de ThreadLux, une marketplace sécurisée permettant aux utilisateurs d'acheter et de vendre avec un système de séquestre (escrow) intégré via **FedaPay**.

## 🚀 Fonctionnalités Clés

- **Authentification Sécurisée** : Gestion via Laravel Sanctum (Tokens Bearer).
- **Système d'Escrow (Séquestre)** : Collecte de fonds sécurisée lors de l'achat et libération manuelle par le vendeur.
- **Payout Automatisé** : Virement réel vers le compte mobile money du vendeur via l'API Payout de FedaPay.
- **Notifications Email** : Envoi automatique d'emails de confirmation (Vendeur/Acheteur) après chaque libération de fonds.
- **Gestion de Catalogue** : API pour les produits (avec variantes) et les catégories.

## 🛠️ Installation et Configuration

### Prérequis

- PHP 8.2+
- Composer
- PostgreSQL (ou tout autre DB compatible Laravel)
- Un compte [FedaPay](https://fedapay.com/) (pour les clés API)
- Un compte [Mailtrap](https://mailtrap.io/) (pour les tests d'emails)

### Étapes d'installation

1. **Cloner le projet**
2. **Installer les dépendances** :
    ```bash
    composer install
    ```
3. **Configurer l'environnement** :
   Copiez `.env.example` en `.env` et remplissez vos informations :

    ```ini
    DB_CONNECTION=pgsql
    # ... configurations DB ...

    # FedaPay Configuration
    FEDAPAY_SECRET_KEY=sk_sandbox_...
    FEDAPAY_PUBLIC_KEY=pk_sandbox_...
    FEDAPAY_ENVIRONMENT=sandbox

    # Mail Configuration (Mailtrap exemple)
    MAIL_MAILER=smtp
    MAIL_HOST=sandbox.smtp.mailtrap.io
    MAIL_PORT=2525
    MAIL_USERNAME=your_user
    MAIL_PASSWORD=your_pass
    ```

4. **Générer la clé d'application** :
    ```bash
    php artisan key:generate
    ```
5. **Lancer les migrations et seeders** :
    ```bash
    php artisan migrate --seed
    ```
6. **Lancer le serveur** :
    ```bash
    php artisan serve
    ```

## 🏗️ Architecture Technique

### Modèles Principaux

- `User` : Acheteurs et Vendeurs.
- `Product` : Produits de la plateforme.
- `Transaction` : Suivi des paiements FedaPay et du statut de l'escrow (`held`, `released`).
- `Commande` : Détails des ventes et des articles.

### Contrôleurs Clés

- `TransactionController` : Le cœur du système. Gère la vérification des paiements (`verify`) et le versement des fonds (`Payout`).
- `AuthController` : Inscription et connexion (Sanctum).

## 💰 Flux d'Escrow (Séquestre)

1. **Paiement** : L'acheteur paie via le widget FedaPay sur le frontend.
2. **Verrouillage** : Le backend vérifie le paiement et place les fonds en statut `held` dans la table `transactions`.
3. **Livraison** : Le vendeur expédie le produit.
4. **Libération** : Le vendeur clique sur "Libérer fond". Le backend appelle l'API FedaPay Payout pour transférer l'argent réel.
5. **Confirmation** : Un email est envoyé aux deux parties via Laravel Mailables (Markdown).

---

## 📄 API Documentation

Retrouvez la documentation interactive des endpoints via le fichier `public/swagger.yaml` ou importez-le dans Swagger Editor / Postman.
