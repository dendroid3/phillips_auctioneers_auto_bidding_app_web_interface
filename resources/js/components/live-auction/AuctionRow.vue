<script setup lang="ts">
import { defineProps, computed } from 'vue';
import { TableRow, TableCell } from '../ui/table';
import dayjs from 'dayjs';
const props = defineProps({
    auction: {
        type: Object,
        required: true
    }
});

const auctionDate = computed(() => {
  const date = dayjs(props.auction.date).format('D MMM YYYY');

  return date;
})
</script>

<template>
  <TableRow class="cursor-pointer">
    <TableCell>
      {{ props.auction.id }}
    </TableCell>
    
    <TableCell style="width: 40rem; min-width: 40rem; max-width: 40rem" class="whitespace-normal">
      {{ props.auction.title }}
    </TableCell>
    
    <TableCell>
      {{ auctionDate }}
    </TableCell>
    
    <TableCell>
      {{ props.auction.status }}
    </TableCell>
    
    <TableCell>{{ props.auction.total_vehicles_count }}</TableCell>
    <TableCell>{{ props.auction.unconfigured_vehicles_count }}</TableCell>
    <TableCell>{{ props.auction.won_vehicles_count }}</TableCell>
    <TableCell>{{ props.auction.lost_vehicles_count }}</TableCell>
    <TableCell>{{ props.auction.outbudgeted_vehicles_count }}</TableCell>
    
  </TableRow>
</template>
