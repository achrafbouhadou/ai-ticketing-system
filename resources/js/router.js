import { createRouter, createWebHistory } from 'vue-router';

const TicketsList = () => import('./views/TicketsList.vue');

const routes = [
  { path: '/', redirect: '/tickets' },
  { path: '/tickets', name: 'tickets.list', component: TicketsList },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
