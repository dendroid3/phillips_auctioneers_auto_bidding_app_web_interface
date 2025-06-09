<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { defineProps } from 'vue';

const props = defineProps({
    type: {
        type: String,
        required: true,
        default: () => ('success')
    },

    title: {
        type: String,
        required: true
    },

    description: {
        type: String,
        required: true
    }
})
</script>

<template>
  <Alert class="border p-4 w-100" :class="props.type == 'success' ? 'border-green-500 text-green-500' : 'border-red-500 text-red-500'">
    <svg v-if="props.type == 'success'" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-arrow-out-up-right-icon lucide-circle-arrow-out-up-right"><path d="M22 12A10 10 0 1 1 12 2"/><path d="M22 2 12 12"/><path d="M16 2h6v6"/></svg>
    <svg v-else xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-arrow-out-down-right-icon lucide-circle-arrow-out-down-right"><path d="M12 22a10 10 0 1 1 10-10"/><path d="M22 22 12 12"/><path d="M22 16v6h-6"/></svg>
    <AlertTitle>{{ props.title }}</AlertTitle>
    <AlertDescription :class="props.type == 'success' ? 'text-green-500' : 'text-red-500'">
      {{ props.description }}
    </AlertDescription>
  </Alert>
</template>
