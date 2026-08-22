import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import Blank from './layout/blank-layout.vue'
import Default from './layout/default-layout.vue'
import Admin from './layout/admin-layout.vue'
import { Toaster } from '@meforma/vue-toaster'

import './assets/css/main.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.use(Toaster, {
  position: 'top-right',
  duration: 3000,
})
app.component('blank-layout', Blank)
app.component('default-layout', Default)
app.component('admin-layout', Admin)

app.mount('#app')
