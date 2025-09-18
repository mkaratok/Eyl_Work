<template>
  <button :class="buttonClass" @click="$emit('click')">
    <slot />
  </button>
</template>

<script setup>
const props = defineProps({
  type: {
    type: String,
    default: 'primary'
  }
})

const buttonClass = computed(() => {
  return props.type === 'secondary' ? 'btn-secondary' : 'btn-primary'
})
</script>