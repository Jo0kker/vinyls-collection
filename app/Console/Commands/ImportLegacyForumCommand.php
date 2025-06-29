<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class ImportLegacyForumCommand extends Command
{
    protected $signature = 'import:legacy-forum';
    protected $description = 'Importe le forum depuis l\'ancienne base de données';

    private $userCacheByPseudo = [];
    private $userCacheByOldId = [];
    private $anonymousUserId = null;

    public function handle()
    {
        $this->info('🗣️  Début de l\'import du forum...');
        $startTime = microtime(true);

        try {
            // Préparer les utilisateurs et l'utilisateur anonyme
            $this->prepareUsers();

            // 1. Import des catégories principales
            $this->importCategories();

            // 2. Import des sous-catégories
            $this->importSubcategories();

            // 3. Import des discussions (threads)
            $this->importThreads();

            // 4. Import des messages (posts)
            $this->importPosts();

            // 5. Import des lectures de threads
            $this->importThreadReads();

            // 6. Mise à jour des compteurs
            $this->updateCounters();

            $totalTime = microtime(true) - $startTime;
            $this->info(sprintf("\n✅ Import du forum terminé en %.2f secondes", $totalTime));

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de l\'import : ' . $e->getMessage());
            $this->error('Trace : ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }

    private function prepareUsers()
    {
        $this->info('👥 Préparation des utilisateurs...');

        // Créer ou récupérer l'utilisateur anonyme
        $this->anonymousUserId = User::firstOrCreate(
            ['email' => 'anonymous@forum.local'],
            [
                'name' => 'Utilisateur Anonyme',
                'password' => bcrypt('random-password'),
                'email_verified_at' => now(),
            ]
        )->id;

        // Charger le cache des utilisateurs par pseudo ET par old_id
        $users = User::whereNotNull('name')->get(['id', 'name', 'old_id']);

        foreach ($users as $user) {
            // Cache par pseudo (pour forum_discussion_auteur)
            if (!empty($user->name)) {
                $this->userCacheByPseudo[strtolower($user->name)] = $user->id;
            }
            // Cache par old_id (pour id_membre dans forummessage)
            if (!empty($user->old_id)) {
                $this->userCacheByOldId[$user->old_id] = $user->id;
            }
        }

        $this->info(sprintf('✅ Cache utilisateurs chargé : %d par pseudo, %d par old_id',
            count($this->userCacheByPseudo),
            count($this->userCacheByOldId)
        ));

        // Debug : afficher quelques exemples
        $this->info('🔍 Exemples old_id → user_id : ' . collect($this->userCacheByOldId)->take(5)->map(function($userId, $oldId) {
                return "{$oldId}→{$userId}";
            })->implode(', '));
    }

    private function getUserIdByPseudo($pseudo)
    {
        if (empty($pseudo)) {
            return $this->anonymousUserId;
        }

        $pseudoLower = strtolower(trim($pseudo));
        return $this->userCacheByPseudo[$pseudoLower] ?? $this->anonymousUserId;
    }

    private function getUserIdByOldId($oldId)
    {
        if (empty($oldId) || !is_numeric($oldId)) {
            return $this->anonymousUserId;
        }

        $oldIdInt = (int) $oldId;
        return $this->userCacheByOldId[$oldIdInt] ?? $this->anonymousUserId;
    }

    private function convertToUtf8($string)
    {
        return empty($string) ? '' : mb_convert_encoding($string, 'UTF-8', 'ISO-8859-1');
    }

    private function fixDate($date)
    {
        return (empty($date) || $date === '0000-00-00 00:00:00') ? now() : $date;
    }

    private function importCategories()
    {
        $this->info('📁 Import des catégories principales...');

        $categories = DB::connection('mysql_old')
            ->table('forumcategorie')
            ->orderBy('forum_categorie_ordre_affichage')
            ->get();

        if ($categories->isEmpty()) {
            $this->info('❌ Aucune catégorie trouvée');
            return;
        }

        $bar = $this->output->createProgressBar($categories->count());
        $bar->start();

        $categoriesToInsert = [];
        $order = 1;

        foreach ($categories as $category) {
            $categoriesToInsert[] = [
                'id' => $category->idforumcategorie,
                'title' => $this->convertToUtf8($category->forum_categorie_nom),
                'description' => null,
                'accepts_threads' => false, // Les catégories principales n'acceptent pas de threads
                'is_private' => false,
                'thread_count' => 0,
                'post_count' => 0,
                'newest_thread_id' => null,
                'latest_active_thread_id' => null,
                'color_light_mode' => null,
                'color_dark_mode' => null,
                '_lft' => $order * 2 - 1, // Pour nested sets
                '_rgt' => $order * 2,
                'parent_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $order++;
            $bar->advance();
        }

        DB::table('forum_categories')->insert($categoriesToInsert);

        $bar->finish();
        $this->line('');
        $this->info(sprintf('✅ %d catégories principales importées', count($categoriesToInsert)));
    }

    private function importSubcategories()
    {
        $this->info('📂 Import des sous-catégories...');

        $subcategories = DB::connection('mysql_old')
            ->table('forumsouscategorie')
            ->orderBy('idForumCategorie')
            ->orderBy('forum_souscategorie_ordreAffichage')
            ->get();

        if ($subcategories->isEmpty()) {
            $this->info('❌ Aucune sous-catégorie trouvée');
            return;
        }

        $bar = $this->output->createProgressBar($subcategories->count());
        $bar->start();

        // Récupérer les catégories existantes pour calculer les nested sets
        $existingCategories = DB::table('forum_categories')->get()->keyBy('id');
        $maxRgt = $existingCategories->max('_rgt');

        $subcategoriesToInsert = [];
        $currentRgt = $maxRgt + 1;

        foreach ($subcategories as $subcategory) {
            $parentCategory = $existingCategories->get($subcategory->idForumCategorie);

            if (!$parentCategory) {
                $this->warn("⚠️  Catégorie parent {$subcategory->idForumCategorie} non trouvée pour la sous-catégorie {$subcategory->idForumSousCategorie}");
                $bar->advance();
                continue;
            }

            // Ignorer les sous-catégories sans nom ou avec ID 0
            $title = $this->convertToUtf8($subcategory->forum_souscategorie_nom);
            if (empty($title) || $subcategory->idForumSousCategorie == 0) {
                $this->warn("⚠️  Sous-catégorie ignorée (ID: {$subcategory->idForumSousCategorie}, nom vide)");
                $bar->advance();
                continue;
            }

            $subcategoriesToInsert[] = [
                'id' => $subcategory->idForumSousCategorie + 1000, // Éviter les conflits d'ID
                'title' => $title,
                'description' => $this->convertToUtf8($subcategory->forum_souscategorie_desc),
                'accepts_threads' => true, // Les sous-catégories acceptent les threads
                'is_private' => false,
                'thread_count' => 0,
                'post_count' => 0,
                'newest_thread_id' => null,
                'latest_active_thread_id' => null,
                'color_light_mode' => null,
                'color_dark_mode' => null,
                '_lft' => $currentRgt,
                '_rgt' => $currentRgt + 1,
                'parent_id' => $parentCategory->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $currentRgt += 2;
            $bar->advance();
        }

        // Ajouter une catégorie par défaut pour les discussions orphelines
        $defaultCategory = $existingCategories->first(); // Prendre la première catégorie disponible
        if ($defaultCategory) {
            $subcategoriesToInsert[] = [
                'id' => 2000, // Pour les discussions avec idforumsouscategorie = 1000
                'title' => 'Discussions orphelines',
                'description' => 'Discussions sans catégorie valide',
                'accepts_threads' => true,
                'is_private' => false,
                'thread_count' => 0,
                'post_count' => 0,
                'newest_thread_id' => null,
                'latest_active_thread_id' => null,
                'color_light_mode' => null,
                'color_dark_mode' => null,
                '_lft' => $currentRgt,
                '_rgt' => $currentRgt + 1,
                'parent_id' => $defaultCategory->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $this->info('✅ Catégorie par défaut créée pour les discussions orphelines (ID: 2000)');
        }

        if (!empty($subcategoriesToInsert)) {
            DB::table('forum_categories')->insert($subcategoriesToInsert);
        }

        $bar->finish();
        $this->line('');
        $this->info(sprintf('✅ %d sous-catégories importées', count($subcategoriesToInsert)));

        // Debug : Afficher les IDs des sous-catégories importées
        $importedIds = collect($subcategoriesToInsert)->pluck('id')->sort()->values();
        $this->info("🔍 IDs sous-catégories importées : " . $importedIds->implode(', '));
    }

    private function importThreads()
    {
        $this->info('💬 Import des discussions...');

        $threads = DB::connection('mysql_old')
            ->table('forumdiscussion')
            ->where('forum_discussion_pseudodest', '') // Exclure les messages privés
            ->where('forum_discussion_edito', 0) // Seulement edito = 0
            ->get();

        if ($threads->isEmpty()) {
            $this->info('❌ Aucune discussion trouvée');
            return;
        }

        $bar = $this->output->createProgressBar($threads->count());
        $bar->start();

        $threadsToInsert = [];
        $imported = 0;
        $skipped = 0;

        foreach ($threads as $thread) {
            $authorId = $this->getUserIdByPseudo($thread->forum_discussion_auteur);
            $categoryId = $thread->idforumsouscategorie + 1000; // Correspondre aux sous-catégories

            // Vérifier que la catégorie existe
            $categoryExists = DB::table('forum_categories')->where('id', $categoryId)->exists();
            if (!$categoryExists) {
                $this->warn("⚠️  Catégorie {$categoryId} non trouvée pour la discussion {$thread->idforumdiscussion} (sous-catégorie originale: {$thread->idforumsouscategorie})");
                $skipped++;
                $bar->advance();
                continue;
            }

            // Tronquer le titre si trop long
            $title = $this->convertToUtf8($thread->forum_discussion_titre);
            if (strlen($title) > 255) {
                $title = substr($title, 0, 252) . '...';
                $this->warn("⚠️  Titre tronqué pour la discussion {$thread->idforumdiscussion}");
            }

            $threadsToInsert[] = [
                'id' => $thread->idforumdiscussion,
                'category_id' => $categoryId,
                'author_id' => $authorId,
                'title' => $title,
                'pinned' => (bool) $thread->forum_discussion_isadmin, // isadmin = pinned
                'locked' => (bool) $thread->forum_discussion_close,
                'reply_count' => max(0, $thread->forum_discussion_nbMessages - 1), // -1 car le premier post n'est pas une réponse
                'first_post_id' => null, // Sera mis à jour après l'import des posts
                'last_post_id' => null,  // Sera mis à jour après l'import des posts
                'created_at' => $this->fixDate($thread->forum_discussion_datederniermessage),
                'updated_at' => $this->fixDate($thread->forum_discussion_datederniermessage),
                'deleted_at' => $thread->forum_discussion_cache ? now() : null, // Si caché = soft deleted
            ];

            $imported++;

            if (count($threadsToInsert) >= 1000) {
                DB::table('forum_threads')->insert($threadsToInsert);
                $threadsToInsert = [];
            }

            $bar->advance();
        }

        if (!empty($threadsToInsert)) {
            DB::table('forum_threads')->insert($threadsToInsert);
        }

        $bar->finish();
        $this->line('');
        $this->info(sprintf('✅ %d discussions importées, %d ignorées (catégorie manquante)', $imported, $skipped));
    }

    private function importPosts()
    {
        $this->info('💭 Import des messages...');

        $posts = DB::connection('mysql_old')
            ->table('forummessage')
            ->where('forum_message_banni', 0) // Exclure les messages bannis
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('forumdiscussion')
                    ->whereColumn('forumdiscussion.idforumdiscussion', 'forummessage.idForumDiscussion')
                    ->where('forumdiscussion.forum_discussion_pseudodest', '') // Seulement discussions publiques
                    ->where('forumdiscussion.forum_discussion_edito', 0);
            })
            ->orderBy('idForumDiscussion')
            ->orderBy('forum_message_dateCrea')
            ->get();

        if ($posts->isEmpty()) {
            $this->info('❌ Aucun message trouvé');
            return;
        }

        $bar = $this->output->createProgressBar($posts->count());
        $bar->start();

        $postsToInsert = [];
        $imported = 0;
        $threadSequences = []; // Pour garder trace de la séquence par thread

        foreach ($posts as $post) {
            // Convertir id_membre en entier et utiliser getUserIdByOldId
            $memberId = is_numeric($post->id_membre) ? (int) $post->id_membre : null;
            $authorId = $memberId ? $this->getUserIdByOldId($memberId) : $this->anonymousUserId;

            // Calculer la séquence pour ce thread
            if (!isset($threadSequences[$post->idForumDiscussion])) {
                $threadSequences[$post->idForumDiscussion] = 1;
            } else {
                $threadSequences[$post->idForumDiscussion]++;
            }

            $postsToInsert[] = [
                'id' => $post->idforummessage,
                'thread_id' => $post->idForumDiscussion,
                'author_id' => $authorId,
                'content' => $this->convertToUtf8($post->forum_message_objet),
                'post_id' => null, // Pas de notion de réponse à un post spécifique dans l'ancien système
                'sequence' => $threadSequences[$post->idForumDiscussion],
                'created_at' => $this->fixDate($post->forum_message_dateCrea),
                'updated_at' => $post->forum_message_dateEdit !== '0000-00-00 00:00:00'
                    ? $this->fixDate($post->forum_message_dateEdit)
                    : $this->fixDate($post->forum_message_dateCrea),
                'deleted_at' => $post->forum_message_cache ? now() : null,
            ];

            $imported++;

            if (count($postsToInsert) >= 1000) {
                DB::table('forum_posts')->insert($postsToInsert);
                $postsToInsert = [];
            }

            $bar->advance();
        }

        if (!empty($postsToInsert)) {
            DB::table('forum_posts')->insert($postsToInsert);
        }

        $bar->finish();
        $this->line('');
        $this->info(sprintf('✅ %d messages importés', $imported));
    }

    private function importThreadReads()
    {
        $this->info('👁️  Import des lectures de discussions...');

        $reads = DB::connection('mysql_old')
            ->table('forumdiscussion_visite')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('forumdiscussion')
                    ->whereColumn('forumdiscussion.idforumdiscussion', 'forumdiscussion_visite.idforumdiscussion')
                    ->where('forumdiscussion.forum_discussion_pseudodest', '')
                    ->where('forumdiscussion.forum_discussion_edito', 0);
            })
            ->get();

        if ($reads->isEmpty()) {
            $this->info('❌ Aucune lecture trouvée');
            return;
        }

        $bar = $this->output->createProgressBar($reads->count());
        $bar->start();

        $readsToInsert = [];
        $imported = 0;

        foreach ($reads as $read) {
            // Utiliser getUserIdByOldId pour récupérer l'utilisateur
            $userId = $this->getUserIdByOldId($read->id_membre);
            if ($userId === $this->anonymousUserId) {
                // Skip les lectures d'utilisateurs non trouvés (sauf si on veut les garder)
                $bar->advance();
                continue;
            }

            $readsToInsert[] = [
                'thread_id' => $read->idforumdiscussion,
                'user_id' => $userId,
                'created_at' => $this->fixDate($read->datevisite),
                'updated_at' => $this->fixDate($read->datevisite),
            ];

            $imported++;

            if (count($readsToInsert) >= 1000) {
                DB::table('forum_threads_read')->insert($readsToInsert);
                $readsToInsert = [];
            }

            $bar->advance();
        }

        if (!empty($readsToInsert)) {
            DB::table('forum_threads_read')->insert($readsToInsert);
        }

        $bar->finish();
        $this->line('');
        $this->info(sprintf('✅ %d lectures importées', $imported));
    }

    private function updateCounters()
    {
        $this->info('🔢 Mise à jour des compteurs...');

        // Mettre à jour first_post_id et last_post_id pour chaque thread
        $this->info('   • Mise à jour des IDs de premier et dernier post...');
        DB::statement("
            UPDATE forum_threads t
            SET first_post_id = (
                SELECT id FROM forum_posts p
                WHERE p.thread_id = t.id
                AND p.deleted_at IS NULL
                ORDER BY p.sequence ASC
                LIMIT 1
            ),
            last_post_id = (
                SELECT id FROM forum_posts p
                WHERE p.thread_id = t.id
                AND p.deleted_at IS NULL
                ORDER BY p.sequence DESC
                LIMIT 1
            )
        ");

        // Mettre à jour les compteurs des catégories
        $this->info('   • Mise à jour des compteurs de catégories...');
        DB::statement("
            UPDATE forum_categories c
            SET thread_count = (
                SELECT COUNT(*) FROM forum_threads t
                WHERE t.category_id = c.id
                AND t.deleted_at IS NULL
            ),
            post_count = (
                SELECT COUNT(*) FROM forum_posts p
                JOIN forum_threads t ON t.id = p.thread_id
                WHERE t.category_id = c.id
                AND p.deleted_at IS NULL
                AND t.deleted_at IS NULL
            ),
            newest_thread_id = (
                SELECT id FROM forum_threads t
                WHERE t.category_id = c.id
                AND t.deleted_at IS NULL
                ORDER BY t.created_at DESC
                LIMIT 1
            ),
            latest_active_thread_id = (
                SELECT id FROM forum_threads t
                WHERE t.category_id = c.id
                AND t.deleted_at IS NULL
                ORDER BY t.updated_at DESC
                LIMIT 1
            )
        ");

        $this->info('✅ Compteurs mis à jour');
    }
}
