/**
 * Composable pour traiter le contenu des messages du forum
 * Gère automatiquement le format selon la date de création
 */

export function useForumContent() {
    // Date seuil pour détecter les anciens messages (format phpBB/personnalisé)
    const LEGACY_DATE_THRESHOLD = '2025-07-14 10:30:00';

    /**
     * Traite le contenu selon le format de l'époque
     * @param {string} content - Le contenu brut du message
     * @param {string} createdAt - Date de création du message
     * @returns {string} - Contenu HTML transformé
     */
    function processForumContent(content, createdAt = null) {
        if (!content) return '';

        const isLegacyMessage = createdAt && new Date(createdAt) < new Date(LEGACY_DATE_THRESHOLD);

        if (isLegacyMessage) {
            return parseLegacyFormat(content);
        }

        // Messages récents : décoder les entités HTML de TinyMCE
        return decodeHtmlEntities(content);
    }

    /**
     * Décode seulement les entités HTML sans toucher aux balises
     * @param {string} content - Contenu avec entités HTML
     * @returns {string} - Contenu avec caractères décodés
     */
    function decodeHtmlEntities(content) {
        const div = document.createElement('div');
        div.innerHTML = content;
        return div.innerHTML;
    }

    /**
     * Parser générique pour l'ancien format (phpBB-like + balises personnalisées)
     * @param {string} content - Contenu à parser
     * @returns {string} - HTML transformé
     */
    function parseLegacyFormat(content) {
        let parsed = content;

        // Table de conversion des balises legacy vers HTML
        const legacyTags = [
            // Citations (format personnalisé)
            {
                pattern: /<citation auteur="([^"]*)">([\s\S]*?)<\/citation>/g,
                replacement: (match, author, content) => 
                    `<blockquote class="border-l-4 border-blue-500 pl-4 py-2 my-4 bg-gray-50 dark:bg-gray-800 rounded-r-lg">
                        <div class="text-sm text-blue-600 dark:text-blue-400 font-semibold mb-2">
                            Citation de ${author} :
                        </div>
                        <div class="text-gray-700 dark:text-gray-300">
                            ${content.trim()}
                        </div>
                    </blockquote>`
            },
            // Mise en forme de base
            { pattern: /<gras>(.*?)<\/gras>/gs, replacement: '<strong>$1</strong>' },
            { pattern: /<italique>(.*?)<\/italique>/gs, replacement: '<em>$1</em>' },
            { pattern: /<souligne>(.*?)<\/souligne>/gs, replacement: '<u>$1</u>' },
            
            // Images
            {
                pattern: /<image>(.*?)<\/image>/gs,
                replacement: (match, url) => {
                    const cleanUrl = url.trim();
                    if (cleanUrl && (cleanUrl.startsWith('http://') || cleanUrl.startsWith('https://'))) {
                        return `<div class="my-4 text-center">
                            <img src="${cleanUrl}" 
                                 alt="Image du message" 
                                 class="max-w-full h-auto rounded-lg shadow-md cursor-pointer hover:shadow-lg transition-shadow"
                                 onclick="window.open('${cleanUrl}', '_blank')"
                                 loading="lazy" />
                        </div>`;
                    }
                    return `<span class="text-gray-500 italic">[Image: ${cleanUrl}]</span>`;
                }
            },
            
            // Liens
            { pattern: /<lien>(.*?)<\/lien>/gs, replacement: '<a href="$1" target="_blank" class="text-blue-600 hover:underline">$1</a>' },
            
            // Couleurs (si elles existent)
            { pattern: /<couleur="([^"]*)">(.*?)<\/couleur>/gs, replacement: '<span style="color: $1">$2</span>' },
            
            // Taille (si elle existe)
            { pattern: /<taille="([^"]*)">(.*?)<\/taille>/gs, replacement: '<span style="font-size: $1">$2</span>' }
        ];

        // Appliquer toutes les transformations
        legacyTags.forEach(tag => {
            if (typeof tag.replacement === 'function') {
                parsed = parsed.replace(tag.pattern, tag.replacement);
            } else {
                parsed = parsed.replace(tag.pattern, tag.replacement);
            }
        });

        // Smileys/Emojis
        const smileys = {
            ':bravo:': '👏',
            ':lol:': '😂', 
            ':thumbsup:': '👍',
            ':wink:': '😉',
            ':smile:': '😊',
            ':sad:': '😢',
            ':cool:': '😎',
            ':mad:': '😠'
        };

        Object.entries(smileys).forEach(([smiley, emoji]) => {
            parsed = parsed.replace(new RegExp(smiley.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g'), emoji);
        });

        // Préserver les sauts de ligne (en dernier)
        parsed = parsed.replace(/\r?\n/g, '<br>');

        return parsed;
    }

    /**
     * Vérifie si un message est un ancien message basé sur sa date de création
     * @param {string} createdAt - Date de création du message
     * @returns {boolean}
     */
    function isLegacyMessage(createdAt) {
        const LEGACY_DATE_THRESHOLD = '2025-07-14 10:30:00';
        return createdAt && new Date(createdAt) < new Date(LEGACY_DATE_THRESHOLD);
    }

    return {
        processForumContent,
        parseLegacyFormat,
        decodeHtmlEntities,
        isLegacyMessage
    };
}