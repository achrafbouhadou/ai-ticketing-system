import { createApp } from 'vue';
import router from './router';
import '../css/app.css';

import App from './components/App.vue';

createApp(App).use(router).mount('#app');
