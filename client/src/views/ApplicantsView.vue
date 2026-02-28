<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useApplicants } from '@/composables/useApplicants'

const router = useRouter()
const { isLoading, isError, data } = useApplicants()

function navigateTo(id: string) {
  router.push({ name: 'applicant-detail', params: { id } })
}
</script>

<template>
  <div class="max-w-4xl mx-auto px-4 py-6">
    <!-- Loading skeleton -->
    <div v-if="isLoading" class="space-y-3">
      <div
        v-for="n in 3"
        :key="n"
        class="bg-white border border-gray-200 rounded-lg p-4 animate-pulse"
      >
        <div class="h-3 bg-gray-200 rounded w-1/3 mb-3" />
        <div class="h-5 bg-gray-200 rounded w-2/3 mb-2" />
        <div class="h-3 bg-gray-200 rounded w-1/2" />
      </div>
    </div>

    <!-- Error state -->
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
        Az adatok betöltése sikertelen. Kérjük, próbálja újra később.
      </p>
    </div>

    <!-- Empty state -->
    <div v-else-if="data?.length === 0" class="text-center py-12">
      <svg
        class="mx-auto h-12 w-12 text-gray-300"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        aria-hidden="true"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="1.5"
          d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"
        />
      </svg>
      <p class="text-lg font-medium text-gray-900 mt-4">Nincsenek jelentkezők</p>
      <p class="text-sm text-gray-500 mt-1">
        A rendszerben még nem szerepel egyetlen jelentkező sem.
      </p>
    </div>

    <!-- Applicant list -->
    <div v-else class="space-y-3">
      <div
        v-for="applicant in data"
        :key="applicant.id"
        class="bg-white border border-gray-200 rounded-lg p-4 flex items-center justify-between cursor-pointer hover:bg-gray-50 transition-colors"
        @click="navigateTo(applicant.id)"
      >
        <div>
          <p class="text-xs text-gray-500">
            {{ applicant.program.university }}
            — {{ applicant.program.faculty }}
          </p>
          <p class="text-base font-semibold text-gray-900 mt-0.5">{{ applicant.program.name }}</p>
        </div>
        <span class="text-gray-400 text-xl ml-4 shrink-0" aria-hidden="true">›</span>
      </div>
    </div>
  </div>
</template>
