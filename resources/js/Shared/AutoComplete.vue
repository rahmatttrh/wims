<script setup>
import axios from '@/Core/http';
import { useI18n } from 'vue-i18n';
// import throttle from 'lodash.throttle';
import debounce from 'lodash.debounce';
import InputLabel from '@/Jetstream/Label.vue';
import InputError from '@/Jetstream/InputError.vue';
import useClickOutside from '@/Core/useClickOutside.js';
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';

const { t } = useI18n({});
const excludeRef = ref(null);
const componentRef = ref(null);
const emit = defineEmits(['change', 'update:modelValue']);

const by_id = ref(false);
const auto_complete = ref(null);
const props = defineProps({
  id: {
    type: String,
    default() {
      return Math.random().toString().replace('0.', '1');
    },
  },
  size: String,
  label: String,
  error: String,
  action: Function,
  addResult: Array,
  position: String,
  valueKey: String,
  labelKey: String,
  disabled: Boolean,
  actionText: String,
  defaultText: String,
  placeholder: String,
  disable: [String, Number],
  suggestions: [String, Array],
  selectedValues: [String, Array],
  modelValue: [String, Number, Array],
  json: { type: Boolean, default: true },
  hideIcon: { type: Boolean, default: false },
  multiple: { type: Boolean, default: false },
  clearable: { type: Boolean, default: false },
  keepFocus: { type: Boolean, default: false },

  searchable: { type: Boolean, default: true },

  placement: { type: String, default: 'bottom' },
  resetSearch: { type: Boolean, default: false },
  inputClass: { type: String, default: '' },
});

const search = ref('');
const result = ref([]);
const input = ref(null);
const open = ref(false);
const stop = ref(false);
const search0 = ref('');
const current = ref(null);
const loading = ref(false);
const selected = ref(null);
const multi_current = ref([]);
const multi_selected = ref([]);

watch(
  () => props.modelValue,
  async v => {
    if (!v) {
      reset();
      await hide();
    } else {
      if (props.multiple) {
        let vl = multi_selected.value.map(s => (props.json ? s[props.valueKey] : s));
        if (v.toString() !== vl.toString()) {
          await initializeValue();
        }
      } else {
        // stop.value = true;
        by_id.value = true;
        await initializeValue(); // check this ya
      }
    }
  }
);

watch(
  () => props.suggestions,
  v => (result.value = v)
);

watch(
  () => props.addResult,
  async v => {
    if (v && v.value) {
      result.value.push(v.value);
      current.value = v.value;
      selected.value = v.value;
      suggestionClick(current.value);
      setTimeout(() => {
        search.value = props.json ? v.value[props.labelKey] : v.value;
        emit('update:modelValue', v.value);
      }, 100);
    }
  }
);

watch(search, s => {
  if (props.searchable) {
    open.value = true;
    if (Array.isArray(props.suggestions)) {
      if (props.multiple && Array.isArray(props.suggestions)) {
        result.value = props.suggestions;
      } else {
        result.value = s ? props.suggestions.filter(r => cLabel(r, s)) : props.suggestions;
      }
    } else if (s) {
      loading.value = true;
      getSuggestions(s);
    }
  }
});

const currentLabel = computed(() => cLabel(selected.value));
const currentValue = computed(() => cValue(selected.value));

useClickOutside(componentRef, () => hide(), excludeRef);

onBeforeUnmount(() => {
  if (props.multiple) {
    window.removeEventListener('click', clickListener);
  }
});

onMounted(async () => {
  if (props.multiple) {
    window.addEventListener('click', clickListener);
  }
  await initializeValue();
});

