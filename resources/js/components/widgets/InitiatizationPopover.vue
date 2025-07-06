<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { defineEmits, onMounted, ref } from 'vue';
import { Input } from '../ui/input';
import { Label } from '../ui/label';

import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import axios from 'axios';

const props = defineProps({
    phillips_accounts_emails: {
        type: Array,
        required: true,
        default: () => [],
    },
    auction_id: {
        type: Number,
        required: true,
        default: () => 1,
    },
    auction_status: {
        type: String,
        required: true,
        default: () => 'unconfigured',
    },
});

const emit = defineEmits(['intialization:started']);

const emailsAndPasswords = ref([]);

onMounted(() => {
    // Get props and add to emailsAndPasswords
    emailsAndPasswords.value = props.phillips_accounts_emails.map((email) => ({
        email: email,
        email_app_password: '',
        phillips_account_password: '',
    }));

    console.log(emailsAndPasswords.value);
});

const isOpen = ref();
const initializeAuctionSession = async () => {
    const data = {
        accounts: emailsAndPasswords.value,
        auction_session_id: props.auction_id,
    };
    const response = await axios.post('/api/auction/initialize', data);
    console.log(data);
    emit('initialization:started', response.data);
    console.log(response.data);
    isOpen.value = false;
};
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogTrigger as-child>
            <Button class="mx-4 cursor-pointer bg-green-500 text-white" :disabled="auction_status != 'unconfigurable'">
                Initialize Bidding Session
            </Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-[925px]">
            <DialogHeader>
                <DialogTitle>Phillips Accounts Email </DialogTitle>
                <DialogDescription> Enter Passwords for the phillips accounts you want used in this auction session </DialogDescription>
            </DialogHeader>
            <!-- <div class="md:grid md:gap-2">
                <div class="flex items-center gap-4 md:grid md:grid-cols-11" v-for="(emailsAndPassword, index) in emailsAndPasswords" :key="index">
                    <Label :for="`${index}-${emailsAndPassword}`" class="md:col-span-3">
                        {{ emailsAndPassword.email }}
                    </Label>
                    <Input
                        :id="`phillips_account_password_${index}`"
                        type="text"
                        class="md:col-span-4 h-8"
                        v-model="emailsAndPasswords[index].phillips_account_password"
                        placeholder="Account Password"
                    />
                    <Input
                        :id="`email_app_password_${index}`"
                        type="text"
                        class="md:col-span-4 h-8"
                        v-model="emailsAndPasswords[index].email_app_password"
                        placeholder="Email Password"
                    />
                </div>
            </div> -->

            <div class="space-y-4">
                <div v-for="(emailsAndPassword, index) in emailsAndPasswords" :key="index" class="flex flex-col md:grid md:grid-cols-11 md:gap-2">
                    <!-- Label -->
                    <Label :for="`${index}-${emailsAndPassword}`" class="mb-1 md:col-span-3 md:mb-0">
                        {{ emailsAndPassword.email }}
                    </Label>

                    <!-- Account Password -->
                    <Input
                        :id="`phillips_account_password_${index}`"
                        type="text"
                        class="mb-2 h-8 md:col-span-4 md:mb-0"
                        v-model="emailsAndPasswords[index].phillips_account_password"
                        placeholder="Account Password"
                    />

                    <!-- Email Password -->
                    <Input
                        :id="`email_app_password_${index}`"
                        type="text"
                        class="h-8 md:col-span-4"
                        v-model="emailsAndPasswords[index].email_app_password"
                        placeholder="Email Password"
                    />
                </div>
            </div>

            <DialogFooter>
                <div class="flex justify-center">
                    <Button class="cursor-pointer bg-green-500 text-white" @click="initializeAuctionSession"> Initialize </Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
