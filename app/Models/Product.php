<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $name
 * @property int $price
 * @property int|null $original_price
 * @property string|null $information
 * @property string|null $image
 * @property int|null $category_id
 * @property string $status
 * @property int $stock
 * @property bool $has_variants
 * @property int|null $price_min
 * @property int|null $price_max
 * @property int|null $store_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Store|null $store
 * @property-read \App\Models\Category|null $category
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductVariant[] $variants
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductAttribute[] $attributes
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'price',
        'original_price',
        'information',
        'image',
        'category_id',
        'status',
        'stock',
        'store_id',
        'has_variants',
        'price_min',
        'price_max',
    ];

    protected $casts = [
        'has_variants' => 'boolean',
        'price' => 'integer',
        'original_price' => 'integer',
        'price_min' => 'integer',
        'price_max' => 'integer',
        'stock' => 'integer',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the reviews for the product.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get active variants for this product.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->where('is_active', true);
    }

    /**
     * Get all variants including inactive.
     */
    public function allVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Get product attributes.
     */
    public function attributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class)->orderBy('display_order');
    }

    /**
     * Get the default variant.
     */
    public function defaultVariant(): HasOne
    {
        return $this->hasOne(ProductVariant::class)->where('is_default', true);
    }

    /**
     * Get price range display string.
     */
    public function getPriceRangeAttribute(): ?string
    {
        if (!$this->has_variants || !$this->price_min) {
            return null;
        }

        if ($this->price_min === $this->price_max) {
            return '$' . number_format($this->price_min);
        }

        return '$' . number_format($this->price_min) . ' 起';
    }

    /**
     * Get the effective display price (variant price range or base price).
     */
    public function getDisplayPriceAttribute(): int
    {
        if ($this->has_variants && $this->price_min) {
            return $this->price_min;
        }
        return $this->price;
    }

    /**
     * Update cached price range from variants.
     */
    public function updatePriceCache(): void
    {
        $activeVariants = $this->allVariants()->where('is_active', true);
        $prices = $activeVariants->pluck('price');

        $this->update([
            'price_min' => $prices->min(),
            'price_max' => $prices->max(),
            'has_variants' => $prices->isNotEmpty(),
        ]);
    }

    /**
     * Get total stock (sum of variant stocks or product stock).
     */
    public function getTotalStockAttribute(): int
    {
        if ($this->has_variants) {
            return $this->variants()->sum('stock');
        }
        return $this->stock;
    }

    /**
     * Scope to filter by category (using category_id or category name/slug).
     */
    public function scopeInCategory($query, $categoryIdentifier)
    {
        if (is_numeric($categoryIdentifier)) {
            return $query->where('category_id', $categoryIdentifier);
        }

        return $query->whereHas('category', function ($q) use ($categoryIdentifier) {
            $q->where('name', $categoryIdentifier)
                ->orWhere('slug', $categoryIdentifier);
        });
    }

    /**
     * Default low stock threshold.
     * Products with stock below this are considered low.
     */
    public const LOW_STOCK_THRESHOLD = 10;

    /**
     * Check if product has low stock.
     */
    public function isLowStock(?int $threshold = null): bool
    {
        $threshold = $threshold ?? self::LOW_STOCK_THRESHOLD;
        return $this->total_stock <= $threshold;
    }

    /**
     * Check if product is out of stock.
     */
    public function isOutOfStock(): bool
    {
        return $this->total_stock <= 0;
    }

    /**
     * Scope to get products with low stock.
     */
    public function scopeLowStock($query, ?int $threshold = null)
    {
        $threshold = $threshold ?? self::LOW_STOCK_THRESHOLD;
        return $query->where('stock', '<=', $threshold)->where('stock', '>', 0);
    }

    /**
     * Scope to get products that are out of stock.
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('stock', '<=', 0);
    }
}
