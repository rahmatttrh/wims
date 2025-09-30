<template>
  <div v-if="item" class="bg-gray-100 -mx-6 px-4 md:px-6 md:rounded-md print:h-full print:block print:m-0 print:pt-0">
    <div class="bg-white -mx-4 md:mx-0 md:rounded-md shadow-sm overflow-x-auto print:m-0 print:block">
      <table class="w-full my-4">
        <tbody>
          <tr>
            <td class="px-6 py-2 whitespace-nowrap">
              <button type="button" @click="showImage">
                <img v-if="item.photo" class="block w-24 h-24 rounded-xs mr-2 -my-2" :src="item.photo" />
              </button>
            </td>
            <td class="px-6 py-2">
              <svg
                ref="barcode"
                class="barcode"
                :data-code="item.code"
                :data-symbology="item.symbology"
              ></svg>
            </td>
          </tr>

          <tr>
            <td class="px-6 py-2 whitespace-nowrap">{{ $t('Name') }}</td>
            <td class="px-6 py-2"><span class="font-bold">{{ item.name }}</span></td>
          </tr>

          <tr>
            <td class="px-6 py-2 whitespace-nowrap">{{ $t('SKU') }}</td>
            <td class="px-6 py-2">{{ item.sku }}</td>
          </tr>

          <tr>
            <td class="px-6 py-2 whitespace-nowrap">{{ $t('Category') }}</td>
            <td class="px-6 py-2">{{ (item.categories && item.categories[0]) ? item.categories[0].name : '' }}</td>
          </tr>

          <tr v-if="item.categories && item.categories.length > 1">
            <td class="px-6 py-2 whitespace-nowrap">{{ $t('Child Category') }}</td>
            <td class="px-6 py-2">{{ item.categories[1].name }}</td>
          </tr>

          <tr>
            <td class="px-6 py-2 whitespace-nowrap">{{ $t('Rack') }}</td>
            <td class="px-6 py-2"><div v-if="item.rack_location">{{ item.rack_location }}</div></td>
          </tr>

          <tr>
            <td class="px-6 py-2 whitespace-nowrap">{{ $t('Unit') }}</td>
            <td class="px-6 py-2"><div v-if="item.unit">{{ item.unit.name }}</div></td>
          </tr>

          <tr>
            <td class="px-6 py-2 whitespace-nowrap">{{ $t('Track Serials') }}</td>
            <td class="px-6 py-2"><icons v-if="item.track_serials" name="tick" class="text-green-600" /><icons v-else name="cross" class="text-red-600" /></td>
          </tr>

          <tr>
            <td class="px-6 py-2 whitespace-nowrap">{{ $t('Track Weight') }}</td>
            <td class="px-6 py-2"><icons v-if="item.track_weight" name="tick" class="text-green-600" /><icons v-else name="cross" class="text-red-600" /></td>
          </tr>

          <tr>
            <td class="px-6 py-2 whitespace-nowrap">{{ $t('Track Quantity') }}</td>
            <td class="px-6 py-2"><icons v-if="item.track_quantity" name="tick" class="text-green-600" /><icons v-else name="cross" class="text-red-600" /></td>
          </tr>

          <tr v-if="item.has_variants">
            <td class="px-6 py-2 whitespace-nowrap">{{ $t('Variants') }}</td>
            <td class="px-6 py-2">
              <div v-for="v in item.variants" :key="v.name">
                <strong>{{ v.name }}:</strong> {{ v.option.filter(o => o).join(', ') }}
              </div>
            </td>
          </tr>

          <tr>
            <td class="px-6 py-2 whitespace-nowrap">{{ $t('Details') }}</td>
            <td class="px-6 py-2"><span v-if="item.details">{{ item.details }}</span></td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Stock per gudang -->
    <div class="-mx-4 md:mx-0 print:m-0 print:block">
      <div class="mt-6 avoid print:mt-0">
        <div class="grid gap-6" :class="modal ? 'grid-cols-1 lg:grid-cols-2' : 'grid-cols-1 lg:grid-cols-2 xl:grid-cols-3'">
          <div v-for="(w, idx) in groupedStock" :key="'w_' + idx" class="w-full print:m-3 print:border print:rounded-md print:w-5/12">
            <div class="bg-white pt-3 pb-2 md:rounded-md shadow-sm overflow-x-auto print:mb-2">
              <div class="flex items-center justify-between px-4 -my-3 py-3">
                <h4 class="text-lg font-bold">{{ w[0].warehouse?.name || '' }} ({{ w[0].warehouse?.code || '' }})</h4>
                <p>{{ w[0].rack_location || '' }}</p>
              </div>
              <table class="w-full mt-3">
                <tbody>
                  <tr v-for="stock in w" :key="stock.id" :class="{ 'font-bold': !stock.variation }">
                    <td class="border-t pl-4 pr-2 py-2">
                      <span v-if="stock.variation" v-html="$meta(stock.variation.meta)" />
                      <span v-else>{{ $t('Quantity') }}</span>
                    </td>
                    <td class="border-t pr-4 pl-2 py-2 text-right">
                      {{ $number(stock.quantity) }} {{ item.unit ? item.unit.code : '' }}
                      <span v-if="item.track_weight">({{ $number(stock.weight) }} {{ $settings.weight_unit }})</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Image modal -->
    <modal :show="showImageModal" max-width="2xl" :closeable="true" :transparent="true" @close="showImageModal = false">
      <div class="print:block print:h-full">
        <img class="block max-w-full mx-auto rounded-md" :src="item.photo" />
      </div>
    </modal>
  </div>
