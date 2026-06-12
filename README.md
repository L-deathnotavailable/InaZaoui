# InaZaoui

Application Symfony de gestion et de présentation de médias photographiques.

Le projet contient une partie **Front Office** permettant de consulter les invités, leurs médias, le portfolio et la page de présentation, ainsi qu’une partie **Administration** permettant de gérer les invités, les albums et les médias.

## Fonctionnalités principales

### Front Office

* Page d’accueil.
* Liste des invités actifs.
* Page détail d’un invité avec ses médias.
* Portfolio de l’administratrice.
* Page “Qui suis-je ?”.

### Back Office

* Connexion administrateur et invité.
* Gestion des médias.
* Gestion des albums par l’administratrice.
* Gestion des invités par l’administratrice.
* Blocage / déblocage des invités.
* Restriction des accès selon les rôles :

  * un invité ne peut gérer que ses propres médias ;
  * l’administratrice peut gérer tous les médias, les albums et les invités.

## Choix techniques principaux

### Authentification

L’application utilise le système de sécurité Symfony avec un `User Provider` basé sur l’entité `User`.

Les utilisateurs sont chargés depuis la base de données via leur adresse email.

Les rôles sont déterminés à partir du champ `admin` :

```php
public function getRoles(): array
{
    return $this->admin
        ? ['ROLE_ADMIN']
        : ['ROLE_USER'];
}
```

Un `UserChecker` empêche l’authentification d’un utilisateur bloqué.

### Upload des médias

Les fichiers uploadés sont validés avec les contraintes Symfony :

* taille maximale : 2 Mo ;
* types MIME autorisés :

  * JPEG ;
  * PNG ;
  * WEBP ;
  * GIF.

Les fichiers sont stockés dans :

```txt
public/uploads
```

Le chemin relatif est enregistré en base de données dans l’entité `Media`.

### Optimisation de la page Invités

Une lenteur a été identifiée sur la page `/guests`.

Avant correction, le template Twig utilisait :

```twig
{{ guest.medias|length }}
```

Ce fonctionnement accédait à la collection Doctrine des médias pour chaque invité.

La correction consiste à calculer le nombre de médias directement en SQL dans `UserRepository`, avec une requête utilisant `COUNT(m.id)`. Le template reçoit désormais une valeur `mediaCount`, ce qui évite de charger inutilement les collections de médias.

## Pré-requis

* PHP 8.3 ou supérieur.
* Composer.
* MySQL ou MariaDB.
* Symfony CLI recommandé.
* Extension PHP `pdo_mysql`.
* Xdebug recommandé pour générer un rapport de couverture de tests.

Vérifier les extensions PHP :

```bash
php -m
```

Vérifier la version de PHP :

```bash
php -v
```

## Installation

### 1. Cloner le projet

```bash
git clone https://github.com/L-deathnotavailable/InaZaoui.git
cd InaZaoui
```

### 2. Installer les dépendances

```bash
composer install
```

### 3. Configurer l’environnement

Créer ou adapter le fichier `.env.local` pour l’environnement de développement.

Exemple :

```env
APP_ENV=dev
APP_DEBUG=1
APP_SECRET=change_me

DATABASE_URL="mysql://root:root@127.0.0.1:3306/inazaoui?serverVersion=8.0.32&charset=utf8mb4"
```

Ne pas versionner `.env.local`, car ce fichier contient la configuration propre à chaque machine.

### 4. Préparer la base de données de développement

Créer la base :

```bash
php bin/console doctrine:database:create --if-not-exists
```

Si le projet est fourni avec un dump SQL initial, l’importer avant d’exécuter les migrations.

Ensuite, exécuter les migrations disponibles :

```bash
php bin/console doctrine:migrations:migrate
```

Vérifier la cohérence Doctrine :

```bash
php bin/console doctrine:schema:validate
```

### 5. Charger les fixtures en développement

Attention : cette commande purge les données existantes avant de charger les données de démonstration.

```bash
php bin/console doctrine:fixtures:load
```

### 6. Lancer le serveur local

