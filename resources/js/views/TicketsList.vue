<template>
  <section class="ticket-list">
    <div class="ticket-list__toolbar">
    <input
        v-model="filters.q"
        @keyup.enter="load(1)"
        class="ticket-list__search"
        placeholder="Search subject or body..."
    />

    <select v-model="filters.status" @change="load(1)" class="ticket-list__select">
        <option value="">All statuses</option>
        <option value="open">Open</option>
        <option value="in_progress">In progress</option>
        <option value="resolved">Resolved</option>
    </select>

    <select v-model="filters.category" @change="load(1)" class="ticket-list__select">
        <option value="">All categories</option>
        <option value="billing">Billing</option>
        <option value="technical">Technical</option>
        <option value="account">Account</option>
        <option value="other">Other</option>
    </select>

    <select v-model="filters.has_note" @change="load(1)" class="ticket-list__select" style="max-width:140px">
        <option :value="''">Any note</option>
        <option :value="'1'">Has note</option>
        <option :value="'0'">No note</option>
    </select>

    <select v-model.number="filters.per_page" @change="load(1)" class="ticket-list__select" style="max-width:120px">
        <option :value="5">5 / page</option>
        <option :value="10">10 / page</option>
        <option :value="20">20 / page</option>
        <option :value="50">50 / page</option>
        <option :value="100">100 / page</option>
    </select>

    <button @click="load(1)" class="ticket-list__btn">Filter</button>
    <button @click="exportCsv" class="ticket-list__btn" :disabled="exporting">
        {{ exporting ? 'Preparing…' : 'Export CSV' }}
    </button>
    </div>



    <form class="ticket-list__create" @submit.prevent="createTicket">
      <input v-model="create.subject" placeholder="Subject" required class="ticket-list__input" />
      <textarea v-model="create.body" placeholder="Body" required class="ticket-list__textarea"></textarea>
      <button class="ticket-list__btn">Create ticket</button>
    </form>

    <div v-if="loading" class="ticket-list__skeleton">
    <div class="ticket-list__table ticket-list__table--skeleton">
        <div class="skeleton-row" v-for="n in Math.min(filters.per_page, 10)" :key="n">
        <span class="skeleton skeleton--w40"></span>
        <span class="skeleton skeleton--w12"></span>
        <span class="skeleton skeleton--w16"></span>
        <span class="skeleton skeleton--w10"></span>
        <span class="skeleton skeleton--w22"></span>
        </div>
    </div>
    </div>
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
            <td><router-link :to="`/tickets/${t.id}`">{{ t.subject }}</router-link></td>
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
      exporting: false,
      exportJobId: null,
      tickets: [],
      meta: null,
      links: {},
      filters: { q: '', status: '', category: '', has_note: '', per_page: 10 },
      create: { subject: '', body: '' },
    };
  },
  created() {
    const stored = Number(localStorage.getItem('per_page'));
    if ([5,10,20,50,100].includes(stored)) this.filters.per_page = stored;
    this.load();
  },
  watch: {
    'filters.per_page'(val) {
      localStorage.setItem('per_page', String(val));
    }
  },
  methods: {
    async load(page = 1) {
      this.loading = true;
      try {
        const params = { ...this.filters, page };
        const { data } = await api.get('/tickets', { params });
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

    async exportCsv() {
      if (this.exporting) return;
      this.exporting = true;
      try {
        const { data } = await api.post('/tickets/export', {
          q: this.filters.q || undefined,
          status: this.filters.status || undefined,
          category: this.filters.category || undefined,
          has_note: this.filters.has_note !== '' ? this.filters.has_note : undefined,
        });
        this.exportJobId = data.export_id;
        await this.pollExportStatus(this.exportJobId);
      } finally {
        this.exporting = false;
      }
    },

    async pollExportStatus(id) {
      const start = Date.now(), maxMs = 60_000;
      while (Date.now() - start < maxMs) {
        const { data } = await api.get(`/tickets/export/${id}`);
        if (data.status === 'done' && data.download_url) {
          window.open(data.download_url, '_blank'); return;
        }
        if (data.status === 'failed') { alert('Export failed: ' + (data.error || 'unknown')); return; }
        await new Promise(r => setTimeout(r, 1500));
      }
      alert('Export is taking longer than expected. Try again.');
    },
  }
};
</script>

