# Guide de contribution

Ce document décrit les règles à respecter pour contribuer au projet InaZaoui.

L’objectif est de faciliter la maintenance du projet, éviter les régressions et permettre une passation claire avec les futurs développeurs.

## Principes généraux

Toute contribution doit respecter les principes suivants :

* garder un code lisible et compréhensible ;
* éviter les modifications trop larges dans un seul commit ;
* tester les fonctionnalités modifiées ;
* ne pas casser les droits d’accès existants ;
* ne pas versionner de données sensibles ;
* documenter les choix techniques importants.

## Organisation des branches

Utiliser des noms de branches explicites.

Format recommandé :

```txt
type/sujet-court
```

Exemples :

```txt
feature/ajout-pagination-medias
fix/upload-image-validation
test/couverture-admin-media
docs/readme-installation
perf/optimisation-page-invites
```

Types recommandés :

```txt
feature  Nouvelle fonctionnalité
fix      Correction de bug
test     Ajout ou correction de tests
docs     Documentation
perf     Optimisation de performance
refactor Refactorisation sans changement fonctionnel
chore    Maintenance technique
```

## Convention de commits

Les messages de commits doivent être courts, clairs et orientés action.

Format recommandé :

```txt
type: description courte
```

Exemples :

```txt
feature: add guest media upload
fix: prevent guest from deleting another user media
test: add functional tests for admin media
docs: add installation instructions
perf: optimize guests page media count
```

Pour les étapes du projet, un format en français est aussi acceptable :

```txt
Etape 5 - Ajout des fixtures et tests automatisés
Etape 6 - Optimisation de la page invités
Etape 7 - Ajout de la documentation projet
```

## Soumettre un problème

Avant de corriger un bug, créer ou consulter une issue.

Une issue doit contenir :

* un titre clair ;
* le comportement observé ;
* le comportement attendu ;
* les étapes pour reproduire le problème ;
* les captures d’écran si utile ;
* l’environnement utilisé.

Exemple :

```md
## Comportement observé

Un invité peut accéder à un média appartenant à un autre invité.

## Comportement attendu

Un invité ne doit voir et gérer que ses propres médias.

## Étapes pour reproduire

1. Se connecter avec invite1@example.com.
2. Aller sur /admin/media.
3. Tenter de supprimer un média appartenant à invite2@example.com.

## Résultat attendu

Une erreur 403 doit être retournée.
```

## Proposer une fonctionnalité

Une demande de fonctionnalité doit expliquer :

* le besoin utilisateur ;
* le comportement attendu ;
* les impacts éventuels sur les rôles ;
* les impacts éventuels sur la base de données ;
* les tests à prévoir.

Exemple :

```md
## Besoin

Permettre à l’administratrice de modifier le titre d’un média existant.

## Comportement attendu

Depuis /admin/media, un bouton “Modifier” permet d’accéder à un formulaire d’édition.

## Règles d’accès

Seule l’administratrice peut modifier tous les médias.
Un invité ne peut modifier que ses propres médias.

## Tests à prévoir

- un admin peut modifier un média ;
- un invité peut modifier son propre média ;
- un invité ne peut pas modifier le média d’un autre invité.
```

## Contribuer au code

### Avant de commencer

Mettre à jour la branche principale :

```bash
git checkout main
git pull
```

Créer une nouvelle branche :

```bash
git checkout -b feature/nom-de-la-fonctionnalite
```

Installer les dépendances si besoin :

```bash
composer install
```

### Pendant le développement

Respecter l’organisation existante :

```txt
src/Controller      Contrôleurs
src/Entity          Entités Doctrine
src/Form            Formulaires Symfony
src/Repository      Requêtes personnalisées
src/Security        Sécurité et authentification
templates           Templates Twig
tests               Tests automatisés
```

Ne pas mélanger dans une même contribution :

* refactorisation ;
* nouvelle fonctionnalité ;
* correction de bug ;
* documentation.

Chaque contribution doit rester ciblée.

## Bonnes pratiques Symfony

### Contrôleurs

Les contrôleurs doivent rester simples.

Ils doivent :

* récupérer la requête ;
* appeler les repositories ou services nécessaires ;
* gérer les droits d’accès ;
* retourner une réponse ou un rendu Twig.

Éviter de placer trop de logique métier dans les contrôleurs.

### Repositories

Les requêtes personnalisées doivent être placées dans les repositories.

Exemple : la page `/guests` utilise une méthode dédiée dans `UserRepository` pour récupérer les invités avec leur nombre de médias :

```php
public function findActiveGuestsWithMediaCount(): array
{
    return $this->createQueryBuilder('u')
        ->select('u AS guest')
        ->addSelect('COUNT(m.id) AS mediaCount')
        ->leftJoin('u.medias', 'm')
        ->andWhere('u.admin = :admin')
        ->andWhere('u.blocked = :blocked')
        ->setParameter('admin', false)
        ->setParameter('blocked', false)
        ->groupBy('u.id')
        ->getQuery()
        ->getResult();
}
```