const initializeValue = async () => {
  if (Array.isArray(props.suggestions)) {
    result.value = props.suggestions;
  }
  if (props.modelValue) {
    multi_current.value = [];
    if (Array.isArray(props.suggestions)) {
      if (props.multiple) {
        if (props.selectedValues) {
          multi_selected.value = props.selectedValues;
          search.value = props.selectedValues.join(', ');
        } else {
          multi_selected.value = props.suggestions.filter(
            s =>
              s == props.modelValue ||
              s[props.valueKey] == props.modelValue ||
              props.modelValue.filter(m => m == s[props.valueKey])[0] ||
              props.modelValue.filter(m => m[props.valueKey] == s[props.valueKey])[0] ||
              s.id == props.modelValue
          );
        }
        if (props.json) {
          props.suggestions.map((s, si) => {
            if (multi_selected.value.find(v => v[props.valueKey] == s[props.valueKey])) {
              multi_current.value = [...multi_current.value, si];
            }
          });
        } else {
          //   multi_selected.value = props.modelValue;
          props.suggestions.map((s, si) => {
            if (multi_selected.value.includes(s)) {
              multi_current.value = [...multi_current.value, si];
            }
          });
        }

        search.value = multi_selected.value.map(v => (props.json ? v[props.labelKey] : v)).join(', ');
      } else {
        if (props.json) {
          selected.value = props.suggestions.find(
            s => s == props.modelValue || s[props.valueKey] == props.modelValue || s.value == props.modelValue || s.id == props.modelValue
          );
          current.value = props.suggestions.findIndex(
            s => s == props.modelValue || s[props.valueKey] == props.modelValue || s.value == props.modelValue || s.id == props.modelValue
          );
          search.value = currentLabel.value;
          // search.value = props.searchable ? currentLabel.value : '';
        } else {
          selected.value = props.modelValue;
        }
      }
      await nextTick();
      await hide(null, true);
    } else {
      if (!stop.value) {
        axios
          .post(props.suggestions, { id: props.modelValue })
          .then(res => {
            if (props.multiple) {
              current.value = 0;
              result.value = res.data;
              res.data.map(async (s, si) => await suggestionClick(si));
              hide(null, true);
            } else {
              current.value = 0;
              result.value = res.data;
              selected.value = res.data[0];
              search.value = cLabel(selected.value);
            }
          })
          .finally(async () => {
            await hide();
            loading.value = false;
          });
      }
    }
    // } else {
    //   reset();
  }
};

const clickListener = e => {
  if (e.target == auto_complete.value || e.composedPath().includes(auto_complete.value)) {
    return false;
  }
  hide(e, true);
};

const getSuggestions = debounce(s => {
  let nr = result.value;
  let nr0 = nr?.length ? nr[0] : {};
  if (!stop.value && s && s !== currentLabel.value) {
    search0.value = s;
    axios
      .post(props.suggestions, { search: s })
      .then(res => {
        result.value = res.data;
        if (result.value.length == 1) {
          suggestionClick(0);
          //   suggestionClick(0, true);
        }
      })
      .finally(() => (loading.value = false));
  } else {
    loading.value = false;
  }
  stop.value = false;
}, 300);

const cLabel = (r, s) => {
  if (r === null) {
    return null;
  } else if (typeof r !== 'object') {
    return r;
  }
  let key = '';
  if (props.labelKey) {
    key = props.labelKey;
  } else if (r.label !== undefined) {
    key = 'label';
  } else if (r.name !== undefined) {
    key = 'name';
  }
  if (s) {
    return key ? r[key].toLowerCase().includes(s.toString().toLowerCase()) : r.toLowerCase().includes(s.toLowerCase());
  }
  return key ? r[key] : r;
};
const cValue = r => {
  if (r === null) {
    return null;
  } else if (typeof r !== 'object') {
    return r;
  }
  let key = '';
  if (props.valueKey) {
    key = props.valueKey;
  } else if (r.value !== undefined) {
    key = 'value';
  } else if (r.id !== undefined) {
    key = 'id';
  }
  return key ? r[key] : r;
};

// const cLabel = (r, search) => {
//   if (r === null) {
//     return null;
//   } else if (typeof r !== 'object') {
//     return r;
//   }
//   let key = '';
//   if (props.labelKey) {
//     key = props.labelKey;
//   } else if (r.label !== undefined) {
//     key = 'label';
//   } else if (r.name !== undefined) {
//     key = 'name';
//   }
//   if (search) {
//     return key ? r[key].toLowerCase().includes(search.toLowerCase()) : r.toLowerCase().includes(search.toLowerCase());
//   }
//   return key ? r[key] : r;
// };
// const cValue = r => {
//   if (r === null) {
//     return null;
//   } else if (typeof r !== 'object') {
//     return r;
//   }
//   let key = '';
//   if (props.valueKey) {
//     key = props.valueKey;
//   } else if (r.value !== undefined) {
//     key = 'value';
//   } else if (r.id !== undefined) {
//     key = 'id';
//   }
//   return key ? r[key] : r;
// };

