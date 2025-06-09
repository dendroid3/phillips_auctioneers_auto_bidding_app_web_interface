<script setup lang="ts">
    import {
        Table,
        TableBody,
        TableCaption,
        TableHead,
        TableHeader,
        TableRow,
        TableCell
    } from '@/components/ui/table'
</script>
<template>
    <div class="rounded-xl mt-4 border border-sidebar-border/70 dark:border-sidebar-border flex-1">
    <Table>
        <TableCaption>Bid History on this Vehicle</TableCaption>
        <TableHeader class="h-20 relative top-0 right-0 left-0">
            <TableRow>
                <TableHead>Name/ID</TableHead>
                <TableHead>Time</TableHead>
                <TableHead>Account</TableHead>
                <TableHead>Stage</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Amount</TableHead>
            </TableRow>
        </TableHeader>

        <TableBody>
            <TableRow v-for="(history, index) in 100" :key="index">
                <TableCell class="border-l-4 border-green-500">KCV 123E - Nissan Note</TableCell>
                <TableCell>5 seconds ago</TableCell>
                <TableCell>ernest95@gmail.com</TableCell>
                <TableCell>Lazy Stage</TableCell>
                <TableCell>Highest</TableCell>
                <TableCell>350,000</TableCell>
            </TableRow>
        </TableBody>
    </Table>
    </div>
</template>