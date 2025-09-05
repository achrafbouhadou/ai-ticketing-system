import { createRouter, createWebHistory } from 'vue-router';

const TicketsList  = () => import('./views/TicketsList.vue');
const TicketDetail = () => import('./views/TicketDetail.vue');
const Dashboard    = () => import('./views/Dashboard.vue');

const routes = [
  { path: '/', redirect: '/tickets' },
  { path: '/tickets', name: 'tickets.list', component: TicketsList },
  { path: '/tickets/:id', name: 'tickets.detail', component: TicketDetail, props: true },
  { path: '/dashboard', name: 'dashboard', component: Dashboard },
];

export default createRouter({ history: createWebHistory(), routes });
