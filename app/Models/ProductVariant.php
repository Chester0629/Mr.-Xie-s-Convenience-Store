<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $product_id
 * @property string $sku
 * @property int $price
 * @property int|null $original_price
 * @property int $stock
 * @property array $options
 * @property string $options_text
 * @property string $options_hash
 * @property string|null $image
 * @property bool $is_default
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Product $product
 */
class ProductVariant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'original_price',
        'stock',
        'options',
        'options_text',
        'options_hash',
        'image',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'options' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'price' => 'integer',
        'original_price' => 'integer',
        'stock' => 'integer',
    ];

    /**
     * Boot the model.
     */
    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Update product price cache after variant changes
        static::saved(function (ProductVariant $variant) {
            $variant->product->updatePriceCache();
        });

        static::deleted(function (ProductVariant $variant) {
            $variant->product->updatePriceCache();
        });
    }

    /**
     * Generate a consistent hash for options array.
     */
    public static function generateOptionsHash(array $options): string
    {
        // Sort by key to ensure consistent ordering
        ksort($options);
        return hash('sha256', json_encode($options));
    }

    /**
     * Relationship to product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the effective image (fallback to product image).
     */
    public function getEffectiveImageAttribute(): ?string
    {
        return $this->image ?? $this->product->image;
    }

    /**
     * Get formatted price.
     */
    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->price);
    }

    /**
     * Check if variant has discount.
     */
    public function hasDiscount(): bool
    {
        return $this->original_price && $this->original_price > $this->price;
    }

    /**
     * Get discount percentage.
     */
    public function getDiscountPercentageAttribute(): ?int
    {
        if (!$this->hasDiscount()) {
            return null;
        }
        return (int) round((1 - $this->price / $this->original_price) * 100);
    }

    /**
     * Check if variant is in stock.
     */
    public function isInStock(): bool
    {
        return $this->stock > 0 && $this->is_active;
    }

    /**
     * Safely decrement stock with lock to prevent overselling.
     * 
     * @param int $quantity Amount to decrement
     * @return bool True if successful, false if insufficient stock
     */
    public function decrementStockSafely(int $quantity): bool
    {
        return DB::transaction(function () use ($quantity) {
            $variant = self::lockForUpdate()->find($this->id);

            if (!$variant || $variant->stock < $quantity) {
                return false;
            }

            $variant->decrement('stock', $quantity);
            return true;
        });
    }

    /**
     * Safely increment stock (for order cancellation/return).
     */
    public function incrementStock(int $quantity): void
    {
        $this->increment('stock', $quantity);
    }

    /**
     * Scope for active variants.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for in-stock variants.
     */
    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }
}
