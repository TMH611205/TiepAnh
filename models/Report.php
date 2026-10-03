<?php

/**
 * Danh mục báo cáo thống kê dùng chung cho dashboard và trợ lý AI.
 * AI chỉ được CHỌN một báo cáo trong danh mục này kèm tham số; mọi câu SQL nằm trong file
 * này và dùng tham số ràng buộc, nên AI không thể truy vấn tự do hay đọc dữ liệu ngoài danh mục.
 */
class Report
{
    /** @return array<string, array{title:string, description:string, params:list<string>}> */
    public static function catalog(): array
    {
        return [
            'revenue_by_period' => [
                'title' => 'Doanh thu theo thời gian',
                'description' => 'Số đơn và doanh thu (không tính đơn hủy), gộp theo ngày hoặc theo tháng.',
                'params' => ['from', 'to', 'group'],
            ],
            'top_products' => [
                'title' => 'Sản phẩm bán chạy',
                'description' => 'Xếp hạng sản phẩm theo số lượng bán và doanh thu.',
                'params' => ['from', 'to', 'limit'],
            ],
            'sales_by_category' => [
                'title' => 'Doanh thu theo danh mục',
                'description' => 'Số lượng bán và doanh thu của từng danh mục sản phẩm.',
                'params' => ['from', 'to'],
            ],
            'orders_by_status' => [
                'title' => 'Đơn hàng theo trạng thái',
                'description' => 'Số đơn và giá trị theo từng trạng thái xử lý.',
                'params' => ['from', 'to'],
            ],
            'top_customers' => [
                'title' => 'Khách hàng mua nhiều nhất',
                'description' => 'Xếp hạng khách theo tổng tiền đã mua (không tính đơn hủy).',
                'params' => ['from', 'to', 'limit'],
            ],
            'purchases_by_supplier' => [
                'title' => 'Chi phí nhập hàng theo nhà cung cấp',
                'description' => 'Số phiếu nhập, số lượng và tổng tiền nhập theo nhà cung cấp.',
                'params' => ['from', 'to'],
            ],
            'profit_estimate' => [
                'title' => 'Ước tính lợi nhuận gộp',
                'description' => 'Doanh thu, giá vốn ước tính (số lượng bán × giá vốn hiện tại), chi nhập hàng và lãi gộp ước tính.',
                'params' => ['from', 'to'],
            ],
            'low_stock' => [
                'title' => 'Sản phẩm sắp hết hàng',
                'description' => 'Sản phẩm có tồn kho nhỏ hơn hoặc bằng ngưỡng.',
                'params' => ['threshold'],
            ],
            'inventory_value' => [
                'title' => 'Giá trị tồn kho',
                'description' => 'Tồn kho, giá vốn và giá trị tồn của từng sản phẩm đang kinh doanh.',
                'params' => [],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $params
     * @return array{key:string, title:string, subtitle:string, headers:list<string>, rows:list<list<mixed>>}
     */
    public static function run(PDO $pdo, string $key, array $params = []): array
    {
        $catalog = self::catalog();

        if (!isset($catalog[$key])) {
            throw new InvalidArgumentException('Báo cáo không tồn tại.');
        }

        [$from, $to, $endExclusive] = self::period($params);
        $limit = max(1, min(50, (int)($params['limit'] ?? 10)));
        $subtitle = 'Từ ' . date('d/m/Y', strtotime($from)) . ' đến ' . date('d/m/Y', strtotime($to));
        $range = ['from_date' => $from, 'to_date' => $endExclusive];

        switch ($key) {
            case 'revenue_by_period':
                $byMonth = ($params['group'] ?? 'day') === 'month';
                $format = $byMonth ? '%Y-%m' : '%Y-%m-%d';
                $statement = $pdo->prepare("
                    SELECT DATE_FORMAT(created_at, '$format') AS period, COUNT(*) AS orders, COALESCE(SUM(total_amount), 0) AS revenue
                    FROM orders WHERE order_status <> 'cancelled' AND created_at >= :from_date AND created_at < :to_date
                    GROUP BY period ORDER BY period");
                $statement->execute($range);
                $rows = array_map(static fn(array $r): array => [$r['period'], (int)$r['orders'], (float)$r['revenue']], $statement->fetchAll());

                return self::result($key, $subtitle, [$byMonth ? 'Tháng' : 'Ngày', 'Số đơn', 'Doanh thu'], $rows, [2]);

            case 'top_products':
                $statement = $pdo->prepare("
                    SELECT d.product_name, SUM(d.quantity) AS qty, SUM(d.total) AS revenue
                    FROM order_details d JOIN orders o ON o.id = d.order_id
                    WHERE o.order_status <> 'cancelled' AND o.created_at >= :from_date AND o.created_at < :to_date
                    GROUP BY d.product_id, d.product_name ORDER BY qty DESC, revenue DESC LIMIT $limit");
                $statement->execute($range);
                $rows = array_map(static fn(array $r): array => [$r['product_name'], (int)$r['qty'], (float)$r['revenue']], $statement->fetchAll());

                return self::result($key, $subtitle, ['Sản phẩm', 'Số lượng bán', 'Doanh thu'], $rows, [1, 2]);

            case 'sales_by_category':
                $statement = $pdo->prepare("
                    SELECT COALESCE(c.name, 'Chưa phân loại') AS category, SUM(d.quantity) AS qty, SUM(d.total) AS revenue
                    FROM order_details d
                    JOIN orders o ON o.id = d.order_id
                    LEFT JOIN products p ON p.id = d.product_id
                    LEFT JOIN categories c ON c.id = p.category_id
                    WHERE o.order_status <> 'cancelled' AND o.created_at >= :from_date AND o.created_at < :to_date
                    GROUP BY category ORDER BY revenue DESC");
                $statement->execute($range);
                $rows = array_map(static fn(array $r): array => [$r['category'], (int)$r['qty'], (float)$r['revenue']], $statement->fetchAll());

                return self::result($key, $subtitle, ['Danh mục', 'Số lượng bán', 'Doanh thu'], $rows, [1, 2]);

            case 'orders_by_status':
                $statement = $pdo->prepare('
                    SELECT order_status, COUNT(*) AS orders, COALESCE(SUM(total_amount), 0) AS amount
                    FROM orders WHERE created_at >= :from_date AND created_at < :to_date
                    GROUP BY order_status ORDER BY orders DESC');
                $statement->execute($range);
                $labels = order_status_labels();
                $rows = array_map(static fn(array $r): array => [$labels[$r['order_status']] ?? $r['order_status'], (int)$r['orders'], (float)$r['amount']], $statement->fetchAll());

                return self::result($key, $subtitle, ['Trạng thái', 'Số đơn', 'Giá trị'], $rows, [1, 2]);

            case 'top_customers':
                $statement = $pdo->prepare("
                    SELECT customer_name, customer_phone, COUNT(*) AS orders, SUM(total_amount) AS amount
                    FROM orders WHERE order_status <> 'cancelled' AND created_at >= :from_date AND created_at < :to_date
                    GROUP BY customer_phone, customer_name ORDER BY amount DESC LIMIT $limit");
                $statement->execute($range);
                $rows = array_map(static fn(array $r): array => [$r['customer_name'], (string)$r['customer_phone'], (int)$r['orders'], (float)$r['amount']], $statement->fetchAll());

                return self::result($key, $subtitle, ['Khách hàng', 'Điện thoại', 'Số đơn', 'Tổng mua'], $rows, [2, 3]);

            case 'purchases_by_supplier':
                $statement = $pdo->prepare("
                    SELECT r.supplier_name, COUNT(DISTINCT r.id) AS receipts, COALESCE(SUM(i.quantity), 0) AS qty, COALESCE(SUM(i.total), 0) AS amount
                    FROM purchase_receipts r LEFT JOIN purchase_receipt_items i ON i.receipt_id = r.id
                    WHERE r.status = 'completed' AND r.created_at >= :from_date AND r.created_at < :to_date
                    GROUP BY r.supplier_name ORDER BY amount DESC");
                $statement->execute($range);
                $rows = array_map(static fn(array $r): array => [$r['supplier_name'], (int)$r['receipts'], (int)$r['qty'], (float)$r['amount']], $statement->fetchAll());

                return self::result($key, $subtitle, ['Nhà cung cấp', 'Số phiếu', 'Số lượng', 'Tiền nhập'], $rows, [1, 2, 3]);

            case 'profit_estimate':
                $sales = $pdo->prepare("
                    SELECT COALESCE(SUM(o.total_amount), 0) FROM orders o
                    WHERE o.order_status <> 'cancelled' AND o.created_at >= :from_date AND o.created_at < :to_date");
                $sales->execute($range);
                $cogs = $pdo->prepare("
                    SELECT COALESCE(SUM(d.quantity * p.cost_price), 0)
                    FROM order_details d JOIN orders o ON o.id = d.order_id JOIN products p ON p.id = d.product_id
                    WHERE o.order_status <> 'cancelled' AND o.created_at >= :from_date AND o.created_at < :to_date");
                $cogs->execute($range);
                $purchases = $pdo->prepare("
                    SELECT COALESCE(SUM(total_amount), 0) FROM purchase_receipts
                    WHERE status = 'completed' AND created_at >= :from_date AND created_at < :to_date");
                $purchases->execute($range);

                $revenue = (float)$sales->fetchColumn();
                $cost = (float)$cogs->fetchColumn();
                $rows = [
                    ['Doanh thu bán hàng', $revenue],
                    ['Giá vốn ước tính (SL bán × giá vốn hiện tại)', $cost],
                    ['Lãi gộp ước tính', $revenue - $cost],
                    ['Chi nhập hàng trong kỳ (tham khảo)', (float)$purchases->fetchColumn()],
                ];

                return self::result($key, $subtitle, ['Chỉ tiêu', 'Giá trị (₫)'], $rows, [1]);

            case 'low_stock':
                $threshold = max(0, min(1000, (int)($params['threshold'] ?? 3)));
                $statement = $pdo->prepare("
                    SELECT name, product_code, stock FROM products
                    WHERE status <> 'hidden' AND stock <= :threshold ORDER BY stock ASC, name ASC");
                $statement->execute(['threshold' => $threshold]);
                $rows = array_map(static fn(array $r): array => [$r['name'], (string)$r['product_code'], (int)$r['stock']], $statement->fetchAll());

                return self::result($key, 'Tồn kho nhỏ hơn hoặc bằng ' . $threshold, ['Sản phẩm', 'Mã', 'Tồn kho'], $rows, [2]);

            case 'inventory_value':
                $statement = $pdo->query("
                    SELECT name, stock, cost_price, stock * cost_price AS value FROM products
                    WHERE status <> 'hidden' ORDER BY value DESC, name ASC");
                $rows = array_map(static fn(array $r): array => [$r['name'], (int)$r['stock'], (float)$r['cost_price'], (float)$r['value']], $statement->fetchAll());

                return self::result($key, 'Tại ' . date('d/m/Y H:i'), ['Sản phẩm', 'Tồn kho', 'Giá vốn', 'Giá trị tồn'], $rows, [1, 2, 3]);
        }

        throw new InvalidArgumentException('Báo cáo không tồn tại.');
    }

    /**
     * Tham số ngày: chỉ nhận YYYY-MM-DD hợp lệ, mặc định 30 ngày gần nhất, tối đa 5 năm.
     *
     * @param array<string, mixed> $params
     * @return array{0:string, 1:string, 2:string}
     */
    public static function period(array $params): array
    {
        $valid = static function (mixed $value): ?string {
            $value = (string)$value;
            $date = DateTime::createFromFormat('!Y-m-d', $value);

            return $date && $date->format('Y-m-d') === $value ? $value : null;
        };

        $to = $valid($params['to'] ?? '') ?? date('Y-m-d');
        $from = $valid($params['from'] ?? '') ?? date('Y-m-d', strtotime($to . ' -29 days'));

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $earliest = date('Y-m-d', strtotime($to . ' -5 years'));
        $from = max($from, $earliest);

        return [$from, $to, date('Y-m-d', strtotime($to . ' +1 day'))];
    }

    /**
     * @param list<string> $headers
     * @param list<list<mixed>> $rows
     * @param list<int> $sumColumns cột số cần cộng dòng tổng
     */
    private static function result(string $key, string $subtitle, array $headers, array $rows, array $sumColumns): array
    {
        if ($rows !== [] && $sumColumns !== [] && $key !== 'profit_estimate') {
            $total = array_fill(0, count($headers), '');
            $total[0] = 'Tổng cộng';

            foreach ($sumColumns as $column) {
                $total[$column] = array_sum(array_column($rows, $column));
            }

            $rows[] = $total;
        }

        return [
            'key' => $key,
            'title' => self::catalog()[$key]['title'],
            'subtitle' => $subtitle,
            'headers' => $headers,
            'rows' => $rows,
        ];
    }
}
