<script setup lang="ts">
import { Table, TableBody, TableCaption, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import axios from 'axios';
import { Loader2 } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { Button } from '../ui/button';
import AuctionRow from './AuctionRow.vue';

const auctions = ref<any[]>([]);
const isLoading = ref(true);

const goToAuction = (title: string) => {
    const formattedTitle = title.replace(',', '').replace(/\s/g, '-');
    location.replace(`/auction/${formattedTitle}`);
};

onMounted(async () => {
    try {
        const response = await axios.get('/api/auction/get_all');
        auctions.value = response.data;

        console.log(`There are ${auctions.value.length} auctions`);
    } catch (err) {
        console.error('Failed to fetch auctions:', err);
    } finally {
        isLoading.value = false;
    }
});
</script>

<template>
    <div v-if="isLoading">Loading...</div>
    <div v-else>
        <div class="mb-4 flex justify-center">
            <Button class="cursor-pointer bg-green-500 text-white">
                Force Scrape Auctions
                <!-- <Loader2 className="mr-2 h-4 w-4 animate-spin" /> -->
            </Button>
        </div>
        <Table>
            <TableCaption>A list of all auctions</TableCaption>
            <TableHeader>
                <TableRow>
                    <TableHead>ID</TableHead>
                    <TableHead>Title</TableHead>
                    <TableHead>Date</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Vehicles</TableHead>
                    <TableHead>Unconfigured</TableHead>
                    <TableHead>Won</TableHead>
                    <TableHead>Lost</TableHead>
                    <TableHead>Out Budgeted</TableHead>
                
                </TableRow>
            </TableHeader>
            <TableBody class="max-h-[80vh] min-h-[80vh] overflow-y-auto">
                <AuctionRow v-for="auction in auctions" :key="auction.id" :auction="auction" @click="goToAuction(auction.title)" />
            </TableBody>
        </Table>
    </div>
</template>
