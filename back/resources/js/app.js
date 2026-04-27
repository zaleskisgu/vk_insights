import { createApp } from 'vue';
import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';
import App from '@/App.vue';
import { primeVueRu } from '@/locales/primevue.ru.js';

import 'primeicons/primeicons.css';

createApp(App)
    .use(PrimeVue, {
        ripple: true,
        locale: primeVueRu,
        theme: {
            preset: Aura,
            options: {
                darkModeSelector: 'html.dark',
                cssLayer: false,
            },
        },
    })
    .mount('#app');
