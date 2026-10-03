<?php

class Product
{
    public static function findCartLines(
        PDO $pdo,
        array $cart,
        bool $forUpdate = false
    ): array {
        $quantities = [];

        foreach ($cart as $productId => $quantity) {
            $productId = (int)$productId;
            $quantity = (int)$quantity;

            if ($productId > 0 && $quantity > 0) {
                $quantities[$productId] = min($quantity, 99);
            }
        }

        if ($quantities === []) {
            return [];
        }

        $placeholders = [];
        $parameters = [];

        foreach (array_keys($quantities) as $index => $productId) {
            $placeholder = ':product_' . $index;
            $placeholders[] = $placeholder;
            $parameters[$placeholder] = $productId;
        }

        $sql = '
			SELECT id, name, product_code, image, price, sale_price, stock, status
			FROM products
			WHERE id IN (' . implode(', ', $placeholders) . ')
		';

        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }

        $statement = $pdo->prepare($sql);
        $statement->execute($parameters);
        $products = $statement->fetchAll();
        $lines = [];

        foreach ($products as $product) {
            $productId = (int)$product['id'];
            $quantity = $quantities[$productId];

            $product['quantity'] = $quantity;
            $product['current_price'] = Pricing::currentFromRow($product);
            $product['line_total'] = $product['current_price'] * $quantity;
            $lines[] = $product;
        }

        return $lines;
    }
}