const show = () => {
  if (props.disabled) {
    return false;
  }
  open.value = true;
  if (!props.multiple) {
    search.value = null;
    input.value.select();
    if (!selected.value) {
      current.value = 0;
    }
    document.getElementById('ac-' + props.id)?.scrollTo({
      top: (document.getElementById('s' + current.value)?.offsetTop || 80) - 80,
    });
  } else {
    if (Array.isArray(props.suggestions)) {
      result.value = props.suggestions;
    }
    // search.value = null;
  }
};

const hide = async (e, force, wait = false) => {
  if (wait) {
    setTimeout(hide, 200);
    return false;
  }
  if (!props.multiple || force) {
    if (selected.value) {
      search.value = currentLabel.value;
    }
    await nextTick();
    setTimeout(() => (open.value = false), 10);
  } else {
    if (multi_selected.value && multi_selected.value.length) {
      search.value = multi_selected.value.map(v => (props.json ? v[props.labelKey] : v)).join(', ');
      search.value = search.value || currentLabel.value;
    }
  }
};

const enter = async e => {
  e.preventDefault();
  e.stopImmediatePropagation();
  suggestionClick(current.value);
  await hide();
  e.stopPropagation();
  return false;
};

const up = e => {
  e.preventDefault();
  e.stopPropagation();
  if (current.value > 0) current.value--;

  if (!props.multiple) {
    document.getElementById('ac-' + props.id)?.scrollTo({
      top: (document.getElementById('s' + current.value)?.offsetTop || 80) - 80,
    });
  }

  return false;
};

const down = e => {
  e.preventDefault();
  e.stopPropagation();
  if (current.value < result.value.length - 1) current.value++;

  if (!props.multiple) {
    document.getElementById('ac-' + props.id)?.scrollTo({
      top: (document.getElementById('s' + current.value)?.offsetTop || 80) - 80,
    });
  }

  return false;
};

const isActive = index => {
  if (props.multiple) {
    return multi_current.value.includes(index);
  }
  return index == current.value;
};

const suggestionClick = async (index, reset = false) => {
  stop.value = true;
  if (index !== undefined) {
    if (props.multiple) {
      if (props.json && multi_selected.value.find(v => v[props.valueKey] == result.value[index][props.valueKey])) {
        multi_selected.value = multi_selected.value.filter(v => v[props.valueKey] != result.value[index][props.valueKey]);
      } else {
        if (multi_selected.value.find(v => v == result.value[index][props.valueKey])) {
          multi_selected.value = multi_selected.value.filter(v => v != result.value[index][props.valueKey]);
        } else {
          multi_selected.value = [...multi_selected.value, result.value[index]];
        }
      }

      // multi_current.value = multi_selected.value.map(s => s[props.valueKey]);
      multi_current.value = [];
      if (props.multiple) {
        result.value.map((s, si) => {
          if (props.json && multi_selected.value.find(v => v[props.valueKey] == s[props.valueKey])) {
            multi_current.value = [...multi_current.value, si];
          } else if (multi_selected.value.includes(s)) {
            multi_current.value = [...multi_current.value, si];
          }
        });
      } else {
        props.suggestions.map((s, si) => {
          if (props.json && multi_selected.value.find(v => v[props.valueKey] == s[props.valueKey])) {
            multi_current.value = [...multi_current.value, si];
          } else if (multi_selected.value.includes(s)) {
            multi_current.value = [...multi_current.value, si];
          }
        });
      }

      emit('update:modelValue', props.json ? multi_selected.value.map(v => v[props.valueKey]) : multi_selected.value);
      emit('change', multi_selected.value);
      // emit('change', props.json ? multi_selected.value.map(v => v[props.valueKey]) : multi_selected.value);
      search.value = multi_selected.value.map(v => (props.json ? v[props.labelKey] : v)).join(', ');
      search.value = search.value || currentLabel.value;
    } else {
      current.value = index;
      selected.value = result.value[index];
      emit('update:modelValue', props.json ? currentValue.value : selected.value);
      emit('change', selected.value);
      // emit('change', props.json ? currentValue.value : selected.value);
      search.value = props.resetSearch ? null : currentLabel.value;
      await hide();
    }
  }
  if (reset) {
    result.value = [];
  }
  if (props.resetSearch) {
    selected.value = null;
    search.value = '';
  }
  if (props.keepFocus) {
    input.value.focus();
  }
};

