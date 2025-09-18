<template>
  <div class="sr-only" aria-live="polite" aria-atomic="true">
    {{ message }}
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const message = ref('')

// Function to announce messages to screen readers
const announce = (text) => {
  message.value = text
  // Clear the message after a short delay to allow screen readers to announce it
  setTimeout(() => {
    message.value = ''
  }, 1000)
}

// Make the announce function globally available
onMounted(() => {
  window.announceToScreenReader = announce
})

defineExpose({ announce })
</script>

<style scoped>
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
</style>