<template>
  <admin-layout :title="$t('Dashboard')">
    <div class="px-4 md:px-0">
      <!-- <tec-section-title class="-mx-4 md:mx-0 mb-6">
        <template #title>{{ $t('Roles') }}</template>
        <template #description>{{ $t('Please review the data in the table below') }}</template>
      </tec-section-title> -->

      <section class="-mt-4 mb-4 mx-auto">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Card Checkin -->
          <div class="p-4 rounded-md shadow-sm bg-white">
            <div class="flex items-start justify-between">
              <h2 class="mb-2 text-xl font-semibold leading-none text-gray-900 truncate">{{ data.checkins }}</h2>
              <span
                v-if="data.checkins != 0 && data.previous_checkins != 0"
                class="flex items-center space-x-1 text-sm font-medium leading-none"
                :class="data.checkins > data.previous_checkins ? 'text-green-500' : 'text-red-500'"
              >
                <icons v-if="data.checkins > data.previous_checkins" name="up" />
                <icons v-else name="down" />
                <span> {{ $number((data.checkins / data.previous_checkins) * 100 - 100) }}% </span>
              </span>
            </div>
            <p class="leading-none text-gray-600">{{ $t('Checkins') }}</p>
          </div>
          <!-- Card Checkout -->
          <div class="p-4 rounded-md shadow-sm bg-white">
            <div class="flex items-start justify-between">
              <h2 class="mb-2 text-xl font-semibold leading-none text-gray-900 truncate">{{ data.checkouts }}</h2>
              <span
                v-if="data.checkouts != 0 && data.previous_checkouts != 0"
                class="flex items-center space-x-1 text-sm font-medium leading-none"
                :class="data.checkouts > data.previous_checkouts ? 'text-green-500' : 'text-red-500'"
              >
                <icons v-if="data.checkouts > data.previous_checkouts" name="up" />
                <icons v-else name="down" />
                <span> {{ $number((data.checkouts / data.previous_checkouts) * 100 - 100) }}% </span>
              </span>
            </div>
            <p class="leading-none text-gray-600">{{ $t('Checkouts') }}</p>
          </div>
          <!-- Card Items -->
          <div class="p-4 rounded-md shadow-sm bg-white">
            <div class="flex items-start justify-between">
              <h2 class="mb-2 text-xl font-semibold leading-none text-gray-900 truncate">{{ data.items }}</h2>
            </div>
            <p class="leading-none text-gray-600">{{ $t('Items') }}</p>
          </div>
          <!-- Card Contact -->
          <div class="p-4 rounded-md shadow-sm bg-white">
            <div class="flex items-start justify-between">
              <h2 class="mb-2 text-xl font-semibold leading-none text-gray-900 truncate">{{ data.contacts }}</h2>
            </div>
            <p class="leading-none text-gray-600">{{ $t('Contacts') }}</p>
          </div>
        </div>
      </section>

      <!-- Grafik -->
      <div class="flex items-center gap-4 mb-2">
        <auto-complete json v-model="month" :suggestions="months" class="w-1/2" @update:modelValue="reload" />
        <auto-complete json v-model="year" :suggestions="years" class="w-1/2" @update:modelValue="reload" />
      </div>
      <div class="bg-white rounded-md shadow-sm overflow-x-auto">
        <vue-highcharts
          type="chart"
          :options="barChartData"
          :redrawOnUpdate="true"
          :oneToOneUpdate="false"
          :animateOnUpdate="true"
          style="min-width: 550px"
        />
      </div>

      <div class="mt-4 flex items-start flex-col md:flex-row gap-4">

        <!-- Pie Chart Overview WMS -->
        <div class="w-full md:w-1/2 bg-white rounded-md shadow-sm overflow-x-auto">
          <vue-highcharts type="chart" :options="pieChartData" :redrawOnUpdate="true" :oneToOneUpdate="false" :animateOnUpdate="true" />
        </div>

        <!-- Radial Chart Overview WMS -->
        <div class="w-full md:w-1/2 bg-white rounded-md shadow-sm overflow-x-auto">
          <vue-highcharts
            type="chart"
            :options="radialChartData"
            :redrawOnUpdate="true"
            :oneToOneUpdate="false"
            :animateOnUpdate="true"
            style="min-width: 550px"
          />
        </div>

      </div>

      <!-- Table Alert Inbound Barang yang mendekati tanggal expired atau mendekati expired -->
        <section class="mt-6 bg-white rounded-md shadow-sm overflow-x-auto">
          <h3 class="text-lg font-semibold text-gray-800 p-4 border-b">Long Stay Cargo</h3>

          <table class="w-full text-sm text-left text-gray-700">
            <thead class="bg-gray-100">
              <tr>
                <th class="px-4 py-3">No</th>
                <th class="px-4 py-3">No Aju Inbound</th>
                <th class="px-4 py-3">No Bukti Barang</th>
                <th class="px-4 py-3">Cargo</th>
                <th class="px-4 py-3">Pengirim</th>
                <th class="px-4 py-3">Pemilik</th>
                <th class="px-4 py-3">Tanggal Inbound</th>
                <th class="px-4 py-3">Tanggal Expired</th>
                <th class="px-4 py-3">Lama Timbun</th>
                <!-- <th class="px-4 py-3">Keterangan</th> -->
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(item, index) in alert_inbound"
                :key="item.id"
                :class="{
                  'bg-red-100 text-red-700': item.status_expired === 'expired',
                  'bg-yellow-100 text-yellow-700': item.status_expired === 'warning',
                }"
                class="border-b hover:bg-gray-50"
              >
                <td class="px-4 py-2">{{ index + 1 }}</td>
                <td class="px-4 py-2"><a :href="route('checkins.show', item.id)" class="hover:underline">{{ item.reference ?? '-' }}</a></td>
                <td class="px-4 py-2">{{ item.no_receive ?? '-' }}</td>
                <td class="px-4 py-2">{{ item.name ?? '-' }}</td>
                <!-- <td class="px-4 py-2">{{ item.user?.name }}</td> -->
                <td class="px-4 py-2">{{ item.sender ?? '-' }}</td>
                <td class="px-4 py-2">{{ item.owner ?? '-' }}</td>
                <td class="px-4 py-2">{{ item.date_receive ?? '-' }}</td>
                <td class="px-4 py-2">{{ item.date_expired ?? '-' }}</td>
                <td class="px-4 py-2">{{ item.lama_total ?? '-' }}</td>
                <!-- <td class="px-4 py-2 font-semibold capitalize">{{ item.status_expired }}</td> -->
                <!-- <td class="px-4 py-2 font-semibold capitalize">Expired 3 hari lagi</td> -->
                <!-- <td class="px-4 py-2 text-sm font-semibold capitalize">{{ item.keterangan }}</td> -->
              </tr>
              <tr v-if="!alert_inbound.length">
                <td colspan="7" class="px-4 py-4 text-center text-gray-500">
                  Tidak ada barang yang melewati masa 6 bulan.
                </td>
              </tr>
            </tbody>

            <tfoot class="text-xs text-gray-600 bg-gray-50">
              <tr>
                <td colspan="6" class="px-4 pt-3">
                  Cargo Barang > 6 bulan sejak inbound = <span class="text-yellow-600 font-medium">Alert Kuning</span>
                </td>
              </tr>
              <tr>
                <td colspan="6" class="px-4 pb-3">
                  Cargo Barang 33-36 bulan sejak inbound = <span class="text-red-600 font-medium">Alert Merah</span>
                </td>
              </tr>
            </tfoot>
          </table>
        </section>

       
    </div>
  </admin-layout>
