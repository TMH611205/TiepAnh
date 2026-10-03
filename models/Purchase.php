<?php

/**
 * Phiếu nhập hàng (chứng từ mua hàng). Nhập hàng tăng tồn kho và cập nhật giá vốn
 * theo bình quân gia quyền; hủy phiếu trả tồn kho về (chỉ khi còn đủ hàng để trừ).
 */
class Purchase
{
    /**
     * @param array{supplier_name:string, supplier_phone:?string, note:?string} $header
     * @param list<array{product_id:int, quantity:int, unit_cost:float}> $items
     * @return int id phiếu
     */
    public static function create(PDO $pdo, array $header, array $items, ?int $userId): int
    {
        if ($items === []) {
            throw new RuntimeException('Phiếu nhập cần ít nhất một sản phẩm.');
        }

        $pdo->beginTransaction();

        try {
            $lockProduct = $pdo->prepare('SELECT id, name, stock, cost_price, status FROM products WHERE id = :id FOR UPDATE');
            $updateProduct = $pdo->prepare('
                UPDATE products
                SET stock = :stock,
                    cost_price = :cost,
                    status = CASE WHEN status = \'out_of_stock\' THEN \'active\' ELSE status END
                WHERE id = :id
            ');
            $lines = [];
            $total = 0.0;

            foreach ($items as $item) {
                $lockProduct->execute(['id' => $item['product_id']]);
                $product = $lockProduct->fetch();

                if (!$product) {
                    throw new RuntimeException('Có sản phẩm không tồn tại.');
                }

                $quantity = (int)$item['quantity'];
                $unitCost = (float)$item['unit_cost'];
                $oldStock = (int)$product['stock'];
                $oldCost = (float)$product['cost_price'];
                $newStock = $oldStock + $quantity;
                $newCost = ($oldStock > 0 && $oldCost > 0)
                    ? round(($oldStock * $oldCost + $quantity * $unitCost) / $newStock, 2)
                    : $unitCost;

                $updateProduct->execute(['stock' => $newStock, 'cost' => $newCost, 'id' => $product['id']]);

                $lineTotal = $quantity * $unitCost;
                $total += $lineTotal;
                $lines[] = [$product['id'], $product['name'], $quantity, $unitCost, $lineTotal];
            }

            $code = 'PN' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
            $pdo->prepare('
                INSERT INTO purchase_receipts (receipt_code, supplier_name, supplier_phone, note, total_amount, created_by)
                VALUES (:code, :supplier_name, :supplier_phone, :note, :total, :created_by)
            ')->execute([
                'code' => $code,
                'supplier_name' => $header['supplier_name'],
                'supplier_phone' => $header['supplier_phone'],
                'note' => $header['note'],
                'total' => $total,
                'created_by' => $userId,
            ]);
            $receiptId = (int)$pdo->lastInsertId();

            $insertItem = $pdo->prepare('
                INSERT INTO purchase_receipt_items (receipt_id, product_id, product_name, quantity, unit_cost, total)
                VALUES (:receipt_id, :product_id, :product_name, :quantity, :unit_cost, :total)
            ');

            foreach ($lines as [$productId, $name, $quantity, $unitCost, $lineTotal]) {
                $insertItem->execute([
                    'receipt_id' => $receiptId,
                    'product_id' => $productId,
                    'product_name' => $name,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'total' => $lineTotal,
                ]);
            }

            $pdo->commit();

            return $receiptId;
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $error;
        }
    }

    /** Hủy phiếu: trừ lại tồn kho đã nhập. Giá vốn bình quân không được tính lại. */
    public static function cancel(PDO $pdo, int $receiptId): string
    {
        $pdo->beginTransaction();

        try {
            $statement = $pdo->prepare('SELECT * FROM purchase_receipts WHERE id = :id FOR UPDATE');
            $statement->execute(['id' => $receiptId]);
            $receipt = $statement->fetch();

            if (!$receipt) {
                throw new RuntimeException('Không tìm thấy phiếu nhập.');
            }

            if ($receipt['status'] === 'cancelled') {
                throw new RuntimeException('Phiếu này đã được hủy trước đó.');
            }

            $items = $pdo->prepare('SELECT product_id, product_name, quantity FROM purchase_receipt_items WHERE receipt_id = :id');
            $items->execute(['id' => $receiptId]);
            $lock = $pdo->prepare('SELECT stock FROM products WHERE id = :id FOR UPDATE');
            $reduce = $pdo->prepare('
                UPDATE products
                SET stock = stock - :quantity,
                    -- MySQL đánh giá SET từ trái sang phải nên ở đây `stock` đã là giá trị mới
                    status = CASE WHEN stock = 0 AND status = \'active\' THEN \'out_of_stock\' ELSE status END
                WHERE id = :id
            ');

            foreach ($items->fetchAll() as $item) {
                $lock->execute(['id' => $item['product_id']]);

                if ((int)$lock->fetchColumn() < (int)$item['quantity']) {
                    throw new RuntimeException(
                        'Không thể hủy: tồn kho "' . $item['product_name'] . '" hiện ít hơn số lượng đã nhập (đã bán bớt).'
                    );
                }

                $reduce->execute([
                    'quantity' => (int)$item['quantity'],
                    'id' => $item['product_id'],
                ]);
            }

            $pdo->prepare('UPDATE purchase_receipts SET status = \'cancelled\' WHERE id = :id')->execute(['id' => $receiptId]);
            $pdo->commit();

            return 'Đã hủy phiếu ' . $receipt['receipt_code'] . ' và trừ lại tồn kho.';
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $error;
        }
    }
}
