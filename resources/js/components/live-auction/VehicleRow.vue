<script setup lang="ts">
import { TableCell, TableRow } from '@/components/ui/table';
import { useMoney } from '@/lib/utils';
import axios from 'axios';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
const { formatKES } = useMoney();

import { Button } from '@/components/ui/button';

import { computed, defineEmits, defineProps, inject, ref } from 'vue';

const props = defineProps({
    modelValue: {
        type: Object,
        required: true,
        default: () => ({
            id: 'Example Vehicle',
            start_amount: '35000',
            maximum_amount: '250000',
            current_bid: '125600',
            last_bid_time: '2025-05-31 16:18:48',
            status: 'Unconfigured',
            lazy_stage: {
                start_time: '10:00:00',
                end_time: '12:00:00',
                increment: '3000',
                status: 'active',
            },
            aggressive_stage: {
                start_time: '12:00:01',
                end_time: '12:55:00',
                increment: '5000',
                stage: 'dormant',
            },
            sniping_stage: {
                start_time: '12:55:01',
                end_time: '13:00:00',
                increment: '10000',
                stage: 'dormant',
            },
        }),
    },
    index: {
        type: Number,
        required: true,
        default: () => 0,
    },
    isAuctionConfigurable: {
        type: Boolean,
        required: true,
        default: () => false,
    },
});

dayjs.extend(relativeTime);

const lastBidRelativeTime = computed(() => {
    const date = dayjs(props.modelValue.last_bid_time);

    if (!date.isValid()) return 'Invalid date';

    return date.fromNow();
});

const dropOff = async (vehicle_id) => {
    if(!confirm(`Are you sure you want to drop ${vehicle_id} off the auction? You can bring it back later.`)) return

    const response = await axios.post("/api/vehicle/drop", {
        id: vehicle_id
    })
    alert(response.data.message)
    location.reload()
}

const emit = defineEmits(['update:modelValue', 'update:vehicle-in-db']);

const changeMade = ref(false);

function increaseTimeByOneSecond(timeString) {
    // Split the time string into parts
    const parts = timeString.split(':');

    // Handle both "HH:mm" and "HH:mm:ss" formats
    let h = Number(parts[0]);
    let m = Number(parts[1]);
    let s = parts.length > 2 ? Number(parts[2]) : 0; // Default seconds to 0 if not provided

    // Increment seconds
    s += 1;

    // Handle overflow
    if (s > 59) {
        s = 0;
        m += 1;
    }
    if (m > 59) {
        m = 0;
        h += 1;
    }
    if (h > 23) {
        h = 0;
    }

    // Always return in HH:mm:ss format
    return [String(h).padStart(2, '0'), String(m).padStart(2, '0'), String(s).padStart(2, '0')].join(':');
}

const updateTime = (value, nestedPath) => {
    // Convert to comparable format (total seconds)
    const toSeconds = (timeStr) => {
        const [h, m, s = 0] = timeStr.split(':').map(Number);
        return h * 3600 + m * 60 + s;
    };

    // Check constraints
    let isValid = true;

    // Lazy stage must be between 11:15 AM and 12:30 PM
    if (nestedPath === 'lazy_stage.end_time') {
        const timeSec = toSeconds(value);
        if (timeSec < toSeconds('11:15:00') || timeSec > toSeconds('12:30:00')) {
            isValid = false;
            alert('Lazy stage must end between 11:15 AM and 12:30 PM');
            // Auto-correct to nearest valid time
            if (timeSec < toSeconds('11:15:00')) {
                value = '11:15:00';
                isValid = true; // Allow the corrected value
            } else if (timeSec > toSeconds('12:30:00')) {
                value = '12:30:00';
                isValid = true; // Allow the corrected value
            }
        }
    }

    // Aggressive stage cannot be after 12:57 PM
    if (nestedPath === 'aggressive_stage.end_time' && toSeconds(value) > toSeconds('12:57:00')) {
        isValid = false;
        alert('Aggressive stage cannot end after 12:57 PM');
        // Auto-correct to maximum allowed time
        value = '12:57:00';
        isValid = true; // Allow the corrected value
    }

    if (!isValid) {
        return; // Don't proceed with update
    }

    changeMade.value = true;
    const newValue = {
        ...props.modelValue,
    };

    const [parent, child] = nestedPath.split('.');
    newValue[parent] = {
        ...newValue[parent],
        [child]: String(value),
    };

    emit('update:modelValue', newValue, props.index);

    setTimeout(() => {
        switch (nestedPath) {
            case 'lazy_stage.end_time':
                updateTime(increaseTimeByOneSecond(value), 'aggressive_stage.start_time');
                break;
            case 'aggressive_stage.end_time':
                updateTime(increaseTimeByOneSecond(value), 'sniping_stage.start_time');
                break;
            default:
                break;
        }
    }, 0);
};

