<script setup lang="ts">
import { RouterView } from 'vue-router'
import { isNavigating } from '@/composables/useProgress'
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <div
      v-if="isNavigating"
      class="fixed top-0 left-0 h-0.5 bg-blue-500 animate-pulse w-full z-50"
    />
    <header class="bg-white border-b border-gray-200">
      <div class="max-w-4xl mx-auto px-4 py-4">
        <h1 class="text-xl font-bold text-gray-900">Oktatasi Hivatal</h1>
        <p class="text-sm text-gray-500">Felveteli pontszamolo</p>
      </div>
    </header>
    <main><RouterView /></main>
  </div>
</template>
