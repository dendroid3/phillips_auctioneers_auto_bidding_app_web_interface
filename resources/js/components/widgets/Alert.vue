<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

const props = defineProps({
    id: {
        type: Number,
        required: true,
    },
    type: {
        type: String,
        required: true,
        default: () => 'success',
    },

    title: {
        type: String,
        required: true,
    },

    description: {
        type: String,
        required: true,
    },

    time: {
        type: String,
        requied: true,
    },
});

const emits = defineEmits(['dismiss']);

const close = () => {
    emits('dismiss', props.id);
};
</script>

<template>
    <Alert
        class="md:w-150 border p-4"
        :class="
            props.type == 'success'
                ? 'border-green-500 text-green-500'
                : props.type == 'fail'
                  ? 'border-red-500 text-red-500'
                  : props.type == 'amber'
                    ? 'border-amber-500 text-amber-500'
                    : 'border-blue-500 text-blue-500'
        "
    >
        <div class="absolute top-1 right-1 cursor-pointer" @click="close">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle v-if="props.type == 'success'" cx="12" cy="12" r="11" stroke="#10B981" stroke-width="2" fill="none" />
                <path v-if="props.type == 'success'" d="M16 8L8 16" stroke="#10B981" stroke-width="2" stroke-linecap="round" />
                <path v-if="props.type == 'success'" d="M8 8L16 16" stroke="#10B981" stroke-width="2" stroke-linecap="round" />

                <circle v-if="props.type == 'fail'" cx="12" cy="12" r="11" stroke="#EF4444" stroke-width="2" fill="none" />
                <path v-if="props.type == 'fail'" d="M16 8L8 16" stroke="#EF4444" stroke-width="2" stroke-linecap="round" />
                <path v-if="props.type == 'fail'" d="M8 8L16 16" stroke="#EF4444" stroke-width="2" stroke-linecap="round" />

                <circle v-if="props.type == 'amber'" cx="12" cy="12" r="11" stroke="#F59E0B" stroke-width="2" fill="none" />
                <path v-if="props.type == 'amber'" d="M16 8L8 16" stroke="#F59E0B" stroke-width="2" stroke-linecap="round" />
                <path v-if="props.type == 'amber'" d="M8 8L16 16" stroke="#F59E0B" stroke-width="2" stroke-linecap="round" />
            </svg>
        </div>
        <AlertTitle>{{ props.title }}</AlertTitle>

        <AlertDescription>
            <p>
                <span
                    :class="
                        props.type == 'success'
                            ? 'text-green-500'
                            : props.type == 'fail'
                              ? 'text-red-500'
                              : props.type == 'amber'
                                ? 'text-amber-500'
                                : 'text-blue-500'
                    "
                >
                    [{{ props.time }}]
                </span>
                {{ ' ' + props.description }}
            </p>
        </AlertDescription>
    </Alert>
</template>
