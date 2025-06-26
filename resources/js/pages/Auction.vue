<script setup lang="ts">
import LiveAuctionSection from '@/components/live-auction/AuctionConfigurationSection.vue';
import BidsTable from '@/components/live-auction/BidsTable.vue';
import Alert from '@/components/widgets/Alert.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Auction',
        href: '/auction',
    },
];

// Dynamic alerts system
const alerts = ref<
    Array<{
        type: string;
        title: string;
        description: string;
        id: number;
    }>
>([]);
const playSuccess = () => {
    const audio = new Audio('/sounds/success.mp3');
    audio.currentTime = 0;
    audio.play();
};

const playFailure = () => {
    const audio = new Audio('/sounds/failure.mp3');
    audio.currentTime = 0;
    audio.play();
};
let interval: number;

const removeAlert = (id: number) => {
    alerts.value = alerts.value.filter((alert) => alert.id !== id);
};

onMounted(() => {
    window.Echo.channel('public-channel').listen('.bid.created', (e) => {
        if (e.type == 'success') {
            playSuccess();
        } else {
            playFailure();
        }
        const newAlert = {
            id: e.id,
            type: e.type,
            title: e.title,
            description: e.description,
        };
        alerts.value.unshift(newAlert);

        // Auto-dismiss after 5-8 seconds
        setTimeout(() => {
            removeAlert(newAlert.id);
        }, 90000);
    });
});

onBeforeUnmount(() => {
    clearInterval(interval);
});

const handleInitilizationStarted = (response) => {
    alerts.value.unshift(response);
    setTimeout(() => {
        removeAlert(response.id);
    }, 90000);
};
</script>

<template>
    <Head title="Auction" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div>
                <!-- Alerts container moved to bottom-right -->
                <!-- Remove the hidden class to show fake alerts -->
                <div class="pointer-events-none fixed inset-0 z-50">
                    <div class="fixed right-4 bottom-4 flex flex-col items-end space-y-2">
                        <Alert
                            v-for="alert in alerts"
                            :key="alert.id"
                            :type="alert.type"
                            :title="alert.title"
                            :description="alert.description"
                            :id="alert.id"
                            @dismiss="removeAlert(alert.id)"
                            class="pointer-events-auto"
                        />
                    </div>
                </div>

                <LiveAuctionSection @initialization:started="handleInitilizationStarted" />
                <BidsTable />
            </div>
        </div>
    </AppLayout>
</template>
