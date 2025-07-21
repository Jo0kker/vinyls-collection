<script setup>
import { computed, onMounted, onUnmounted, ref, nextTick } from 'vue';

const props = defineProps({
    align: {
        type: String,
        default: 'right',
    },
    width: {
        type: String,
        default: '48',
    },
    contentClasses: {
        type: String,
        default: 'py-1 bg-white dark:bg-gray-700',
    },
});

const closeOnEscape = (e) => {
    if (open.value && e.key === 'Escape') {
        open.value = false;
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));

const widthClass = computed(() => {
    return {
        48: 'w-48',
    }[props.width.toString()];
});

const dropdownRef = ref(null);
const dynamicAlign = ref(props.align);

const alignmentClasses = computed(() => {
    if (dynamicAlign.value === 'left') {
        return 'ltr:origin-top-left rtl:origin-top-right start-0';
    } else if (dynamicAlign.value === 'right') {
        // Sur mobile : décale le dropdown vers la gauche pour qu'il reste visible
        // Sur desktop : alignement normal
        return 'ltr:origin-top-right rtl:origin-top-left -right-32 sm:-right-16 md:-right-4 lg:right-0 lg:end-0';
    } else {
        return 'origin-top';
    }
});

const open = ref(false);

const checkDropdownPosition = async () => {
    if (!open.value) return;
    
    await nextTick();
    
    const dropdown = dropdownRef.value;
    if (!dropdown) return;
    
    const rect = dropdown.getBoundingClientRect();
    const windowWidth = window.innerWidth;
    
    // Si le dropdown sort de l'écran à droite
    if (rect.right > windowWidth) {
        dynamicAlign.value = 'left';
    } else {
        dynamicAlign.value = props.align;
    }
};

// Surveiller l'ouverture du dropdown
const toggleDropdown = () => {
    open.value = !open.value;
    if (open.value) {
        checkDropdownPosition();
    }
};
</script>

<template>
    <div class="relative">
        <div @click="toggleDropdown">
            <slot name="trigger" />
        </div>

        
        <div
            v-show="open"
            class="fixed inset-0 z-40"
            @click="open = false"
        ></div>

        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-show="open"
                ref="dropdownRef"
                class="absolute z-50 mt-2 rounded-md shadow-lg"
                :class="[widthClass, alignmentClasses]"
                style="display: none"
                @click="open = false"
            >
                <div
                    class="rounded-md ring-1 ring-black ring-opacity-5"
                    :class="contentClasses"
                >
                    <slot name="content" />
                </div>
            </div>
        </Transition>
    </div>
</template>
