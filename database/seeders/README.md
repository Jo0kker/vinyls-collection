# Seeders Vinyls Collection

Ce dossier contient tous les seeders pour peupler la base de données avec des données de test réalistes.

## Utilisation

### Reset complet de la base de données

```bash
./vendor/bin/sail artisan migrate:fresh --seed
```

### Exécuter seulement les seeders

```bash
./vendor/bin/sail artisan db:seed
```

### Exécuter un seeder spécifique

```bash
./vendor/bin/sail artisan db:seed --class=UserSeeder
./vendor/bin/sail artisan db:seed --class=VinylSeeder
./vendor/bin/sail artisan db:seed --class=CollectionSeeder
./vendor/bin/sail artisan db:seed --class=ForumSeeder
```

## Structure des seeders

### UserSeeder
- Crée 32 utilisateurs au total
- 2 comptes de test avec des credentials fixes
- 10 utilisateurs avec des profils musicaux variés
- 20 utilisateurs aléatoires

**Comptes de test :**
- Admin: `admin@vinyls-collection.com` / `password`
- User: `user@vinyls-collection.com` / `password`

### VinylSeeder
- Crée 150 vinyles au total
- 10 albums mythiques avec des données réalistes
- 15 vinyles rares (peu de collectionneurs)
- 25 vinyles avec données Discogs
- 100 vinyles aléatoires

### CollectionSeeder
- Crée des collections pour chaque utilisateur (1-4 par utilisateur)
- Collections avec 3-15 vinyles en moyenne
- Collections spéciales pour les "power users" (30-50 vinyles)
- Collections rares avec des vinyles chers

### ForumSeeder
- Crée 4 catégories principales :
  - **Site et Forum** : Support et suggestions
  - **Blabla Musical** : Discussions musicales
  - **Workshop** : Conseils techniques
  - **Les Bons Plans** : Ventes et échanges
- Génère des discussions avec des réponses réalistes
- Contenu adapté à chaque catégorie

## Données générées

Après exécution complète :
- 32 utilisateurs avec des profils variés
- 150 vinyles avec des données réalistes
- Collections complètes avec des vinyles
- Forum actif avec 4 catégories et de nombreuses discussions

## Factories disponibles

Tous les modèles ont des factories configurées :

```php
// Créer des utilisateurs
User::factory(10)->create();

// Créer des vinyles rares
Vinyl::factory(5)->rare()->create();

// Créer des vinyles avec ID Discogs
Vinyl::factory(10)->withDiscogsId()->create();

// Créer des collections pour un utilisateur
Collection::factory(3)->forUser($user)->create();

// Créer des entrées de collection chères
CollectionVinyl::factory(10)->expensive()->create();
```

## Personnalisation

Vous pouvez facilement modifier les données générées en éditant les seeders :
- Changez les albums mythiques dans `VinylSeeder`
- Modifiez les catégories du forum dans `ForumSeeder`
- Ajustez les profils utilisateur dans `UserSeeder`
- Personnalisez les types de collections dans `CollectionSeeder`