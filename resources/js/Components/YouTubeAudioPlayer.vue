<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    videoId: {
        type: String,
        required: true
    },
    title: {
        type: String,
        default: ''
    }
});

const playerId = ref(`youtube-player-${Math.random().toString(36).substr(2, 9)}`);
const isPlaying = ref(false);
const isReady = ref(false);
const duration = ref(0);
const currentTime = ref(0);
const volume = ref(50);
const isMuted = ref(false);
let player = null;
let updateInterval = null;

const togglePlay = () => {
    if (!player || !isReady.value) return;
    
    if (isPlaying.value) {
        player.pauseVideo();
    } else {
        player.playVideo();
    }
};

const seekTo = (time) => {
    if (!player || !isReady.value) return;
    player.seekTo(time, true);
};

const setVolume = (newVolume) => {
    if (!player || !isReady.value) return;
    volume.value = newVolume;
    player.setVolume(newVolume);
    isMuted.value = newVolume === 0;
};

const toggleMute = () => {
    if (!player || !isReady.value) return;
    
    if (isMuted.value) {
        player.unMute();
        player.setVolume(volume.value);
        isMuted.value = false;
    } else {
        player.mute();
        isMuted.value = true;
    }
};

const formatTime = (seconds) => {
    if (!seconds || isNaN(seconds)) return '0:00';
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = Math.floor(seconds % 60);
    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
};

const updateProgress = () => {
    if (!player || !isReady.value) return;
    
    try {
        const current = player.getCurrentTime();
        const total = player.getDuration();
        
        if (current !== undefined) currentTime.value = current;
        if (total !== undefined) duration.value = total;
    } catch (error) {
        // Ignore les erreurs de l'API YouTube
    }
};

const startProgressUpdate = () => {
    if (updateInterval) clearInterval(updateInterval);
    updateInterval = setInterval(updateProgress, 1000);
};

const stopProgressUpdate = () => {
    if (updateInterval) {
        clearInterval(updateInterval);
        updateInterval = null;
    }
};

onMounted(() => {
    // Charger l'API YouTube si pas déjà chargée
    if (!window.YT) {
        const tag = document.createElement('script');
        tag.src = 'https://www.youtube.com/iframe_api';
        const firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
        
        window.onYouTubeIframeAPIReady = () => {
            initPlayer();
        };
    } else if (window.YT && window.YT.Player) {
        initPlayer();
    } else {
        // L'API est en cours de chargement
        const checkInterval = setInterval(() => {
            if (window.YT && window.YT.Player) {
                clearInterval(checkInterval);
                initPlayer();
            }
        }, 100);
    }
});

const initPlayer = () => {
    player = new YT.Player(playerId.value, {
        height: '1',
        width: '1',
        videoId: props.videoId,
        playerVars: {
            autoplay: 0,
            controls: 0,
            disablekb: 1,
            fs: 0,
            modestbranding: 1,
            rel: 0
        },
        events: {
            onReady: () => {
                isReady.value = true;
                player.setVolume(volume.value);
                updateProgress();
            },
            onStateChange: (event) => {
                if (event.data === YT.PlayerState.PLAYING) {
                    isPlaying.value = true;
                    startProgressUpdate();
                } else if (event.data === YT.PlayerState.PAUSED) {
                    isPlaying.value = false;
                    stopProgressUpdate();
                } else if (event.data === YT.PlayerState.ENDED) {
                    isPlaying.value = false;
                    stopProgressUpdate();
                    currentTime.value = 0;
                }
            }
        }
    });
};

onUnmounted(() => {
    stopProgressUpdate();
    if (player && player.destroy) {
        player.destroy();
    }
});
</script>

