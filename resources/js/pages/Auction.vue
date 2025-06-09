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

let alertIdCounter = 0;
let interval: number;

const generateRandomAlert = () => {
    const carModels = ['Toyota Noah', 'Nissan Note', 'Toyota Vitz', 'Subaru Forester', 'Mazda Demio'];
    const plates = ['KCV-129G', 'KCD-543K', 'KBD-846K', 'KEF-782L', 'KGH-456M'];
    const bidAmounts = [100000, 125000, 150000, 175000, 200000];

    const alertTypes = [
        {
            type: 'success',
            title: 'Bid Successful',
            template: (car: string, plate: string, amount: number) => `Bid of Ksh ${amount.toLocaleString()} placed on ${plate} - ${car}`,
        },
        {
            type: 'fail',
            title: 'Outbid',
            template: (car: string, plate: string, amount: number) =>
                `Your bid on ${plate} - ${car} was outbid by Ksh ${(amount + 5000).toLocaleString()}`,
        },
        {
            type: 'warning',
            title: 'Auction Ending',
            template: (car: string, plate: string) => `Auction for ${plate} - ${car} closes in ${Math.floor(Math.random() * 10) + 2} minutes`,
        },
    ];

    const type = alertTypes[Math.floor(Math.random() * alertTypes.length)];
    const car = carModels[Math.floor(Math.random() * carModels.length)];
    const plate = plates[Math.floor(Math.random() * plates.length)];
    const amount = bidAmounts[Math.floor(Math.random() * bidAmounts.length)];

    return {
        type: type.type,
        title: type.title,
        description: type.template(car, plate, amount),
        id: alertIdCounter++,
    };
};

const addAlert = () => {
    if (alerts.value.length >= 5) return;

    const newAlert = generateRandomAlert();
    alerts.value.unshift(newAlert);

    // Auto-dismiss after 5-8 seconds
    setTimeout(
        () => {
            alerts.value = alerts.value.filter((alert) => alert.id !== newAlert.id);
        },
        Math.random() * 9000 + 10000,
    );
};

const removeAlert = (id: number) => {
    alerts.value = alerts.value.filter((alert) => alert.id !== id);
};

onMounted(() => {
    // Initial alert
    addAlert();

    // Set up interval for new alerts (every 3-6 seconds)
    interval = window.setInterval(
        () => {
            addAlert();
        },
        Math.random() * 3000 + 3000,
    );
});

onBeforeUnmount(() => {
    clearInterval(interval);
});
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
                            @dismiss="removeAlert(alert.id)"
                            class="pointer-events-auto"
                        />
                    </div>
                </div>

                <LiveAuctionSection />
                <BidsTable />
            </div>
        </div>
    </AppLayout>
</template>
