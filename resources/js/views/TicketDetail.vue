<template>
  <section class="ticket-detail">
    <div v-if="loading" class="ticket-detail__loading">Loading…</div>

    <div v-else-if="ticket" class="ticket-detail__card">
      <h2 class="ticket-detail__title">{{ ticket.subject }}</h2>

      <p class="ticket-detail__body">{{ ticket.body }}</p>

      <div class="ticket-detail__meta">
        <div><strong>Current status:</strong> {{ ticket.status || '—' }}</div>
        <div><strong>Current category:</strong> {{ ticket.category || '—' }}</div>
        <div><strong>Confidence:</strong> {{ ticket.confidence ?? '—' }}</div>
        <div v-if="ticket.explanation"><strong>Explanation:</strong> {{ ticket.explanation }}</div>
      </div>

      <form class="ticket-detail__form" @submit.prevent="save">
        <label class="ticket-detail__label">Status</label>
        <select v-model="form.status" class="ticket-detail__input">
          <option value="">(no change)</option>
          <option value="open">Open</option>
          <option value="in_progress">In progress</option>
          <option value="resolved">Resolved</option>
        </select>

        <label class="ticket-detail__label">Category</label>
        <select v-model="form.category" class="ticket-detail__input">
          <option :value="null">(clear)</option>
          <option value="billing">Billing</option>
          <option value="technical">Technical</option>
          <option value="account">Account</option>
          <option value="other">Other</option>
        </select>

        <label class="ticket-detail__label">Note</label>
        <textarea
          v-model="form.note"
          class="ticket-detail__textarea"
          placeholder="Internal note (optional)"
        ></textarea>

        <div class="ticket-detail__actions">
          <button type="submit" class="ticket-detail__btn" :disabled="saving">
            {{ saving ? 'Saving…' : 'Save changes' }}
          </button>
          <button type="button"
                    class="ticket-detail__btn"
                    :disabled="classifying"
                    @click="classify">
            {{ classifying ? 'Classifying…' : 'Run Classification' }}
        </button>
          <router-link class="ticket-detail__link" to="/tickets">Back to list</router-link>
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
.ticket-detail__loading{color:#666}
.ticket-detail__card{border:1px solid #eee;border-radius:12px;padding:16px;background:#fafafa}
.ticket-detail__title{margin:0 0 6px 0}
.ticket-detail__body{white-space:pre-wrap;margin:8px 0 16px 0}
.ticket-detail__meta{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:16px}
.ticket-detail__form{display:grid;gap:8px}
.ticket-detail__label{font-weight:600}
.ticket-detail__input,.ticket-detail__textarea{
  padding:8px;border:1px solid #eee;border-radius:8px
}
.ticket-detail__actions{display:flex;gap:12px;align-items:center;margin-top:8px}
.ticket-detail__btn{padding:8px 12px;border:1px solid #eee;border-radius:8px;background:#f7f7f7;cursor:pointer}
.ticket-detail__link{text-decoration:none}
.ticket-detail__empty{color:#666}
</style>
