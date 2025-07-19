<?php

namespace Database\Seeders;

use App\Models\User;
use TeamTeaTime\Forum\Models\Category;
use TeamTeaTime\Forum\Models\Thread;
use TeamTeaTime\Forum\Models\Post;
use Illuminate\Database\Seeder;

class ForumSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('Aucun utilisateur trouvé. Assurez-vous d\'exécuter UserSeeder avant.');
            return;
        }

        // Créer les catégories avec hiérarchie parent/enfant
        $categories = [
            [
                'title' => 'Général',
                'description' => 'Catégories générales du forum',
                'color_light_mode' => '#6b7280',
                'color_dark_mode' => '#9ca3af',
                'accepts_threads' => false,
                'is_private' => false,
                '_lft' => 1,
                '_rgt' => 8,
                'subcategories' => [
                    [
                        'title' => 'Site et Forum',
                        'description' => 'Discussions générales sur le site, suggestions et support technique. Partagez vos idées d\'amélioration et signalez les bugs.',
                        'color_light_mode' => '#3b82f6',
                        'color_dark_mode' => '#60a5fa',
                        'accepts_threads' => true,
                        'is_private' => false,
                        '_lft' => 2,
                        '_rgt' => 3,
                        'threads' => [
                            [
                                'title' => 'Bienvenue sur le forum !',
                                'content' => 'Bienvenue à tous sur le forum Vinyls Collection ! N\'hésitez pas à vous présenter et à partager vos découvertes musicales.',
                                'replies' => 12
                            ],
                            [
                                'title' => 'Suggestions d\'amélioration',
                                'content' => 'Vous avez des idées pour améliorer le site ? Partagez-les ici !',
                                'replies' => 8
                            ],
                            [
                                'title' => 'Problèmes techniques',
                                'content' => 'Signalez ici les bugs et problèmes techniques que vous rencontrez.',
                                'replies' => 5
                            ]
                        ]
                    ],
                    [
                        'title' => 'Présentations',
                        'description' => 'Présentez-vous à la communauté et faites connaissance avec les autres collectionneurs.',
                        'color_light_mode' => '#06b6d4',
                        'color_dark_mode' => '#22d3ee',
                        'accepts_threads' => true,
                        'is_private' => false,
                        '_lft' => 4,
                        '_rgt' => 5,
                        'threads' => [
                            [
                                'title' => 'Présentez-vous !',
                                'content' => 'Nouveau membre ? Présentez-vous et parlez-nous de vos goûts musicaux !',
                                'replies' => 18
                            ],
                            [
                                'title' => 'Vos collections en chiffres',
                                'content' => 'Combien de vinyles possédez-vous ? Quel est votre style de prédilection ?',
                                'replies' => 12
                            ]
                        ]
                    ]
                ]
            ],
            [
                'title' => 'Musique',
                'description' => 'Tout sur la musique et les vinyles',
                'color_light_mode' => '#6b7280',
                'color_dark_mode' => '#9ca3af',
                'accepts_threads' => false,
                'is_private' => false,
                '_lft' => 9,
                '_rgt' => 12,
                'subcategories' => [
                    [
                        'title' => 'Blabla Musical',
                        'description' => 'Discussions libres sur la musique, actualités, coups de cœur et découvertes. Partagez vos avis, critiques et recommandations musicales.',
                        'color_light_mode' => '#10b981',
                        'color_dark_mode' => '#34d399',
                        'accepts_threads' => true,
                        'is_private' => false,
                        '_lft' => 10,
                        '_rgt' => 11,
                        'threads' => [
                            [
                                'title' => 'Vos dernières découvertes musicales',
                                'content' => 'Partagez vos dernières découvertes musicales et faites-nous découvrir de nouveaux artistes !',
                                'replies' => 25
                            ],
                            [
                                'title' => 'Albums cultes des années 70',
                                'content' => 'Quels sont selon vous les albums incontournables des années 70 ?',
                                'replies' => 18
                            ],
                            [
                                'title' => 'Festival de cet été',
                                'content' => 'Qui va à des festivals cet été ? Quels sont vos coups de cœur programmation ?',
                                'replies' => 15
                            ],
                            [
                                'title' => 'Vinyles vs CD vs Streaming',
                                'content' => 'Le grand débat : quel support préférez-vous pour écouter de la musique ?',
                                'replies' => 32
                            ]
                        ]
                    ]
                ]
            ],
            [
                'title' => 'Workshop',
                'description' => 'Conseils d\'achat, vente, échange et expertise de vinyles',
                'color_light_mode' => '#8b5cf6',
                'color_dark_mode' => '#a78bfa',
                'accepts_threads' => false,
                'is_private' => false,
                '_lft' => 13,
                '_rgt' => 18,
                'subcategories' => [
                    [
                        'title' => 'Expertise & Authentification',
                        'description' => 'Authentification, évaluation et expertise de vinyles rares. Conseils pour éviter les contrefaçons.',
                        'color_light_mode' => '#8b5cf6',
                        'color_dark_mode' => '#a78bfa',
                        'accepts_threads' => true,
                        'is_private' => false,
                        '_lft' => 14,
                        '_rgt' => 15,
                        'threads' => [
                            [
                                'title' => 'Authentifier un vinyle rare',
                                'content' => 'Comment s\'assurer de l\'authenticité d\'un vinyle rare avant l\'achat ?',
                                'replies' => 20
                            ],
                            [
                                'title' => 'Évaluation état vinyle',
                                'content' => 'Besoin d\'aide pour évaluer l\'état de mes vinyles selon les standards Goldmine',
                                'replies' => 9
                            ]
                        ]
                    ],
                    [
                        'title' => 'Entretien & Nettoyage',
                        'description' => 'Conseils et techniques pour l\'entretien, le nettoyage et la conservation de vos vinyles.',
                        'color_light_mode' => '#06b6d4',
                        'color_dark_mode' => '#22d3ee',
                        'accepts_threads' => true,
                        'is_private' => false,
                        '_lft' => 16,
                        '_rgt' => 17,
                        'threads' => [
                            [
                                'title' => 'Nettoyer ses vinyles',
                                'content' => 'Quelles sont vos techniques pour nettoyer efficacement vos vinyles ?',
                                'replies' => 16
                            ],
                            [
                                'title' => 'Conservation long terme',
                                'content' => 'Comment bien conserver vos vinyles pour éviter l\'usure ?',
                                'replies' => 12
                            ]
                        ]
                    ]
                ]
            ],
            [
                'title' => 'Marketplace',
                'description' => 'Ventes, achats, échanges et bonnes affaires',
                'color_light_mode' => '#f59e0b',
                'color_dark_mode' => '#fbbf24',
                'accepts_threads' => false,
                'is_private' => false,
                '_lft' => 19,
                '_rgt' => 26,
                'subcategories' => [
                    [
                        'title' => 'Ventes',
                        'description' => 'Mettez en vente vos vinyles. Respect des règles de courtoisie et description précise obligatoire.',
                        'color_light_mode' => '#f59e0b',
                        'color_dark_mode' => '#fbbf24',
                        'accepts_threads' => true,
                        'is_private' => false,
                        '_lft' => 20,
                        '_rgt' => 21,
                        'threads' => [
                            [
                                'title' => 'Vente collection jazz',
                                'content' => 'Je vends une partie de ma collection jazz, plusieurs raretés disponibles.',
                                'replies' => 7
                            ],
                            [
                                'title' => 'Vente vinyles rock progressif',
                                'content' => 'Collection prog rock à vendre, pressings originaux uniquement.',
                                'replies' => 5
                            ]
                        ]
                    ],
                    [
                        'title' => 'Recherches',
                        'description' => 'Recherchez des vinyles spécifiques. Précisez vos critères : état, pressing, budget.',
                        'color_light_mode' => '#10b981',
                        'color_dark_mode' => '#34d399',
                        'accepts_threads' => true,
                        'is_private' => false,
                        '_lft' => 22,
                        '_rgt' => 23,
                        'threads' => [
                            [
                                'title' => 'Recherche Pink Floyd rarities',
                                'content' => 'Je recherche des pressings originaux de Pink Floyd, notamment des bootlegs.',
                                'replies' => 11
                            ],
                            [
                                'title' => 'ISO vinyles Krautrock',
                                'content' => 'Recherche albums Neu!, Can, Kraftwerk en pressings allemands.',
                                'replies' => 8
                            ]
                        ]
                    ],
                    [
                        'title' => 'Échanges',
                        'description' => 'Proposez et organisez vos échanges de vinyles. Système de feedback recommandé.',
                        'color_light_mode' => '#8b5cf6',
                        'color_dark_mode' => '#a78bfa',
                        'accepts_threads' => true,
                        'is_private' => false,
                        '_lft' => 24,
                        '_rgt' => 25,
                        'threads' => [
                            [
                                'title' => 'Échange vinyles punk',
                                'content' => 'Propose échange de vinyles punk/new wave contre rock progressif.',
                                'replies' => 4
                            ]
                        ]
                    ]
                ]
            ],
            [
                'title' => 'Bons Plans',
                'description' => 'Partagez vos bonnes adresses et découvertes',
                'color_light_mode' => '#ef4444',
                'color_dark_mode' => '#f87171',
                'accepts_threads' => false,
                'is_private' => false,
                '_lft' => 27,
                '_rgt' => 30,
                'subcategories' => [
                    [
                        'title' => 'Disquaires & Shops',
                        'description' => 'Partagez vos bonnes adresses de disquaires, boutiques en ligne et magasins spécialisés.',
                        'color_light_mode' => '#ef4444',
                        'color_dark_mode' => '#f87171',
                        'accepts_threads' => true,
                        'is_private' => false,
                        '_lft' => 28,
                        '_rgt' => 29,
                        'threads' => [
                            [
                                'title' => 'Bons plans disquaires Paris',
                                'content' => 'Partagez vos bons plans disquaires et brocantes sur Paris !',
                                'replies' => 28
                            ],
                            [
                                'title' => 'Sites de vente en ligne fiables',
                                'content' => 'Quels sites recommandez-vous pour acheter des vinyles en ligne ?',
                                'replies' => 15
                            ]
                        ]
                    ]
                ]
            ],
            [
                'title' => 'Administration',
                'description' => 'Gestion et modération du forum',
                'color_light_mode' => '#6b7280',
                'color_dark_mode' => '#9ca3af',
                'accepts_threads' => false,
                'is_private' => true,
                '_lft' => 31,
                '_rgt' => 34,
                'subcategories' => [
                    [
                        'title' => 'Staff & Modération',
                        'description' => 'Espace réservé aux modérateurs et administrateurs pour la gestion du forum et les discussions internes.',
                        'color_light_mode' => '#6b7280',
                        'color_dark_mode' => '#9ca3af',
                        'accepts_threads' => true,
                        'is_private' => true,
                        '_lft' => 32,
                        '_rgt' => 33,
                        'threads' => [
                            [
                                'title' => 'Règles de modération',
                                'content' => 'Guidelines et procédures pour la modération du forum.',
                                'replies' => 3
                            ],
                            [
                                'title' => 'Signalements en attente',
                                'content' => 'Suivi des signalements utilisateurs et actions à prendre.',
                                'replies' => 8
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $createdCategories = [];
        
        foreach ($categories as $categoryData) {
            // Créer la catégorie parent
            $parentCategory = Category::create([
                'title' => $categoryData['title'],
                'description' => $categoryData['description'],
                'color_light_mode' => $categoryData['color_light_mode'],
                'color_dark_mode' => $categoryData['color_dark_mode'],
                'accepts_threads' => $categoryData['accepts_threads'],
                'is_private' => $categoryData['is_private'],
                'thread_count' => 0,
                'post_count' => 0,
                'parent_id' => null,
                '_lft' => $categoryData['_lft'],
                '_rgt' => $categoryData['_rgt'],
                'newest_thread_id' => null,
                'latest_active_thread_id' => null,
            ]);

            $createdCategories[] = $parentCategory;

            // Créer les threads pour cette catégorie (si elle accepte les threads)
            if (isset($categoryData['threads']) && $categoryData['accepts_threads']) {
                $this->createThreadsForCategory($parentCategory, $categoryData['threads'], $users);
            }

            // Créer les sous-catégories
            if (isset($categoryData['subcategories'])) {
                foreach ($categoryData['subcategories'] as $subCategoryData) {
                    $subCategory = Category::create([
                        'title' => $subCategoryData['title'],
                        'description' => $subCategoryData['description'],
                        'color_light_mode' => $subCategoryData['color_light_mode'],
                        'color_dark_mode' => $subCategoryData['color_dark_mode'],
                        'accepts_threads' => $subCategoryData['accepts_threads'],
                        'is_private' => $subCategoryData['is_private'],
                        'thread_count' => 0,
                        'post_count' => 0,
                        'parent_id' => $parentCategory->id, // Lier à la catégorie parent
                        '_lft' => $subCategoryData['_lft'],
                        '_rgt' => $subCategoryData['_rgt'],
                        'newest_thread_id' => null,
                        'latest_active_thread_id' => null,
                    ]);

                    $createdCategories[] = $subCategory;

                    // Créer les threads pour cette sous-catégorie
                    if (isset($subCategoryData['threads'])) {
                        $this->createThreadsForCategory($subCategory, $subCategoryData['threads'], $users);
                    }
                }
            }
        }

        $totalCategories = Category::count();
        $totalThreads = Thread::count();
        $totalPosts = Post::count();

        $this->command->info('Forum créé avec succès !');
        $this->command->info("- {$totalCategories} catégories créées");
        $this->command->info("- {$totalThreads} discussions créées");
        $this->command->info("- {$totalPosts} messages créés");
    }

    private function createThreadsForCategory($category, $threadsData, $users): void
    {
        foreach ($threadsData as $threadData) {
            $author = $users->random();
            
            $thread = Thread::create([
                'category_id' => $category->id,
                'author_id' => $author->id,
                'title' => $threadData['title'],
                'reply_count' => 0,
                'created_at' => fake()->dateTimeBetween('-3 months', '-1 week'),
                'updated_at' => fake()->dateTimeBetween('-1 week', 'now'),
            ]);

            // Créer le premier post (contenu principal)
            $firstPost = Post::create([
                'thread_id' => $thread->id,
                'author_id' => $author->id,
                'content' => $threadData['content'],
                'sequence' => 1,
                'created_at' => $thread->created_at,
                'updated_at' => $thread->created_at,
            ]);

            // Créer les réponses
            for ($i = 0; $i < $threadData['replies']; $i++) {
                $replyAuthor = $users->random();
                $replyContent = $this->generateReplyContent($category->title, $threadData['title']);
                
                Post::create([
                    'thread_id' => $thread->id,
                    'author_id' => $replyAuthor->id,
                    'content' => $replyContent,
                    'sequence' => $i + 2,
                    'created_at' => fake()->dateTimeBetween($thread->created_at, 'now'),
                    'updated_at' => fake()->dateTimeBetween($thread->created_at, 'now'),
                ]);
            }

            // Mettre à jour les compteurs et les IDs des posts
            $firstPost = $thread->posts()->orderBy('created_at', 'asc')->first();
            $lastPost = $thread->posts()->orderBy('created_at', 'desc')->first();
            
            $thread->update([
                'reply_count' => $threadData['replies'],
                'first_post_id' => $firstPost ? $firstPost->id : null,
                'last_post_id' => $lastPost ? $lastPost->id : null,
            ]);
        }

        // Récupérer le dernier thread créé et le plus récemment actif
        $newestThread = $category->threads()->orderBy('created_at', 'desc')->first();
        $latestActiveThread = $category->threads()->orderBy('updated_at', 'desc')->first();
        
        // Mettre à jour les compteurs de la catégorie
        $category->update([
            'thread_count' => count($threadsData),
            'post_count' => collect($threadsData)->sum('replies') + count($threadsData),
            'newest_thread_id' => $newestThread ? $newestThread->id : null,
            'latest_active_thread_id' => $latestActiveThread ? $latestActiveThread->id : null,
        ]);
    }

    private function generateReplyContent(string $category, string $threadTitle): string
    {
        $responses = [
            'Site et Forum' => [
                'Merci pour cette initiative !',
                'Excellente idée, je suis totalement d\'accord.',
                'J\'ai rencontré le même problème récemment.',
                'Pouvez-vous donner plus de détails ?',
                'C\'est exactement ce dont nous avions besoin.',
                'Je vais tester ça de mon côté.',
                'Problème résolu de mon côté, merci !',
            ],
            'Présentations' => [
                'Bienvenue dans la communauté !',
                'Très belle collection, bravo !',
                'J\'ai les mêmes goûts musicaux que toi.',
                'N\'hésite pas si tu as des questions.',
                'Impressionnant, combien d\'années de collection ?',
                'Quel est ton vinyle le plus précieux ?',
            ],
            'Blabla Musical' => [
                'Excellent album, je recommande vivement !',
                'J\'ai découvert cet artiste récemment, c\'est génial.',
                'Personnellement, je préfère leur album précédent.',
                'Quelqu\'un connaît des artistes similaires ?',
                'J\'ai vu cet artiste en concert, c\'était incroyable !',
                'Ce morceau me donne des frissons à chaque fois.',
                'J\'ai ce vinyle dans ma collection, pressage original.',
                'La production de cet album est exceptionnelle.',
                'Un classique intemporel selon moi.',
            ],
            'Expertise & Authentification' => [
                'Merci pour ces conseils pratiques !',
                'Attention aux contrefaçons sur ce titre.',
                'Je peux vous donner une estimation si vous voulez.',
                'Photos nécessaires pour évaluer l\'état.',
                'Les numéros de matrice sont cruciaux.',
                'Consultez Discogs pour les détails techniques.',
            ],
            'Entretien & Nettoyage' => [
                'J\'utilise la même méthode depuis des années.',
                'J\'ai testé cette technique, ça marche parfaitement.',
                'Attention aux produits trop agressifs.',
                'Un bon nettoyage peut sauver un vinyle.',
                'Je recommande cette brosse antistatique.',
            ],
            'Ventes' => [
                'Intéressé par votre lot, je vous contacte en privé.',
                'Quel est votre prix pour ce vinyle ?',
                'État du vinyle et de la pochette ?',
                'Photos disponibles ?',
                'Frais de port inclus ?',
                'Excellent rapport qualité-prix !',
            ],
            'Recherches' => [
                'J\'ai peut-être ce que vous cherchez.',
                'Je l\'ai vu à 30€ dans une brocante récemment.',
                'Essayez sur Discogs ou eBay.',
                'Quel est votre budget maximum ?',
                'Pressing spécifique recherché ?',
            ],
            'Échanges' => [
                'Intéressant, j\'ai peut-être quelque chose.',
                'Photos des vinyles disponibles ?',
                'Quel est l\'état général ?',
                'Je propose un échange 2 contre 1.',
                'Frais de port partagés ?',
            ],
            'Disquaires & Shops' => [
                'Excellente adresse, j\'y ai trouvé des perles.',
                'Leurs prix sont très corrects.',
                'Accueil sympa et bons conseils.',
                'Stock renouvelé régulièrement.',
                'Attention aux horaires, souvent fermé le lundi.',
            ],
            'Staff & Modération' => [
                'Message bien reçu, je m\'en occupe.',
                'Procédure appliquée selon les guidelines.',
                'Utilisateur averti, suivi nécessaire.',
                'Règle mise à jour dans la documentation.',
            ],
        ];

        $categoryResponses = $responses[$category] ?? $responses['Blabla Musical'];
        
        return fake()->randomElement($categoryResponses);
    }
}