</template>

<script>
import JsBarcode from 'jsbarcode';
import Loading from '@/Shared/Loading.vue';
import Modal from '@/Jetstream/Modal.vue';

export default {
  components: { Loading, Modal },
  props: { item: Object, modal: { default: false } },

  data() {
    return {
      showImageModal: false,
      loading: false,
      variations: [],
    };
  },

  computed: {
    // groupedStock: supaya tetap aman walau struktur stock berbeda
    groupedStock() {
      // jika item.stock sudah tergrup (array of arrays) kembalikan langsung
      if (!this.item) return [];
      if (Array.isArray(this.item.stock) && this.item.stock.length && Array.isArray(this.item.stock[0])) {
        return this.item.stock;
      }
      // jika stock adalah flat array, group by warehouse_id
      if (Array.isArray(this.item.stock)) {
        const groups = {};
        this.item.stock.forEach(s => {
          const key = s.warehouse_id || 'unknown';
          groups[key] = groups[key] || [];
          groups[key].push(s);
        });
        return Object.values(groups);
      }
      // fallback: jika ada all_stock
      if (Array.isArray(this.item.all_stock)) {
        const groups = {};
        this.item.all_stock.forEach(s => {
          const key = s.warehouse_id || 'unknown';
          groups[key] = groups[key] || [];
          groups[key].push(s);
        });
        return Object.values(groups);
      }
      return [];
    },
  },

  mounted() {
    this.renderBarcode();
    // jika perlu load variasi via API uncomment dan sesuaikan
    // if (this.item && this.item.has_variants == 1) { ... }
  },

  updated() {
    this.renderBarcode();
  },

  methods: {
    renderBarcode() {
      this.$nextTick(() => {
        try {
          const svgs = this.$el.querySelectorAll('.barcode');
          svgs.forEach(svg => {
            const code = svg.getAttribute('data-code') || (this.item && this.item.code) || '';
            const sym = svg.getAttribute('data-symbology') || (this.item && this.item.symbology) || undefined;
            // JsBarcode(svg, value, options)
            JsBarcode(svg, code, {
              format: sym || undefined,
              width: 2,
              height: 70,
              fontSize: 12,
              displayValue: true,
            });
          });
        } catch (e) {
          // jika JsBarcode tidak tersedia atau error, jangan crash
          // console.warn('JsBarcode error', e);
        }
      });
    },

    showImage() {
      this.showImageModal = true;
    },
  },
};
</script>
