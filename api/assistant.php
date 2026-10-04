<?php

require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json; charset=utf-8');

$respond = static function (int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    $respond(405, ['error' => 'Phương thức không được hỗ trợ.']);
}

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!hash_equals($_SESSION['csrf_token'], $token)) {
    $respond(403, ['error' => 'Phiên làm việc đã hết hạn. Hãy tải lại trang.']);
}

$payload = json_decode(file_get_contents('php://input'), true);
$action = (string)($payload['action'] ?? 'chat');
$message = trim((string)($payload['message'] ?? ''));
$intent = (string)($payload['intent'] ?? '');
$selectedProductIds = array_values(array_unique(array_filter(
    array_map('intval', $payload['product_ids'] ?? []),
    static fn(int $id): bool => $id > 0
)));

if ($action === 'compare' && count($selectedProductIds) > 0) {
    if (count($selectedProductIds) < 2 || count($selectedProductIds) > 3) {
        $respond(422, ['error' => 'Chọn từ hai đến ba sản phẩm để so sánh.']);
    }
} elseif ($action === 'compare' && (int)($payload['product_id'] ?? 0) > 0) {
    $productId = (int)$payload['product_id'];
} elseif ($action === 'recommend') {
    if (!in_array($intent, ['school', 'distance', 'speed', 'range', 'budget'], true)) {
        $respond(422, ['error' => 'Nhu cầu tư vấn không hợp lệ.']);
    }
} elseif ($message === '' || mb_strlen($message, 'UTF-8') > 600) {
    $respond(422, ['error' => 'Tin nhắn cần có nội dung và không vượt quá 600 ký tự.']);
}

$normalize = static function (string $value): string {
    $value = mb_strtolower($value, 'UTF-8');
    $groups = [
        'a' => 'áàảãạăắằẳẵặâấầẩẫậ',
        'e' => 'éèẻẽẹêếềểễệ',
        'i' => 'íìỉĩị',
        'o' => 'óòỏõọôốồổỗộơớờởỡợ',
        'u' => 'úùủũụưứừửữự',
        'y' => 'ýỳỷỹỵ',
        'd' => 'đ',
    ];
    $characters = [];

    foreach ($groups as $plain => $accentedCharacters) {
        foreach (preg_split('//u', $accentedCharacters, -1, PREG_SPLIT_NO_EMPTY) as $character) {
            $characters[$character] = $plain;
        }
    }

    $value = strtr($value, $characters);

    return trim(preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '');
};

