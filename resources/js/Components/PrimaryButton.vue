<script setup>
import { computed, onMounted, ref } from 'vue'

const isDark = ref(false)

onMounted(() => {
  isDark.value = document.documentElement.classList.contains('dark')
  
  // Observer pour détecter les changements de thème
  const observer = new MutationObserver(() => {
    isDark.value = document.documentElement.classList.contains('dark')
  })
  
  observer.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['class']
  })
})

const buttonStyle = computed(() => {
  return isDark.value ? { 
    color: '#000000 !important',
    backgroundColor: '#ffffff !important' 
  } : {}
})
</script>

<template>
    <button
        class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-gray-700 focus:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 active:bg-gray-900 dark:bg-white dark:text-gray-900"
        :style="buttonStyle"
    >
        <slot />
    </button>
</template>
