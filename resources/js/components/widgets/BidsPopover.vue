<script setup lang="ts">
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTrigger } from '@/components/ui/dialog';
import { Button } from '../ui/button';
import BidsTable from '../live-auction/BidsTable.vue';

const props = defineProps({
    vehicleId: {
        type: String,
        required: true,
    },
});
</script>
<style>
/* In your global CSS (e.g., styles.css) */
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}

.scrollbar-hide {
  -ms-overflow-style: none;  /* IE and Edge */
  scrollbar-width: none;     /* Firefox */
}
</style>
<template>
    <Dialog>
        <DialogTrigger as-child>
            <Button class="cursor-pointer bg-green-500 text-white"> View </Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-[925px] max-h-[700px] overflow-y-scroll scrollbar-hide">
            <DialogHeader>
                <DialogTitle class="text-center"> {{ `BIDS FOR ${props.vehicleId}` }} </DialogTitle>
                <BidsTable :vehicleId="props.vehicleId"/>
            </DialogHeader>
        </DialogContent>
    </Dialog>
</template>
