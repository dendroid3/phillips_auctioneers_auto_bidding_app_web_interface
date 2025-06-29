<script setup lang="ts">
import { Table, TableBody, TableCaption, TableHead, TableHeader, TableRow } from '@/components/ui/table';

import { defineEmits, defineProps } from 'vue';

import VehicleRow from './VehicleRow.vue';

const props = defineProps({
    vehicles: {
        type: Object,
        required: true,
        default: () => [],
    },
    isAuctionConfigurable: {
        type: Boolean,
        required: true,
        default: () => (false)
    }
});

const emit = defineEmits(['update-vehicle', 'update-vehicle-in-DB']);

// Add this method to handle vehicle updates from rows
const handleVehicleUpdate = (vehicle, index) => {
    emit('update-vehicle', vehicle, index);
};

const handleVehicleUpdateInDB = () => {
    emit('update-vehicle-in-DB')
}


</script>

<template>
    <div>
        <Table>
            <TableCaption>A list of vehicles in the auction.</TableCaption>
            <TableHeader>
                <TableRow>
                    <TableHead>Name/ID</TableHead>
                    <TableHead>Start Amount</TableHead>
                    <TableHead>Maximum Amount</TableHead>
                    <TableHead v-if="!isAuctionConfigurable">Total Bids</TableHead>
                    <TableHead>{{ isAuctionConfigurable ? 'Current Bid' : 'Final Bid'}}</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Lazy Stage Increment</TableHead>
                    <TableHead>Aggressive Stage Increment</TableHead>
                    <TableHead>Sniping Stage Increment</TableHead>
                    <TableHead v-if="isAuctionConfigurable">Actions</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody class="max-h-[80vh] min-h-[80vh] overflow-y-auto">
                <VehicleRow
                    v-for="(vehicle, index) in $props.vehicles"
                    :key="index"
                    :modelValue="vehicle"
                    :index="index"
                    @update:modelValue="handleVehicleUpdate"
                    @update:vehicle-in-db="handleVehicleUpdateInDB"
                    :isAuctionConfigurable="isAuctionConfigurable"
                />
            </TableBody>
        </Table>
    </div>
</template>
