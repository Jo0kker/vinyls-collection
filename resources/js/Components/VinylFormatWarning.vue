<script setup>
import { computed } from 'vue';

const props = defineProps({
    vinyl: {
        type: Object,
        required: true,
    },
});

const v = computed(() => props.vinyl || {});

const isLegacyImport = computed(() => !!v.value.old_id);
const isDefaultFormat = computed(() => Number(v.value.vinyl_format) === 1);
const isManual = computed(() => !v.value.discogs_id && !!v.value.created_by);
const isDiscogs = computed(() => !!v.value.discogs_id);

const show = computed(() => {
    if (isLegacyImport.value) return false;
    if (!isDefaultFormat.value) return false;
    return isDiscogs.value || isManual.value;
});

const message = computed(() => {
    if (isManual.value) {
        return 'Ce vinyle a été ajouté manuellement à une époque où le choix du format n’était pas disponible. Le format affiché est probablement incorrect — corrigez-le via le bouton « Modifier ».';
    }
    return 'Ce vinyle provient d’un import Discogs antérieur à un correctif sur la gestion des formats. Une re-synchronisation automatique est en cours, le format sera corrigé sous peu.';
});
</script>

<template>
    <div
        v-if="show"
        class="mb-4 rounded-lg border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/30 p-4"
        role="alert"
    >
        <div class="flex items-start gap-3">
            <svg class="h-5 w-5 mt-0.5 shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" />
            </svg>
            <p class="text-sm text-amber-800 dark:text-amber-200">
                {{ message }}
            </p>
        </div>
    </div>
</template>
