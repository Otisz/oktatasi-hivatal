import { VueQueryPlugin } from '@tanstack/vue-query'
import { createApp } from 'vue'
import { queryClient } from '@/lib/query'
import App from './App.vue'
import './assets/main.css'

createApp(App).use(VueQueryPlugin, { queryClient }).mount('#app')
