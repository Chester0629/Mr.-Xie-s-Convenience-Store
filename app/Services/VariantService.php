<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class VariantService
{
    /**
     * Generate Cartesian product of all attribute values.
     * Returns array of possible combinations.
     *
     * @param Product $product
     * @return array Each element contains array of attribute info
     */
    public function generateCombinations(Product $product): array
    {
        $attributes = $product->attributes()->with('values')->orderBy('display_order')->get();

        if ($attributes->isEmpty()) {
            return [];
        }

        $sets = $attributes->map(
            fn(ProductAttribute $attr) =>
            $attr->values->map(fn(ProductAttributeValue $v) => [
                'attribute_id' => $attr->id,
                'attribute_name' => $attr->name,
                'value_id' => $v->id,
                'value' => $v->value,
                'display_order' => $attr->display_order,
            ])->all()
        )->filter(fn($set) => !empty($set))->values()->all();

        if (empty($sets)) {
            return [];
        }

        return $this->cartesianProduct($sets);
    }

    /**
     * Generate Cartesian product of multiple sets.
     */
    private function cartesianProduct(array $sets): array
    {
        if (empty($sets)) {
            return [[]];
        }

        $result = [[]];
        foreach ($sets as $set) {
            $newResult = [];
            foreach ($result as $existing) {
                foreach ($set as $item) {
                    $newResult[] = array_merge($existing, [$item]);
                }
            }
            $result = $newResult;
        }
        return $result;
    }

    /**
     * Build options array (attribute_id => value_id) from combination.
     */
    public function buildOptionsFromCombination(array $combination): array
    {
        $options = [];
        foreach ($combination as $item) {
            $options[$item['attribute_id']] = $item['value_id'];
        }
        return $options;
    }

    /**
     * Build options_text from combination, respecting display_order.
     */
    public function buildOptionsTextFromCombination(array $combination): string
    {
        // Sort by display_order to ensure consistent ordering
        usort($combination, fn($a, $b) => ($a['display_order'] ?? 0) <=> ($b['display_order'] ?? 0));

        return collect($combination)->pluck('value')->implode(' / ');
    }

    /**
     * Build options_text from options array and product.
     */
    public function buildOptionsTextFromOptions(Product $product, array $options): string
    {
        $attributes = $product->attributes()->with('values')->orderBy('display_order')->get();

        $parts = [];
        foreach ($attributes as $attr) {
            if (isset($options[$attr->id])) {
                $value = $attr->values->firstWhere('id', $options[$attr->id]);
                if ($value) {
                    $parts[] = $value->value;
                }
            }
        }

        return implode(' / ', $parts);
    }

    /**
     * Bulk create variants from combinations.
     *
     * @param Product $product
     * @param array $combinations Combinations from generateCombinations()
     * @param int $basePrice Base price in cents
     * @param int $baseStock Base stock quantity
     * @param int|null $originalPrice Original price in cents
     * @param string|null $skuPrefix SKU prefix (defaults to product ID)
     * @return Collection Created variants
     */
    public function bulkCreateVariants(
        Product $product,
        array $combinations,
        int $basePrice,
        int $baseStock = 0,
        ?int $originalPrice = null,
        ?string $skuPrefix = null
    ): Collection {
        $skuPrefix = $skuPrefix ?? 'P' . str_pad($product->id, 4, '0', STR_PAD_LEFT);
        $createdVariants = collect();

        DB::transaction(function () use ($product, $combinations, $basePrice, $baseStock, $originalPrice, $skuPrefix, &$createdVariants) {
            // Get the highest existing SKU number for this product (including deleted)
            $existingSkus = ProductVariant::withTrashed()
                ->where('product_id', $product->id)
                ->pluck('sku')
                ->toArray();

            $maxSkuNumber = 0;
            foreach ($existingSkus as $sku) {
                if (preg_match('/-(\\d+)$/', $sku, $matches)) {
                    $maxSkuNumber = max($maxSkuNumber, (int) $matches[1]);
                }
            }

            $skuCounter = $maxSkuNumber;

            foreach ($combinations as $combo) {
                $options = $this->buildOptionsFromCombination($combo);
                $optionsText = $this->buildOptionsTextFromCombination($combo);

                // Check if variant with same options already exists (including deleted)
                $existingHash = ProductVariant::generateOptionsHash($options);
                $existingVariant = ProductVariant::withTrashed()
                    ->where('product_id', $product->id)
                    ->where('options_hash', $existingHash)
                    ->first();

                if ($existingVariant) {
                    if ($existingVariant->trashed()) {
                        $existingVariant->restore();
                        $updateData = [
                            'price' => $basePrice,
                            'stock' => $baseStock,
                        ];
                        if ($originalPrice !== null && $originalPrice > 0) {
                            $updateData['original_price'] = $originalPrice;
                        }
                        $existingVariant->update($updateData);
                        $createdVariants->push($existingVariant);
                    }
                } else {
                    $skuCounter++;
                    $sku = $skuPrefix . '-' . str_pad($skuCounter, 3, '0', STR_PAD_LEFT);

                    // Ensure SKU is unique
                    while (in_array($sku, $existingSkus)) {
                        $skuCounter++;
                        $sku = $skuPrefix . '-' . str_pad($skuCounter, 3, '0', STR_PAD_LEFT);
                    }
                    $existingSkus[] = $sku;

                    $variantData = [
                        'product_id' => $product->id,
                        'sku' => $sku,
                        'price' => $basePrice,
                        'stock' => $baseStock,
                        'options' => $options,
                        'options_text' => $optionsText,
                        'options_hash' => $existingHash,
                        'is_default' => $createdVariants->isEmpty() && !$product->variants()->where('is_default', true)->exists(),
                    ];

                    if ($originalPrice !== null && $originalPrice > 0) {
                        $variantData['original_price'] = $originalPrice;
                    }

                    $variant = ProductVariant::create($variantData);
                    $createdVariants->push($variant);
                }
            }

            $product->updatePriceCache();
        });

        return $createdVariants;
    }

    /**
     * Create a single variant.
     */
    public function createVariant(
        Product $product,
        array $options,
        int $price,
        int $stock = 0,
        ?string $sku = null,
        ?string $image = null,
        bool $isDefault = false
    ): ProductVariant {
        $optionsText = $this->buildOptionsTextFromOptions($product, $options);

        if (!$sku) {
            $skuPrefix = 'P' . str_pad($product->id, 4, '0', STR_PAD_LEFT);

            // Find the max SKU number
            $maxSkuNumber = 0;
            $existingSkus = ProductVariant::where('product_id', $product->id)->pluck('sku')->toArray();
            foreach ($existingSkus as $existingSku) {
                if (preg_match('/-(\d+)$/', $existingSku, $matches)) {
                    $maxSkuNumber = max($maxSkuNumber, (int) $matches[1]);
                }
            }

            $skuNumber = $maxSkuNumber + 1;
            $sku = $skuPrefix . '-' . str_pad($skuNumber, 3, '0', STR_PAD_LEFT);

            // Ensure SKU is unique
            while (in_array($sku, $existingSkus)) {
                $skuNumber++;
                $sku = $skuPrefix . '-' . str_pad($skuNumber, 3, '0', STR_PAD_LEFT);
            }
        }

        // If setting as default, unset other defaults
        if ($isDefault) {
            $product->allVariants()->update(['is_default' => false]);
        }

        return ProductVariant::create([
            'product_id' => $product->id,
            'sku' => $sku,
            'price' => $price,
            'stock' => $stock,
            'options' => $options,
            'options_text' => $optionsText,
            'image' => $image,
            'is_default' => $isDefault,
        ]);
    }

    /**
     * Update variant prices in bulk.
     */
    public function bulkUpdatePrice(Product $product, int $price): int
    {
        $count = $product->allVariants()->update(['price' => $price]);
        $product->updatePriceCache();
        return $count;
    }

    /**
     * Update variant stock in bulk.
     */
    public function bulkUpdateStock(Product $product, int $stock): int
    {
        return $product->allVariants()->update(['stock' => $stock]);
    }

    /**
     * Add attribute to product.
     */
    public function addAttribute(Product $product, string $name, int $displayOrder = 0): ProductAttribute
    {
        return ProductAttribute::create([
            'product_id' => $product->id,
            'name' => $name,
            'display_order' => $displayOrder,
        ]);
    }

    /**
     * Add value to attribute.
     */
    public function addAttributeValue(
        ProductAttribute $attribute,
        string $value,
        ?string $colorCode = null,
        int $displayOrder = 0
    ): ProductAttributeValue {
        return ProductAttributeValue::create([
            'attribute_id' => $attribute->id,
            'value' => $value,
            'color_code' => $colorCode,
            'display_order' => $displayOrder,
        ]);
    }

    /**
     * Get valid option combinations for a product.
     * Returns array of options arrays for frontend validation.
     */
    public function getValidCombinations(Product $product): array
    {
        return $product->variants()
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->pluck('options')
            ->toArray();
    }

    /**
     * Find variant by options.
     */
    public function findVariantByOptions(Product $product, array $options): ?ProductVariant
    {
        $hash = ProductVariant::generateOptionsHash($options);
        return $product->variants()->where('options_hash', $hash)->first();
    }
}
