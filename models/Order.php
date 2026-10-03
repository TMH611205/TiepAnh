<?php

require_once __DIR__ . '/Product.php';

class Order
{
    public static function create(
        PDO $pdo,
        array $customer,
        array $cart,
        string $paymentMethod
    ): string {
        if (!in_array($paymentMethod, ['cod', 'bank_transfer'], true)) {
            throw new InvalidArgumentException('Phương thức thanh toán không hợp lệ.');
        }

        $pdo->beginTransaction();

        try {
            $lines = Product::findCartLines($pdo, $cart, true);
            $expectedProducts = array_filter(
                $cart,
                static fn($quantity): bool => (int)$quantity > 0
            );

            if (count($lines) !== count($expectedProducts)) {
                throw new RuntimeException('Một số sản phẩm không còn khả dụng.');
            }

            $subtotal = 0;

            foreach ($lines as $line) {
                if (
                    $line['status'] !== 'active' ||
                    (int)$line['stock'] < (int)$line['quantity']
                ) {
                    throw new RuntimeException(
                        'Sản phẩm "' . $line['name'] . '" không đủ tồn kho.'
                    );
                }

                $subtotal += (float)$line['line_total'];
            }

            $customerId = self::saveCustomer($pdo, $customer);
            $orderCode = 'TA' . date('ymdHis') . strtoupper(bin2hex(random_bytes(3)));

            $orderStatement = $pdo->prepare('
				INSERT INTO orders (
					order_code,
					customer_id,
					customer_name,
					customer_phone,
					customer_email,
                    customer_cccd,
					shipping_address,
					subtotal,
					shipping_fee,
					discount,
					total_amount,
					payment_method,
					payment_status,
					order_status,
					note
				) VALUES (
					:order_code,
					:customer_id,
					:customer_name,
					:customer_phone,
					:customer_email,
                    :customer_cccd,
					:shipping_address,
					:subtotal,
					0,
					0,
					:total_amount,
					:payment_method,
					\'pending\',
					\'pending\',
					:note
				)
			');

            $orderStatement->execute([
                'order_code' => $orderCode,
                'customer_id' => $customerId,
                'customer_name' => trim($customer['full_name']),
                'customer_phone' => trim($customer['phone']),
                'customer_email' => trim($customer['email'] ?? '') ?: null,
                'customer_cccd' => trim($customer['cccd'] ?? '') ?: null,
                'shipping_address' => trim($customer['address']),
                'subtotal' => $subtotal,
                'total_amount' => $subtotal,
                'payment_method' => $paymentMethod,
                'note' => trim($customer['note'] ?? '') ?: null,
            ]);

            $orderId = (int)$pdo->lastInsertId();
            $detailStatement = $pdo->prepare('
				INSERT INTO order_details (
					order_id,
					product_id,
					product_name,
					quantity,
					price,
					total
				) VALUES (
					:order_id,
					:product_id,
					:product_name,
					:quantity,
					:price,
					:total
				)
			');
            $stockStatement = $pdo->prepare('
				UPDATE products
				SET stock = stock - :quantity
				WHERE id = :product_id AND stock >= :required_quantity
			');

            foreach ($lines as $line) {
                $quantity = (int)$line['quantity'];

                $stockStatement->execute([
                    'quantity' => $quantity,
                    'product_id' => (int)$line['id'],
                    'required_quantity' => $quantity,
                ]);

                if ($stockStatement->rowCount() !== 1) {
                    throw new RuntimeException(
                        'Tồn kho vừa thay đổi. Vui lòng kiểm tra lại giỏ hàng.'
                    );
                }

                $detailStatement->execute([
                    'order_id' => $orderId,
                    'product_id' => (int)$line['id'],
                    'product_name' => $line['name'],
                    'quantity' => $quantity,
                    'price' => $line['current_price'],
                    'total' => $line['line_total'],
                ]);
            }

            $paymentStatement = $pdo->prepare('
				INSERT INTO payments (order_id, payment_method, amount, status)
				VALUES (:order_id, :payment_method, :amount, \'pending\')
			');
            $paymentStatement->execute([
                'order_id' => $orderId,
                'payment_method' => $paymentMethod,
                'amount' => $subtotal,
            ]);

            $invoiceStatement = $pdo->prepare('
                INSERT INTO invoices (
                    order_id,
                    customer_name,
                    customer_cccd,
                    customer_address,
                    customer_email,
                    subtotal,
                    tax_amount,
                    total_amount,
                    status
                ) VALUES (
                    :order_id,
                    :customer_name,
                    :customer_cccd,
                    :customer_address,
                    :customer_email,
                    :subtotal,
                    0,
                    :total_amount,
                    \'draft\'
                )
            ');
            $invoiceStatement->execute([
                'order_id' => $orderId,
                'customer_name' => trim($customer['full_name']),
                'customer_cccd' => trim($customer['cccd']),
                'customer_address' => trim($customer['address']),
                'customer_email' => trim($customer['email']),
                'subtotal' => $subtotal,
                'total_amount' => $subtotal,
            ]);

            $pdo->commit();

            return $orderCode;
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $error;
        }
    }

    public static function findByCode(PDO $pdo, string $orderCode): ?array
    {
        $statement = $pdo->prepare('
			SELECT o.*, p.status AS payment_record_status
			FROM orders o
			LEFT JOIN payments p ON p.order_id = o.id
			WHERE o.order_code = :order_code
			LIMIT 1
		');
        $statement->execute(['order_code' => $orderCode]);
        $order = $statement->fetch();

        return $order ?: null;
    }

    public static function findItems(PDO $pdo, int $orderId): array
    {
        $statement = $pdo->prepare('
			SELECT product_name, quantity, price, total
			FROM order_details
			WHERE order_id = :order_id
			ORDER BY id ASC
		');
        $statement->execute(['order_id' => $orderId]);

        return $statement->fetchAll();
    }

    private static function saveCustomer(PDO $pdo, array $customer): int
    {
        $phone = trim($customer['phone']);
        $statement = $pdo->prepare('
			SELECT id
			FROM customers
			WHERE phone = :phone
			ORDER BY id DESC
			LIMIT 1
			FOR UPDATE
		');
        $statement->execute(['phone' => $phone]);
        $customerId = $statement->fetchColumn();

        if ($customerId !== false) {
            $update = $pdo->prepare('
				UPDATE customers
				SET full_name = :full_name,
					email = :email,
                    cccd_number = :cccd_number,
					address = :address
				WHERE id = :id
			');
            $update->execute([
                'full_name' => trim($customer['full_name']),
                'email' => trim($customer['email'] ?? '') ?: null,
                'cccd_number' => trim($customer['cccd'] ?? '') ?: null,
                'address' => trim($customer['address']),
                'id' => (int)$customerId,
            ]);

            return (int)$customerId;
        }

        $insert = $pdo->prepare('
            INSERT INTO customers (full_name, phone, email, cccd_number, address)
            VALUES (:full_name, :phone, :email, :cccd_number, :address)
		');
        $insert->execute([
            'full_name' => trim($customer['full_name']),
            'phone' => $phone,
            'email' => trim($customer['email'] ?? '') ?: null,
            'cccd_number' => trim($customer['cccd'] ?? '') ?: null,
            'address' => trim($customer['address']),
        ]);

        return (int)$pdo->lastInsertId();
    }
}
