<script setup lang="ts">
import { DefineProps } from 'vue';
import { Button } from '@/components/ui/button'
import { Checkbox } from '../ui/checkbox';
import { Label } from '../ui/label';
import { Input } from '../ui/input';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'

const props = defineProps({
  phillips_accounts_emails: {
    type: Array,
    required: true,
    default: () => ([])
  }
})
</script>

<template>
  <Dialog>
    <DialogTrigger as-child>
     <Button class="mx-4 cursor-pointer bg-green-500 text-white">
      Initialize Bidding Session
      </Button>
    </DialogTrigger>
    <DialogContent class="sm:max-w-[425px]">
      <DialogHeader>
        <DialogTitle>Phillips Accounts Emails</DialogTitle>
        <DialogDescription>
          Enter Passwords for the phillips accounts you want used in this auction session
        </DialogDescription>
      </DialogHeader>
      <div class="grid gap-2">
          <div class="grid grid-cols-10 items-center gap-4 flex" v-for="(phillips_accounts_email, index) in phillips_accounts_emails" :key="index">
            <Checkbox :id="`${phillips_accounts_email}-${index}`"/>
            <Label :for="`${index}-${phillips_accounts_email}`" class="col-span-5">
              {{ phillips_accounts_email }}
            </Label>
            <Input
              :id="`${index}-${phillips_accounts_emails}`"
              type="text"
              
              class="col-span-4 h-8"
            />
          </div>
        </div>
      <DialogFooter>
        <div class="flex justify-center">
          <Button class="cursor-pointer bg-green-500 text-white">
            Initialize
          </Button>
        </div>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