<template>
    <div class="youtube-audio-player">
        <!-- Player YouTube caché -->
        <div :id="playerId" style="position: absolute; width: 1px; height: 1px; overflow: hidden;"></div>
        
        <!-- Interface audio complète -->
        <div class="bg-gray-50 dark:bg-gray-600 rounded-lg p-4">
            <!-- Header avec titre et lien YouTube -->
            <div class="mb-3 flex items-start justify-between gap-2">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                        {{ title }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ isReady ? (isPlaying ? 'En lecture' : 'En pause') : 'Chargement...' }}
                    </p>
                </div>
                <!-- Slot pour le bouton YouTube -->
                <slot name="extra-actions"></slot>
            </div>

            <!-- Contrôles principaux -->
            <div class="flex items-center space-x-3 mb-3">
                <!-- Bouton Play/Pause -->
                <button @click="togglePlay"
                        :disabled="!isReady"
                        class="flex-shrink-0 p-2 bg-purple-600 hover:bg-purple-700 disabled:cursor-not-allowed disabled:opacity-50 text-white rounded-full transition-colors">
                    <svg v-if="!isPlaying" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"></path>
                    </svg>
                    <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M5.75 3a.75.75 0 00-.75.75v12.5c0 .414.336.75.75.75h1.5a.75.75 0 00.75-.75V3.75A.75.75 0 007.25 3h-1.5zM12.75 3a.75.75 0 00-.75.75v12.5c0 .414.336.75.75.75h1.5a.75.75 0 00.75-.75V3.75a.75.75 0 00-.75-.75h-1.5z"></path>
                    </svg>
                </button>

                <!-- Timeline -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center space-x-2">
                        <span class="text-xs text-gray-500 dark:text-gray-400 w-10 text-right">
                            {{ formatTime(currentTime) }}
                        </span>
                        <div class="flex-1 relative">
                            <!-- Barre de fond -->
                            <div class="relative group">
                                <div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full transition-all group-hover:h-3"></div>
                                <!-- Barre de progression -->
                                <div class="absolute top-0 left-0 h-2 bg-gradient-to-r from-purple-500 to-purple-600 rounded-full transition-all duration-150 group-hover:h-3 shadow-sm"
                                     :style="{ width: duration > 0 ? (currentTime / duration) * 100 + '%' : '0%' }"></div>
                                <!-- Curseur circulaire -->
                                <div class="absolute top-1/2 w-3 h-3 bg-purple-600 border-2 border-white rounded-full shadow-md transform -translate-y-1/2 transition-all opacity-0 group-hover:opacity-100 group-hover:w-4 group-hover:h-4"
                                     :style="{ left: duration > 0 ? (currentTime / duration) * 100 + '%' : '0%', transform: 'translateX(-50%) translateY(-50%)' }"></div>
                                <!-- Input range transparent par-dessus -->
                                <input type="range"
                                       :min="0"
                                       :max="duration || 100"
                                       :value="currentTime"
                                       @input="seekTo($event.target.value)"
                                       :disabled="!isReady || duration === 0"
                                       class="absolute top-0 left-0 w-full h-2 opacity-0 cursor-pointer disabled:cursor-not-allowed group-hover:h-3">
                            </div>
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400 w-10">
                            {{ formatTime(duration) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Contrôles de volume -->
            <div class="flex items-center space-x-2">
                <!-- Bouton muet -->
                <button @click="toggleMute"
                        :disabled="!isReady"
                        class="p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg v-if="isMuted || volume === 0" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.617.789L6.035 15H3a1 1 0 01-1-1V6a1 1 0 011-1h3.035l2.348-1.789zm0 0a1 1 0 01.617-.023L12 4a1 1 0 011 1v2.5A2.5 2.5 0 0115.5 10a2.5 2.5 0 01-2.5 2.5V14a1 1 0 01-1 1l-2-.023zM3.707 9.293a1 1 0 011.414 0L6.5 10.672 7.879 9.293a1 1 0 011.414 1.414L7.914 12.086l1.379 1.379a1 1 0 01-1.414 1.414L6.5 13.5l-1.379 1.379a1 1 0 01-1.414-1.414L5.086 12.086 3.707 10.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                    <svg v-else-if="volume <= 33" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.617.789L6.035 15H3a1 1 0 01-1-1V6a1 1 0 011-1h3.035l2.348-1.789A1 1 0 019.383 3.076z" clip-rule="evenodd"></path>
                    </svg>
                    <svg v-else-if="volume <= 66" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.617.789L6.035 15H3a1 1 0 01-1-1V6a1 1 0 011-1h3.035l2.348-1.789A1 1 0 019.383 3.076zM12.293 7.293a1 1 0 011.414 0L15 8.586l1.293-1.293a1 1 0 111.414 1.414L16.414 10l1.293 1.293a1 1 0 01-1.414 1.414L15 11.414l-1.293 1.293a1 1 0 01-1.414-1.414L13.586 10l-1.293-1.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                    <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.617.789L6.035 15H3a1 1 0 01-1-1V6a1 1 0 011-1h3.035l2.348-1.789A1 1 0 019.383 3.076zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828 1 1 0 010-1.415z" clip-rule="evenodd"></path>
                    </svg>
                </button>

                <!-- Curseur de volume -->
                <div class="flex-1 max-w-20 relative">
                    <div class="relative group">
                        <div class="w-full h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full transition-all group-hover:h-2"></div>
                        <!-- Barre de volume -->
                        <div class="absolute top-0 left-0 h-1.5 bg-gradient-to-r from-gray-400 to-purple-500 rounded-full transition-all duration-150 group-hover:h-2 shadow-sm"
                             :style="{ width: (isMuted ? 0 : volume) + '%' }"></div>
                        <!-- Curseur circulaire -->
                        <div class="absolute top-1/2 w-2 h-2 bg-purple-500 border border-white rounded-full shadow-sm transform -translate-y-1/2 transition-all opacity-0 group-hover:opacity-100 group-hover:w-3 group-hover:h-3"
                             :style="{ left: (isMuted ? 0 : volume) + '%', transform: 'translateX(-50%) translateY(-50%)' }"></div>
                        <!-- Input range transparent -->
                        <input type="range"
                               :min="0"
                               :max="100"
                               :value="isMuted ? 0 : volume"
                               @input="setVolume($event.target.value)"
                               :disabled="!isReady"
                               class="absolute top-0 left-0 w-full h-1.5 opacity-0 cursor-pointer disabled:cursor-not-allowed group-hover:h-2">
                    </div>
                </div>

                <span class="text-xs text-gray-500 dark:text-gray-400 w-8 text-right">
                    {{ isMuted ? 0 : volume }}
                </span>
            </div>
        </div>
    </div>
</template>