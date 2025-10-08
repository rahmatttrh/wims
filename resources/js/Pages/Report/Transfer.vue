<template>
  <admin-layout :title="$t('x_report', { x: $t('Transfer') })">
    <div class="px-4 md:px-0">
      <div class="flex items-start justify-between">
        <tec-section-title class="-mx-4 md:mx-0 mb-6">
          <template #title>{{ $t('x_report', { x: $t('Transfer') }) }}</template>
          <template #description>{{ $t('Please review the report below') }}</template>
        </tec-section-title>

        <div class="flex space-x-2">
          <!-- Toggle Form -->
          <tec-button type="button" @click="toggleForm()">
            <span>
              <icons name="toggle" class="w-5 h-5 lg:mr-2" />
            </span>
            <span class="hidden lg:inline">{{ $t('toggle_x', { x: $t('Form') }) }}</span>
          </tec-button>

          <!-- Export Dropdown -->
          <div class="relative">
            <button
              @click="showExport = !showExport"
              class="flex items-center px-4 py-4 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-hidden"
            >
              <icons name="download" class="w-5 h-5 lg:mr-2"></icons>
              <span>{{ $t('Export') }}</span>
            </button>

            <div
              v-show="showExport"
              class="absolute right-0 mt-2 w-40 bg-white border rounded-lg shadow-lg z-50"
            >
              <button
                type="button"
                @click="exportTransferCSV(); showExport = false"
                class="block w-full px-4 py-2 text-left hover:bg-gray-100"
              >
                Export CSV
              </button>
              <button
                type="button"
                @click="exportTransferXLSX(); showExport = false"
                class="block w-full px-4 py-2 text-left hover:bg-gray-100"
              >
                Export XLSX
              </button>
              <button
                type="button"
                @click="exportTransferPDF(); showExport = false"
                class="block w-full px-4 py-2 text-left hover:bg-gray-100"
              >
                Export PDF
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Filter form -->
      <transition name="slidedown">
        <div v-show="showForm" class="w-full print:hidden">
          <report-form
            :users="users"
            type="transfer"
            :categories="categories"
            :warehouses="warehouses"
            :action="route('reports.transfer')"
          />
        </div>
      </transition>

      <!-- Table -->
      <div class="bg-white -mx-4 md:mx-0 md:rounded-md shadow-sm overflow-x-auto">
        <table class="w-full whitespace-nowrap">
          <thead>
            <tr class="text-left font-bold">
              <th class="px-6 pt-6 pb-4">#</th>
              <th class="px-6 pt-6 pb-4">{{ $t('Item') }}</th>
              <th class="px-6 pt-6 pb-4">{{ $t('Stock Summary') }}</th>
              <th class="px-6 pt-6 pb-4">{{ $t('Details') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(transfer, ci) in transfers"
              :key="transfer.id"
              class="hover:bg-gray-100 focus-within:bg-gray-100"
            >
              <!-- Item info -->
              <td class="border-t px-6 py-4">{{ ci + 1 }}</td>
              <td class="border-t px-6 py-4">
                <div>
                  <div class="font-medium">{{ transfer.code }}</div>
                  <div>{{ transfer.name }}</div>
                  <div>{{ transfer.unit }}</div>
                </div>
              </td>

              <!-- Stock info -->
              <td class="border-t px-6 py-4">
                <div><b>{{ $t('Jumlah Barang') }}:</b> {{ formatQty(transfer.jumlah_barang) }}</div>
                <div><b>{{ $t('Saldo Awal') }}:</b> {{ formatQty(transfer.saldo_awal) }}</div>
                <div><b>{{ $t('Barang Masuk') }}:</b> {{ formatQty(transfer.barang_masuk) }}</div>
                <div><b>{{ $t('Barang Keluar') }}:</b> {{ formatQty(transfer.barang_keluar) }}</div>
                <div><b>{{ $t('Adjustment') }}:</b> {{ formatQty(transfer.adjustment) }}</div>
                <div><b>{{ $t('Saldo Akhir') }}:</b> {{ formatQty(transfer.saldo_akhir) }}</div>
                <div><b>{{ $t('Cacah') }}:</b> {{ formatQty(transfer.cacah) }}</div>
                <div><b>{{ $t('Selisih') }}:</b> {{ formatQty(transfer.selisih) }}</div>
              </td>

              <!-- Details -->
              <td class="border-t px-6 py-4 max-w-lg min-w-56">
                {{ transfer.details }}
              </td>
            </tr>

            <tr v-if="!transfers.length">
              <td colspan="4" class="border-t px-6 py-4 text-center text-gray-500">
                {{ $t('There is no data to display.') }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </admin-layout>
</template>

<script>
import Modal from '@/Jetstream/Modal.vue'
import TecButton from '@/Jetstream/Button.vue'
import ReportForm from '@/Pages/Report/Form.vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import TecSectionTitle from '@/Jetstream/SectionTitle.vue'

export default {
  components: {
    Modal,
    TecButton,
    ReportForm,
    AdminLayout,
    TecSectionTitle
  },

  props: {
    filters: Object,
    transfers: Array, // ✅ BUKAN Object (karena bukan pagination)
    users: Array,
    categories: Array,
    warehouses: Array
  },

  data() {
    return {
      showForm: false,
      showExport: false
    }
  },

  mounted() {
    document.addEventListener('click', this.handleClickOutside)
  },
  beforeUnmount() {
    document.removeEventListener('click', this.handleClickOutside)
  },

  methods: {
    toggleForm() {
      this.showForm = !this.showForm
    },

    handleClickOutside(event) {
      const dropdown = this.$el.querySelector('.relative')
      if (dropdown && !dropdown.contains(event.target)) {
        this.showExport = false
      }
    },

    formatQty(value) {
      return Number(value ?? 0).toLocaleString('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      })
    },

    exportTransferCSV() {
      const headers = [
        'No',
        'Kode Barang',
        'Nama Barang',
        'Satuan',
        'Saldo Awal',
        'Barang Masuk',
        'Barang Keluar',
        'Adjustment',
        'Saldo Akhir',
        'Cacah',
        'Selisih',
        'Keterangan'
      ]

      const rows = this.transfers.map((item, index) => [
        index + 1,
        item.code,
        item.name,
        item.unit,
        item.saldo_awal,
        item.barang_masuk,
        item.barang_keluar,
        item.adjustment,
        item.saldo_akhir,
        item.cacah,
        item.selisih,
        item.details
      ])

      const csvContent = [headers, ...rows]
        .map(r => r.map(cell => `"${cell}"`).join(';'))
        .join('\n')

      const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' })
      const link = document.createElement('a')
      link.href = URL.createObjectURL(blob)
      link.download = `Transfer-report-${new Date().toISOString().slice(0, 10)}.csv`
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
    },

    exportTransferXLSX() {
      window.location.href = route('reports.transfer.export.xlsx')
    },
    exportTransferPDF() {
      const query = window.location.search
      window.location.href = route('reports.transfer.export.pdf') + query
    }
  }
}
</script>

<style scoped>
.relative {
  position: relative;
}
</style>
