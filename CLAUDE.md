# Instructions pour Claude

## Environnement de développement

- **Docker/Sail** : Toutes les commandes artisan, composer, etc. doivent être exécutées via Laravel Sail
  - `./vendor/bin/sail artisan ...`
  - `./vendor/bin/sail composer ...`
  - `./vendor/bin/sail npm ...`

- **Frontend** : Utiliser `yarn` via Sail pour le build frontend (pas npm)
  - `./vendor/bin/sail yarn build`
  - `./vendor/bin/sail yarn dev`

- **Base de données** : PostgreSQL (pas MySQL)
  - Pas de ENUM, utiliser des CHECK constraints ou string
  - Pas de FIELD(), utiliser CASE WHEN pour l'ordre personnalisé

## Stack technique

- Laravel 12
- Vue 3 + Inertia.js
- Tailwind CSS
- Spatie Laravel Permission pour les rôles
- Laravel Reverb pour les WebSockets
- Brevo (Sendinblue) pour les emails

## Conventions

- Langue de l'interface : Français
- Les layouts Vue utilisent la composition API (`<script setup>`)
