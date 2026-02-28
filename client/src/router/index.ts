import { createRouter, createWebHistory } from 'vue-router'
import { isNavigating } from '@/composables/useProgress'
import ApplicantDetailView from '@/views/ApplicantDetailView.vue'
import ApplicantsView from '@/views/ApplicantsView.vue'

export const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      redirect: { name: 'applicants' },
    },
    {
      path: '/applicants',
      name: 'applicants',
      component: ApplicantsView,
    },
    {
      path: '/applicants/:id',
      name: 'applicant-detail',
      component: ApplicantDetailView,
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: { name: 'applicants' },
    },
  ],
})

router.beforeEach(() => {
  isNavigating.value = true
})

router.afterEach(() => {
  isNavigating.value = false
})