Avec Symfony CLI :

```bash
symfony server:start
```

Ou avec le serveur PHP intégré :

```bash
php -S 127.0.0.1:8000 -t public
```

L’application est ensuite disponible à l’adresse :

```txt
http://127.0.0.1:8000
```

## Usage

### Front Office

Routes principales :

```txt
/              Page d’accueil
/guests        Liste des invités
/guest/{id}    Page détail d’un invité
/portfolio     Portfolio
/about         Page “Qui suis-je ?”
```

### Administration

Routes principales :

```txt
/login                  Connexion
/admin/media            Gestion des médias
/admin/media/add        Ajout d’un média
/admin/album            Gestion des albums
/admin/album/add        Ajout d’un album
/admin/guests           Gestion des invités
/admin/guests/add       Ajout d’un invité
```

### Comptes de test

Les fixtures créent des comptes utilisables pour les tests et la démonstration.

Exemple :

```txt
Admin :
email : ina@zaoui.com
mot de passe : test123

Invité :
email : invite1@example.com
mot de passe : test123
```

Ces identifiants sont destinés à l’environnement local ou de test uniquement.

## Tests automatisés

Les tests sont organisés en trois catégories :

```txt
tests/Form        Tests des formulaires Symfony
tests/Functional  Tests fonctionnels des routes et comportements
tests/Unit        Tests unitaires des entités et services
```

### Préparer l’environnement de test

Le projet utilise une base MySQL dédiée à l’environnement de test.

La configuration `.env.test` pointe vers la base principale `inazaoui`, mais Symfony ajoute automatiquement le suffixe `_test` via la configuration Doctrine de test.

La base réellement utilisée est donc :

```txt
inazaoui_test
```

Commandes de préparation de la base de test :

```bash
php bin/console doctrine:database:drop --env=test --force --if-exists
php bin/console doctrine:database:create --env=test --if-not-exists
php bin/console doctrine:schema:create --env=test
php bin/console doctrine:fixtures:load --env=test
```

Le projet utilise `doctrine:schema:create` pour l’environnement de test, car les migrations existantes ne reconstruisent pas entièrement le schéma initial depuis une base vide.

### Lancer les tests

```bash
php bin/phpunit
```

### Lancer les tests avec couverture

Xdebug doit être installé et configuré avec :

```ini
xdebug.mode=coverage
```

Puis lancer :

```bash
php bin/console doctrine:fixtures:load --env=test
php bin/phpunit --coverage-text
```

La commande `--coverage-text` exécute déjà toute la suite de tests. Il n’est donc pas nécessaire de lancer `php bin/phpunit` juste avant.

Pour générer un rapport HTML :

```bash
php bin/phpunit --coverage-html var/coverage
```

Puis ouvrir :

```bash
start var/coverage/index.html
```

## Performance

La page `/guests` a été optimisée.

Avant correction, le nombre de médias était calculé dans Twig avec :

```twig
guest.medias|length
```

Après correction, le nombre est calculé dans `UserRepository` avec une requête SQL agrégée.

Résultat observé sur la page `/guests` :

```txt
Requêtes SQL : 4 → 2
Temps SQL : 29.06 ms → 3.95 ms
Entités gérées : 55 → 3
```

Cette optimisation réduit le coût SQL et limite le chargement inutile d’entités Doctrine.

## Structure du projet

```txt
src/
├── Controller/
│   ├── Admin/
│   └── HomeController.php
├── DataFixtures/
├── Entity/
├── Form/
├── Repository/
└── Security/

templates/
├── admin/
└── front/

tests/
├── Form/
├── Functional/
└── Unit/
```

## Commandes utiles

Vider le cache :

```bash
php bin/console cache:clear
```

Lister les routes :

```bash
php bin/console debug:router
```

Vérifier le schéma Doctrine :

```bash
php bin/console doctrine:schema:validate
```

Auditer les dépendances Composer :

```bash
composer audit
```

Lancer les tests :

```bash
php bin/phpunit
```

Lancer la couverture :

```bash
php bin/phpunit --coverage-text
```
