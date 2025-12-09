<template>
  <AppLayout>
    <div class="max-w-4xl mx-auto px-4 py-8">
      <h1 class="text-3xl font-bold mb-8">Checkout</h1>

      <!-- Progress Steps -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div v-for="(step, index) in steps" :key="index" class="flex-1">
            <div class="flex items-center">
              <div 
                :class="[
                  'w-10 h-10 rounded-full flex items-center justify-center',
                  currentStep > index ? 'bg-primary-600 text-white' : 
                  currentStep === index ? 'bg-primary-600 text-white' : 
                  'bg-gray-300 text-gray-600'
                ]"
              >
                {{ index + 1 }}
              </div>
              <div v-if="index < steps.length - 1" class="flex-1 h-1 mx-2 bg-gray-300"></div>
            </div>
            <p class="text-sm mt-2 text-center">{{ step }}</p>
          </div>
        </div>
      </div>

      <!-- Step Content -->
      <div class="bg-white rounded-lg shadow p-6">
        <!-- Step 1: Address -->
        <div v-if="currentStep === 0">
          <h2 class="text-2xl font-semibold mb-4">Shipping Address</h2>
          
          <div v-if="addresses.length === 0" class="text-center py-8">
            <p class="text-gray-600 mb-4">You don't have any saved addresses</p>
            <button @click="showAddressForm = true" class="btn-primary">
              Add New Address
            </button>
          </div>

          <div v-else class="space-y-4">
            <div 
              v-for="address in addresses" 
              :key="address.id"
              @click="selectAddress(address)"
              :class="[
                'border-2 rounded-lg p-4 cursor-pointer transition',
                selectedAddress?.id === address.id ? 'border-primary-600 bg-primary-50' : 'border-gray-200 hover:border-primary-300'
              ]"
            >
              <div class="flex justify-between items-start">
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-2">
                    <h3 class="font-semibold">{{ address.label }}</h3>
                    <span v-if="address.is_default" class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">
                      Default
                    </span>
                  </div>
                  <p class="text-sm font-medium">{{ address.recipient_name }}</p>
                  <p class="text-sm text-gray-600">{{ address.phone }}</p>
                  <p class="text-sm text-gray-600 mt-2">{{ address.address_line }}</p>
                  <p class="text-sm text-gray-600">
                    {{ address.district }}, {{ address.city }}, {{ address.province }} {{ address.postal_code }}
                  </p>
                </div>
                <input 
                  type="radio" 
                  :checked="selectedAddress?.id === address.id"
                  class="mt-1"
                >
              </div>
            </div>

            <button @click="showAddressForm = true" class="btn-secondary w-full">
              Add New Address
            </button>
          </div>

          <!-- Address Form Modal would go here -->
        </div>

        <!-- Step 2: Shipping -->
        <div v-if="currentStep === 1">
          <h2 class="text-2xl font-semibold mb-4">Shipping Method</h2>
          
          <div v-if="loadingShipping" class="text-center py-8">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto"></div>
            <p class="mt-4 text-gray-600">Calculating shipping costs...</p>
          </div>

          <div v-else class="space-y-6">
            <div v-for="(vendor, vendorId) in cartByVendor" :key="vendorId">
              <h3 class="font-semibold mb-3">{{ vendor.name }}</h3>
              
              <div class="space-y-2">
                <div 
                  v-for="option in shippingOptions[vendorId]" 
                  :key="`${vendorId}-${option.service}`"
                  @click="selectShipping(vendorId, option)"
                  :class="[
                    'border-2 rounded-lg p-4 cursor-pointer transition',
                    selectedShipping[vendorId]?.service === option.service ? 'border-primary-600 bg-primary-50' : 'border-gray-200 hover:border-primary-300'
                  ]"
                >
                  <div class="flex justify-between items-center">
                    <div>
                      <p class="font-medium">{{ option.courier_name }} - {{ option.service }}</p>
                      <p class="text-sm text-gray-600">{{ option.description }}</p>
                      <p class="text-sm text-gray-600">ETD: {{ option.etd }} days</p>
                    </div>
                    <div class="text-right">
                      <p class="font-bold text-lg">{{ formatPrice(option.cost) }}</p>
                      <input 
                        type="radio" 
                        :checked="selectedShipping[vendorId]?.service === option.service"
                        class="mt-2"
                      >
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 3: Payment -->
        <div v-if="currentStep === 2">
          <h2 class="text-2xl font-semibold mb-4">Payment Method</h2>
          
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <p class="text-sm text-blue-800">
              You will be redirected to Midtrans secure payment page to complete your payment.
            </p>
          </div>

          <div class="space-y-4">
            <div class="border rounded-lg p-4">
              <h3 class="font-semibold mb-2">Order Summary</h3>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span>Subtotal</span>
                  <span>{{ formatPrice(orderSummary.subtotal) }}</span>
                </div>
                <div class="flex justify-between">
                  <span>Shipping Cost</span>
                  <span>{{ formatPrice(orderSummary.shipping) }}</span>
                </div>
                <div class="flex justify-between">
                  <span>Tax (VAT 11%)</span>
                  <span>{{ formatPrice(orderSummary.tax) }}</span>
                </div>
                <div class="border-t pt-2 flex justify-between font-bold text-lg">
                  <span>Total</span>
                  <span>{{ formatPrice(orderSummary.total) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Navigation Buttons -->
      <div class="flex justify-between mt-6">
        <button 
          v-if="currentStep > 0"
          @click="prevStep"
          class="btn-secondary"
        >
          Previous
        </button>
        <div v-else></div>

        <button 
          v-if="currentStep < steps.length - 1"
          @click="nextStep"
          :disabled="!canProceed"
          class="btn-primary"
          :class="{ 'opacity-50 cursor-not-allowed': !canProceed }"
        >
          Continue
        </button>
        <button 
          v-else
          @click="processPayment"
          :disabled="processing"
          class="btn-primary"
          :class="{ 'opacity-50 cursor-not-allowed': processing }"
        >
          {{ processing ? 'Processing...' : 'Pay Now' }}
        </button>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatPrice } from '@/utils/helpers';

const props = defineProps({
  addresses: Array,
  cart: Object,
});

const steps = ['Address', 'Shipping', 'Payment'];
const currentStep = ref(0);
const selectedAddress = ref(null);
const selectedShipping = ref({});
const shippingOptions = ref({});
const loadingShipping = ref(false);
const processing = ref(false);
const showAddressForm = ref(false);

const cartByVendor = computed(() => {
  const grouped = {};
  props.cart.items.forEach(item => {
    if (!grouped[item.vendor_id]) {
      grouped[item.vendor_id] = {
        name: item.vendor_name,
        items: [],
        weight: 0
      };
    }
    grouped[item.vendor_id].items.push(item);
    grouped[item.vendor_id].weight += item.weight * item.quantity;
  });
  return grouped;
});

const orderSummary = computed(() => {
  const subtotal = props.cart.subtotal || 0;
  const shipping = Object.values(selectedShipping.value).reduce((sum, option) => sum + (option?.cost || 0), 0);
  const tax = Math.round(subtotal * 0.11);
  return {
    subtotal,
    shipping,
    tax,
    total: subtotal + shipping + tax
  };
});

const canProceed = computed(() => {
  if (currentStep.value === 0) return selectedAddress.value !== null;
  if (currentStep.value === 1) {
    return Object.keys(selectedShipping.value).length === Object.keys(cartByVendor.value).length;
  }
  return true;
});

const selectAddress = (address) => {
  selectedAddress.value = address;
};

const selectShipping = (vendorId, option) => {
  selectedShipping.value[vendorId] = option;
};

const nextStep = async () => {
  if (currentStep.value === 0 && selectedAddress.value) {
    await calculateShipping();
  }
  if (canProceed.value) {
    currentStep.value++;
  }
};

const prevStep = () => {
  if (currentStep.value > 0) {
    currentStep.value--;
  }
};

const calculateShipping = async () => {
  loadingShipping.value = true;
  try {
    const response = await axios.post('/api/v1/checkout/calculate-shipping', {
      address_id: selectedAddress.value.id,
      items: props.cart.items
    });
    
    shippingOptions.value = response.data.shipping_options;
  } catch (error) {
    console.error('Failed to calculate shipping:', error);
  } finally {
    loadingShipping.value = false;
  }
};

const processPayment = async () => {
  processing.value = true;
  try {
    const response = await axios.post('/api/v1/checkout/create-order', {
      address_id: selectedAddress.value.id,
      shipping_options: Object.entries(selectedShipping.value).map(([vendorId, option]) => ({
        vendor_id: parseInt(vendorId),
        courier_name: option.courier_name,
        service: option.service,
        cost: option.cost
      }))
    });

    // Redirect to Midtrans payment page
    if (response.data.snap_token) {
      window.snap.pay(response.data.snap_token, {
        onSuccess: () => {
          router.visit('/checkout/success');
        },
        onPending: () => {
          router.visit('/checkout/pending');
        },
        onError: () => {
          router.visit('/checkout/failed');
        },
        onClose: () => {
          processing.value = false;
        }
      });
    }
  } catch (error) {
    console.error('Failed to create order:', error);
    processing.value = false;
  }
};

onMounted(() => {
  // Set default address if exists
  const defaultAddress = props.addresses.find(a => a.is_default);
  if (defaultAddress) {
    selectedAddress.value = defaultAddress;
  }

  // Load Midtrans Snap
  const script = document.createElement('script');
  script.src = 'https://app.sandbox.midtrans.com/snap/snap.js';
  script.setAttribute('data-client-key', window.midtransClientKey);
  document.head.appendChild(script);
});
</script>
