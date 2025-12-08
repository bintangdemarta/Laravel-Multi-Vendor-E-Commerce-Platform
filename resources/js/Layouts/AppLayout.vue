<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <Link href="/" class="text-2xl font-bold text-indigo-600">
              Marketplace
            </Link>
          </div>

          <!-- Search -->
          <div class="flex-1 max-w-2xl mx-8 flex items-center">
            <div class="w-full">
              <input
                type="search"
                placeholder="Search products..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              />
            </div>
          </div>

          <!-- Right Menu -->
          <div class="flex items-center space-x-4">
            <Link
              href="/cart"
              class="relative p-2 text-gray-600 hover:text-indigo-600"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-500 rounded-full">
                {{ cartCount }}
              </span>
            </Link>

            <div v-if="$page.props.auth.user" class="flex items-center space-x-4">
              <Link href="/orders" class="text-gray-600 hover:text-indigo-600">
                Orders
              </Link>
              <Link href="/profile" class="text-gray-600 hover:text-indigo-600">
                Profile
              </Link>
            </div>
            <div v-else class="flex items-center space-x-4">
              <Link href="/login" class="text-gray-600 hover:text-indigo-600">
                Login
              </Link>
              <Link href="/register" class="btn-primary">
                Register
              </Link>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main>
      <slot />
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
          <div>
            <h3 class="text-lg font-bold mb-4">About Us</h3>
            <p class="text-gray-400">
              Indonesia's trusted multi-vendor e-commerce platform
            </p>
          </div>
          <div>
            <h3 class="text-lg font-bold mb-4">Customer Service</h3>
            <ul class="space-y-2 text-gray-400">
              <li><a href="#" class="hover:text-white">Help Center</a></li>
              <li><a href="#" class="hover:text-white">How to Buy</a></li>
              <li><a href="#" class="hover:text-white">Shipping</a></li>
            </ul>
          </div>
          <div>
            <h3 class="text-lg font-bold mb-4">For Sellers</h3>
            <ul class="space-y-2 text-gray-400">
              <li><a href="#" class="hover:text-white">Become a Seller</a></li>
              <li><a href="#" class="hover:text-white">Seller Center</a></li>
            </ul>
          </div>
          <div>
            <h3 class="text-lg font-bold mb-4">Payment</h3>
            <div class="flex space-x-2">
              <img src="/images/payment/visa.svg" alt="Visa" class="h-8" />
              <img src="/images/payment/mastercard.svg" alt="Mastercard" class="h-8" />
              <img src="/images/payment/gopay.svg" alt="GoPay" class="h-8" />
            </div>
          </div>
        </div>
        <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
          <p>&copy; 2024 Multi-Vendor Marketplace. All rights reserved.</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  cartCount: {
    type: Number,
    default: 0,
  },
});
</script>

<style scoped>
.btn-primary {
  @apply px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition;
}
</style>
