# TagSign Back

**Avancement: En cours**

# API Rest Symfony

## Démarrage

### Etape 1 : Installer Symfony

[Installing & Setting up the Symfony Framework (Symfony Docs)](https://symfony.com/doc/current/setup.html)

```
symfony check:requirements
```

### Etape 2 : Installer MySQL

[MySQL :: Download MySQL Installer](https://dev.mysql.com/downloads/installer/)

### Etape 3 : Cloner le repo

### Etape 4 : Créer l’environnement .env.local

- Copier/coller le fichier .env
- Modifier la ligne de connexion à la Base de donnée

```
DATABASE_URL="mysql://app:!ChangeMe!@127.0.0.1:3306/TagSign?charset=utf8mb4"
```
- Créer un dossier jwt dans le dossier config
- Créer des clés de sécurité OpenSSL (passé par un terminal GIT pour la prise en charge de OpenSSL)

```
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
```

Mettez le même mot de passe pour les 2 clés et entré le mot de passe dans votre .env.local :

```
JWT_PASSPHRASE=!ChangeMe!
```

### Etape 5 : Installer les dépendances
```
composer install
```

### Etape 6 : Créer la base de donnée

```
php bin/console doctrine:database:create //Créer la base de donnée
php bin/console doctrine:schema:update --force //Génère les tables
php bin/console doctrine:fixtures:load //Ajoute les données de test /scr/DataFixtures/AppFixtures.php
```

### Etape 7 : Utiliser PostMan ou Insomnia pour faire des requêtes HTTP

### Etape 8 : Connecter vous à l’API

Pour cela entrer dans le body en JSON :

```json
{
	"username" : "admin",
	"password": "admin"
}
```

Vous allez recevoir un token. Enregistrez le dans le Header Http en ajoutant bearer : 

```json
Authorisation : bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9........
```

### Etape 8 bis : Utiliser la documentation

Entrez dans votre navigateur le lien suivant : localhost:8000/api/doc

Vous devez aussi vous connecter en récupérant un token.

Après cela fait, apuyer sur le bouton Authorize et mettez votre token sans oublier “bearer ”

## Commande utile :

- **symfony server:start** : démarre symfony
- **php bin/console :** make:NOM : créer une entité
- **php bin/console** : make:controller : créer un controller