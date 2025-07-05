<script setup lang="ts">
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useMoney } from '@/lib/utils';
import axios from 'axios';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import { onMounted, onUnmounted, ref } from 'vue';
const { formatKES } = useMoney();

dayjs.extend(relativeTime);
const getRelativeTime = (time) => {
    const date = dayjs(time);
    return date.isValid() ? date.fromNow() : 'Invalid date';
};

// Auto-refresh logic
let refreshInterval: number;
const bidRelativeTime = (time) => {
    const date = dayjs(time);

    if (!date.isValid()) return 'Invalid date';

    return date.format("HH:mm:ss");
};

const props = defineProps({
    vehicleId: {
        type: String,
        required: false,
    },
});

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

const fetchVehicleBids = async () => {
    try {
        const data = {
            vehicle_id: props.vehicleId,
        };
        const response = await axios.post(`/api/vehicle/bids`, data);
        console.log(response.data);
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
        if (props.vehicleId) {
            fetchVehicleBids();
            refreshInterval = setInterval(fetchVehicleBids, 3_000);
        } else {
            fetchAuctionBids();
            refreshInterval = setInterval(fetchAuctionBids, 3_000);
        }
    }, 0);
});

onUnmounted(() => clearInterval(refreshInterval));
const capitalize = (str) => (str ? `${str[0].toUpperCase()}${str.slice(1)}` : '');
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

            <TableBody v-if="bids">
                <TableRow
                    :class="
                        bid.status == 'highest' || bid.status == 'Highest'
                            ? 'text-green-500'
                            : bid.status == 'Toppled' || bid.status == 'toppled' || bid.status == 'Outbudgeted' || bid.status == 'outbudgeted'
                              ? 'text-red-500'
                              : 'text-amber-500'
                    "
                    v-for="(bid, index) in bids"
                    :key="index"
                >
                    <TableCell
                        v-if="bid.status"
                        class="border-l-4 border-green-500"
                        :class="
                            bid.status == 'highest' || bid.status == 'Highest'
                                ? 'border-green-500'
                                : ['Toppled', 'toppled', 'Outbudgeted', 'outbudgeted', 'Outbudged'].includes(bid.status)
                                  ? 'border-red-500'
                                  : 'border-amber-500'
                        "
                        >{{ bid?.vehicle?.phillips_vehicle_id }}</TableCell
                    >
                    <TableCell v-if="bid.status">{{ bidRelativeTime(bid.created_at) }}</TableCell>
                    <TableCell v-if="bid.status">{{ bid?.phillips_account?.email }}</TableCell>
                    <TableCell v-if="bid.status">{{ capitalize(bid?.bid_stage?.name) }}</TableCell>
                    <TableCell v-if="bid.status">{{ capitalize(bid.status) }}</TableCell>
                    <TableCell v-if="bid.status">{{ formatKES(bid.amount) }}</TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>
