<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { defineEmits, defineProps, ref } from 'vue';

const props = defineProps({
    stages: {
        type: Object,
        required: true,
        default: () => ({
            lazy_stage: {
                start_time: '',
                end_time: '',
                status: '',
            },
            aggressive_stage: {
                start_time: '',
                end_time: '',
                stage: '',
            },
            sniping_stage: {
                start_time: '',
                end_time: '',
                stage: '',
            },
        }),
        validator: (value) => {
            return ['lazy_stage', 'aggressive_stage', 'sniping_stage'].every((stage) => value[stage]);
        },
    },
    isAuctionConfigurable: {
        type: Boolean,
        required: true,
        default: false,
    },
});
const emit = defineEmits<{
    (e: 'update:time', payload: { stageName: string; field: 'start_time' | 'end_time'; value: string }): void;
    (e: 'save:time', payload: null): void;
}>();

// let isAuctionConfigurable = computed(() => {

// });
const changeMade = ref(false);

function increaseTimeByOneSecond(timeString: string) {
    const parts = timeString.split(':');
    let h = Number(parts[0]);
    let m = Number(parts[1]);
    let s = parts.length > 2 ? Number(parts[2]) : 0;

    s += 1;

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

    return [String(h).padStart(2, '0'), String(m).padStart(2, '0'), String(s).padStart(2, '0')].join(':');
}

const updateTime = (value: string, nestedPath: string) => {
    const toSeconds = (timeStr: string) => {
        const [h, m, s = 0] = timeStr.split(':').map(Number);
        return h * 3600 + m * 60 + s;
    };

    let isValid = true;

    // Validation logic remains exactly the same
    if (nestedPath === 'lazy_stage.start_time') {
        const timeSec = toSeconds(value);
        if (timeSec < toSeconds('10:00:00') || timeSec > toSeconds('12:30:00')) {
            isValid = false;
            alert('Lazy stage must end between 11:15 AM and 12:45 PM');
            if (timeSec < toSeconds('10:00:00')) {
                value = '11:15:00';
                isValid = true;
            } else if (timeSec > toSeconds('12:30:00')) {
                value = '12:30:00';
                isValid = true;
            }
        }
    }

    if (nestedPath === 'lazy_stage.end_time') {
        const timeSec = toSeconds(value);
        if (timeSec < toSeconds('11:15:00') || timeSec > toSeconds('12:45:00')) {
            isValid = false;
            alert('Lazy stage must end between 11:15 AM and 12:45 PM');
            if (timeSec < toSeconds('11:15:00')) {
                value = '11:15:00';
                isValid = true;
            } else if (timeSec > toSeconds('12:45:00')) {
                value = '12:45:00';
                isValid = true;
            }
        }
    }

    if (nestedPath === 'aggressive_stage.end_time' && toSeconds(value) > toSeconds('12:57:00')) {
        isValid = false;
        alert('Aggressive stage cannot end after 12:57 PM');
        value = '12:57:00';
        isValid = true;
    }

    if (!isValid) return;

    changeMade.value = true;

    // Extract stage name and field from nestedPath
    const [stageType, field] = nestedPath.split('.');
    const stageName = stageType.replace('_stage', '');

    emit('update:time', {
        stageName,
        field: field as 'start_time' | 'end_time',
        value,
    });

    // Handle automatic time adjustments
    setTimeout(() => {
        switch (nestedPath) {
            case 'lazy_stage.end_time':
                emit('update:time', {
                    stageName: 'aggressive',
                    field: 'start_time',
                    value: increaseTimeByOneSecond(value),
                });
                break;
            case 'aggressive_stage.end_time':
                emit('update:time', {
                    stageName: 'sniping',
                    field: 'start_time',
                    value: increaseTimeByOneSecond(value),
                });
                break;
            default:
                break;
        }
    }, 0);
};

