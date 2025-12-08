<template>
  <AppLayout :cart-count="cartCount">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">
          Shop from Thousands of Vendors
        </h1>
        <p class="text-xl mb-8">
          Find the best deals from trusted sellers across Indonesia
        </p>
        <div class="max- md:w-2/3 lg:w-1/2">
          <input
            type="search"
            placeholder="What are you looking for?"
            class="w-full px-6 py-4 text-gray-900 rounded-lg focus:ring-2 focus:ring-white"
          />
        </div>
      </div>
    </div>

    <!-- Categories -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <h2 class="text-2xl font-bold mb-6">Shop by Category</h2>
      <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <div
          v-for="category in categories"
          :key="category.id"
          class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition cursor-pointer text-center"
        >
          <div class="text-4xl mb-2">{{ category.icon }}</div>
          <h3 class="font-semibold">{{ category.name }}</h3>
        </div>
      </div>
    </div>

    <!-- Featured Products -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Featured Products</h2>
        <Link href="/products" class="text-indigo-600 hover:text-indigo-700">
          View All →
        </Link>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <div
          v-for="product in featuredProducts"
          :key="product.id"
          class="bg-white rounded-lg shadow hover:shadow-xl transition"
        >
          <img
            :src="product.image"
            :alt="product.name"
            class="w-full h-48 object-cover rounded-t-lg"
          />
          <div class="p-4">
            <h3 class="font-semibold text-lg mb-2 line-clamp-2">
              {{ product.name }}
            </h3>
            <p class="text-gray-600 text-sm mb-2">{{ product.vendor }}</p>
            <div class="flex items-center justify-between">
              <span class="text-2xl font-bold text-indigo-600">
                {{ formatPrice(product.price) }}
              </span>
              <button class="btn-primary text-sm">
                Add to Cart
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Top Vendors -->
    <div class="bg-gray-100 py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold mb-6">Top Vendors</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
          <div
            v-for="vendor in topVendors"
            :key="vendor.id"
            class="bg-white p-6 rounded-lg shadow text-center hover:shadow-lg transition"
          >
            <img
              :src="vendor.logo"
              :alt="vendor.name"
              class="w-20 h-20 mx-auto mb-4 rounded-full object-cover"
            />
            <h3 class="font-semibold">{{ vendor.name }}</h3>
            <p class="text-sm text-gray-600">{{ vendor.city }}</p>
            <p class="text-xs text-gray-500 mt-2">{{ vendor.products_count }} products</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
  categories: Array,
  featuredProducts: Array,
  topVendors: Array,
  cartCount: Number,
});

const formatPrice = (price) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(price);
};
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.btn-primary {
  @apply px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition;
}
</style>