const updateAmount = (value, field) => {
    changeMade.value = true;
    const newValue = {
        ...props.modelValue,
    };

    const index = props.index;
    newValue[field] = String(value);
    emit('update:modelValue', newValue, index);
};

const preventNonNumericInput = (event) => {
    // Allow: backspace, delete, tab, escape, enter, decimal point
    // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
    if (
        ['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', '.'].includes(event.key) ||
        (event.key === 'a' && event.ctrlKey) ||
        (event.key === 'c' && event.ctrlKey) ||
        (event.key === 'v' && event.ctrlKey) ||
        (event.key === 'x' && event.ctrlKey)
    ) {
        return;
    }

    // Prevent if not a number
    if (isNaN(Number(event.key))) {
        event.preventDefault();
    }
};

const parentComponent = inject('parent');

let isSaving = ref(false);
const saveChanges = async () => {
    try {
        console.log(props.modelValue.start_amount);
        console.log(props.modelValue.maximum_amount);
        // if(props.modelValue.start_amount > props.modelValue.maximum_amount){
        //     alert("Maximum amount cannot be less than the start amount");
        //     return;
        // }
        isSaving = true;
        const response = await axios.put(`/api/vehicle/update`, props.modelValue);

        changeMade.value = false;
        alert(response.data.message);
        location.reload()

        console.log('Save successful');
        isSaving = false;
    } catch (error) {
        console.error('Save failed', error);
    }
};
</script>

