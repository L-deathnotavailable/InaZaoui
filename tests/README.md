# Tests automatisés

Les tests sont organisés en trois catégories :

- `Unit/` : tests unitaires des entités et services simples.
- `Form/` : tests des formulaires Symfony.
- `Functional/` : tests fonctionnels des routes, pages, droits d’accès et comportements métier.

La suite de tests s’appuie sur une base MySQL dédiée à l’environnement `test`, initialisée avec les fixtures Doctrine.