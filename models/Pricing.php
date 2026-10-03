<?php

/**
 * Nguồn duy nhất cho quy tắc giá: sale_price chỉ áp dụng khi 0 < sale_price < price.
 * Phiên bản PHP và các đoạn SQL bên dưới phải luôn đồng nhất.
 */
class Pricing
{
    public static function isOnSale(float $price, ?float $salePrice): bool
    {
        return $salePrice !== null && $salePrice > 0 && $salePrice < $price;
    }

    public static function current(float $price, ?float $salePrice): float
    {
        return self::isOnSale($price, $salePrice) ? $salePrice : $price;
    }

    public static function currentFromRow(array $product): float
    {
        $salePrice = $product['sale_price'] ?? null;

        return self::current(
            (float)$product['price'],
            $salePrice === null ? null : (float)$salePrice
        );
    }

    /** Điều kiện SQL: sản phẩm đang giảm giá. */
    public static function sqlOnSale(string $alias = 'p'): string
    {
        return "({$alias}.sale_price IS NOT NULL AND {$alias}.sale_price > 0 AND {$alias}.sale_price < {$alias}.price)";
    }

    /** Biểu thức SQL: giá bán hiện tại. */
    public static function sqlCurrent(string $alias = 'p'): string
    {
        return '(CASE WHEN ' . self::sqlOnSale($alias)
            . " THEN {$alias}.sale_price ELSE {$alias}.price END)";
    }

    /** Biểu thức SQL: giá gốc để gạch ngang (NULL nếu không giảm giá). */
    public static function sqlOld(string $alias = 'p'): string
    {
        return '(CASE WHEN ' . self::sqlOnSale($alias)
            . " THEN {$alias}.price ELSE NULL END)";
    }
}
