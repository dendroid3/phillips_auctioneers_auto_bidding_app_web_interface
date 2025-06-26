<script setup lang="ts">
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import axios from 'axios';
import { useMoney } from '@/lib/utils';
import { onMounted, ref } from 'vue';
const { formatKES } = useMoney();
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';

dayjs.extend(relativeTime);
const bidRelativeTime = (time) => {
    const date = dayjs(time);

    if (!date.isValid()) return 'Invalid date';

    return date.fromNow();
};

let bids = ref([]);
let bidsFetched = ref(false);
const fetchAuctionBids = async () => {
    try {
        const path = window.location.pathname;
        const parts = path.split('/');
        const auctionID = parts[2];
        const deconstructedPath = auctionID.replaceAll('-', ' ');
        const decontructedPathArray = deconstructedPath.split(' ');
        decontructedPathArray[2] = `${decontructedPathArray[2]},`;
        const properAuctionID = decontructedPathArray.join(' ');

        const response = await axios.get(`/api/bid/get_all_for_auction/${properAuctionID}`);
        bids.value = {
            ...response.data,
            // Ensure vehicles exists even if API doesn't return it
            bids: response.data.vehicles || [],
        };
        bidsFetched = true;
    } catch (error) {
        console.log(error);
    }
};

onMounted(() => {
    setTimeout(() => {
        fetchAuctionBids();
    }, 0);
});
const capitalize = str => str ? `${str[0].toUpperCase()}${str.slice(1)}` : '';
</script>
<template>
    <div class="border-sidebar-border/70 dark:border-sidebar-border mt-4 flex-1 rounded-xl border">
        <Table>
            <TableCaption>Bid History on this Vehicle</TableCaption>
            <TableHeader class="relative top-0 right-0 left-0 h-20">
                <TableRow>
                    <TableHead>Name/ID</TableHead>
                    <TableHead>Time</TableHead>
                    <TableHead>Account</TableHead>
                    <TableHead>Stage</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Amount</TableHead>
                </TableRow>
            </TableHeader>

            <TableBody>
                <TableRow v-for="(bid, index) in bids" :key="index">
                    <TableCell class="border-l-4 border-green-500">{{ bid.vehicle.phillips_vehicle_id }}</TableCell>
                    <TableCell>{{ bidRelativeTime(bid.created_at) }}</TableCell>
                    <TableCell>{{ bid.phillips_account.email }}</TableCell>
                    <TableCell>{{ capitalize(bid.bid_stage.name) }}</TableCell>
                    <TableCell>{{ capitalize(bid.status) }}</TableCell>
                    <TableCell>{{ formatKES(bid.amount) }}</TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>
