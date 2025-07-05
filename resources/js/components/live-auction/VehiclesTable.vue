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
    },
    activeStage: {
        type: Object,
        required: true,
        default: () => ({
            name: "none"
        })
    }
});

const emit = defineEmits(['update-vehicle', 'update-vehicle-in-DB', 'vehicle-saved']);

// Add this method to handle vehicle updates from rows
const handleVehicleUpdate = (vehicle, index) => {
    emit('update-vehicle', vehicle, index);
};

const handleVehicleUpdateInDB = () => {
    emit('update-vehicle-in-DB')
}

const handleVehicleSaved = () => {
    emit('vehicle-saved')
}


</script>

<template>
    <div>
        <Table>
            <TableCaption>A list of vehicles in the auction.</TableCaption>
            <TableHeader>
                <TableRow>
                    <TableHead>Name</TableHead>
                    <TableHead>Start Amount</TableHead>
                    <TableHead>Maximum Amount</TableHead>
                    <TableHead v-if="!isAuctionConfigurable">Total Bids</TableHead>
                    <TableHead>{{ isAuctionConfigurable ? 'Current Bid' : 'Final Bid'}}</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead :class="activeStage?.name == 'lazy' ? 'bg-green-500 text-black': ''">Lazy Stage Increment</TableHead>
                    <TableHead :class="activeStage?.name == 'aggressive' ? 'bg-amber-500 text-black': ''">Aggressive Stage Increment</TableHead>
                    <TableHead :class="activeStage?.name == 'sniping' ? 'bg-red-500 text-black': ''">Sniping Stage Increment</TableHead>
                    <TableHead>Actions</TableHead>
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
                    @vehicle-saved="handleVehicleSaved"
                    :isAuctionConfigurable="isAuctionConfigurable"
                />
            </TableBody>
        </Table>
    </div>
</template>
