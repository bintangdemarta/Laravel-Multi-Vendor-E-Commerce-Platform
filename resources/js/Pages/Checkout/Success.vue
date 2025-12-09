<template>
  <AppLayout>
    <div class="max-w-2xl mx-auto px-4 py-16 text-center">
      <div class="mb-8">
        <div class="mx-auto w-20 h-20 bg-green-100 rounded-full flex items-center justify-center">
          <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
        </div>
      </div>

      <h1 class="text-3xl font-bold text-gray-900 mb-4">Payment Successful!</h1>
      <p class="text-lg text-gray-600 mb-8">
        Thank you for your order. We've sent a confirmation email to your inbox.
      </p>

      <div v-if="order" class="bg-white rounded-lg shadow p-6 mb-8">
        <div class="border-b pb-4 mb-4">
          <p class="text-sm text-gray-600">Order Number</p>
          <p class="text-2xl font-bold text-gray-900">{{ order.order_number }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4 text-sm">
          <div>
            <p class="text-gray-600">Total Amount</p>
            <p class="font-semibold">{{ formatPrice(order.total) }}</p>
          </div>
          <div>
            <p class="text-gray-600">Payment Status</p>
            <p class="font-semibold text-green-600">Paid</p>
          </div>
        </div>
      </div>

      <div class="flex gap-4 justify-center">
        <Link href="/orders" class="btn-primary">
          View Orders
        </Link>
        <Link href="/" class="btn-secondary">
          Continue Shopping
        </Link>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatPrice } from '@/utils/helpers';

defineProps({
  order: Object,
});
</script>
