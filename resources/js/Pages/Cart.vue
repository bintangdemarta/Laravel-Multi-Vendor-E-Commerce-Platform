<template>
  <AppLayout :cart-count="cart.total_items">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <h1 class="text-3xl font-bold mb-8">Shopping Cart</h1>

      <div v-if="cart.vendors && cart.vendors.length > 0" class="lg:grid lg:grid-cols-3 lg:gap-8">
        <!-- Cart Items -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Group by Vendor -->
          <div
            v-for="vendor in cart.vendors"
            :key="vendor.vendor_id"
            class="bg-white rounded-lg shadow p-6"
          >
            <div class="flex items-center justify-between mb-4 border-b pb-4">
              <h3 class="text-lg font-semibold">{{ vendor.vendor_name }}</h3>
              <span class="text-sm text-gray-600">{{ vendor.items.length }} items</span>
            </div>

            <!-- Vendor Items -->
            <div class="space-y-4">
              <div
                v-for="item in vendor.items"
                :key="item.id"
                class="flex gap-4 py-4 border-b last:border-0"
              >
                <!-- Product Image -->
                <img
                  :src="item.image || '/images/placeholder.jpg'"
                  :alt="item.product_name"
                  class="w-24 h-24 object-cover rounded-lg"
                />

                <!-- Product Info -->
                <div class="flex-1">
                  <Link :href="`/products/${item.product_slug}`" class="font-semibold hover:text-indigo-600">
                    {{ item.product_name }}
                  </Link>
                  <p class="text-sm text-gray-600 mt-1">{{ item.sku_code }}</p>
                  <div class="flex items-center gap-4 mt-2">
                    <span class="text-lg font-bold text-indigo-600">
                      {{ formatPrice(item.price) }}
                    </span>
                    <span v-if="item.weight" class="text-sm text-gray-500">
                      {{ item.weight }}g
                    </span>
                  </div>
                </div>

                <!-- Quantity Controls -->
                <div class="flex flex-col items-end justify-between">
                  <button
                    @click="removeItem(item.id)"
                    class="text-red-600 hover:text-red-700"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>

                  <div class="flex items-center gap-2">
                    <button
                      @click="updateQuantity(item.id, item.quantity - 1)"
                      class="p-1 border rounded hover:bg-gray-50"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                      </svg>
                    </button>
                    <input
                      type="number"
                      :value="item.quantity"
                      @change="(e) => updateQuantity(item.id, parseInt(e.target.value))"
                      min="1"
                      class="w-16 px-2 py-1 border rounded text-center"
                    />
                    <button
                      @click="updateQuantity(item.id, item.quantity + 1)"
                      class="p-1 border rounded hover:bg-gray-50"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                      </svg>
                    </button>
                  </div>

                  <span class="font-semibold">
                    {{ formatPrice(item.subtotal) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Order Summary -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-lg shadow p-6 sticky top-4">
            <h3 class="text-lg font-semibold mb-4">Order Summary</h3>

            <div class="space-y-3 mb-6">
              <div class="flex justify-between">
                <span class="text-gray-600">Subtotal ({{ cart.total_items }} items)</span>
                <span class="font-semibold">{{ formatPrice(cart.subtotal) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Weight</span>
                <span>{{ cart.total_weight }}g</span>
              </div>
            </div>

            <div class="border-t pt-4 mb-6">
              <div class="flex justify-between text-lg font-bold">
                <span>Total</span>
                <span class="text-indigo-600">{{ formatPrice(cart.subtotal) }}</span>
              </div>
              <p class="text-sm text-gray-500 mt-1">
                *Shipping cost calculated at checkout
              </p>
            </div>

            <Link href="/checkout" class="block w-full btn-primary text-center text-lg py-3">
              Proceed to Checkout
            </Link>

            <Link href="/products" class="block text-center text-indigo-600 hover:text-indigo-700 mt-4">
              Continue Shopping
            </Link>
          </div>
        </div>
      </div>

      <!-- Empty Cart -->
      <div v-else class="text-center py-16">
        <svg class="w-24 h-24 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        <h3 class="text-2xl font-semibold text-gray-700 mb-2">Your cart is empty</h3>
        <p class="text-gray-500 mb-6">Start shopping to add items to your cart</p>
        <Link href="/products" class="btn-primary inline-block">
          Browse Products
        </Link>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
  cart: Object,
});

const formatPrice = (price) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(price);
};

const updateQuantity = (itemId, quantity) => {
  router.put(`/api/v1/cart/items/${itemId}`, {
    quantity: Math.max(1, quantity),
  }, {
    preserveScroll: true,
  });
};

const removeItem = (itemId) => {
  if (confirm('Remove this item from cart?')) {
    router.delete(`/api/v1/cart/items/${itemId}`, {
      preserveScroll: true,
    });
  }
};
</script>

<style scoped>
.btn-primary {
  @apply px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-semibold;
}
</style>
