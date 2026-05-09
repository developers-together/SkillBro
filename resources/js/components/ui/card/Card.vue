<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <div
    data-slot="card"
    :class="
      cn(
        'bg-card text-card-foreground flex flex-col gap-6 rounded-xl border py-6 shadow-[0_8px_8px_rgba(0,0,0,0.10),0_4px_4px_rgba(0,0,0,0.10),0_2px_2px_rgba(0,0,0,0.10),0_0_0_1px_rgba(0,0,0,0.10)] dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.04),0_1px_2px_rgba(255,255,255,0.05)]',
        props.class,
      )
    "
  >
    <slot />
  </div>
</template>
