<script setup lang="ts">
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCaption, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import axios from 'axios';
import dayjs from 'dayjs';
import { onMounted, ref } from 'vue';
import { Button } from '../ui/button';
import AuctionRow from './AuctionRow.vue';
const auctions = ref<any[]>([]);
const isLoading = ref(true);

const goToAuction = (title: string) => {
    const formattedTitle = title.replace(',', '').replace(/\s/g, '-');
    location.replace(`/auction/${formattedTitle}`);
};

const forceScrape = async () => {
    const response = await axios.post('/api/auction/scrape');

    alert(response.data.message);
};

const auctionDate = (auctionDate) => {
    const date = dayjs(auctionDate).format('D MMM YYYY');

    return date;
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
            <Button class="cursor-pointer bg-green-500 text-white" @click="forceScrape"> Force Scrape Auctions </Button>
        </div>
        <Table class="hidden md:block table-fixed">
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
        <Card class="block md:hidden" v-for="auction in auctions" :key="auction.id" :auction="auction">
            <CardHeader>
                <CardTitle>{{ auction.title }}</CardTitle>
                <!-- <CardDescription>Configure Bid Stage Times.</CardDescription> -->
            </CardHeader>
            <CardContent class="mt-3">
                <div class="mb-2 space-y-1">
                    <p class="text-md leading-none font-medium">
                        {{ 'Date: ' }}
                        <span class="text-muted-foreground text-md">
                            {{ auctionDate(auction.date) }}
                        </span>
                    </p>
                </div>

                <div class="mb-2 space-y-1">
                    <p class="text-md leading-none font-medium">
                        {{ 'Status: ' }}
                        <span class="text-muted-foreground text-md">
                            {{ auction.status }}
                        </span>
                    </p>
                </div>

                <div class="mb-2 space-y-1">
                    <p class="text-md leading-none font-medium">
                        {{ 'Vehicles: ' }}
                        <span class="text-muted-foreground text-md">
                            {{ auction.total_vehicles_count }}
                        </span>
                    </p>
                </div>

                <div class="mb-2 space-y-1">
                    <p class="text-md leading-none font-medium">
                        {{ 'Unconfigured: ' }}
                        <span class="text-muted-foreground text-md">
                            {{ auction.unconfigured_vehicles_count }}
                        </span>
                    </p>
                </div>

                <div class="mb-2 space-y-1">
                    <p class="text-md leading-none font-medium">
                        {{ 'Won: ' }}
                        <span class="text-muted-foreground text-md">
                            {{ auction.won_vehicles_count }}
                        </span>
                    </p>
                </div>

                <div class="mb-2 space-y-1">
                    <p class="text-md leading-none font-medium">
                        {{ 'Lost: ' }}
                        <span class="text-muted-foreground text-md">
                            {{ auction.lost_vehicles_count }}
                        </span>
                    </p>
                </div>

                <div class="mb-2 space-y-1">
                    <p class="text-md leading-none font-medium">
                        {{ 'Out Budgeted: ' }}
                        <span class="text-muted-foreground text-md">
                            {{ auction.outbudgeted_vehicles_count }}
                        </span>
                    </p>
                </div>
            </CardContent>

            <CardFooter class="flex justify-center px-6 pt-3 pb-2">
                <Button class="mx-4 cursor-pointer bg-green-500 text-white" @click="goToAuction(auction.title)"> View </Button>
            </CardFooter>
        </Card>
    </div>
</template>
