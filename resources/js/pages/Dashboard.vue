<script setup lang="ts">
import AuctionsTable from '@/components/live-auction/AuctionsTable.vue';
import Alert from '@/components/widgets/Alert.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div>
                <AuctionsTable />
            </div>
        </div>
    </AppLayout>
</template>
