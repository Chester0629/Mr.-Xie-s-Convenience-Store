<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    /**
     * Get paginated products for public listing.
     * 
     * @param array $filters ['category' => string|int, 'search' => string, 'page' => int]
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getPublicProducts(array $filters = [])
    {
        $query = Product::with('category');

        // If no filters, use cache for default landing page
        if (empty($filters) || (count($filters) == 1 && isset($filters['page']))) {
            $page = $filters['page'] ?? 1;
            return Cache::remember("products_active_page_{$page}", 300, function () {
                return Product::with('category')->where('status', 'active')->paginate(20);
            });
        }

        // Filter by category
        if (!empty($filters['category'])) {
            $categoryInput = $filters['category'];

            if (is_numeric($categoryInput)) {
                $query->where('category_id', $categoryInput);
            } else {
                $query->whereHas('category', function ($q) use ($categoryInput) {
                    $q->where('name', $categoryInput)
                        ->orWhere('slug', $categoryInput);
                });
            }
        }

        // Search by name
        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->where('status', 'active')->paginate(20);
    }

    /**
     * Get paginated products for admin listing.
     */
    public function getAdminProducts()
    {
        return Product::with('category')->latest()->paginate(20);
    }

    /**
     * Get a single product by ID with all relations.
     */
    public function getProduct(int $id): array
    {
        $product = Product::with([
            'category',
            'store',
            'reviews',
            'defaultVariant',
            'variants' => fn($q) => $q->orderBy('is_default', 'desc'),
            'attributes.values',
        ])->findOrFail($id);

        // Build valid combinations for frontend variant selector
        $validCombinations = $product->variants
            ->where('is_active', true)
            ->pluck('options')
            ->values()
            ->toArray();

        // Get stock combinations (which combos are in stock)
        $inStockCombinations = $product->variants
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->pluck('options')
            ->values()
            ->toArray();

        return [
            'product' => $product,
            'valid_combinations' => $validCombinations,
            'in_stock_combinations' => $inStockCombinations,
        ];
    }

    /**
     * Create a new product.
     * 
     * @param array $data Product data
     * @param \Illuminate\Http\UploadedFile|null $image
     * @param int|null $userStoreId Fallback store ID from authenticated user
     * @return Product
     */
    public function createProduct(array $data, $image = null, ?int $userStoreId = null): Product
    {
        $productData = $this->prepareProductData($data, $userStoreId);

        // Handle image upload - use configured disk (s3 in production, public locally)
        if ($image) {
            $disk = config('filesystems.default');
            $productData['image'] = $image->store('images', $disk);
        }

        $product = Product::create($productData);
        $this->clearCache();

        return $product->load('category');
    }

    /**
     * Update an existing product.
     * 
     * @param int $id
     * @param array $data
     * @param \Illuminate\Http\UploadedFile|null $image
     * @return Product
     */
    public function updateProduct(int $id, array $data, $image = null): Product
    {
        $product = Product::findOrFail($id);
        $productData = $this->prepareProductData($data, null, false);

        // Handle image upload - use configured disk
        if ($image) {
            $disk = config('filesystems.default');
            // Delete old image
            if ($product->image && Storage::disk($disk)->exists($product->image)) {
                Storage::disk($disk)->delete($product->image);
            }
            $productData['image'] = $image->store('images', $disk);
        }

        $product->update($productData);
        $this->clearCache();

        return $product->load('category');
    }

    /**
     * Delete a product.
     */
    public function deleteProduct(int $id): void
    {
        Product::destroy($id);
        $this->clearCache();
    }

    /**
     * Get all categories.
     */
    public function getCategories()
    {
        return Cache::remember('categories', 3600, function () {
            return Category::orderBy('name')->get(['id', 'name', 'slug']);
        });
    }

    /**
     * Prepare product data for create/update.
     */
    protected function prepareProductData(array $data, ?int $userStoreId = null, bool $requireStore = true): array
    {
        $productData = collect($data)->only([
            'name',
            'price',
            'original_price',
            'information',
            'status',
            'stock',
            'store_id'
        ])->toArray();

        // Handle category
        if (isset($data['category'])) {
            $categoryInput = $data['category'];

            if (is_numeric($categoryInput)) {
                $productData['category_id'] = (int) $categoryInput;
            } else {
                $category = Category::firstOrCreate(
                    ['name' => $categoryInput],
                    ['slug' => Str::slug($categoryInput)]
                );
                $productData['category_id'] = $category->id;
            }
        }

        // Resolve store_id for new products
        if ($requireStore && !isset($productData['store_id'])) {
            if ($userStoreId) {
                $productData['store_id'] = $userStoreId;
            } else {
                $existingStore = \App\Models\Store::first();
                if (!$existingStore) {
                    abort(422, 'No store available. Please create a store first.');
                }
                $productData['store_id'] = $existingStore->id;
            }
        }

        // Ensure prices are integers
        if (isset($productData['price'])) {
            $productData['price'] = (int) $productData['price'];
        }
        if (isset($productData['original_price'])) {
            $productData['original_price'] = (int) $productData['original_price'];
        }

        return $productData;
    }

    /**
     * Clear product-related caches.
     * Uses pattern-based clearing for better scalability.
     */
    protected function clearCache(): void
    {
        Cache::forget('categories');

        // Clear all paginated product caches using pattern matching
        // Note: For Redis, use Cache::tags(['products'])->flush() for better performance
        $cacheStore = config('cache.default');

        if ($cacheStore === 'redis' || $cacheStore === 'memcached') {
            // These drivers support tags - ideal solution
            try {
                Cache::tags(['products'])->flush();
            } catch (\Exception $e) {
                // Fallback if tags not configured
                $this->clearPaginatedCache();
            }
        } else {
            // Fallback for file/array drivers
            $this->clearPaginatedCache();
        }
    }

    /**
     * Clear paginated product cache (fallback method).
     */
    protected function clearPaginatedCache(): void
    {
        // Clear reasonable number of cached pages
        for ($i = 1; $i <= 50; $i++) {
            Cache::forget("products_active_page_{$i}");
        }
    }
}
