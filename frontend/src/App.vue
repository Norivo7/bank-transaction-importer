<script setup>
import { onMounted, ref } from 'vue'
import api from './services/api'

const selectedFile = ref(null)
const imports = ref([])
const selectedImport = ref(null)
const isUploading = ref(false)
const isLoadingImports = ref(false)
const isLoadingDetails = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

function onFileChange(event) {
  const files = event.target.files

  if (!files || files.length === 0) {
    selectedFile.value = null
    return
  }

  selectedFile.value = files[0]
}

async function loadImports() {
  isLoadingImports.value = true
  errorMessage.value = ''

  try {
    const response = await api.get('/imports')
    imports.value = response.data
  } catch (error) {
    errorMessage.value = 'Failed to load imports.'
  } finally {
    isLoadingImports.value = false
  }
}

async function loadImportDetails(importId) {
  isLoadingDetails.value = true
  errorMessage.value = ''

  try {
    const response = await api.get(`/imports/${importId}`)
    selectedImport.value = response.data
  } catch (error) {
    errorMessage.value = 'Failed to load import details.'
  } finally {
    isLoadingDetails.value = false
  }
}

async function uploadFile() {
  if (!selectedFile.value) {
    errorMessage.value = 'Please select a file first.'
    successMessage.value = ''
    return
  }

  isUploading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  const formData = new FormData()
  formData.append('file', selectedFile.value)

  try {
    const response = await api.post('/imports', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })

    successMessage.value = 'Import completed.'
    selectedImport.value = response.data
    selectedFile.value = null

    await loadImports()
  } catch (error) {
    errorMessage.value = 'Import failed.'
  } finally {
    isUploading.value = false
  }
}

function formatDate(value) {
  if (!value) {
    return '-'
  }

  return new Date(value).toLocaleString()
}

function statusClass(status) {
  return `status-${status}`
}

onMounted(async () => {
  await loadImports()
})
</script>

<template>
  <main class="app-shell">
    <header class="topbar">
      <div>
        <p class="eyebrow">Recruitment task</p>
        <h1>Bank Transaction Importer</h1>
      </div>

      <div class="topbar-actions">
        <button class="secondary-button" :disabled="isLoadingImports" @click="loadImports">
          {{ isLoadingImports ? 'Refreshing...' : 'Refresh imports' }}
        </button>
      </div>
    </header>

    <section class="upload-panel">
      <div class="upload-copy">
        <h2>Import file</h2>
        <p>Accepted formats: CSV, JSON, XML</p>
      </div>

      <div class="upload-controls">
        <label class="file-input-wrapper">
          <input type="file" accept=".csv,.json,.xml" @change="onFileChange" />
        </label>

        <div class="file-name">
          {{ selectedFile ? selectedFile.name : 'No file selected' }}
        </div>

        <button class="primary-button" :disabled="isUploading" @click="uploadFile">
          {{ isUploading ? 'Uploading...' : 'Start import' }}
        </button>
      </div>

      <div class="messages">
        <p v-if="errorMessage" class="message message-error">{{ errorMessage }}</p>
        <p v-if="successMessage" class="message message-success">{{ successMessage }}</p>
      </div>
    </section>

    <section class="content-grid">
      <section class="panel">
        <div class="panel-header">
          <h2>Import history</h2>
          <span class="panel-meta">{{ imports.length }} records</span>
        </div>

        <div v-if="imports.length === 0" class="empty-state">
          No imports available.
        </div>

        <div v-else class="import-list">
          <article
              v-for="item in imports"
              :key="item.id"
              class="import-card"
              @click="loadImportDetails(item.id)"
          >
            <div class="import-card-top">
              <div>
                <h3>{{ item.file_name }}</h3>
                <p class="muted">Created {{ formatDate(item.created_at) }}</p>
              </div>

              <span class="status-pill" :class="statusClass(item.status)">
                {{ item.status }}
              </span>
            </div>

            <div class="import-stats">
              <div>
                <span class="stats-label">Total</span>
                <strong>{{ item.total_records }}</strong>
              </div>
              <div>
                <span class="stats-label">Success</span>
                <strong>{{ item.successful_records }}</strong>
              </div>
              <div>
                <span class="stats-label">Failed</span>
                <strong>{{ item.failed_records }}</strong>
              </div>
            </div>
          </article>
        </div>
      </section>

      <section class="panel">
        <div class="panel-header">
          <h2>Import details</h2>
        </div>

        <div v-if="isLoadingDetails" class="empty-state">
          Loading details...
        </div>

        <div v-else-if="selectedImport === null" class="empty-state">
          Select an import from the list.
        </div>

        <div v-else class="details-layout">
          <div class="details-summary">
            <div class="details-row">
              <span>File</span>
              <strong>{{ selectedImport.file_name }}</strong>
            </div>
            <div class="details-row">
              <span>Status</span>
              <span class="status-pill" :class="statusClass(selectedImport.status)">
                {{ selectedImport.status }}
              </span>
            </div>
            <div class="details-row">
              <span>Total records</span>
              <strong>{{ selectedImport.total_records }}</strong>
            </div>
            <div class="details-row">
              <span>Successful records</span>
              <strong>{{ selectedImport.successful_records }}</strong>
            </div>
            <div class="details-row">
              <span>Failed records</span>
              <strong>{{ selectedImport.failed_records }}</strong>
            </div>
            <div class="details-row">
              <span>Created at</span>
              <strong>{{ formatDate(selectedImport.created_at) }}</strong>
            </div>
          </div>

          <div class="logs-section">
            <div class="logs-header">
              <h3>Error logs</h3>
              <span class="panel-meta">
                {{ selectedImport.logs ? selectedImport.logs.length : 0 }} entries
              </span>
            </div>

            <div v-if="!selectedImport.logs || selectedImport.logs.length === 0" class="empty-state">
              No errors for this import.
            </div>

            <div v-else class="log-list">
              <article v-for="log in selectedImport.logs" :key="log.id" class="log-card">
                <div class="log-row">
                  <span>Transaction ID</span>
                  <strong>{{ log.transaction_id || '-' }}</strong>
                </div>
                <div class="log-row">
                  <span>Error</span>
                  <strong>{{ log.error_message }}</strong>
                </div>
                <div class="log-row">
                  <span>Created at</span>
                  <strong>{{ formatDate(log.created_at) }}</strong>
                </div>
              </article>
            </div>
          </div>
        </div>
      </section>
    </section>
  </main>
