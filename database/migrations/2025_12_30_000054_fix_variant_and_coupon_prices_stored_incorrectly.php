<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix prices that were incorrectly stored multiplied by 100.
     * 
     * This migration corrects the data stored by AdminProductVariants.vue and AdminCoupon.vue
     * which incorrectly multiplied prices by 100 before sending to the backend.
     * 
     * The backend expects prices in TWD (dollars), not cents.
     * 
     * IMPORTANT: Run this migration only ONCE. If prices look correct, you may skip this.
     */
    public function up(): void
    {
        // Fix product_variants table
        // Only fix prices that appear to be multiplied by 100 (divisible by 100 and > 1000)
        DB::table('product_variants')
            ->where('price', '>=', 10000) // Likely affected: prices >= 10000 (should be >= 100)
            ->whereRaw('price % 100 = 0') // Price is divisible by 100
            ->update([
                'price' => DB::raw('price / 100')
            ]);

        DB::table('product_variants')
            ->whereNotNull('original_price')
            ->where('original_price', '>=', 10000)
            ->whereRaw('original_price % 100 = 0')
            ->update([
                'original_price' => DB::raw('original_price / 100')
            ]);

        // Fix coupons table - discount_amount for fixed type
        DB::table('coupons')
            ->where('type', 'fixed')
            ->where('discount_amount', '>=', 100) // Likely affected
            ->whereRaw('discount_amount % 100 = 0')
            ->update([
                'discount_amount' => DB::raw('discount_amount / 100')
            ]);

        // Fix coupons table - limit_price
        DB::table('coupons')
            ->whereNotNull('limit_price')
            ->where('limit_price', '>=', 10000)
            ->whereRaw('limit_price % 100 = 0')
            ->update([
                'limit_price' => DB::raw('limit_price / 100')
            ]);
    }

    /**
     * Reverse the migrations.
     * 
     * WARNING: This will multiply all prices by 100, which may not be desired
     * if some records were already correct.
     */
    public function down(): void
    {
        // This is a data fix, reverting might cause issues
        // We don't reverse this migration as it could corrupt correct data
    }
};
