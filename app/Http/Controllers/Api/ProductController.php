<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /**
     * List products with filters
     * GET /api/v1/products
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['vendor', 'category', 'brand', 'skus'])
            ->published();

        // Filter by category
        if ($request->has('category')) {
            $query->where('category_id', $request->category);
        }

        // Filter by vendor
        if ($request->has('vendor')) {
            $query->where('vendor_id', $request->vendor);
        }

        // Filter by brand
        if ($request->has('brand')) {
            $query->where('brand_id', $request->brand);
        }

        // Featured products
        if ($request->boolean('featured')) {
            $query->featured();
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        if ($sortBy === 'price') {
            // Sort by minimum SKU price
            $query->withMin('skus', 'price');
            $query->orderBy('skus_min_price', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $products = $query->paginate($request->get('per_page', 20));

        return response()->json($products);
    }

    /**
     * Get product detail
     * GET /api/v1/products/{slug}
     */
    public function show(string $slug): JsonResponse
    {
        $product = Product::with([
            'vendor',
            'category',
            'brand',
            'skus.attributeOptions.attribute',
            'reviews' => fn($q) => $q->approved()->latest()->limit(10),
        ])->where('slug', $slug)->firstOrFail();

        return response()->json([
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'short_description' => $product->short_description,
                'vendor' => [
                    'id' => $product->vendor->id,
                    'name' => $product->vendor->shop_name,
                    'city' => $product->vendor->city,
                ],
                'category' => [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                ],
                'brand' => $product->brand ? [
                    'id' => $product->brand->id,
                    'name' => $product->brand->name,
                ] : null,
                'price_range' => [
                    'min' => $product->getMinPrice(),
                    'max' => $product->getMaxPrice(),
                ],
                'rating' => [
                    'average' => $product->getAverageRating(),
                    'total' => $product->getTotalReviews(),
                ],
                'skus' => $product->skus->map(fn($sku) => [
                    'id' => $sku->id,
                    'sku_code' => $sku->sku_code,
                    'price' => $sku->price,
                    'compare_at_price' => $sku->compare_at_price,
                    'stock' => $sku->getAvailableStock(),
                    'is_active' => $sku->is_active,
                    'variants' => $sku->attributeOptions->mapWithKeys(fn($opt) => [
                        $opt->attribute->name => $opt->value
                    ]),
                ]),
                'reviews' => $product->reviews->map(fn($review) => [
                    'id' => $review->id,
                    'user_name' => $review->user->name,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at->diffForHumans(),
                ]),
            ],
        ]);
    }

    /**
     * Search products
     * GET /api/v1/products/search
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q');

        if (!$query) {
            return response()->json([
                'products' => [],
                'total' => 0,
            ]);
        }

        // Use Laravel Scout for search
        $products = Product::search($query)
            ->where('status', 'published')
            ->paginate($request->get('per_page', 20));

        return response()->json($products);
    }
}