</template>

<style scoped>
:global(body) {
  margin: 0;
  background: #0b1020;
  color: #e5e7eb;
  font-family: Inter, Arial, sans-serif;
}

:global(*) {
  box-sizing: border-box;
}

.app-shell {
  max-width: 1320px;
  margin: 0 auto;
  padding: 28px;
}

.topbar {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 20px;
  margin-bottom: 24px;
}

.eyebrow {
  margin: 0 0 8px;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  font-size: 12px;
  color: #7c8aa5;
}

h1 {
  margin: 0;
  font-size: 32px;
  line-height: 1.1;
  color: #f8fafc;
}

h2 {
  margin: 0;
  font-size: 18px;
  color: #f8fafc;
}

h3 {
  margin: 0;
  font-size: 16px;
  color: #f8fafc;
}

.upload-panel,
.panel {
  background: linear-gradient(180deg, #11182c 0%, #0f1728 100%);
  border: 1px solid #1f2a44;
  border-radius: 18px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.22);
}

.upload-panel {
  padding: 20px;
  margin-bottom: 24px;
}

.upload-copy p,
.muted,
.panel-meta,
.empty-state,
.file-name {
  color: #94a3b8;
}

.upload-controls {
  display: flex;
  align-items: center;
  gap: 14px;
  flex-wrap: wrap;
  margin-top: 16px;
}

.file-input-wrapper input {
  color: #cbd5e1;
}

.primary-button,
.secondary-button {
  border: 0;
  border-radius: 12px;
  padding: 11px 16px;
  font-weight: 600;
  cursor: pointer;
}

.primary-button {
  background: #3b82f6;
  color: white;
}

.secondary-button {
  background: #1e293b;
  color: #e2e8f0;
  border: 1px solid #334155;
}

.primary-button:disabled,
.secondary-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.messages {
  margin-top: 14px;
}

.message {
  margin: 0;
  font-weight: 600;
}

.message-error {
  color: #fda4af;
}

.message-success {
  color: #86efac;
}

.content-grid {
  display: grid;
  grid-template-columns: 0.95fr 1.05fr;
  gap: 24px;
}

.panel {
  padding: 20px;
}

.panel-header,
.logs-header,
.import-card-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
}

.import-list,
.log-list,
.details-layout {
  margin-top: 18px;
}

.import-list,
.log-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.import-card,
.log-card {
  border: 1px solid #23304c;
  background: #0d1526;
  border-radius: 14px;
  padding: 16px;
}

.import-card {
  cursor: pointer;
  transition: transform 0.16s ease, border-color 0.16s ease, background 0.16s ease;
}

.import-card:hover {
  transform: translateY(-1px);
  border-color: #36507d;
  background: #101b31;
}

.import-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  margin-top: 16px;
}

.import-stats > div {
  background: #10192d;
  border: 1px solid #1d2942;
  border-radius: 12px;
  padding: 12px;
}

.stats-label {
  display: block;
  font-size: 12px;
  color: #7c8aa5;
  margin-bottom: 6px;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  padding: 6px 10px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.status-success {
  background: rgba(34, 197, 94, 0.14);
  color: #86efac;
  border: 1px solid rgba(34, 197, 94, 0.35);
}

.status-partial {
  background: rgba(245, 158, 11, 0.14);
  color: #fcd34d;
  border: 1px solid rgba(245, 158, 11, 0.35);
}

.status-failed {
  background: rgba(239, 68, 68, 0.14);
  color: #fca5a5;
  border: 1px solid rgba(239, 68, 68, 0.35);
}

.details-summary {
  display: grid;
  gap: 10px;
}

.details-row,
.log-row {
  display: grid;
  grid-template-columns: 170px 1fr;
  gap: 16px;
  align-items: start;
  padding: 12px 0;
  border-bottom: 1px solid #1b263d;
}

.details-row:last-child {
  border-bottom: 0;
}

.details-row span,
.log-row span {
  color: #7c8aa5;
}

.logs-section {
  margin-top: 26px;
}

.empty-state {
  margin-top: 18px;
}

@media (max-width: 980px) {
  .content-grid {
    grid-template-columns: 1fr;
  }

  .topbar {
    flex-direction: column;
    align-items: stretch;
  }

  .details-row,
  .log-row {
    grid-template-columns: 1fr;
    gap: 8px;
  }

  .import-stats {
    grid-template-columns: 1fr;
  }
}
</style>