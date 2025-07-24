# Afridays

## Description

Afridays est une plateforme moderne de réservation de propriétés en Côte d'Ivoire, développée avec Laravel et Filament. Elle permet aux utilisateurs de découvrir, rechercher et réserver des propriétés à travers le pays avec une interface intuitive et des fonctionnalités d'autocomplétion avancées.

## Installation

### Prérequis

-   PHP >= 8.0
-   Composer
-   MySQL

### Étapes d'installation

1. Clonez le dépôt :

    ```sh
    git clone https://github.com/Nackito/reservation.git
    cd reservation
    ```

2. Installez les dépendances :

    composer install
    npm install
    npm run dev

3. Copiez le fichier .env.example en .env et configurez vos variables d'environnement :

    cp .env.example .env

4. Générez la clé de l'application :

    php artisan key:generate

5. Configurez votre base de données dans le fichier .env

6. Exécutez les migrations et les seeders :

    php artisan migrate --seed

7. Démarrez le serveur de développement :

#Authentification

L'authentification est gérer avec Laravel Breeze pour les clients et filament pour les gestionnaires de propriétés et pour le super admin

Inscription
Les utilisateurs peuvent s'inscrire en accédant à la route /register. Le formulaire d'inscription nécessite un nom, une adresse e-mail et un mot de passe.

Connexion
Les utilisateurs peuvent se connecter en accédant à la route /login. Le formulaire de connexion nécessite une adresse e-mail et un mot de passe.

Déconnexion
Les utilisateurs peuvent se déconnecter en cliquant sur le bouton "Logout" dans la barre de navigation. Cela soumettra un formulaire de déconnexion et redirigera l'utilisateur vers la page de connexion.

#Gestion des réservations

Ajouter une réservation
Les utilisateurs peuvent ajouter une réservation en sélectionnant une propriété et en choisissant les dates d'entrée et de sortie. Si l'utilisateur n'est pas authentifié, il sera redirigé vers la page de connexion. Il pourra créer son compte au cas où il n'en a pas

Calcul du prix total
Le prix total de la réservation est calculé en fonction du nombre de jours et du prix par nuit de la propriété. Le prix total est affiché dans une alerte de confirmation avant que l'utilisateur ne confirme la réservation.

Filament

Ressource Utilisateur
La ressource UserResource est utilisée pour gérer les utilisateurs dans le panneau d'administration de Filament. Cette ressource est masquée dans la navigation du panneau d'administration.

Relations
La ressource UserResource inclut des relations avec les réservations et les propriétés.

Ressource Booking
La ressource booking est utilisé pour la gestion des réservations, l'utilisateur (gestionnaire de propriété) pourra valider ou refuser toutes les demandes de reservation liées à ses propriétés

Ressource Properties
Cette ressource est utilisé pour le CRUD des propriétés de l'utilisateur connecté

Contribution
Les contributions sont les bienvenues ! Veuillez soumettre une pull request ou ouvrir une issue pour discuter des modifications que vous souhaitez apporter.
