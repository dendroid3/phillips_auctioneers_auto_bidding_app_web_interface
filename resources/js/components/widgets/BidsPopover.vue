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
    <Button class="cursor-pointer bg-green-500 text-white">View</Button>
  </DialogTrigger>

  <DialogContent class="sm:max-w-[925px] max-h-[700px] overflow-hidden">
    <DialogHeader class="border-b pb-2">
      <DialogTitle class="text-center">
        {{ `BIDS FOR ${props.vehicleId}` }}
      </DialogTitle>
    </DialogHeader>

    <!-- Scrollable content area -->
    <div class="overflow-y-auto max-h-[640px] scrollbar-hide">
      <!-- Adjust max-h here to leave room for title/header (700px - ~60px) -->
      <BidsTable :vehicleId="props.vehicleId" :isPopover="true" />
    </div>
  </DialogContent>
</Dialog>

</template>
