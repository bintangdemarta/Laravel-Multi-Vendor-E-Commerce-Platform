<template>
  <AppLayout :cart-count="cartCount">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Breadcrumb -->
      <nav class="mb-6 text-sm">
        <Link href="/" class="text-gray-500 hover:text-gray-700">Home</Link>
        <span class="mx-2 text-gray-400">/</span>
        <span class="text-gray-900">Products</span>
      </nav>

      <div class="lg:grid lg:grid-cols-4 lg:gap-8">
        <!-- Filters Sidebar -->
        <div class="hidden lg:block">
          <div class="bg-white p-6 rounded-lg shadow-sm">
            <h3 class="text-lg font-semibold mb-4">Filters</h3>

            <!-- Categories -->
            <div class="mb-6">
              <h4 class="font-medium mb-3">Categories</h4>
              <div class="space-y-2">
                <label
                  v-for="category in categories"
                  :key="category.id"
                  class="flex items-center"
                >
                  <input
                    type="checkbox"
                    :value="category.id"
                    v-model="selectedCategories"
                    class="rounded text-indigo-600 focus:ring-indigo-500"
                  />
                  <span class="ml-2 text-sm">{{ category.name }}</span>
                </label>
              </div>
            </div>

            <!-- Price Range -->
            <div class="mb-6">
              <h4 class="font-medium mb-3">Price Range</h4>
              <div class="space-y-2">
                <input
                  type="number"
                  v-model="priceMin"
                  placeholder="Min"
                  class="w-full px-3 py-2 border rounded-lg"
                />
                <input
                  type="number"
                  v-model="priceMax"
                  placeholder="Max"
                  class="w-full px-3 py-2 border rounded-lg"
                />
              </div>
            </div>

            <!-- Brands -->
            <div class="mb-6">
              <h4 class="font-medium mb-3">Brands</h4>
              <div class="space-y-2 max-h-48 overflow-y-auto">
                <label
                  v-for="brand in brands"
                  :key="brand.id"
                  class="flex items-center"
                >
                  <input
                    type="checkbox"
                    :value="brand.id"
                    v-model="selectedBrands"
                    class="rounded text-indigo-600 focus:ring-indigo-500"
                  />
                  <span class="ml-2 text-sm">{{ brand.name }}</span>
                </label>
              </div>
            </div>

            <button @click="applyFilters" class="w-full btn-primary">
              Apply Filters
            </button>
          </div>
        </div>

        <!-- Products Grid -->
        <div class="lg:col-span-3">
          <!-- Sort & View Options -->
          <div class="flex justify-between items-center mb-6">
            <p class="text-gray-600">
              Showing {{ products.data.length }} of {{ products.total }} products
            </p>
            <select
              v-model="sortBy"
              @change="applySort"
              class="px-4 py-2 border rounded-lg"
            >
              <option value="latest">Latest</option>
              <option value="price_low">Price: Low to High</option>
              <option value="price_high">Price: High to Low</option>
              <option value="popular">Most Popular</option>
            </select>
          </div>

          <!-- Products -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div
              v-for="product in products.data"
              :key="product.id"
              class="bg-white rounded-lg shadow hover:shadow-xl transition group"
            >
              <Link :href="`/products/${product.slug}`" class="block">
                <img
                  :src="product.image || '/images/placeholder.jpg'"
                  :alt="product.name"
                  class="w-full h-48 object-cover rounded-t-lg group-hover:opacity-90 transition"
                />
              </Link>
              <div class="p-4">
                <Link :href="`/products/${product.slug}`">
                  <h3 class="font-semibold text-lg mb-2 line-clamp-2 hover:text-indigo-600">
                    {{ product.name }}
                  </h3>
                </Link>
                <p class="text-gray-600 text-sm mb-2">{{ product.vendor_name }}</p>
                
                <!-- Rating -->
                <div class="flex items-center mb-2">
                  <div class="flex text-yellow-400">
                    <span v-for="i in 5" :key="i">
                      {{ i <= Math.floor(product.average_rating) ? '★' : '☆' }}
                    </span>
                  </div>
                  <span class="text-sm text-gray-600 ml-2">
                    ({{ product.total_reviews }})
                  </span>
                </div>

                <div class="flex items-center justify-between">
                  <div>
                    <span class="text-2xl font-bold text-indigo-600">
                      {{ formatPrice(product.min_price) }}
                    </span>
                    <span v-if="product.max_price > product.min_price" class="text-sm text-gray-500">
                      - {{ formatPrice(product.max_price) }}
                    </span>
                  </div>
                  <button
                    @click="addToCart(product)"
                    class="p-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div class="mt-8 flex justify-center">
            <nav class="flex space-x-2">
              <Link
                v-for="link in products.links"
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
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  products: Object,
  categories: Array,
  brands: Array,
  filters: Object,
  cartCount: Number,
});

const selectedCategories = ref(props.filters.categories || []);
const selectedBrands = ref(props.filters.brands || []);
const priceMin = ref(props.filters.price_min || '');
const priceMax = ref(props.filters.price_max || '');
const sortBy = ref(props.filters.sort || 'latest');

const formatPrice = (price) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(price);
};

const applyFilters = () => {
  router.get('/products', {
    categories: selectedCategories.value,
    brands: selectedBrands.value,
    price_min: priceMin.value,
    price_max: priceMax.value,
    sort: sortBy.value,
  }, {
    preserveState: true,
  });
};

const applySort = () => {
  applyFilters();
};

const addToCart = (product) => {
  router.post('/api/v1/cart/items', {
    sku_id: product.default_sku_id,
    quantity: 1,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      alert('Product added to cart!');
    },
  });
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
