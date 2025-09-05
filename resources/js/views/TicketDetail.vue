<template>
  <section class="ticket-detail">
    <div v-if="loading" class="ticket-detail__card card">
        <div class="skeleton skeleton--title"></div>
        <div class="skeleton skeleton--block" style="height:60px;margin-top:10px;"></div>

        <div class="ticket-detail__meta" style="margin-top:16px;">
            <div class="skeleton skeleton--w30"></div>
            <div class="skeleton skeleton--w30"></div>
            <div class="skeleton skeleton--w20"></div>
            <div class="skeleton skeleton--w50"></div>
        </div>

        <div class="skeleton skeleton--w40" style="height:36px;margin-top:16px;"></div>
        <div class="skeleton skeleton--block" style="height:80px;margin-top:8px;"></div>
        <div class="skeleton skeleton--w25" style="height:36px;margin-top:12px;"></div>
    </div>

    <div v-else-if="ticket" class="ticket-detail__card card">
      <h2 class="ticket-detail__title">{{ ticket.subject }}</h2>

      <p class="ticket-detail__body">{{ ticket.body }}</p>

      <div class="ticket-detail__meta">
        <div>
          <strong>Status:</strong>
          <span v-if="ticket.status" :class="['badge','badge--status','badge--status-'+ticket.status]">{{ labelStatus(ticket.status) }}</span>
          <span v-else>—</span>
        </div>
        <div>
          <strong>Category:</strong>
          <span v-if="ticket.category" :class="['badge','badge--category','badge--category-'+ticket.category]" :title="ticket.explanation || ''">{{ labelCategory(ticket.category) }}</span>
          <span v-else class="badge badge--category badge--category-unclassified" :title="ticket.explanation || ''">Unclassified</span>
        </div>
        <div><strong>Confidence:</strong> {{ formatConfidence(ticket.confidence) }}</div>
        <div v-if="ticket.explanation"><strong>Explanation:</strong> {{ ticket.explanation }}</div>
      </div>

      <form class="ticket-detail__form" @submit.prevent="save">
        <label class="ticket-detail__label">Status</label>
        <select v-model="form.status" class="ticket-detail__input select">
          <option value="">(no change)</option>
          <option value="open">Open</option>
          <option value="in_progress">In progress</option>
          <option value="resolved">Resolved</option>
        </select>

        <label class="ticket-detail__label">Category</label>
        <select v-model="form.category" class="ticket-detail__input select">
          <option :value="null">(clear)</option>
          <option value="billing">Billing</option>
          <option value="technical">Technical</option>
          <option value="account">Account</option>
          <option value="other">Other</option>
        </select>

        <label class="ticket-detail__label">Note</label>
        <textarea
          v-model="form.note"
          class="ticket-detail__textarea textarea"
          placeholder="Internal note (optional)"
        ></textarea>

        <div class="ticket-detail__actions">
          <button type="submit" class="btn btn--primary" :disabled="saving">
            {{ saving ? 'Saving…' : 'Save changes' }}
          </button>
          <button type="button"
                    class="btn"
                    :disabled="classifying"
                    @click="classify">
            {{ classifying ? 'Classifying…' : 'Run Classification' }}
        </button>
          <router-link class="link" to="/tickets">Back to list</router-link>
        </div>
      </form>
    </div>

    <p v-else class="ticket-detail__empty">Ticket not found.</p>
  </section>
</template>

<script>
import api from '../services/api';

export default {
  name: 'TicketDetail',
  props: { id: { type: String, required: true } },
  data() {
    return {
      loading: true,
      saving: false,
      classifying: false,
      ticket: null,
      form: { status: '', category: null, note: '' },
    };
  },
  async created() {
    await this.load();
  },
  methods: {
    labelStatus(k) {
      const map = { open: 'Open', in_progress: 'In progress', resolved: 'Resolved' };
      return map[k] ?? k;
    },
    labelCategory(k) {
      const map = { billing: 'Billing', technical: 'Technical', account: 'Account', other: 'Other', unclassified: 'Unclassified' };
      return map[k] ?? k;
    },
    formatConfidence(c) {
      if (c === null || c === undefined) return '—';
      const n = Number(c);
      if (Number.isNaN(n)) return '—';
      return n.toFixed(2);
    },
    async load() {
      this.loading = true;
      try {
        const { data } = await api.get(`/tickets/${this.id}`);
        this.ticket = data.data;
        this.form.status   = '';
        this.form.category = this.ticket.category; 
        this.form.note     = this.ticket.note ?? '';
      } finally {
        this.loading = false;
      }
    },
    async save() {
      this.saving = true;
      try {
        const payload = {};
        if (this.form.status) payload.status = this.form.status;
        if (this.form.category !== this.ticket.category) {
          payload.category = this.form.category;
        }
        if ((this.form.note || '') !== (this.ticket.note || '')) {
          payload.note = this.form.note || null;
        }

        if (Object.keys(payload).length === 0) return; 

        const { data } = await api.patch(`/tickets/${this.id}`, payload);
        this.ticket = data.data;
        this.form.status = '';
        this.form.category = this.ticket.category;
        this.form.note = this.ticket.note ?? '';
      } finally {
        this.saving = false;
      }
    },

    async classify() {
      this.classifying = true;
      try {
        await api.post(`/tickets/${this.id}/classify`);
        setTimeout(() => this.load(), 1200);
      } finally {
        this.classifying = false;
      }
    }
  },
};
</script>

<style>
.ticket-detail__title{margin:0 0 6px 0}
.ticket-detail__body{white-space:pre-wrap;margin:8px 0 16px 0}
.ticket-detail__meta{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:16px}
.ticket-detail__form{display:grid;gap:8px}
.ticket-detail__label{font-weight:600}
.ticket-detail__actions{display:flex;gap:12px;align-items:center;margin-top:8px}
.ticket-detail__empty{color:var(--muted)}
</style>