<template>
    <!-- <div> -->
    <TableRow>
        <TableCell
            class="group relative w-30 cursor-pointer border-l-4 font-medium"
            :class="
                modelValue.status == 'highest' || modelValue.status == 'active'
                    ? 'border-green-500'
                    : modelValue.status == 'outbidded'
                      ? 'border-amber-500'
                      : 'border-red-500'
            "
        >
            <!-- Beeping Notification Button - Only for "Highest" status -->
            <div v-if="modelValue.status === 'Highest'" class="absolute top-0 right-0 translate-x-1/2 -translate-y-1/2 transform">
                <div class="relative">
                    <div class="z-10 flex h-4 w-4 items-center justify-center rounded-full bg-green-500">
                        <span class="text-[10px] font-bold text-white">!</span>
                    </div>
                    <div class="absolute inset-0 animate-ping rounded-full bg-green-500 opacity-75"></div>
                </div>
            </div>

            {{ modelValue.id }}
        </TableCell>
        <TableCell>
            <div class="relative">
                <div class="mb-2 w-30 text-center">
                    {{ formatKES(modelValue.start_amount) }}
                </div>
                <input
                    v-if="isAuctionConfigurable"
                    type="number"
                    @keydown="preventNonNumericInput"
                    :value="modelValue.start_amount"
                    @input="updateAmount($event.target.value, 'start_amount')"
                    placeholder="4,000"
                    class="w-30 rounded-md border border-gray-300 px-2 py-1 pl-7 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                />
            </div>
        </TableCell>
        <TableCell>
            <div class="relative">
                <div class="mb-2 w-30 text-center">
                    {{ formatKES(modelValue.maximum_amount) }}
                </div>
                <input
                    v-if="isAuctionConfigurable"
                    type="number"
                    @keydown="preventNonNumericInput"
                    :value="modelValue.maximum_amount"
                    @input="updateAmount($event.target.value, 'maximum_amount')"
                    placeholder="4,000"
                    class="w-35 rounded-md border border-gray-300 px-2 py-1 pl-7 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                />
            </div>
        </TableCell>
        <TableCell v-if="!isAuctionConfigurable"> {{ modelValue.bids.length }} </TableCell>
        <TableCell class="cursor-pointer">
            <div class="flex">
                {{ modelValue.current_bid < 1 || modelValue.bids.lenght < 1 ? 'None ' : formatKES(modelValue.current_bid) }}
            </div>
            <div v-if="isAuctionConfigurable">
                <div class="mt-4 flex">
                    <div class="mr-2">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="12" cy="12" r="9" />
                            <path d="M12 7v5l3 3" />
                        </svg>
                    </div>
                    {{ lastBidRelativeTime }}
                </div>
            </div>
        </TableCell>
        <TableCell
            :class="
                modelValue.status == 'highest' || modelValue.status == 'active'
                    ? 'text-green-500'
                    : modelValue.status == 'Outbidded'
                      ? 'text-amber-500'
                      : 'text-red-500'
            "
        >
            {{ modelValue.status }}
        </TableCell>
        <TableCell>
            <div class="flex flex-col space-y-2">
                <!-- Amount Input -->

                <div class="relative">
                    <div class="mb-2 w-30 text-center">
                        {{ formatKES(modelValue.lazy_stage_increment) }}
                    </div>
                    <input
                        v-if="isAuctionConfigurable"
                        :class="!isAuctionConfigurable ? 'bg-yellow-900' : ''"
                        type="number"
                        @keydown="preventNonNumericInput"
                        :value="modelValue.lazy_stage_increment"
                        @input="updateAmount($event.target.value, 'lazy_stage_increment')"
                        placeholder="4,000"
                        class="w-35 rounded-md border border-gray-300 px-2 py-1 pl-10 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                    />
                </div>
            </div>
        </TableCell>
        <TableCell>
            <div class="flex flex-col space-y-2">
                <!-- Amount Input -->
                <div class="relative">
                    <div class="mb-2 w-30 text-center">
                        {{ formatKES(modelValue.aggressive_stage_increment) }}
                    </div>
                    <input
                        v-if="isAuctionConfigurable"
                        :class="!isAuctionConfigurable ? 'bg-yellow-900' : ''"
                        type="number"
                        :value="modelValue.aggressive_stage_increment"
                        @input="updateAmount($event.target.value, 'aggressive_stage_increment')"
                        placeholder="4,000"
                        class="w-35 rounded-md border border-gray-300 px-2 py-1 pl-10 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                    />
                </div>
            </div>
        </TableCell>
        <TableCell>
            <div class="flex flex-col space-y-2">
                <!-- Amount Input -->
                <div class="relative">
                    <div class="mb-2 w-30 text-center">
                        {{ formatKES(modelValue.sniping_stage_increment) }}
                    </div>
                    <input
                        v-if="isAuctionConfigurable"
                        :class="!isAuctionConfigurable ? 'bg-yellow-900' : ''"
                        type="number"
                        @keydown="preventNonNumericInput"
                        :value="modelValue.sniping_stage_increment"
                        @input="updateAmount($event.target.value, 'sniping_stage_increment')"
                        placeholder="4,000"
                        class="w-35 rounded-md border border-gray-300 px-2 py-1 pl-10 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                    />
                </div>
            </div>
        </TableCell>
        <TableCell v-if="isAuctionConfigurable">
            <Button class="cursor-pointer" :class="changeMade ? 'bg-green-500' : ''" :disabled="!changeMade" @click="saveChanges">
                <div v-if="!isSaving">Save</div>
                <div class="flex" v-else>
                    <svg class="mr-2 h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        />
                    </svg>
                    <span>Saving</span>
                </div>
            </Button>
            <br />
            <!-- <Button class="my-2 cursor-pointer bg-green-500"> Force Bid </Button> -->
            <br />
            <Button class="cursor-pointer bg-red-500" @click="dropOff(modelValue.id)"> Drop Off </Button>
        </TableCell>
    </TableRow>
    <!-- </div> -->
</template>