const saveTime = () => {
    emit('save:time');
    console.log('Should emit');
};
</script>
<template>
    <div>
        <Table class="hidden md:block">
            <TableCaption>
                <Button class="mx-4 cursor-pointer bg-green-500 text-white" :disabled="!changeMade" @click="saveTime"> Save </Button>
            </TableCaption>
            <TableHeader>
                <TableRow>
                    <TableHead>Lazy Stage</TableHead>
                    <TableHead>Aggressive Stage</TableHead>
                    <TableHead>Sniping Stage</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow>
                    <TableCell>
                        <div class="flex flex-col space-y-2">
                            <!-- Lazy Stage - Start Time -->
                            <div class="relative">
                                <input
                                    :disabled="!isAuctionConfigurable"
                                    :class="!isAuctionConfigurable ? 'bg-yellow-900' : ''"
                                    type="time"
                                    :value="stages.lazy_stage.start_time"
                                    @input="updateTime($event.target.value, 'lazy_stage.start_time')"
                                    @focus="$event.target.showPicker()"
                                    class="w-35 rounded-md border border-gray-300 px-2 py-1 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                />
                            </div>

                            <!-- Lazy Stage - End Time -->
                            <div class="relative">
                                <input
                                    disabled
                                    type="time"
                                    :value="stages.lazy_stage.end_time"
                                    @input="updateTime($event.target.value, 'lazy_stage.end_time')"
                                    @focus="$event.target.showPicker()"
                                    class="w-35 rounded-md border border-gray-300 bg-yellow-900 px-2 py-1 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                />
                            </div>
                        </div>
                    </TableCell>
                    <TableCell>
                        <div class="flex flex-col space-y-2">
                            <!-- Aggressive Stage - Start Time -->
                            <div class="relative">
                                <input
                                    type="time"
                                    :value="stages.aggressive_stage.start_time"
                                    @input="updateTime($event.target.value, 'aggressive_stage.start_time')"
                                    @focus="$event.target.showPicker()"
                                    disabled
                                    class="w-35 rounded-md border border-gray-300 bg-yellow-900 px-2 py-1 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                />
                            </div>

                            <!-- Aggressive Stage - End Time -->
                            <div class="relative">
                                <input
                                    type="time"
                                    :value="stages.aggressive_stage.end_time"
                                    disabled
                                    class="w-35 rounded-md border border-gray-300 bg-yellow-900 px-2 py-1 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                />
                            </div>
                        </div>
                    </TableCell>
                    <TableCell>
                        <div class="flex flex-col space-y-2">
                            <!-- Sniping Stage - Start Time -->
                            <div class="relative">
                                <input
                                    type="time"
                                    :value="stages.sniping_stage.start_time"
                                    @input="updateTime($event.target.value, 'sniping_stage.start_time')"
                                    disabled
                                    @focus="$event.target.showPicker()"
                                    class="w-35 rounded-md border border-gray-300 bg-yellow-900 px-2 py-1 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                />
                            </div>

                            <!-- Sniping Stage - End Time -->
                            <div class="relative">
                                <input
                                    type="time"
                                    :value="stages.sniping_stage.end_time"
                                    @input="updateTime($event.target.value, 'sniping_stage.end_time')"
                                    @focus="$event.target.showPicker()"
                                    disabled
                                    class="w-35 rounded-md border border-gray-300 bg-yellow-900 px-2 py-1 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                />
                            </div>
                        </div>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
        <Card class="block md:hidden">
            <CardHeader>
                <CardTitle>Bid Stages </CardTitle>
                <CardDescription>Configure Bid Stage Times.</CardDescription>
            </CardHeader>
            <CardContent>

                <CardDescription>Lazy Stage</CardDescription>
                <div class="flex flex-row space-x-2">
                    <!-- Lazy Stage - Start Time -->
                    <div class="relative">
                        <input
                            :disabled="!isAuctionConfigurable"
                            :class="!isAuctionConfigurable ? 'bg-yellow-900' : ''"
                            type="time"
                            :value="stages.lazy_stage.start_time"
                            @input="updateTime($event.target.value, 'lazy_stage.start_time')"
                            @focus="$event.target.showPicker()"
                            class="w-35 rounded-md border border-gray-300 px-2 py-1 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                        />
                    </div>

                    <!-- Lazy Stage - End Time -->
                    <div class="relative">
                        <input
                            disabled
                            type="time"
                            :value="stages.lazy_stage.end_time"
                            @input="updateTime($event.target.value, 'lazy_stage.end_time')"
                            @focus="$event.target.showPicker()"
                            class="w-35 rounded-md border border-gray-300 bg-yellow-900 px-2 py-1 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                </div>

                <CardDescription class="mt-3">Aggressive Stage</CardDescription>
                <div class="flex flex-row space-x-2">
                    <!-- Aggressive Stage - Start Time -->
                    <div class="relative">
                        <input
                            type="time"
                            :value="stages.aggressive_stage.start_time"
                            @input="updateTime($event.target.value, 'aggressive_stage.start_time')"
                            @focus="$event.target.showPicker()"
                            disabled
                            class="w-35 rounded-md border border-gray-300 bg-yellow-900 px-2 py-1 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                        />
                    </div>

                    <!-- Aggressive Stage - End Time -->
                    <div class="relative">
                        <input
                            type="time"
                            :value="stages.aggressive_stage.end_time"
                            disabled
                            class="w-35 rounded-md border border-gray-300 bg-yellow-900 px-2 py-1 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                </div>

                <CardDescription class="mt-3">Sniping Stage</CardDescription>
                <div class="flex flex-row space-x-2">
                    <!-- Sniping Stage - Start Time -->
                    <div class="relative">
                        <input
                            type="time"
                            :value="stages.sniping_stage.start_time"
                            @input="updateTime($event.target.value, 'sniping_stage.start_time')"
                            disabled
                            @focus="$event.target.showPicker()"
                            class="w-35 rounded-md border border-gray-300 bg-yellow-900 px-2 py-1 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                        />
                    </div>

                    <!-- Sniping Stage - End Time -->
                    <div class="relative">
                        <input
                            type="time"
                            :value="stages.sniping_stage.end_time"
                            @input="updateTime($event.target.value, 'sniping_stage.end_time')"
                            @focus="$event.target.showPicker()"
                            disabled
                            class="w-35 rounded-md border border-gray-300 bg-yellow-900 px-2 py-1 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                </div>
            </CardContent>
            <CardFooter class="flex justify-center px-6 pb-2 pt-3">

                <Button class="mx-4 cursor-pointer bg-green-500 text-white" :disabled="!changeMade" @click="saveTime"> Save </Button>
            </CardFooter>
        </Card>
    </div>
</template>