const reset = async () => {
  search.value = '';
  current.value = 0;
  selected.value = null;
  multi_current.value = [];
  multi_selected.value = [];
  emit('change', null);
  emit('update:modelValue', '');
  await hide();
};
</script>

<template>
  <div ref="excludeRef" class="col-span-6 sm:col-span-4 relative mb-2">
    <div class="flex items-center justify-between">
      <InputLabel :for="id" :value="label" v-if="label" class="inline-block" />
      <button v-if="actionText && action" type="button" @click="action()" class="link text-sm focus:ring-0">{{ actionText }}</button>
    </div>
    <div class="relative flex items-center" ref="auto_complete">
      <label :for="id" class="inline-block cursor-pointer absolute top-2 right-0 border border-transparent p-2 text-gray-500">
        <Icons v-if="!hideIcon" name="chevron-down" size="size-4" />
      </label>
      <button
        type="button"
        tabindex="-1"
        @click="reset()"
        v-if="clearable && modelValue"
        class="cursor-pointer focus:outline-hidden absolute top-2 right-6 border border-transparent p-2 text-gray-500 hover:text-red-500"
      >
        <Icons name="cross" size="size-4" />
      </button>
      <input
        :id="id"
        ref="input"
        @focus="show"
        @click="show"
        @keydown.up="up"
        v-model="search"
        @keydown.down="down"
        autocomplete="none"
        @keydown.left="enter"
        @keydown.right="enter"
        :readonly="!searchable || disabled"
        :placeholder="placeholder || label"
        :class="[
          clearable ? 'pr-16' : 'pr-8',
          inputClass || 'mt-1 border focus rounded-md shadow-xs',
          size == 'small' ? 'py-1.5 pl-2 text-sm' : 'py-2 pl-4 text-base',
          error ? 'border-red-500' : 'border-gray-300 ',
          disabled ? 'opacity-60 focus:border-gray-200 focus:ring-0' : 'focus:border-blue-500 focus:ring-blue-500 ',
        ]"
        class="block w-full"
      />
      <!-- @keyup.enter.stop.prevent="enter" -->
      <div
        v-show="open"
        ref="componentRef"
        class="absolute w-full rounded-md z-20"
        :class="placement == 'top' ? 'mb-1 bottom-full ' + position : 'mt-1 top-full ' + position"
      >
        <ul
          :id="'ac-' + id"
          class="max-h-56 rounded-md bg-white border text-base overflow-auto focus:outline-hidden border-blue-300 ring-2 ring-blue-200/50 sm:text-sm"
        >
          <template v-if="result && result.length">
            <template :key="ri" v-for="(r, ri) in result">
              <!-- v-if="disable && !disabledOptions.includes(cValue(r))" -->
              <template v-if="!(disable && disable == cValue())">
                <li :id="'s' + ri">
                  <button
                    type="button"
                    :id="id + '-' + ri"
                    @click="suggestionClick(ri)"
                    :class="isActive(ri) ? 'bg-blue-500 text-white ' : 'text-focus'"
                    class="w-full text-left flex items-center select-none relative py-2 hover:bg-blue-200 hover:text-blue-800 focus:outline-hidden focus:ring-0 focus:rounded-none"
                  >
                    <span class="ml-3 block font-normal truncate">
                      {{ r.label || cLabel(r) }}
                    </span>
                  </button>
                </li>
              </template>
            </template>
          </template>
          <li v-else class="bg-blue-600 text-white cursor-default select-none relative py-2">
            <div class="flex items-center">
              <span class="ml-3 block font-normal truncate">
                <span v-if="loading">{{ $t('Searching for results') }}...</span>
                <span v-else-if="result == null">{{ $t('Scan barcode or search items for next') }}</span>
                <span v-else-if="json">{{ defaultText || $t('Please type to search') }}</span>
                <span v-else>{{ $t('No suggestions to list.') }}</span>
              </span>
            </div>
          </li>
        </ul>
      </div>
    </div>
    <InputError v-if="error" :message="error.replace(' id', '')" class="mt-0" />
  </div>
</template>
