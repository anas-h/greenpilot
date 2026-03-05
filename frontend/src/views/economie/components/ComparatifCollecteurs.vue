<template>
  <div>
    <v-data-table
      v-if="props.data.headers"
      :headers="props.data.headers"
      :items="props.data.items || []"
      density="compact"
    />
    <p
      v-else
      class="text-center text-grey pa-8"
    >
      Aucune donnee de comparatif
    </p>
  </div>
</template>

<script setup>
const props = defineProps({
  data: { type: Object, default: () => ({}) },
})
</script>
