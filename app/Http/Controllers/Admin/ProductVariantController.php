<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductVariant;
use App\Services\VariantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductVariantController extends Controller
{
    public function __construct(
        private VariantService $variantService
    ) {}

    /**
     * Get all variants for a product.
     */
    public function index(Product $product): JsonResponse
    {
        $product->load([
            'attributes.values',
            'variants' => fn($q) => $q->withTrashed(),
        ]);

        return response()->json([
            'product' => $product->only(['id', 'name', 'has_variants', 'price', 'stock']),
            'attributes' => $product->attributes,
            'variants' => $product->allVariants,
            'valid_combinations' => $this->variantService->getValidCombinations($product),
        ]);
    }

    /**
     * Add attribute to product.
     */
    public function storeAttribute(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $attribute = $this->variantService->addAttribute(
            $product,
            $validated['name'],
            $validated['display_order'] ?? $product->attributes()->count()
        );

        return response()->json($attribute->load('values'), 201);
    }

    /**
     * Update attribute.
     */
    public function updateAttribute(Request $request, ProductAttribute $attribute): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'display_order' => 'sometimes|integer|min:0',
        ]);

        $attribute->update($validated);

        // Rebuild options_text for all variants if name changed
        if (isset($validated['name'])) {
            $this->rebuildOptionsText($attribute->product);
        }

        return response()->json($attribute->load('values'));
    }

    /**
     * Delete attribute (and its values).
     */
    public function destroyAttribute(ProductAttribute $attribute): JsonResponse
    {
        $product = $attribute->product;

        // Delete all variants that use this attribute
        $product->allVariants()->delete();

        $attribute->delete();
        $product->updatePriceCache();

        return response()->json(['message' => '屬性已刪除']);
    }

    /**
     * Add value to attribute.
     */
    public function storeAttributeValue(Request $request, ProductAttribute $attribute): JsonResponse
    {
        $validated = $request->validate([
            'value' => 'required|string|max:100',
            'color_code' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $value = $this->variantService->addAttributeValue(
            $attribute,
            $validated['value'],
            $validated['color_code'] ?? null,
            $validated['display_order'] ?? $attribute->values()->count()
        );

        return response()->json($value, 201);
    }

    /**
     * Update attribute value.
     */
    public function updateAttributeValue(Request $request, ProductAttributeValue $value): JsonResponse
    {
        $validated = $request->validate([
            'value' => 'sometimes|string|max:100',
            'color_code' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'display_order' => 'sometimes|integer|min:0',
        ]);

        $value->update($validated);

        // Rebuild options_text for variants using this value
        if (isset($validated['value'])) {
            $this->rebuildOptionsText($value->attribute->product);
        }

        return response()->json($value);
    }

    /**
     * Delete attribute value.
     */
    public function destroyAttributeValue(ProductAttributeValue $value): JsonResponse
    {
        $product = $value->attribute->product;

        // Delete variants that use this value
        $valueId = $value->id;
        $product->allVariants()->get()->each(function ($variant) use ($valueId) {
            if (in_array($valueId, array_values($variant->options))) {
                $variant->delete();
            }
        });

        $value->delete();
        $product->updatePriceCache();

        return response()->json(['message' => '屬性值已刪除']);
    }

    /**
     * Create a single variant.
     */
    public function storeVariant(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'sku' => 'nullable|string|max:50|unique:product_variants,sku',
            'price' => 'required|integer|min:0',
            'original_price' => 'nullable|integer|min:0',
            'stock' => 'required|integer|min:0',
            'options' => 'required|array',
            'options.*' => 'required|integer|exists:product_attribute_values,id',
            'image' => 'nullable|string|max:255',
            'is_default' => 'nullable|boolean',
        ]);

        // Validate options belong to product's attributes
        $validValueIds = $product->attributes()->with('values')->get()
            ->flatMap(fn($attr) => $attr->values->pluck('id'))
            ->toArray();

        foreach ($validated['options'] as $valueId) {
            if (!in_array($valueId, $validValueIds)) {
                return response()->json(['message' => '無效的規格選項'], 422);
            }
        }

        $variant = $this->variantService->createVariant(
            $product,
            $validated['options'],
            $validated['price'],
            $validated['stock'],
            $validated['sku'] ?? null,
            $validated['image'] ?? null,
            $validated['is_default'] ?? false
        );

        if (isset($validated['original_price'])) {
            $variant->update(['original_price' => $validated['original_price']]);
        }

        return response()->json($variant, 201);
    }

    /**
     * Update variant.
     */
    public function updateVariant(Request $request, ProductVariant $variant): JsonResponse
    {
        $validated = $request->validate([
            'sku' => ['sometimes', 'string', 'max:50', Rule::unique('product_variants')->ignore($variant->id)],
            'price' => 'sometimes|integer|min:0',
            'original_price' => 'nullable|integer|min:0',
            'stock' => 'sometimes|integer|min:0',
            'image' => 'nullable|string|max:255',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        // If setting as default, unset other defaults
        if (($validated['is_default'] ?? false) === true) {
            $variant->product->allVariants()->where('id', '!=', $variant->id)->update(['is_default' => false]);
        }

        $variant->update($validated);

        return response()->json($variant);
    }

    /**
     * Delete variant.
     */
    public function destroyVariant(ProductVariant $variant): JsonResponse
    {
        $product = $variant->product;
        $variant->delete();
        $product->updatePriceCache();

        return response()->json(['message' => '規格已刪除']);
    }

    /**
     * Bulk generate variants from all attribute combinations.
     */
    public function bulkGenerate(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'original_price' => 'nullable|integer|min:0',
            'base_price' => 'required|integer|min:0',
            'base_stock' => 'required|integer|min:0',
        ]);

        $combinations = $this->variantService->generateCombinations($product);

        if (empty($combinations)) {
            return response()->json(['message' => '沒有可用的屬性組合，請先新增屬性和屬性值'], 422);
        }

        $created = $this->variantService->bulkCreateVariants(
            $product,
            $combinations,
            $validated['base_price'],
            $validated['base_stock'],
            $validated['original_price'] ?? null
        );

        return response()->json([
            'message' => "已建立 {$created->count()} 個規格組合",
            'created_count' => $created->count(),
            'total_combinations' => count($combinations),
            'variants' => $created,
        ], 201);
    }

    /**
     * Bulk update all variant prices.
     */
    public function bulkUpdatePrice(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'price' => 'required|integer|min:0',
        ]);

        $count = $this->variantService->bulkUpdatePrice($product, $validated['price']);

        return response()->json([
            'message' => "已更新 {$count} 個規格的價格",
            'updated_count' => $count,
        ]);
    }

    /**
     * Bulk update all variant stock.
     */
    public function bulkUpdateStock(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'stock' => 'required|integer|min:0',
        ]);

        $count = $this->variantService->bulkUpdateStock($product, $validated['stock']);

        return response()->json([
            'message' => "已更新 {$count} 個規格的庫存",
            'updated_count' => $count,
        ]);
    }

    /**
     * Rebuild options_text for all variants of a product.
     */
    private function rebuildOptionsText(Product $product): void
    {
        $product->allVariants()->each(function ($variant) use ($product) {
            $variant->update([
                'options_text' => $this->variantService->buildOptionsTextFromOptions($product, $variant->options),
            ]);
        });
    }
}
