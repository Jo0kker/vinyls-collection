<template>
    <!-- Ce composant injecte les données structurées via JavaScript -->
</template>

<script setup>
import { computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    type: {
        type: String,
        required: true,
        validator: (value) => ['forum', 'thread', 'category'].includes(value)
    },
    data: {
        type: Object,
        required: true
    },
    baseUrl: {
        type: String,
        default: () => (typeof window !== 'undefined' ? window.location.origin : '')
    }
});

const jsonLd = computed(() => {
    let structuredData = {};
    
    switch (props.type) {
        case 'forum':
            structuredData = {
                "@context": "https://schema.org",
                "@type": "DiscussionForumPosting",
                "name": "Forum Vinyles Collection",
                "description": "Forum de discussion sur les vinyles, collections et échanges",
                "url": `${props.baseUrl}/forum`,
                "publisher": {
                    "@type": "Organization",
                    "name": props.data.appName || "Vinyls Collection"
                },
                "potentialAction": {
                    "@type": "SearchAction",
                    "target": `${props.baseUrl}/forum/search?q={search_term_string}`,
                    "query-input": "required name=search_term_string"
                }
            };
            break;
            
        case 'thread':
            structuredData = {
                "@context": "https://schema.org",
                "@type": "DiscussionForumPosting",
                "headline": props.data.title,
                "text": props.data.first_post?.content_plain?.substring(0, 200),
                "url": `${props.baseUrl}/forum/thread/${props.data.id}`,
                "datePublished": props.data.created_at,
                "dateModified": props.data.updated_at,
                "author": {
                    "@type": "Person",
                    "name": props.data.author?.name || "Anonyme"
                },
                "interactionStatistic": [
                    {
                        "@type": "InteractionCounter",
                        "interactionType": "https://schema.org/CommentAction",
                        "userInteractionCount": props.data.posts_count || 0
                    },
                    {
                        "@type": "InteractionCounter",
                        "interactionType": "https://schema.org/ViewAction",
                        "userInteractionCount": props.data.views_count || 0
                    }
                ],
                "discussionUrl": `${props.baseUrl}/forum/thread/${props.data.id}`
            };
            break;
            
        case 'category':
            structuredData = {
                "@context": "https://schema.org",
                "@type": "CollectionPage",
                "name": props.data.title,
                "description": props.data.description,
                "url": `${props.baseUrl}/forum/category/${props.data.id}`,
                "breadcrumb": {
                    "@type": "BreadcrumbList",
                    "itemListElement": [
                        {
                            "@type": "ListItem",
                            "position": 1,
                            "name": "Forum",
                            "item": `${props.baseUrl}/forum`
                        },
                        {
                            "@type": "ListItem",
                            "position": 2,
                            "name": props.data.title,
                            "item": `${props.baseUrl}/forum/category/${props.data.id}`
                        }
                    ]
                }
            };
            break;
    }
    
    return structuredData;
});

let scriptElement = null;

onMounted(() => {
    // Créer et injecter le script JSON-LD
    scriptElement = document.createElement('script');
    scriptElement.type = 'application/ld+json';
    scriptElement.textContent = JSON.stringify(jsonLd.value, null, 2);
    document.head.appendChild(scriptElement);
});

onUnmounted(() => {
    // Nettoyer le script au démontage
    if (scriptElement && scriptElement.parentNode) {
        scriptElement.parentNode.removeChild(scriptElement);
    }
});
</script>