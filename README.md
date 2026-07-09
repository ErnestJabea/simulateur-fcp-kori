# Simulateur FCP - Kori Asset Management

Ce projet est un simulateur financier de Fonds Communs de Placement (FCP) de niveau institutionnel, conçu spécialement pour **Kori Asset Management**. Il permet aux investisseurs de projeter leurs placements périodiques (méthode DCA) et de générer un rapport PDF personnalisé tout en collectant des leads qualifiés.

## Technologies Utilisées

* **Backend :** Laravel 10 (PHP 8.1+)
* **Administration :** Filament v3
* **Frontend Réactif :** Livewire v3 & Livewire Volt (Single File Components)
* **Design & Animations :** Alpine.js & Tailwind CSS
* **Dataviz :** ApexCharts (Graphique d'évolution interactif)
* **Architecture :** Domain-Driven Design (DDD) & Clean Architecture

---

## Installation Locale

1. **Cloner le projet** dans votre répertoire de développement (ex: MAMP/htdocs).
2. **Configurer l'environnement :**
   ```bash
   cp .env.example .env
   ```
   Renseigner les accès à votre base de données locale (ex: `simulateur_fcp` sous MAMP).
3. **Installer les dépendances PHP :**
   ```bash
   composer install --ignore-platform-reqs
   ```
4. **Générer la clé d'application :**
   ```bash
   php artisan key:generate
   ```
5. **Lancer les migrations et les seeders :**
   ```bash
   php artisan migrate:fresh --seed
   ```
   Cela va créer les tables et insérer les 3 fonds d'investissement par défaut de Kori Asset Management.
6. **Lancer le serveur de développement :**
   ```bash
   php artisan serve
   ```
   Le simulateur est alors accessible sur `http://localhost:8000`.

---

## Guide de Déploiement Sécurisé sur cPanel (Production)

Pour déployer de manière sécurisée cette application sur un serveur cPanel sans exposer vos fichiers sensibles (comme le fichier `.env` ou le code source PHP), suivez cette structure recommandée.

### 1. Organisation des dossiers sur le serveur

Déposez le projet de la manière suivante dans votre répertoire utilisateur cPanel (ex: `/home/kori_user/`) :

1. Créez un dossier `/home/kori_user/simulateur-fcp/` et transférez-y **l'ensemble des fichiers du projet**, à l'exception du dossier `public/`.
2. Transférez le contenu du dossier `public/` de votre projet local directement dans le dossier public ciblé par cPanel (ex: `/home/kori_user/public_html/simulateur/` ou directement dans `public_html/` si vous utilisez un sous-domaine dédié comme `simulateur.koriassetmanagement.com`).

Votre arborescence serveur doit ressembler à ceci :
```text
/home/kori_user/
├── simulateur-fcp/                  <-- Code source protégé (inaccessible par le web)
│   ├── app/
│   ├── config/
│   ├── .env                         <-- Vos clés de sécurité de production
│   └── ...
└── public_html/ (ou sous-domaine)
    └── simulateur/                  <-- Fichiers publics (index.php, CSS, JS)
```

### 2. Configuration d'index.php

Éditez le fichier `index.php` qui se trouve maintenant dans votre répertoire public web (ex: `/home/kori_user/public_html/simulateur/index.php`) pour corriger les chemins de chargement vers le dossier sécurisé :

Modifiez la ligne 24 (chargement de l'autoloader) :
```php
// Avant : require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/../../simulateur-fcp/vendor/autoload.php';
```

Modifiez la ligne 38 (chargement de l'application) :
```php
// Avant : $app = require_once __DIR__.'/../bootstrap/app.php';
$app = require_once __DIR__.'/../../simulateur-fcp/bootstrap/app.php';
```

### 3. Tâches Planifiées (Cron) sur cPanel

Pour gérer le nettoyage régulier et l'historisation des simulations en tâche de fond, ajoutez un **Cron Job** dans votre interface cPanel :
* **Fréquence :** Chaque minute (`* * * * *`)
* **Commande :**
  ```bash
  /usr/local/bin/php /home/kori_user/simulateur-fcp/artisan schedule:run >> /dev/null 2>&1
  ```
  *(Note : Ajustez le chemin `/usr/local/bin/php` selon la configuration PHP 8.2 de votre hébergeur cPanel)*.
