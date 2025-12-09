<template>
  <AppLayout :cart-count="0">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <h1 class="text-3xl font-bold mb-8">My Orders</h1>

      <!-- Order List -->
      <div v-if="orders.data && orders.data.length > 0" class="space-y-4">
        <div
          v-for="order in orders.data"
          :key="order.id"
          class="bg-white rounded-lg shadow hover:shadow-md transition"
        >
          <div class="p-6">
            <!-- Order Header -->
            <div class="flex justify-between items-start mb-4 border-b pb-4">
              <div>
                <Link :href="`/orders/${order.order_number}`" class="text-lg font-semibold text-indigo-600 hover:text-indigo-700">
                  {{ order.order_number }}
                </Link>
                <p class="text-sm text-gray-500 mt-1">
                  {{ formatDate(order.created_at) }}
                </p>
              </div>
              <div class="text-right">
                <span :class="getStatusBadgeClass(order.status)" class="px-3 py-1 rounded-full text-sm font-semibold">
                  {{ order.status.toUpperCase() }}
                </span>
                <p class="text-lg font-bold text-gray-900 mt-2">
                  {{ formatPrice(order.total) }}
                </p>
              </div>
            </div>

            <!-- Order Items Preview -->
            <div class="space-y-3 mb-4">
              <div
                v-for="item in order.items.slice(0, 3)"
                :key="item.id"
                class="flex gap-4"
              >
                <img
                  :src="item.image || '/images/placeholder.jpg'"
                  :alt="item.product_name"
                  class="w-16 h-16 object-cover rounded"
                />
                <div class="flex-1">
                  <p class="font-medium">{{ item.product_name }}</p>
                  <p class="text-sm text-gray-600">{{ item.quantity }}x {{ formatPrice(item.price) }}</p>
                </div>
              </div>
              <p v-if="order.items.length > 3" class="text-sm text-gray-500">
                +{{ order.items.length - 3 }} more items
              </p>
            </div>

            <!-- Actions -->
            <div class="flex justify-between items-center pt-4 border-t">
              <div class="flex gap-2">
                <span v-if="order.payment_status" class="text-sm text-gray-600">
                  Payment: <span class="font-medium">{{ order.payment_status }}</span>
                </span>
              </div>
              <div class="flex gap-2">
                <Link
                  :href="`/orders/${order.order_number}`"
                  class="btn-outline text-sm"
                >
                  View Details
                </Link>
                <button
                  v-if="canCancel(order)"
                  @click="cancelOrder(order.order_number)"
                  class="btn-outline text-sm text-red-600 border-red-600 hover:bg-red-50"
                >
                  Cancel Order
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-16">
        <svg class="w-24 h-24 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="text-2xl font-semibold text-gray-700 mb-2">No orders yet</h3>
        <p class="text-gray-500 mb-6">When you place orders, they will appear here</p>
        <Link href="/products" class="btn-primary inline-block">
          Start Shopping
        </Link>
      </div>

      <!-- Pagination -->
      <div v-if="orders.links && orders.links.length > 3" class="mt-8 flex justify-center">
        <nav class="flex space-x-2">
          <Link
            v-for="link in orders.links"
            :key="link.label"
            :href="link.url"
            :class="[
              'px-4 py-2 border rounded-lg',
              link.active
                ? 'bg-indigo-600 text-white border-indigo-600'
                : 'bg-white text-gray-700 hover:bg-gray-50'
            ]"
            v-html="link.label"
          />
        </nav>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
  orders: Object,
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

const cancelOrder = (orderNumber) => {
  if (confirm('Are you sure you want to cancel this order?')) {
    router.post(`/api/v1/orders/${orderNumber}/cancel`, {}, {
      onSuccess: () => {
        alert('Order cancelled successfully');
      },
    });
  }
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
