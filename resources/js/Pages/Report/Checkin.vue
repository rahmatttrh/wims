<template>
  <admin-layout :title="$t('x_report', { x: $t('Checkin') })">
    <div class="px-4 md:px-0">
      <!-- <div class="flex items-start justify-between">
        <tec-section-title class="-mx-4 md:mx-0 mb-6">
          <template #title>{{ $t('x_report', { x: $t('Checkin') }) }}</template>
          <template #description>{{ $t('Please review the report below') }}</template>
        </tec-section-title>
        <tec-button type="button" @click="toggleForm()">
          <span>
            <icons name="toggle" class="w-5 h-5 lg:mr-2" />
          </span>
          <span class="hidden lg:inline">{{ $t('toggle_x', { x: $t('Form') }) }}</span>
        </tec-button>
        <tec-button type="button" @click="">
          <span>
            <icons name="toggle" class="w-5 h-5 lg:mr-1" />
          </span>
          <span class="hidden lg:inline">Export</span>
        </tec-button>
      </div> -->
      <div class="flex items-start justify-between">
        <tec-section-title class="-mx-4 md:mx-0 mb-6">
          <template #title>{{ $t('x_report', { x: $t('Checkin') }) }}</template>
          <template #description>{{ $t('Please review the report below') }}</template>
        </tec-section-title>

        <!-- <div class="flex space-x-2">
          <tec-button type="button" @click="toggleForm()">
            <span>
              <icons name="toggle" class="w-5 h-5 lg:mr-2" />
            </span>
            <span class="hidden lg:inline">{{ $t('toggle_x', { x: $t('Form') }) }}</span>
          </tec-button>
          <tec-button type="button" @click="exportInboundCSV">
            <span>
              <icons name="download" class="w-5 h-5 lg:mr-2" />
            </span>
            <span class="hidden lg:inline">Export</span>
          </tec-button>
        </div> -->

        <div class="flex space-x-2">
          <!-- Toggle Form -->
          <tec-button type="button" @click="toggleForm()">
            <span>
              <icons name="toggle" class="w-5 h-5 lg:mr-2" />
            </span>
            <span class="hidden lg:inline">{{ $t('toggle_x', { x: $t('Form') }) }}</span>
          </tec-button>

          <!-- Export Dropdown (tanpa permission) -->
          <div class="relative">
            <button
              @click="showExport = !showExport"
              class="flex items-center px-4 py-4 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-hidden"
            >
              <icons name="download" class="w-5 h-5 lg:mr-2"></icons>
              <span>{{ $t('Export') }}</span>
            </button>

            <!-- Dropdown Menu -->
            <div
              v-show="showExport"
              class="absolute right-0 mt-2 w-40 bg-white border rounded-lg shadow-lg z-50"
            >
              <button
                type="button"
                @click="exportInboundCSV(); showExport = false"
                class="block w-full px-4 py-2 text-left hover:bg-gray-100"
              >
                Export CSV
              </button>
              <button
                type="button"
                @click="exportInboundXLSX(); showExport = false"
                class="block w-full px-4 py-2 text-left hover:bg-gray-100"
              >
                Export XLSX
              </button>
              <button
                type="button"
                @click="exportInboundPDF(); showExport = false"
                class="block w-full px-4 py-2 text-left hover:bg-gray-100"
              >
                Export PDF
              </button>
            </div>
          </div>

        </div>

      </div>

      <transition name="slidedown">
        <div v-show="showForm" class="w-full print:hidden">
          <report-form
            type="checkin"
            :users="users"
            :contacts="contacts"
            :categories="categories"
            :warehouses="warehouses"
            :action="route('reports.checkin')"
          />
        </div>
      </transition>
      <div class="bg-white -mx-4 md:mx-0 md:rounded-md shadow-sm overflow-x-auto">
        <table class="w-full whitespace-nowrap">
          <thead>
            <tr class="text-left font-bold">
              <th class="px-6 pt-6 pb-4">{{ $t('Checkin') }}</th>
              <th class="px-6 pt-6 pb-4">{{ $t('Relations') }}</th>
              <th class="px-6 pt-6 pb-4">{{ $t('Details') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr :key="checkin.id" v-for="(checkin, ci) in checkins.data" class="hover:bg-gray-100 focus-within:bg-gray-100">
              <td class="border-t" @click="goto(checkin.id)" :class="{ 'cursor-pointer': $can('read-checkins') }">
                <div class="px-6 py-4 flex items-center focus:text-indigo-500">
                  <div>
                    <div>{{ checkin.reference }}</div>
                    <div>{{ $date(checkin.date) }}</div>
                    <!-- <div class="flex items-center">
                      {{ $t('Draft') }}:
                      <icons v-if="checkin.draft == 1" name="tick" class="text-green-600 mx-auto" />
                      <icons v-else name="cross" class="text-red-600 mx-auto" />
                    </div> -->
                  </div>

                  <icons v-if="checkin.deleted_at" name="trash" class="shrink-0 w-4 h-4 text-red-500 ml-2" />
                </div>
              </td>
              <td class="border-t" @click="goto(checkin.id)" :class="{ 'cursor-pointer': $can('read-checkins') }">
                <div class="px-6 py-4">
                  <div class="flex items-center">
                    <div class="text-gray-500 mr-1">{{ $t('Contact') }}:</div>
                    {{ checkin.contact.name }}
                  </div>
                  <div class="flex items-center">
                    <div class="text-gray-500 mr-1">{{ $t('Warehouse') }}:</div>
                    {{ checkin.warehouse.name }}
                  </div>
                  <div class="flex items-center">
                    <div class="text-gray-500 mr-1">{{ $t('User') }}:</div>
                    {{ checkin.user.name }}
                  </div>
                </div>
              </td>
              <td class="border-t max-w-lg min-w-56" @click="goto(checkin.id)" :class="{ 'cursor-pointer': $can('read-checkins') }">
                <div class="px-6 py-4 flex items-center">
                  <div class="w-full whitespace-normal line-clamp-3">
                    {{ checkin.details }}
                  </div>
                </div>
              </td>
            </tr>
            <tr v-if="checkins.data.length === 0">
              <td class="border-t px-6 py-4" colspan="3">{{ $t('There is no data to display.') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <pagination class="mt-6" :meta="checkins.meta" :links="checkins.links" />
    </div>

    <!-- Item Details Modal -->
    <modal :show="details" max-width="4xl" :closeable="true" @close="hideDetails">
      <div class="px-6 py-4 print:px-0">
        <div v-if="details && checkin" class="flex items-center justify-between print:hidden">
          <div class="text-lg">
            {{ $t('Checkin Details') }} <span class="hidden sm:inline">({{ checkin.reference }})</span>
          </div>
          <div class="-mr-2 flex items-center">
            <button
              @click="print()"
              class="flex items-center justify-center mr-2 h-8 w-8 rounded-full text-gray-600 hover:text-gray-800 hover:bg-gray-300 focus:outline-hidden"
            >
              <icons name="printer" class="h-5 w-5" />
            </button>
            <Link
              v-if="$can('update-checkins')"
              :href="route('checkins.edit', checkin.id)"
              class="flex items-center justify-center mr-2 h-8 w-8 rounded-full text-gray-600 hover:text-gray-800 hover:bg-gray-300 focus:outline-hidden"
            >
              <icons name="edit" class="h-5 w-5" />
            </Link>
            <button
              @click="hideDetails()"
              class="flex items-center justify-center h-8 w-8 rounded-full text-gray-600 hover:text-gray-800 hover:bg-gray-300 focus:outline-hidden"
            >
              <icons name="cross" class="h-5 w-5" />
            </button>
          </div>
        </div>

        <div class="mt-4 print-mt-0">
          <checkin-details v-if="checkin" :checkin="checkin" />
        </div>
      </div>
    </modal>

    <loading v-if="loading" />
  </admin-layout>
</template>

<script>

  import Modal from '@/Jetstream/Modal.vue';
  import TecButton from '@/Jetstream/Button.vue';
  import ReportForm from '@/Pages/Report/Form.vue';
  import Pagination from '@/Shared/Pagination.vue';
  import AdminLayout from '@/Layouts/AdminLayout.vue';
  import CheckinDetails from '@/Pages/Checkin/Details.vue';
  import TecSectionTitle from '@/Jetstream/SectionTitle.vue';
  import BCLayout from '@/Layouts/BCLayout.vue';

  export default {
    components: {
      Modal,
      TecButton,
      ReportForm,
      Pagination,
      AdminLayout,
      BCLayout,
      CheckinDetails,
      TecSectionTitle,
    },

    props: {
      filters: Object,
      checkins: Object,
      contacts: Array,
      users: Array,
      categories: Array,
      warehouses: Array,
    },

    data() {
      return {
        checkin: null,
        details: false,
        showForm: false,
        loading: false,
        showExport: false,
      };
    },

    // Add
    mounted() {
      document.addEventListener("click", this.handleClickOutside);
    },
    beforeUnmount() {
      document.removeEventListener("click", this.handleClickOutside);
    },

    methods: {
      toggleForm() {
        this.showForm = !this.showForm;
      },

      goto(id) {
        if (this.checkin && this.checkin.id == id) {
          this.details = true;
        } else {
          this.loading = true;
          axios.get(route('checkins.show', id) + '?json=yes').then(res => {
            this.checkin = res.data;
            this.details = true;
            this.loading = false;
          });
        }
      },
      showDetails() {
        this.details = false;
      },
      hideDetails() {
        this.details = false;
      },
      print() {
        window.print();
      },
      
      handleClickOutside(event) {
        const dropdown = this.$el.querySelector(".relative");
        if (dropdown && !dropdown.contains(event.target)) {
          this.showExport = false;
        }
      },
      exportInboundCSV() {
        // Header
        const headers = ["No", "Reference / No Aju", "Tanggal", "Contact", "Warehouse", "User", "Draft", "Deleted"];

        // Data rows
        const rows = this.checkins.data.map((item, index) => [
          index + 1,
          item.reference || "",
          this.$date(item.date) || "",
          item.contact?.name || "",
          item.warehouse?.name || "",
          item.user?.name || "",
          item.draft == 1 ? "Yes" : "No",
          item.deleted_at ? "Yes" : "No"
        ]);

        // Pakai ";" biar Excel di Indonesia baca sebagai kolom
        const delimiter = ";";

        // Gabungkan header + data
        const csvContent = [headers, ...rows]
          .map(row => row.map(cell => `"${cell}"`).join(delimiter)) // kasih quote untuk aman
          .join("\n");

        // Tambahkan BOM untuk UTF-8
        const bom = "\uFEFF" + csvContent;

        // Download
        const blob = new Blob([bom], { type: "text/csv;charset=utf-8;" });
        const link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.setAttribute("download", `Inbound-report-${new Date().toISOString().slice(0, 10)}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      },
      exportInboundXLSX(){
        window.location.href = route('reports.checkin.export.xlsx');
      },
      exportInboundPDF(){
        window.location.href = route('reports.checkin.export.pdf');
      },


    },
  };
</script>