$products = $pdo->query('
    SELECT
        p.id,
        p.product_code,
        p.name,
        p.price,
        p.sale_price,
        p.max_speed,
        p.battery_range,
        p.motor_power,
        p.warranty_months,
        p.stock,
        p.status,
        p.description,
        p.specifications,
        c.name AS category_name,
        c.slug AS category_slug
    FROM products p
    INNER JOIN categories c ON c.id = p.category_id
    WHERE p.status IN (\'active\', \'out_of_stock\')
    ORDER BY p.id DESC
')->fetchAll();

if ($action === 'recommend') {
    $eligible = array_values(array_filter(
        $products,
        static function (array $product) use ($intent): bool {
            if ($product['status'] !== 'active' || (int)$product['stock'] < 1 || (float)$product['price'] <= 0) {
                return false;
            }

            return match ($intent) {
                'school' => in_array($product['category_slug'], ['xe-dien-hoc-sinh', 'xe-dap-dien'], true),
                'distance' => $product['battery_range'] !== null && (int)$product['battery_range'] >= 80,
                'speed' => $product['max_speed'] !== null,
                'range' => $product['battery_range'] !== null,
                'budget' => true,
                default => false,
            };
        }
    ));

    foreach ($eligible as &$product) {
        $product['current_price'] = Pricing::currentFromRow($product);
    }
    unset($product);

    usort($eligible, static function (array $left, array $right) use ($intent): int {
        return match ($intent) {
            'speed' => (int)$right['max_speed'] <=> (int)$left['max_speed'],
            'distance', 'range' => (int)$right['battery_range'] <=> (int)$left['battery_range'],
            'budget' => (float)$left['current_price'] <=> (float)$right['current_price'],
            default => (int)$right['battery_range'] <=> (int)$left['battery_range'],
        };
    });

    $eligible = array_slice($eligible, 0, 3);

    if ($eligible === []) {
        $intentLabels = [
            'school' => 'nhu cầu đi học',
            'distance' => 'đi xa',
            'speed' => 'tốc độ tối đa',
            'range' => 'quãng đường',
            'budget' => 'giá tiết kiệm',
        ];
        $respond(200, [
            'answer' => 'Catalog chưa có sản phẩm đang còn hàng và đủ dữ liệu cho nhu cầu ' . $intentLabels[$intent] . '. Bạn có thể liên hệ cửa hàng để hỏi các mẫu đang cập nhật.',
            'products' => [],
        ]);
    }

    $intentLabels = [
        'school' => 'đi học (xe đạp điện đang có hàng)',
        'distance' => 'đi xa (ưu tiên quãng đường từ 80 km)',
        'speed' => 'tốc độ tối đa đã được công bố',
        'range' => 'quãng đường mỗi lần sạc đã được công bố',
        'budget' => 'giá thấp hơn trong số mẫu còn hàng',
    ];
    $recommendations = [];
    $answerLines = ['Gợi ý theo nhu cầu ' . $intentLabels[$intent] . ', dựa trên catalog:'];

    foreach ($eligible as $product) {
        $range = $product['battery_range'] !== null
            ? (int)$product['battery_range'] . ' km/lần sạc'
            : 'chưa cập nhật';
        $speed = $product['max_speed'] !== null
            ? (int)$product['max_speed'] . ' km/h'
            : 'chưa cập nhật';
        $answerLines[] = $product['name'] . ' (' . $product['product_code'] . '): ' .
            number_format((float)$product['current_price'], 0, ',', '.') . '₫; quãng đường ' . $range .
            '; tốc độ tối đa ' . $speed . '; còn ' . (int)$product['stock'] . ' sản phẩm.';
        $recommendations[] = [
            'id' => (int)$product['id'],
            'name' => $product['name'],
            'product_code' => $product['product_code'],
            'price' => number_format((float)$product['current_price'], 0, ',', '.') . '₫',
            'battery_range' => $product['battery_range'] !== null ? (int)$product['battery_range'] : null,
            'max_speed' => $product['max_speed'] !== null ? (int)$product['max_speed'] : null,
        ];
    }

    $respond(200, [
        'answer' => implode("\n", $answerLines) . "\nThông số thiếu được ghi là chưa cập nhật, không suy đoán ngoài database.",
        'products' => $recommendations,
    ]);
}

if ($action === 'compare' && count($selectedProductIds) >= 2) {
    $selectedProducts = [];

    foreach ($selectedProductIds as $selectedId) {
        foreach ($products as $product) {
            if ((int)$product['id'] === $selectedId) {
                $product['current_price'] = Pricing::currentFromRow($product);
                $selectedProducts[] = $product;
                break;
            }
        }
    }

    if (count($selectedProducts) !== count($selectedProductIds)) {
        $respond(404, ['error' => 'Một sản phẩm đã chọn không còn trong catalog.']);
    }

    $priceText = static fn(array $product): string => (float)$product['current_price'] > 0
        ? number_format((float)$product['current_price'], 0, ',', '.') . '₫'
        : 'Chưa cập nhật';
    $valueText = static fn(mixed $value, string $suffix = ''): string => $value !== null && $value !== ''
        ? (string)$value . $suffix
        : 'Chưa cập nhật';
    $rows = [
        ['label' => 'Danh mục', 'values' => array_column($selectedProducts, 'category_name')],
        ['label' => 'Giá hiện tại', 'values' => array_map($priceText, $selectedProducts)],
        ['label' => 'Động cơ', 'values' => array_map(static fn(array $product): string => $valueText($product['motor_power']), $selectedProducts)],
        ['label' => 'Quãng đường', 'values' => array_map(static fn(array $product): string => $valueText($product['battery_range'], ' km/lần sạc'), $selectedProducts)],
        ['label' => 'Tốc độ tối đa', 'values' => array_map(static fn(array $product): string => $valueText($product['max_speed'], ' km/h'), $selectedProducts)],
        ['label' => 'Bảo hành', 'values' => array_map(static fn(array $product): string => (int)$product['warranty_months'] > 0 ? (int)$product['warranty_months'] . ' tháng' : 'Chưa cập nhật', $selectedProducts)],
        ['label' => 'Tình trạng', 'values' => array_map(static fn(array $product): string => $product['status'] === 'active' && (int)$product['stock'] > 0 ? 'Còn ' . (int)$product['stock'] : 'Đang cập nhật / chưa có hàng', $selectedProducts)],
    ];

    $respond(200, [
        'comparison' => [
            'products' => array_map(static fn(array $product): array => [
                'id' => (int)$product['id'],
                'name' => $product['name'],
                'product_code' => $product['product_code'],
            ], $selectedProducts),
            'rows' => $rows,
            'notice' => 'Bảng lấy dữ liệu trực tiếp từ catalog; trường trống được ghi “Chưa cập nhật”.',
        ],
    ]);
}

if ($action === 'compare') {
    $currentProduct = null;

    foreach ($products as $product) {
        if ((int)$product['id'] === $productId) {
            $currentProduct = $product;
            break;
        }
    }

    if (!$currentProduct) {
        $respond(404, ['error' => 'Không tìm thấy sản phẩm trong catalog.']);
    }

    $competitors = array_values(array_filter(
        $products,
        static fn(array $product): bool =>
        (int)$product['id'] !== $productId &&
            $product['category_slug'] === $currentProduct['category_slug'] &&
            $product['status'] === 'active' &&
            (float)$product['price'] > 0
    ));

    if ($competitors === []) {
        $respond(200, [
            'answer' => 'Chưa có mẫu khác cùng danh mục với đủ dữ liệu để đối chiếu.',
            'products' => [],
        ]);
    }

    $getCurrentPrice = static fn(array $product): ?float => (float)$product['price'] > 0
        ? Pricing::currentFromRow($product)
        : null;

    usort($competitors, static function (array $left, array $right) use ($currentProduct, $getCurrentPrice): int {
        $leftPrice = $getCurrentPrice($left);
        $rightPrice = $getCurrentPrice($right);
        $currentPrice = $getCurrentPrice($currentProduct);
        $leftDistance = $leftPrice !== null && $currentPrice !== null ? abs($leftPrice - $currentPrice) : PHP_FLOAT_MAX;
        $rightDistance = $rightPrice !== null && $currentPrice !== null ? abs($rightPrice - $currentPrice) : PHP_FLOAT_MAX;

        return $leftDistance <=> $rightDistance;
    });

    $competitor = $competitors[0];
    $advantages = [];
    $tradeoffs = [];
    $currentPrice = $getCurrentPrice($currentProduct);
    $competitorPrice = $getCurrentPrice($competitor);

    if ($currentPrice !== null && $competitorPrice !== null && $currentPrice !== $competitorPrice) {
        $amount = number_format(abs($currentPrice - $competitorPrice), 0, ',', '.') . '₫';
        if ($currentPrice < $competitorPrice) {
            $advantages[] = 'Giá thấp hơn ' . $amount;
        } else {
            $tradeoffs[] = 'Giá cao hơn ' . $amount;
        }
    }

    foreach (
        [
            'battery_range' => ['Quãng đường', ' km/lần sạc', false],
            'max_speed' => ['Tốc độ tối đa', ' km/h', false],
        ] as $field => [$label, $unit]
    ) {
        if ($currentProduct[$field] === null || $competitor[$field] === null) {
            continue;
        }

        $difference = (int)$currentProduct[$field] - (int)$competitor[$field];

        if ($difference > 0) {
            $advantages[] = $label . ' cao hơn ' . $difference . $unit;
        } elseif ($difference < 0) {
            $tradeoffs[] = $label . ' thấp hơn ' . abs($difference) . $unit;
        }
    }

    $currentMotor = null;
    $competitorMotor = null;
    preg_match('/([0-9]+(?:\.[0-9]+)?)\s*w/i', (string)$currentProduct['motor_power'], $currentMotorMatch);
    preg_match('/([0-9]+(?:\.[0-9]+)?)\s*w/i', (string)$competitor['motor_power'], $competitorMotorMatch);
    $currentMotor = isset($currentMotorMatch[1]) ? (float)$currentMotorMatch[1] : null;
    $competitorMotor = isset($competitorMotorMatch[1]) ? (float)$competitorMotorMatch[1] : null;

    if ($currentMotor !== null && $competitorMotor !== null && $currentMotor !== $competitorMotor) {
        if ($currentMotor > $competitorMotor) {
            $advantages[] = 'Công suất ghi trong catalog cao hơn';
        } else {
            $tradeoffs[] = 'Công suất ghi trong catalog thấp hơn';
        }
    }

    if ($advantages === []) {
        $advantages[] = 'Chưa ghi nhận điểm nổi trội theo các trường dữ liệu chung.';
    }

    if ($tradeoffs === []) {
        $tradeoffs[] = 'Chưa ghi nhận điểm bất lợi theo các trường dữ liệu chung.';
    }

    $respond(200, [
        'answer' => '',
        'comparison' => [
            'current' => $currentProduct['name'],
            'other' => $competitor['name'],
            'advantages' => array_slice($advantages, 0, 3),
            'tradeoffs' => array_slice($tradeoffs, 0, 3),
            'notice' => 'Đối chiếu tự động từ các trường đang có trong database; trường thiếu không được suy đoán.',
        ],
    ]);
}

// Câu hỏi tự do: ưu tiên Claude (API) trả lời dựa trên catalog; lỗi/không có khóa thì dùng tìm kiếm từ khóa bên dưới.
require_once __DIR__ . '/../models/AiChat.php';

$aiProducts = array_map(static function (array $product): array {
    $product['current_price'] = Pricing::currentFromRow($product);

    return $product;
}, $products);
$aiAnswer = AiChat::answer($message, $aiProducts);

if ($aiAnswer !== null) {
    $respond(200, ['answer' => $aiAnswer, 'products' => [], 'source' => 'ai']);
}

$normalizedMessage = $normalize($message);
$messageTokens = array_values(array_filter(
    explode(' ', $normalizedMessage),
    static fn(string $token): bool => strlen($token) > 1
));
$scoredProducts = [];

foreach ($products as $product) {
    $name = $normalize($product['name']);
    $code = $normalize($product['product_code'] ?? '');
    $category = $normalize($product['category_name']);
    $identityTokens = array_values(array_filter(
        explode(' ', $name),
        static fn(string $word): bool => strlen($word) >= 4 &&
            !in_array($word, ['tiep', 'anh', 'electric', 'scooter', 'student', 'school', 'model', 'mau'], true)
    ));
    $searchable = $normalize(implode(' ', [
        $product['name'],
        $product['product_code'] ?? '',
        $product['category_name'],
        $product['description'] ?? '',
        $product['specifications'] ?? '',
        $product['motor_power'] ?? '',
    ]));
    $score = 0;

    if ($name !== '' && str_contains($normalizedMessage, $name)) {
        $score += 20;
    }

    if ($code !== '' && str_contains($normalizedMessage, $code)) {
        $score += 20;
    }

    foreach ($messageTokens as $messageToken) {
        if (str_contains($searchable, $messageToken)) {
            $score += str_contains($name . ' ' . $code, $messageToken) ? 3 : 1;
        }
    }

    $product['current_price'] = Pricing::currentFromRow($product);
    $product['_score'] = $score;
    $product['_category_match'] = $category !== '' && str_contains($normalizedMessage, $category);
    $product['_identity_match'] = ($name !== '' && str_contains($normalizedMessage, $name)) ||
        ($code !== '' && str_contains($normalizedMessage, $code)) ||
        count(array_filter(
            $identityTokens,
            static fn(string $word): bool => str_contains($normalizedMessage, $word)
        )) > 0;
    $scoredProducts[] = $product;
}

usort($scoredProducts, static function (array $left, array $right): int {
    return [$right['_score'], (int)$right['stock']] <=> [$left['_score'], (int)$left['stock']];
});

$isComparison = preg_match(
    '/\b(so sanh|khac nhau|compare|doi chieu)\b/',
    $normalizedMessage
) === 1;
$matchedProducts = $isComparison
    ? array_values(array_filter(
        $scoredProducts,
        static fn(array $product): bool => $product['_identity_match']
    ))
    : array_values(array_filter(
        $scoredProducts,
        static fn(array $product): bool => $product['_identity_match']
    ));

if ($isComparison && count($matchedProducts) < 2) {
    $respond(200, [
        'answer' => 'Để so sánh chính xác, hãy nêu tên hoặc mã ít nhất hai mẫu xe. Trợ lý chỉ sử dụng dữ liệu đang có trong catalog.',
        'products' => [],
    ]);
}

if (!$isComparison && $matchedProducts === []) {
    $matchedProducts = array_values(array_filter(
        $scoredProducts,
        static fn(array $product): bool => $product['_score'] > 0
    ));
}

if ($matchedProducts === []) {
    $respond(200, [
        'answer' => 'Mình chưa tìm thấy mẫu phù hợp trong catalog hiện tại. Bạn có thể hỏi theo tên xe, mã sản phẩm, mức giá, quãng đường, động cơ hoặc nhóm hàng.',
        'products' => [],
    ]);
}

$matchedProducts = array_slice($matchedProducts, 0, 4);
$formatPrice = static function (array $product): string {
    return (float)$product['current_price'] > 0
        ? number_format((float)$product['current_price'], 0, ',', '.') . '₫'
        : 'chưa cập nhật';
};

$answerLines = [
    $isComparison
        ? 'Đối chiếu theo dữ liệu hiện có trong catalog:'
        : 'Thông tin mình tìm thấy trong catalog:'
];
$responseProducts = [];

foreach ($matchedProducts as $product) {
    $facts = [
        'Giá: ' . $formatPrice($product),
        'Danh mục: ' . $product['category_name'],
        'Động cơ: ' . ($product['motor_power'] ?: 'chưa cập nhật'),
        'Quãng đường: ' . ($product['battery_range'] !== null
            ? (int)$product['battery_range'] . ' km/lần sạc'
            : 'chưa cập nhật'),
        'Tốc độ tối đa: ' . ($product['max_speed'] !== null
            ? (int)$product['max_speed'] . ' km/h'
            : 'chưa cập nhật'),
        'Bảo hành: ' . ((int)$product['warranty_months'] > 0
            ? (int)$product['warranty_months'] . ' tháng'
            : 'chưa cập nhật'),
        'Tình trạng: ' . ($product['status'] === 'active' && (int)$product['stock'] > 0
            ? 'còn ' . (int)$product['stock'] . ' sản phẩm'
            : 'đang cập nhật / chưa có hàng'),
    ];

    if (!empty($product['specifications'])) {
        $facts[] = 'Ghi chú catalog: ' . trim($product['specifications']);
    }

    $answerLines[] = $product['name'] . ' (' . $product['product_code'] . '): ' . implode('; ', $facts) . '.';
    $responseProducts[] = [
        'id' => (int)$product['id'],
        'name' => $product['name'],
        'product_code' => $product['product_code'],
        'category' => $product['category_name'],
        'price' => $formatPrice($product),
        'motor_power' => $product['motor_power'] ?: null,
        'battery_range' => $product['battery_range'] !== null
            ? (int)$product['battery_range']
            : null,
        'max_speed' => $product['max_speed'] !== null
            ? (int)$product['max_speed']
            : null,
        'warranty_months' => (int)$product['warranty_months'] > 0
            ? (int)$product['warranty_months']
            : null,
        'stock_status' => $product['status'] === 'active' && (int)$product['stock'] > 0
            ? 'Còn hàng'
            : 'Đang cập nhật / chưa có hàng',
    ];
}

$answerLines[] = 'Thông số để trống trong database được ghi là “chưa cập nhật”; mình không tự suy đoán thông tin ngoài catalog.';

$respond(200, [
    'answer' => implode("\n", $answerLines),
    'products' => $responseProducts,
]);
