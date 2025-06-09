<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
  striped?: boolean
  isEven?: boolean
  selected?: boolean
}>()
</script>

<template>
  <tr
    :class="cn(
      'border-b transition-colors',
      {
        'bg-muted/50': striped && isEven,
        'hover:bg-muted/50': !selected,
        'data-[state=selected]:bg-muted': selected
      },
      props.class
    )"
    :data-state="selected ? 'selected' : undefined"
  >
    <slot />
  </tr>
</template>