import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useVinylFormats() {
    const page = usePage();

    const vinylFormats = computed(() => page.props.vinylFormats || []);

    const formatsById = computed(() => {
        const map = {};
        for (const f of vinylFormats.value) {
            map[f.id] = f;
        }
        return map;
    });

    const getFormatLabel = (id) => {
        const f = formatsById.value[id];
        return f ? f.label : 'Format inconnu';
    };

    return { vinylFormats, formatsById, getFormatLabel };
}
