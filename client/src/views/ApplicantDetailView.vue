<script setup lang="ts">
import { RouterLink, useRoute } from 'vue-router'

const route = useRoute()
</script>

<template>
  <div class="max-w-4xl mx-auto px-4 py-6">
    <RouterLink to="/applicants" class="text-sm text-blue-600 hover:underline mb-4 inline-block">
      &larr; Vissza
    </RouterLink>
    <h2 class="text-lg font-semibold text-gray-900">Pontozas</h2>
    <p class="text-gray-500 text-sm mt-1">{{ route.params.id }}</p>
    <!-- Phase 4 fills score content here -->
  </div>
</template>
