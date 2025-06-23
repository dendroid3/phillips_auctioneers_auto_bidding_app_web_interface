<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { onMounted, ref, defineEmits } from 'vue';
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
});

const emit = defineEmits(['intialization:started'])

const emailsAndPasswords = ref([]);

onMounted(() => {
    // Get props and add to emailsAndPasswords
    emailsAndPasswords.value = props.phillips_accounts_emails.map((email) => ({
        email: email,
        password: '',
    }));

    console.log(emailsAndPasswords.value);
});

const isOpen = ref();
const initializeAuctionSession = async () => {
    const response = await axios.post('/api/auction/initialize', emailsAndPasswords.value);
    emit('initialization:started',  response.data)
    console.log(response.data);
    isOpen.value = false
};


</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogTrigger as-child>
            <Button class="mx-4 cursor-pointer bg-green-500 text-white"> Initialize Bidding Session </Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>Phillips Accounts Emails</DialogTitle>
                <DialogDescription> Enter Passwords for the phillips accounts you want used in this auction session </DialogDescription>
            </DialogHeader>
            <div class="grid gap-2">
                <div class="flex grid grid-cols-10 items-center gap-4" v-for="(emailsAndPassword, index) in emailsAndPasswords" :key="index">
                    <Label :for="`${index}-${emailsAndPassword}`" class="col-span-5">
                        {{ emailsAndPassword.email }}
                    </Label>
                    <Input :id="`${index}-${emailsAndPassword}`" type="text" class="col-span-4 h-8" v-model="emailsAndPasswords[index].password" />
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
