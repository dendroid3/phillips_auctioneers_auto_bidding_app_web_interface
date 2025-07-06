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

    return date.format('HH:mm:ss');
};

const props = defineProps({
    vehicleId: {
        type: String,
        required: false,
    },
    isPopover: {
        type: Boolean,
        required: false,
        default: () => false,
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
        <Table class="w-full table-auto">
            <TableCaption>Bid History on this Vehicle</TableCaption>

            <TableHeader class="sticky top-0">
                <TableRow>
                    <TableHead>Time</TableHead>
                    <TableHead v-if="!isPopover">Name/ID</TableHead>
                    <TableHead class="hidden md:table-cell">Account</TableHead>
                    <TableHead class="hidden md:table-cell">Stage</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Amount</TableHead>
                </TableRow>
            </TableHeader>

            <TableBody v-if="bids">
                <TableRow
                    v-for="(bid, index) in bids"
                    :key="index"
                    :class="{
                        'text-green-500': ['highest', 'Highest'].includes(bid.status),
                        'text-red-500': ['Toppled', 'toppled', 'Outbudgeted', 'outbudgeted'].includes(bid.status),
                        'text-amber-500': !['highest', 'Highest', 'Toppled', 'toppled', 'Outbudgeted', 'outbudgeted'].includes(bid.status),
                    }"
                >
                    <!-- Bid Time -->
                    <TableCell
                        v-if="bid.status"
                        :class="[
                            'border-l-4',
                            bid.status === 'highest' || bid.status === 'Highest'
                                ? 'border-green-500'
                                : ['Toppled', 'toppled', 'Outbudgeted', 'outbudgeted', 'Outbudged'].includes(bid.status)
                                  ? 'border-red-500'
                                  : 'border-amber-500',
                        ]"
                    >
                        {{ bidRelativeTime(bid.created_at) }}
                    </TableCell>
                    <!-- Vehicle ID -->
                    <TableCell v-if="bid.status && !isPopover">
                        {{ bid?.vehicle?.phillips_vehicle_id }}
                    </TableCell>

                    <!-- Account Email -->
                    <TableCell v-if="bid.status" class="hidden md:table-cell">
                        {{ bid?.phillips_account?.email }}
                    </TableCell>

                    <!-- Stage -->
                    <TableCell v-if="bid.status" class="hidden md:table-cell">
                        {{ capitalize(bid?.bid_stage?.name) }}
                    </TableCell>

                    <!-- Status -->
                    <TableCell v-if="bid.status">
                        {{ capitalize(bid.status) }}
                    </TableCell>

                    <!-- Amount -->
                    <TableCell v-if="bid.status">
                        {{ formatKES(bid.amount) }}
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>
