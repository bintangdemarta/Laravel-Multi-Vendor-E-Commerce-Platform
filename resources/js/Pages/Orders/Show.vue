<template>
  <AppLayout :cart-count="0">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Back Button -->
      <Link href="/orders" class="inline-flex items-center text-indigo-600 hover:text-indigo-700 mb-6">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Back to Orders
      </Link>

      <div v-if="order" class="grid lg:grid-cols-3 gap-8">
        <!-- Order Details -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Order Header -->
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-start mb-4">
              <div>
                <h1 class="text-2xl font-bold">{{ order.order_number }}</h1>
                <p class="text-gray-600 mt-1">
                  Placed on {{ formatDate(order.created_at) }}
                </p>
              </div>
              <span :class="getStatusBadgeClass(order.status)" class="px-4 py-2 rounded-full text-sm font-semibold">
                {{ order.status.toUpperCase() }}
              </span>
            </div>
          </div>

          <!-- Order Items by Vendor -->
          <div
            v-for="vendor in order.vendors"
            :key="vendor.vendor_id"
            class="bg-white rounded-lg shadow p-6"
          >
            <h3 class="text-lg font-semibold mb-4 border-b pb-3">
              {{ vendor.vendor_name }}
            </h3>

            <div class="space-y-4">
              <div
                v-for="item in vendor.items"
                :key="item.id"
                class="flex gap-4 pb-4 border-b last:border-0"
              >
                <img
                  :src="item.image || '/images/placeholder.jpg'"
                  :alt="item.product_name"
                  class="w-24 h-24 object-cover rounded-lg"
                />
                <div class="flex-1">
                  <h4 class="font-semibold">{{ item.product_name }}</h4>
                  <p v-if="item.variant" class="text-sm text-gray-600 mt-1">
                    {{ formatVariant(item.variant) }}
                  </p>
                  <div class="flex items-baseline gap-2 mt-2">
                    <span class="text-lg font-bold text-indigo-600">
                      {{ formatPrice(item.price) }}
                    </span>
                    <span class="text-gray-600">x {{ item.quantity }}</span>
                  </div>
                </div>
                <div class="text-right">
                  <p class="font-semibold">
                    {{ formatPrice(item.subtotal) }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Shipping Info -->
            <div v-if="vendor.shipping" class="mt-4 pt-4 border-t bg-gray-50 -mx-6 -mb-6 px-6 py-4 rounded-b-lg">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Shipping</span>
                <span class="font-medium">
                  {{ vendor.shipping.courier }} - {{ vendor.shipping.service }}
                  ({{ formatPrice(vendor.shipping.cost) }})
                </span>
              </div>
              <div v-if="vendor.tracking_number" class="flex justify-between text-sm mt-2">
                <span class="text-gray-600">Tracking Number</span>
                <span class="font-mono font-medium">{{ vendor.tracking_number }}</span>
              </div>
            </div>
          </div>

          <!-- Order Timeline -->
          <div v-if="order.history && order.history.length > 0" class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Order Timeline</h3>
            <div class="space-y-4">
              <div
                v-for="(item, index) in order.history"
                :key="index"
                class="flex gap-4"
              >
                <div class="flex flex-col items-center">
                  <div class="w-3 h-3 bg-indigo-600 rounded-full"></div>
                  <div v-if="index < order.history.length - 1" class="w-0.5 h-full bg-gray-300 mt-1"></div>
                </div>
                <div class="flex-1 pb-4">
                  <p class="font-medium">{{ item.status }}</p>
                  <p v-if="item.notes" class="text-sm text-gray-600 mt-1">{{ item.notes }}</p>
                  <p class="text-xs text-gray-500 mt-1">{{ formatDate(item.created_at) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Order Summary Sidebar -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-lg shadow p-6 sticky top-4 space-y-6">
            <!-- Shipping Address -->
            <div>
              <h3 class="font-semibold mb-3">Shipping Address</h3>
              <div class="text-sm space-y-1">
                <p class="font-medium">{{ order.shipping_address.name }}</p>
                <p class="text-gray-600">{{ order.shipping_address.phone }}</p>
                <p class="text-gray-600">{{ order.shipping_address.address }}</p>
                <p class="text-gray-600">
                  {{ order.shipping_address.city }}, {{ order.shipping_address.province }}
                </p>
                <p class="text-gray-600">{{ order.shipping_address.postal_code }}</p>
              </div>
            </div>

            <!-- Payment Info -->
            <div v-if="order.payment" class="border-t pt-4">
              <h3 class="font-semibold mb-3">Payment Information</h3>
              <div class="text-sm space-y-2">
                <div class="flex justify-between">
                  <span class="text-gray-600">Method</span>
                  <span class="font-medium">{{ order.payment.method }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Status</span>
                  <span :class="order.payment.status === 'success' ? 'text-green-600' : 'text-yellow-600'" class="font-medium">
                    {{ order.payment.status }}
                  </span>
                </div>
                <div v-if="order.payment.paid_at" class="flex justify-between">
                  <span class="text-gray-600">Paid At</span>
                  <span class="font-medium">{{ formatDate(order.payment.paid_at) }}</span>
                </div>
              </div>
            </div>

            <!-- Price Breakdown -->
            <div class="border-t pt-4">
              <h3 class="font-semibold mb-3">Order Summary</h3>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span class="text-gray-600">Subtotal</span>
                  <span>{{ formatPrice(order.subtotal) }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Shipping</span>
                  <span>{{ formatPrice(order.shipping_cost) }}</span>
                </div>
                <div v-if="order.tax" class="flex justify-between">
                  <span class="text-gray-600">Tax</span>
                  <span>{{ formatPrice(order.tax.total) }}</span>
                </div>
                <div class="flex justify-between font-bold text-lg pt-2 border-t">
                  <span>Total</span>
                  <span class="text-indigo-600">{{ formatPrice(order.total) }}</span>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="border-t pt-4 space-y-2">
              <button
                v-if="canCancel(order)"
                @click="cancelOrder"
                class="w-full btn-outline text-red-600 border-red-600 hover:bg-red-50"
              >
                Cancel Order
              </button>
              <button
                v-if="canReorder(order)"
                @click="reorder"
                class="w-full btn-primary"
              >
                Order Again
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
  order: Object,
});

const formatPrice = (price) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(price);
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const formatVariant = (variant) => {
  return Object.entries(variant || {})
    .map(([key, value]) => `${key}: ${value}`)
    .join(', ');
};

const getStatusBadgeClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    paid: 'bg-blue-100 text-blue-800',
    processing: 'bg-purple-100 text-purple-800',
    shipped: 'bg-indigo-100 text-indigo-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
    refunded: 'bg-gray-100 text-gray-800',
  };
  return classes[status] || classes.pending;
};

const canCancel = (order) => {
  return ['pending', 'paid'].includes(order.status);
};

const canReorder = (order) => {
  return ['completed', 'cancelled'].includes(order.status);
};

const cancelOrder = () => {
  if (confirm('Are you sure you want to cancel this order?')) {
    router.post(`/api/v1/orders/${props.order.order_number}/cancel`, {}, {
      onSuccess: () => {
        alert('Order cancelled successfully');
      },
    });
  }
};

const reorder = () => {
  // Implementation would add all items back to cart
  alert('Reorder functionality coming soon!');
};
</script>

<style scoped>
.btn-outline {
  @apply px-4 py-2 bg-transparent border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-semibold;
}

.btn-primary {
  @apply px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-semibold;
}
</style>
