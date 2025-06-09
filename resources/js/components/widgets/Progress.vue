<script setup lang="ts">
import { Slider } from '@/components/ui/slider';

const props = defineProps({
    value: {
        type: Number,
        required: true,
        default: () => 1100,
    },
    max: {
        type: Number,
        required: true,
        default: () => 1300,
    },
    min: {
        type: Number,
        required: true,
        default: () => 1100,
    },
    startTime: {
        type: String,
        required: true,
        default: () => "11:00:00"
    },
    endTime: {
        type: String,
        required: true,
        default: () => "13:00:00"
    }
});
</script>

<template>
    <div class="flex">
        <span class="w-25"> {{ `${props.startTime}` }} </span>
        <Slider :default-value="[props.value]" class="w-150" :max="props.max" :min="props.min" :step="1" disabled/>
        <span class="w-25 align-center flex justify-end">{{ `${props.endTime}` }}</span>
    </div>
</template>
