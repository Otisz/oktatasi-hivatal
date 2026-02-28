<script setup lang="ts">
import { useQueryClient } from '@tanstack/vue-query'
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useApplicantScore } from '@/composables/useApplicantScore'
import type { Applicant } from '@/types/api'

const route = useRoute()
const queryClient = useQueryClient()

const applicant = computed(() => {
  const cached = queryClient.getQueryData<Applicant[]>(['applicants'])
  return cached?.find((a) => a.id === (route.params.id as string))
})

const { isLoading, isError, error, data, refetch } = useApplicantScore(
  () => route.params.id as string,
)
</script>

<template>
  <div class="max-w-4xl mx-auto px-4 py-6">
    <!-- Back link — always visible above all states -->
    <RouterLink to="/applicants" class="text-sm text-blue-600 hover:underline mb-4 inline-block">
      &larr; Vissza
    </RouterLink>

    <!-- Programme context header — visible when cache is warm -->
    <div v-if="applicant" class="mb-6">
      <p class="text-xs text-gray-500">
        {{ applicant.program.university }}
        — {{ applicant.program.faculty }}
      </p>
      <h2 class="text-lg font-semibold text-gray-900 mt-0.5">{{ applicant.program.name }}</h2>
    </div>
    <h2 v-else class="text-lg font-semibold text-gray-900 mb-6">Pontozás</h2>

    <!-- Branch 1: Loading skeleton -->
    <div v-if="isLoading" class="animate-pulse">
      <div class="bg-white border border-gray-200 rounded-lg p-8 mb-4 flex flex-col items-center">
        <div class="h-4 bg-gray-200 rounded w-32 mb-4" />
        <div class="h-16 bg-gray-200 rounded w-24" />
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div class="bg-white border border-gray-200 rounded-lg p-4">
          <div class="h-3 bg-gray-200 rounded w-20 mb-2" />
          <div class="h-8 bg-gray-200 rounded w-16" />
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4">
          <div class="h-3 bg-gray-200 rounded w-20 mb-2" />
          <div class="h-8 bg-gray-200 rounded w-16" />
        </div>
      </div>
    </div>

    <!-- Branch 2: 422 domain error — amber card (MUST come before generic error branch) -->
    <div
      v-else-if="isError && error?.kind === 'domain'"
      class="bg-amber-50 border border-amber-200 rounded-lg p-6"
    >
      <h3 class="text-base font-semibold text-amber-900 mb-2">Pontozás nem lehetséges</h3>
      <p class="text-sm text-amber-800">{{ error.message }}</p>
    </div>

    <!-- Branch 3: Generic error with retry -->
    <div v-else-if="isError" class="text-center py-12">
      <svg
        class="h-12 w-12 text-gray-300 mx-auto"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        aria-hidden="true"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="1.5"
          d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"
        />
      </svg>
      <p class="text-lg font-medium text-gray-900 mt-4">Hiba történt</p>
      <p class="text-sm text-gray-500 mt-1">
        A pontozás betöltése sikertelen. Kérjük, próbálja újra.
      </p>
      <button type="button" class="mt-4 text-sm text-blue-600 hover:underline" @click="refetch()">
        Próbálja újra
      </button>
    </div>

    <!-- Branch 4: Score breakdown — success state -->
    <div v-else-if="data">
      <div class="bg-white border border-gray-200 rounded-lg p-8 mb-4 text-center">
        <p class="text-sm text-gray-500 mb-2">Összpontszám</p>
        <p class="text-5xl font-bold text-gray-900">{{ data.osszpontszam }}</p>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div class="bg-white border border-gray-200 rounded-lg p-4 text-center">
          <p class="text-xs text-gray-500 mb-1">Alappont</p>
          <p class="text-2xl font-semibold text-gray-900">{{ data.alappont }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4 text-center">
          <p class="text-xs text-gray-500 mb-1">Többletpont</p>
          <p class="text-2xl font-semibold text-gray-900">{{ data.tobbletpont }}</p>
        </div>
      </div>
    </div>
  </div>
</template>
