<template>
  <section class="ticket-list">
    <div class="ticket-list__toolbar">
    <input v-model="filters.q" @keyup.enter="load()" class="ticket-list__search" placeholder="Search subject or body..." />

    <select v-model="filters.status" @change="load()" class="ticket-list__select">
        <option value="">All statuses</option>
        <option value="open">Open</option>
        <option value="in_progress">In progress</option>
        <option value="resolved">Resolved</option>
    </select>

    <select v-model="filters.category" @change="load()" class="ticket-list__select">
        <option value="">All categories</option>
        <option value="billing">Billing</option>
        <option value="technical">Technical</option>
        <option value="account">Account</option>
        <option value="other">Other</option>
    </select>

    <select v-model="filters.has_note" @change="load()" class="ticket-list__select" style="max-width:140px">
        <option :value="''">Any note</option>
        <option :value="'1'">Has note</option>
        <option :value="'0'">No note</option>
    </select>

    <button @click="load()" class="ticket-list__btn">Filter</button>
    <button @click="exportCsv" class="ticket-list__btn">Export CSV</button>
    </div>


    <form class="ticket-list__create" @submit.prevent="createTicket">
      <input v-model="create.subject" placeholder="Subject" required class="ticket-list__input" />
      <textarea v-model="create.body" placeholder="Body" required class="ticket-list__textarea"></textarea>
      <button class="ticket-list__btn">Create ticket</button>
    </form>

    <div v-if="loading" class="ticket-list__loading">Loading…</div>
    <div v-else>
      <table class="ticket-list__table" v-if="tickets.length">
        <thead>
          <tr>
            <th>Subject</th>
            <th>Status</th>
            <th>Category</th>
            <th>Confidence</th>
            <th>Created</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="t in tickets" :key="t.id">
            <router-link :to="`/tickets/${t.id}`">{{ t.subject }}</router-link>
            <td>{{ t.status || '—' }}</td>
            <td>{{ t.category || '—' }}</td>
            <td>{{ t.confidence ?? '—' }}</td>
            <td>{{ new Date(t.created_at).toLocaleString() }}</td>
          </tr>
        </tbody>
      </table>

      <p v-else class="ticket-list__empty">No tickets yet.</p>

      <div class="ticket-list__pager" v-if="meta">
        <button :disabled="!links.prev" @click="go(meta.current_page - 1)">Prev</button>
        <span>Page {{ meta.current_page }} / {{ meta.last_page }}</span>
        <button :disabled="!links.next" @click="go(meta.current_page + 1)">Next</button>
      </div>
    </div>
  </section>
</template>

<script>
import api from '../services/api';

export default {
  name: 'TicketsList',
  data() {
    return {
      loading: false,
      tickets: [],
      meta: null,
      links: {},
      filters: { q: '', status: '', category: '', has_note: '', per_page: 10 },
      create: { subject: '', body: '' },
    };
  },
  created() { this.load(); },
  methods: {
    async load(page = 1) {
      this.loading = true;
      try {
        const { data } = await api.get('/tickets', {
          params: { ...this.filters, page }
        });
        this.tickets = data.data || [];
        this.meta = data.meta || null;
        this.links = data.links || {};
      } finally {
        this.loading = false;
      }
    },
    go(page) { this.load(page); },
    async createTicket() {
      if (!this.create.subject || !this.create.body) return;
      await api.post('/tickets', this.create);
      this.create.subject = '';
      this.create.body = '';
      this.load(1);
    },
    exportCsv() {
      const params = new URLSearchParams();
      if (this.filters.q)         params.set('q', this.filters.q);
      if (this.filters.status)    params.set('status', this.filters.status);
      if (this.filters.category)  params.set('category', this.filters.category);
      if (this.filters.has_note !== '') params.set('has_note', this.filters.has_note);
      const url = `/api/tickets/export?${params.toString()}`;
      window.open(url, '_blank');
    },
  }
};
</script>
