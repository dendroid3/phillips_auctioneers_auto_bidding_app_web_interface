<!-- Progress.vue -->
<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import { Slider } from '@/components/ui/slider';
const props = defineProps({
    stage: {
        type: String,
        required: false,
    },
    startTime: {
        type: String,
        required: true,
    },
    endTime: {
        type: String,
        required: true,
    },
    name: {
        type: String,
        required: false,
    },
});

let refreshInterval: number;
const timeToInt = (timeStr) => {
    const [hours, minutes = '00'] = timeStr?.split(':');
    return parseInt(hours + minutes);
};

var progressMin = ref(1100);
var progressValue = ref(1200);
var progressMax = ref(1300);

const processProgressValues = () => {
    const now = new Date();
    const hours = now.getHours().toString().padStart(2, '0');
    const minutes = now.getMinutes().toString().padStart(2, '0');
    const seconds = now.getSeconds().toString().padStart(2, '0');
    const current_time = `${hours}:${minutes}:${seconds}`;

    progressMin.value = timeToInt(props.startTime);
    progressValue.value = timeToInt(current_time); // assuming this is what you meant
    progressMax.value = timeToInt(props.endTime);
};

const timeLeft = ref('');

// ⏱️ Update countdown every second
const updateCountdown = () => {
    const now = new Date();

    // Parse target time (today's date with target hours/minutes/seconds)
    const [targetH, targetM, targetS] = props.endTime.split(':').map(Number);
    const target = new Date();
    target.setHours(targetH, targetM, targetS, 0);

    // Difference in milliseconds
    const diffMs = target - now;

    if (diffMs <= 0) {
        timeLeft.value = '00:00:00'; // time has passed
        return;
    }

    const totalSeconds = Math.floor(diffMs / 1000);
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;
    const pad = (n) => n.toString().padStart(2, '0');

    timeLeft.value = `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
};

let timer = null;

onMounted(() => {
    refreshInterval = setInterval(processProgressValues, 3_000);
    updateCountdown();
    timer = setInterval(updateCountdown, 1000);
});

onUnmounted(() => {
    clearInterval(refreshInterval);
    clearInterval(timer);
});
</script>

<template>
    <div class="flex">
        <span class="text-sm">{{ props.startTime }}</span>
        <input type="range" :value="progressValue" :min="progressMin" :max="progressMax" disabled class="w-150 accent-green-500" />
        <span class="text-sm">{{ props.endTime }}</span>

        <br />
        <div
            class="fixed top-25 right-25 z-50 rounded p-1 text-center font-mono text-2xl"
            :class="
                props.stage == 'lazy'
                    ? 'bg-green-500'
                    : props.stage == 'aggressive'
                      ? 'bg-amber-500'
                      : props.stage == 'sniping'
                        ? 'bg-red-500'
                        : 'hidden'
            "
        >
            {{ timeLeft }}
        </div>
    </div>
</template>
