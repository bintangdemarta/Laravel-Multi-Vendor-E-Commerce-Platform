<template>
  <AppLayout :cart-count="cartCount">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Breadcrumb -->
      <nav class="mb-6 text-sm">
        <Link href="/" class="text-gray-500 hover:text-gray-700">Home</Link>
        <span class="mx-2 text-gray-400">/</span>
        <Link href="/products" class="text-gray-500 hover:text-gray-700">Products</Link>
        <span class="mx-2 text-gray-400">/</span>
        <span class="text-gray-900">{{ product.name }}</span>
      </nav>

      <div class="lg:grid lg:grid-cols-2 lg:gap-12">
        <!-- Product Images -->
        <div>
          <div class="bg-white rounded-lg shadow-lg p-4 mb-4">
            <img
              :src="selectedImage"
              :alt="product.name"
              class="w-full h-96 object-contain"
            />
          </div>
          <div class="grid grid-cols-4 gap-4">
            <img
              v-for="(image, index) in product.images"
              :key="index"
              :src="image"
              @click="selectedImage = image"
              :class="[
                'w-full h-24 object-cover rounded-lg cursor-pointer border-2 transition',
                selectedImage === image ? 'border-indigo-600' : 'border-gray-200 hover:border-gray-400'
              ]"
            />
          </div>
        </div>

        <!-- Product Info -->
        <div>
          <h1 class="text-3xl font-bold mb-4">{{ product.name }}</h1>

          <!-- Vendor -->
          <div class="flex items-center mb-4">
            <span class="text-gray-600">Sold by:</span>
            <Link :href="`/vendors/${product.vendor.id}`" class="ml-2 text-indigo-600 hover:underline font-medium">
              {{ product.vendor.name }}
            </Link>
            <span class="ml-2 text-gray-500">| {{ product.vendor.city }}</span>
          </div>

          <!-- Rating -->
          <div class="flex items-center mb-6">
            <div class="flex text-yellow-400 text-xl">
              <span v-for="i in 5" :key="i">
                {{ i <= Math.floor(product.rating.average) ? '★' : '☆' }}
              </span>
            </div>
            <span class="ml-2 text-gray-600">
              {{ product.rating.average.toFixed(1) }}
            </span>
            <span class="ml-2 text-gray-500">
              ({{ product.rating.total }} reviews)
            </span>
          </div>

          <!-- Price -->
          <div class="mb-6">
            <div class="flex items-baseline">
              <span class="text-4xl font-bold text-indigo-600">
                {{ formatPrice(selectedSku?.price || product.price_range.min) }}
              </span>
              <span v-if="selectedSku?.compare_at_price" class="ml-4 text-xl text-gray-500 line-through">
                {{ formatPrice(selectedSku.compare_at_price) }}
              </span>
              <span v-if="selectedSku?.compare_at_price" class="ml-2 text-sm bg-red-100 text-red-600 px-2 py-1 rounded">
                {{ getDiscountPercentage(selectedSku) }}% OFF
              </span>
            </div>
          </div>

          <!-- Variants -->
          <div v-if="product.variants && product.variants.length > 0" class="mb-6">
            <div v-for="variant in groupedVariants" :key="variant.name" class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ variant.name }}
              </label>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="option in variant.options"
                  :key="option"
                  @click="selectVariant(variant.name, option)"
                  :class="[
                    'px-4 py-2 border rounded-lg transition',
                    selectedVariants[variant.name] === option
                      ? 'border-indigo-600 bg-indigo-50 text-indigo-600'
                      : 'border-gray-300 hover:border-gray-400'
                  ]"
                >
                  {{ option }}
                </button>
              </div>
            </div>
          </div>

          <!-- Quantity -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Quantity
            </label>
            <div class="flex items-center space-x-4">
              <button
                @click="quantity = Math.max(1, quantity - 1)"
                class="p-2 border rounded-lg hover:bg-gray-50"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                </svg>
              </button>
              <input
                type="number"
                v-model="quantity"
                min="1"
                :max="selectedSku?.stock || 999"
                class="w-20 px-4 py-2 border rounded-lg text-center"
              />
              <button
                @click="quantity++"
                class="p-2 border rounded-lg hover:bg-gray-50"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
              </button>
              <span class="text-gray-600">
                {{ selectedSku?.stock || 0 }} available
              </span>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex space-x-4 mb-8">
            <button
              @click="addToCart"
              :disabled="!selectedSku || selectedSku.stock === 0"
              class="flex-1 btn-primary text-lg py-3 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Add to Cart
            </button>
            <button
              @click="buyNow"
              :disabled="!selectedSku || selectedSku.stock === 0"
              class="flex-1 btn-secondary text-lg py-3 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Buy Now
            </button>
          </div>

          <!-- Description -->
          <div class="border-t pt-6">
            <h3 class="text-xl font-semibold mb-4">Product Description</h3>
            <div class="prose max-w-none" v-html="product.description"></div>
          </div>
        </div>
      </div>

      <!-- Reviews Section -->
      <div class="mt-12 border-t pt-8">
        <h3 class="text-2xl font-bold mb-6">Customer Reviews</h3>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="review in product.reviews"
            :key="review.id"
            class="bg-white p-6 rounded-lg shadow"
          >
            <div class="flex items-center mb-4">
              <div class="flex text-yellow-400">
                <span v-for="i in 5" :key="i">
                  {{ i <= review.rating ? '★' : '☆' }}
                </span>
              </div>
              <span class="ml-2 font-medium">{{ review.user_name }}</span>
            </div>
            <p class="text-gray-700 mb-2">{{ review.comment }}</p>
            <p class="text-sm text-gray-500">{{ review.created_at }}</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
  product: Object,
  cartCount: Number,
});

const selectedImage = ref(props.product.images?.[0] || '/images/placeholder.jpg');
const selectedVariants = ref({});
const quantity = ref(1);

// Group variants by attribute name
const groupedVariants = computed(() => {
  const groups = {};
  
  props.product.skus?.forEach(sku => {
    Object.entries(sku.variants || {}).forEach(([name, value]) => {
      if (!groups[name]) {
        groups[name] = { name, options: new Set() };
      }
      groups[name].options.add(value);
    });
  });

  return Object.values(groups).map(group => ({
    name: group.name,
    options: Array.from(group.options),
  }));
});

// Find matching SKU based on selected variants
const selectedSku = computed(() => {
  return props.product.skus?.find(sku => {
    return Object.entries(selectedVariants.value).every(([key, value]) => {
      return sku.variants?.[key] === value;
    });
  }) || props.product.skus?.[0];
});

const formatPrice = (price) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(price);
};

const getDiscountPercentage = (sku) => {
  if (!sku.compare_at_price) return 0;
  return Math.round(((sku.compare_at_price - sku.price) / sku.compare_at_price) * 100);
};

const selectVariant = (name, value) => {
  selectedVariants.value[name] = value;
};

const addToCart = () => {
  if (!selectedSku.value) {
    alert('Please select product variant');
    return;
  }

  router.post('/api/v1/cart/items', {
    sku_id: selectedSku.value.id,
    quantity: quantity.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      alert('Product added to cart!');
    },
  });
};

const buyNow = () => {
  addToCart();
  setTimeout(() => {
    router.visit('/cart');
  }, 500);
};
</script>

<style scoped>
.btn-primary {
  @apply px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-semibold;
}

.btn-secondary {
  @apply px-6 py-3 bg-white text-indigo-600 border-2 border-indigo-600 rounded-lg hover:bg-indigo-50 transition font-semibold;
}
</style>