Ce type de requête évite de charger inutilement des collections Doctrine dans les templates.

### Templates Twig

Les templates ne doivent pas contenir de logique lourde.

À éviter :

```twig
{{ guest.medias|length }}
```

À préférer :

```twig
{{ guestData.mediaCount }}
```

Si une information demande un calcul ou une requête, elle doit être préparée côté contrôleur ou repository.

### Upload de fichiers

Toute modification liée à l’upload doit conserver :

* la validation du type MIME ;
* la limite de taille ;
* l’enregistrement dans `public/uploads` ;
* la protection contre les fichiers invalides.

Les tests doivent vérifier au minimum :

* l’upload d’une image valide ;
* le refus d’un fichier invalide ;
* l’association correcte du média à l’utilisateur.

## Contribuer aux tests

Toute nouvelle fonctionnalité doit être accompagnée de tests.

Les tests sont organisés ainsi :

```txt
tests/Unit        Tests unitaires
tests/Form        Tests des formulaires Symfony
tests/Functional  Tests fonctionnels
```

### Tests unitaires

Utilisés pour tester :

* les entités ;
* les méthodes simples ;
* les services isolés ;
* le `UserChecker`.

### Tests de formulaires

Utilisés pour tester :

* les champs attendus ;
* le mapping des données ;
* les contraintes de validation.

### Tests fonctionnels

Utilisés pour tester :

* les routes ;
* les pages ;
* les redirections ;
* les droits d’accès ;
* les comportements réels côté utilisateur.

Exemples de comportements à tester :

* un invité ne voit que ses propres médias ;
* un invité ne peut pas accéder à la gestion des albums ;
* un admin peut créer un invité ;
* un admin peut ajouter un média ;
* un fichier non-image est refusé.

## Lancer les tests avant contribution

Avant de proposer une contribution, préparer la base de test :

```bash
php bin/console doctrine:database:drop --env=test --force --if-exists
php bin/console doctrine:database:create --env=test --if-not-exists
php bin/console doctrine:schema:create --env=test
php bin/console doctrine:fixtures:load --env=test
```

Puis lancer :

```bash
php bin/phpunit
```

Pour la couverture :

```bash
php bin/console doctrine:fixtures:load --env=test
php bin/phpunit --coverage-text
```

La couverture de code doit rester supérieure à 70 %.

## Contribuer à la documentation

La documentation doit être mise à jour lorsque la contribution modifie :

* l’installation ;
* les commandes utiles ;
* les variables d’environnement ;
* les tests ;
* les règles métier ;
* les performances ;
* les droits d’accès.

Fichiers concernés :

```txt
README.md
CONTRIBUTING.md
tests/README.md
```

## Performance

Toute modification pouvant impacter les performances doit être vérifiée avec le Symfony Web Profiler.

Indicateurs à surveiller :

* temps total de la requête ;
* nombre de requêtes SQL ;
* temps SQL ;
* mémoire utilisée ;
* nombre d’entités Doctrine gérées.

Éviter les traitements coûteux dans Twig, notamment les accès aux collections Doctrine dans des boucles.

Exemple à éviter :

```twig
{% for guest in guests %}
    {{ guest.medias|length }}
{% endfor %}
```

Préférer une requête optimisée côté repository.

## Sécurité

Les règles suivantes doivent être respectées :

* un invité ne doit accéder qu’à ses propres médias ;
* un invité ne doit pas gérer les albums ;
* un invité ne doit pas gérer les autres invités ;
* l’administratrice peut gérer les invités, albums et médias ;
* un utilisateur bloqué ne doit pas pouvoir se connecter ;
* les fichiers uploadés doivent être validés.

Toute modification de sécurité doit être accompagnée de tests fonctionnels.

## Validation d’une contribution

Avant validation, vérifier :

```bash
composer audit
php bin/console doctrine:schema:validate
php bin/phpunit
```

Puis, si Xdebug est disponible :

```bash
php bin/phpunit --coverage-text
```

Une contribution est acceptable si :

* les tests passent ;
* la couverture reste supérieure à 70 % ;
* aucun problème de sécurité Composer n’est détecté ;
* le code reste lisible ;
* la documentation est mise à jour si nécessaire.

## Fichiers à ne pas versionner

Ne pas versionner :

```txt
.env.local
.env.*.local
var/
vendor/
public/uploads générés localement si non nécessaires au projet
```

Le fichier `.env.test` peut être versionné s’il ne contient que des valeurs locales de test non sensibles.

Les identifiants de production ne doivent jamais être commités.

## Processus recommandé

1. Créer une issue ou partir d’une demande claire.
2. Créer une branche dédiée.
3. Implémenter la correction ou fonctionnalité.
4. Ajouter ou mettre à jour les tests.
5. Lancer la suite de tests.
6. Vérifier la couverture.
7. Mettre à jour la documentation si nécessaire.
8. Créer une pull request.
9. Faire relire la contribution.
10. Fusionner après validation.
