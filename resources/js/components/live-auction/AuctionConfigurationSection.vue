<script setup lang="ts">
import { Button } from '@/components/ui/button';
import axios from 'axios';
import { onMounted, ref } from 'vue';
import InitiatizationPopover from '../widgets/InitiatizationPopover.vue';
import Progress from '../widgets/Progress.vue';
import VehiclesTable from './VehiclesTable.vue';
// Proper reactive declaration
const auction = ref({
    start_time: '11:00:00',
    end_time: '13:00:00',
    vehicles: [],
});

const fetchAuctionDetails = async () => {
    try {
        const path = window.location.pathname;
        const parts = path.split('/');
        const auctionID = parts[2];
        const deconstructedPath = auctionID.replaceAll('-', ' ');
        const decontructedPathArray = deconstructedPath.split(' ');
        decontructedPathArray[2] = `${decontructedPathArray[2]},`;
        const properAuctionID = decontructedPathArray.join(' ');

        const response = await axios.get(`/api/auction/${properAuctionID}`);
        auction.value = {
            ...response.data,
            // Ensure vehicles exists even if API doesn't return it
            vehicles: response.data.vehicles || [],
        };
    } catch (error) {
        console.log(error);
    }
};

onMounted(async () => {
    await fetchAuctionDetails();
});

const updateVehicle = (updatedVehicle, index) => {
    auction.value.vehicles[index] = updatedVehicle;
};

const saveAllVehicles = async () => {
    try {
        const response = await axios.put(`/api/auction/${auction.value.id}/vehicles`, {
            vehicles: auction.value.vehicles,
        });
        console.log('Save successful', response.data);
        // Optionally show success message
    } catch (error) {
        console.error('Save failed', error);
        // Optionally show error message
    }
};

// Expose the save method to child components
defineExpose({
    saveAllVehicles,
});

function hasDatePassed(dateString) {
    // const inputDate = new Date(dateString + 'T00:00:00Z');
    // const today = new Date();
    // const todayUTC = Date.UTC(today.getFullYear(), today.getMonth(), today.getDate());

    // return inputDate.getTime() < todayUTC;
    if (dateString == '2025-06-05') {
        return false;
    }

    return true;
}
</script>
<template>
    <div class="border-sidebar-border/70 dark:border-sidebar-border flex-1 rounded-xl border">
        <h2 class="text-3rem p-4 text-2xl font-bold tracking-tight sm:text-3xl">
            {{ auction.title }}
        </h2>
        <div class="flex w-full justify-center p-4">
            <Progress
                disabled
                :value="1230"
                :max="1300"
                :min="1100"
                :name="`Fuck`"
                :startTime="auction.start_time"
                :end_time="auction.end_time"
                v-if="!hasDatePassed(auction.date)"
            />
        </div>
        <div class="flex w-full justify-center p-4" v-if="!hasDatePassed(auction.date)">
            <Button :variant="`destructive`" class="mx-4 cursor-pointer">
                <svg width="24" height="24" viewBox="0 0 24 24">
                    <!-- Finger curve -->
                    <path d="M10 18C10 18 8 16 8 14V10" stroke="currentColor" stroke-width="2" fill="none" />
                    <!-- Trigger -->
                    <path d="M8 10H12V14H8" fill="currentColor" class="transition-transform hover:translate-x-1" />
                </svg>
                Bomb!
            </Button>
            <InitiatizationPopover :phillips_accounts_emails="auction.phillips_accounts_emails"/>
        </div>
        <div class="relative">
            <VehiclesTable
                :vehicles="auction.vehicles"
                @update-vehicle="updateVehicle"
                @update-vehicle-in-DB="fetchAuctionDetails"
                :isAuctionConfigurable="!hasDatePassed(auction.date)"
            />
        </div>
    </div>
</template>
