<script setup lang="ts">
import axios from 'axios';
import { defineEmits, onMounted, onUnmounted, ref } from 'vue';
import AuctionStagesConfigurationTable from '../widgets/AuctionStagesConfigurationTable.vue';
import InitiatizationPopover from '../widgets/InitiatizationPopover.vue';
import Progress from '../widgets/Progress.vue';
import VehiclesTable from './VehiclesTable.vue';
// Proper reactive declaration
const auction = ref({
    start_time: '11:00:00',
    end_time: '13:00:00',
    vehicles: [],
});

const stages = ref([]);

const transformStages = (stagesArray) => {
    return {
        lazy_stage: stagesArray.find((s) => s.name === 'lazy'),
        aggressive_stage: stagesArray.find((s) => s.name === 'aggressive'),
        sniping_stage: stagesArray.find((s) => s.name === 'sniping'),
    };
};

const emit = defineEmits(['initialization:started']);

const handleInitilizationStarted = (response) => {
    emit('initialization:started', response);
};

let auctionSessionFetched = ref(false);

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
        stages.value = auction.value.bid_stages;
        console.log(auction);
        auctionSessionFetched = true;
    } catch (error) {
        console.log(error);
    }
};

const handleTimeUpdate = ({ stageName, field, value }) => {
    const stageIndex = stages.value.findIndex((s) => s.name === stageName);
    if (stageIndex !== -1) {
        stages.value[stageIndex][field] = value;

        // If you need to update the transformed stages reference:
        const updatedStages = transformStages(stages.value);
        // Use updatedStages if needed for other purposes
    }
};

onMounted(() => {
    const events = ['.bid.created', '.account.testresults'];

    events.forEach((event) => {
        window.Echo.channel('public-channel').listen(event, (e) => {
            fetchAuctionDetails();
        });
    });
    setTimeout(() => {
        fetchAuctionDetails();
    }, 0);
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

const handleSaveTime = async () => {
    const response = await axios.post('/api/auction/bid_stages/update', stages.value);
    await fetchAuctionDetails();
    await console.log(stages.value);
};
// Expose the save method to child components
defineExpose({
    saveAllVehicles,
});

function isToday(dateString) {
    const inputDate = new Date(dateString + 'T00:00:00Z');
    const today = new Date();
    const todayUTC = Date.UTC(today.getFullYear(), today.getMonth(), today.getDate());

    return inputDate.getTime() == todayUTC;
}

</script>
<template>
    <div class="border-sidebar-border/70 dark:border-sidebar-border flex-1 rounded-xl border">
        <h2 class="text-3rem p-4 text-2xl font-bold tracking-tight sm:text-3xl">
            {{ auction.title }}
        </h2>
        <h3 class="text2rem p-4 text-xl tracking-tight">
            {{ auction.status }}
        </h3>
        <div class="flex w-full justify-center p-4">
            <!-- {{ progressValue }} -->
            <Progress
                disabled
                :stage="auction.bid_stage?.name"
                :name="`Progress`"
                :startTime="auction.start_time"
                :endTime="auction.end_time"
                v-if="isToday(auction.date)"
            />
        </div>
        <div class="flex w-full justify-center p-4" v-if="isToday(auction.date)">
            <!-- <Button :variant="`destructive`" class="mx-4 cursor-pointer"> Bomb! </Button> -->
            <InitiatizationPopover
                :phillips_accounts_emails="auction.phillips_accounts_emails"
                :auction_id="auction.id"
                :auction_status="auction.status"
                @initialization:started="handleInitilizationStarted"
            />
        </div>

        <div class="flex w-full justify-center p-4" v-if="isToday(auction.date)">
            <AuctionStagesConfigurationTable
                :stages="transformStages(stages)"
                :isAuctionConfigurable="auction.status == 'unconfigured'"
                v-if="auctionSessionFetched"
                @update:time="handleTimeUpdate"
                @save:time="handleSaveTime"
                @vehicle-saved="fetchAuctionDetails"
            />
        </div>

        <div class="">
            <VehiclesTable
                :activeStage="auction.bid_stage"
                :vehicles="auction.vehicles"
                @update-vehicle="updateVehicle"
                @update-vehicle-in-DB="fetchAuctionDetails"
                :isAuctionConfigurable="isToday(auction.date) && (auction.status == 'configured' || auction.status == 'active')"
            />
        </div>
    </div>
</template>
