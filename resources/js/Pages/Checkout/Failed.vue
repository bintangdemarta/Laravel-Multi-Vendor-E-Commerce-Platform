<template>
  <AppLayout>
    <div class="max-w-2xl mx-auto px-4 py-16 text-center">
      <div class="mb-8">
        <div class="mx-auto w-20 h-20 bg-red-100 rounded-full flex items-center justify-center">
          <svg class="w-12 h-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </div>
      </div>

      <h1 class="text-3xl font-bold text-gray-900 mb-4">Payment Failed</h1>
      <p class="text-lg text-gray-600 mb-8">
        Unfortunately, your payment could not be processed. Please try again or use a different payment method.
      </p>

      <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-8">
        <p class="text-sm text-red-800">
          {{ errorMessage || 'An error occurred during payment processing.' }}
        </p>
      </div>

      <div class="flex gap-4 justify-center">
        <Link href="/cart" class="btn-primary">
          Return to Cart
        </Link>
        <Link href="/" class="btn-secondary">
          Go to Home
        </Link>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  errorMessage: String,
});
</script>