</template>

<script>
import VueHighcharts from 'vue3-highcharts';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AutoComplete from '@/Shared/AutoComplete.vue';
import TecSectionTitle from '@/Jetstream/SectionTitle.vue';
// import Pagination from '@/Shared/Pagination.vue';

export default {
  props: ['data', 'chart', 'top_products', 'alert_inbound'],

  components: { AdminLayout, AutoComplete, VueHighcharts, TecSectionTitle},

  data() {
    return {
      year: new Date().getFullYear(),
      month: new Date().getMonth() + 1,
      // months: [
      //   { label: this.$monthName('Januari'), value: 1 },
      //   { label: this.$monthName('Februari'), value: 2 },
      //   { label: this.$monthName('Maret'), value: 3 },
      //   { label: this.$monthName('April'), value: 4 },
      //   { label: this.$monthName('Mei'), value: 5 },
      //   { label: this.$monthName('Juni'), value: 6 },
      //   { label: this.$monthName('Juli'), value: 7 },
      //   { label: this.$monthName('Agustus'), value: 8 },
      //   { label: this.$monthName('September'), value: 9 },
      //   { label: this.$monthName('Oktober'), value: 10 },
      //   { label: this.$monthName('November'), value: 11 },
      //   { label: this.$monthName('Desember'), value: 12 },
      // ],
      months: [
        { label: this.$monthName(1), value: 1 },
        { label: this.$monthName(2), value: 2 },
        { label: this.$monthName(3), value: 3 },
        { label: this.$monthName(4), value: 4 },
        { label: this.$monthName(5), value: 5 },
        { label: this.$monthName(6), value: 6 },
        { label: this.$monthName(7), value: 7 },
        { label: this.$monthName(8), value: 8 },
        { label: this.$monthName(9), value: 9 },
        { label: this.$monthName(10), value: 10 },
        { label: this.$monthName(11), value: 11 },
        { label: this.$monthName(12), value: 12 },
      ],

    };
  },

  computed: {
    years() {
      let years = [];
      let date = new Date();
      let year = date.getFullYear();
      for (let y = 2020; y <= year; y++) {
        years.push({ label: y + '', value: y + '' });
      }
      return years;
    },
    barChartData() {
      return this.barChartOptions();
    },
    pieChartData() {
      return this.pieChartOptions();
    },
    radialChartData() {
      return this.radialChartOptions();
    },
  },

  // watch: {
  //   month: function (m) {
  //     // let month = this.months.find(mn => mn.value == m).label;
  //     this.$inertia.visit(route('dashboard', { month: +m, year: +this.year }), { preserveState: false, preserveScroll: false });
  //   },
  //   year: function (y) {
  //     this.$inertia.visit(route('dashboard', { month: +this.month, year: +y }), { preserveState: false, preserveScroll: false });
  //   },
  // },

  created() {
    let params = new URLSearchParams(this.$page.url.substring(1));
    if (params.get('month') && params.get('year')) {
      this.year = params.get('year');
      this.month = params.get('month');
    }

    // if (params.get('year') && this.year != params.get('year')) {
    //   this.year = params.get('year');
    // }
    // if (params.get('month') && this.month != params.get('month')) {
    //   this.month = params.get('month');
    // }
  },

  methods: {
    reload() {
      this.$inertia.visit(route('dashboard', { month: +this.month, year: +this.year }), { preserveState: false, preserveScroll: false });
    },
    sortValues(data) {
      return Object.values(
        Object.keys(data)
          .sort()
          .reduce((obj, key) => {
            obj[key] = data[key];
            return obj;
          }, {})
      );
    },
    onRender() {
      console.log('Chart rendered');
    },
    onUpdate() {
      console.log('Chart updated');
    },
    onDestroy() {
      console.log('Chart destroyed');
    },

    radialChartOptions() {
      return {
        chart: {
          zoomType: 'xy',
          spacingTop: 20,
          style: { fontFamily: 'Nunito' },
        },
        credits: {
          enabled: false,
        },
        title: {
          text: this.$t('Month Overview'),
          style: { fontWeight: 'bold', paddingTop: '10px' },
        },
        subtitle: {
          text: this.$t('Please review the chart below'),
        },
        colors: ['#059669', '#D97706', '#4F46E5', '#DC2626'],
        xAxis: [
          {
            categories: this.chart.month.labels,
            // categories: this.chart.month.adjustment.labels.map(d => this.$jsdate(d)),
            //categories: Object.keys(this.chart.month.checkin)
            //  .sort()
            //  .map(d => this.$date(d)),
            crosshair: true,
          },
        ],
        yAxis: {
          min: 0,
          title: {
            text: '',
          },
        },
        tooltip: {
          shared: true,
          shadow: false,
          useHTML: true,
          borderRadius: '5',
          borderColor: '#1F2937',
          style: { color: '#fff' },
          backgroundColor: '#1F2937',
        },
        series: [
          {
            type: 'spline',
            name: this.$t('Checkins'),
            // data: Object.keys(this.chart.month.checkin.values).map(k => this.chart.month.checkin.values[k]),
            // data: this.sortValues(this.chart.month.checkin.values),
            data: this.chart.month.checkin,
          },
          {
            type: 'spline',
            name: this.$t('Checkouts'),
            // data: Object.keys(this.chart.month.checkout.values).map(k => this.chart.month.checkout.values[k]),
            // data: this.sortValues(this.chart.month.checkout.values),
            data: this.chart.month.checkout,
          },
          {
            type: 'spline',
            name: this.$t('Transfers'),
            // data: Object.keys(this.chart.month.transfer.values).map(k => this.chart.month.transfer.values[k]),
            // data: this.sortValues(this.chart.month.transfer.values),
            data: this.chart.month.transfer,
          },
          {
            type: 'spline',
            name: this.$t('Adjustments'),
            // data: Object.keys(this.chart.month.adjustment.values).map(k => this.chart.month.adjustment.values[k]),
            // data: this.sortValues(this.chart.month.adjustment.values),
            data: this.chart.month.adjustment,
          },
        ],
      };
    },
    pieChartOptions() {
      return {
        chart: {
          type: 'pie',
          spacingTop: 20,
          plotShadow: false,
          plotBorderWidth: null,
          plotBackgroundColor: null,
          style: { fontFamily: 'Nunito' },
        },
        credits: {
          enabled: false,
        },
        colors: ['#059669', '#D97706', '#4F46E5', '#DC2626', '#3182CE', '#DB2777', '#4B5563', '#805AD5', '#38B2AC', '#ECC94B'],
        title: {
          text: this.$t('Overview WMS'),
          style: { fontWeight: 'bold', paddingTop: '10px' },
        },
        subtitle: {
          text: this.$t('Overview for the month'),
        },
        tooltip: {
          pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>',
          shared: true,
          shadow: false,
          useHTML: true,
          borderRadius: '5',
          borderColor: '#1F2937',
          style: { color: '#fff' },
          backgroundColor: '#1F2937',
        },
        accessibility: {
          point: {
            valueSuffix: '%',
          },
        },
        plotOptions: {
          pie: {
            allowPointSelect: true,
            cursor: 'pointer',
            dataLabels: {
              enabled: false,
            },
            showInLegend: true,
          },
        },
        series: [
          {
            colorByPoint: true,
            name: this.$t('Movement'),
            data: this.top_products
              .sort((a, b) => (a.y < b.y ? 1 : b.y < a.y ? -1 : 0))
              .map((i, ii) => (ii ? i : { ...i, sliced: true, selected: true })),
          },
        ],
      };
    },
    barChartOptions() {
      return {
        chart: {
          type: 'column',
          spacingTop: 20,
          style: { fontFamily: 'Nunito' },
        },
        credits: {
          enabled: false,
        },
        title: {
          text: this.$t('Year Overview'),
          style: { fontWeight: 'bold', paddingTop: '10px' },
        },
        subtitle: {
          text: this.$t('Please review the chart below'),
        },
        colors: ['#059669', '#D97706', '#4F46E5', '#DC2626'],
        xAxis: {
          categories: this.chart.year.year_categories,
          // categories: Object.keys(this.chart.year.checkin)
          //   .sort()
          //   .map(d => this.$month(d)),
          crosshair: true,
        },
        yAxis: {
          min: 0,
          title: {
            text: '',
          },
        },
        tooltip: {
          // headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
          // pointFormat:
          //   '<tr><td style="color:{series.color};padding:3px;">{series.name}: </td>' +
          //   '<td style="text-align:right;padding:3px 3px 3px 10px;"><b>{point.y:.2f} mm</b></td></tr>',
          // footerFormat: '</table>',
          shared: true,
          shadow: false,
          useHTML: true,
          borderRadius: '5',
          borderColor: '#1F2937',
          style: { color: '#fff' },
          backgroundColor: '#1F2937',
        },
        plotOptions: {
          column: {
            pointPadding: 0.2,
            borderWidth: 0,
          },
        },
        series: [
          {
            name: this.$t('Checkins'),
            data: this.chart.year.checkin,
            // data: this.sortValues(this.chart.year.checkin),
          },
          {
            name: this.$t('Checkouts'),
            data: this.chart.year.checkout,
            // data: this.sortValues(this.chart.year.checkout),
          },
          {
            name: this.$t('Transfers'),
            data: this.chart.year.transfer,
            // data: this.sortValues(this.chart.year.transfer),
          },
          {
            name: this.$t('Adjustments'),
            data: this.chart.year.adjustment,
            // data: this.sortValues(this.chart.year.adjustment),
          },
        ],
      };
    },
  },
};
</script>
