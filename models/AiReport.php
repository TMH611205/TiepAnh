<?php

require_once __DIR__ . '/Report.php';
require_once __DIR__ . '/Text.php';

/**
 * Chuyển câu hỏi tiếng Việt của chủ cửa hàng thành MỘT báo cáo trong danh mục Report + tham số.
 *
 * - Có ANTHROPIC_API_KEY và đã `composer install`: dùng Claude (structured outputs) để hiểu câu hỏi.
 *   Chỉ câu hỏi và danh mục báo cáo được gửi đi; không có dữ liệu khách hàng, đơn hàng hay tồn kho.
 * - Không có khóa hoặc AI lỗi: dùng bộ nhận diện từ khóa tiếng Việt (chạy hoàn toàn trên máy chủ).
 *
 * Kết quả luôn được Report::run() kiểm tra lại, AI không thể chạy SQL tùy ý.
 */
class AiReport
{
    public const MAX_CALLS_PER_HOUR = 20;

    public static function isAiConfigured(): bool
    {
        return (string)env('ANTHROPIC_API_KEY', '') !== ''
            && is_file(__DIR__ . '/../vendor/autoload.php');
    }

    /**
     * @return array{report:string, params:array<string, mixed>, note:string, source:string}
     */
    public static function resolve(string $question): array
    {
        $aiError = null;

        if (self::isAiConfigured() && self::underRateLimit()) {
            try {
                $spec = self::askClaude($question);

                if ($spec !== null) {
                    return $spec + ['source' => 'ai'];
                }
            } catch (Throwable $error) {
                // Không lộ chi tiết lỗi (có thể chứa thông tin nhạy cảm) cho người dùng.
                error_log('AI report: ' . get_class($error) . ': ' . $error->getMessage());
                $aiError = 'AI tạm thời không phản hồi nên hệ thống dùng chế độ nhận diện từ khóa.';
            }
        } elseif (self::isAiConfigured()) {
            $aiError = 'Đã dùng hết lượt AI trong giờ này, hệ thống dùng chế độ nhận diện từ khóa.';
        }

        $spec = self::heuristic($question);

        if ($aiError !== null) {
            $spec['note'] = $aiError . ' ' . $spec['note'];
        }

        return $spec + ['source' => 'rules'];
    }

    private static function underRateLimit(): bool
    {
        $now = time();
        $calls = array_filter((array)($_SESSION['ai_calls'] ?? []), static fn(int $t): bool => $now - $t < 3600);

        if (count($calls) >= self::MAX_CALLS_PER_HOUR) {
            $_SESSION['ai_calls'] = array_values($calls);

            return false;
        }

        $calls[] = $now;
        $_SESSION['ai_calls'] = array_values($calls);

        return true;
    }

    /** @return array{report:string, params:array<string, mixed>, note:string}|null */
    private static function askClaude(string $question): ?array
    {
        require_once __DIR__ . '/../vendor/autoload.php';

        $client = new \Anthropic\Client(
            apiKey: (string)env('ANTHROPIC_API_KEY'),
            requestOptions: ['timeout' => 25.0, 'maxRetries' => 1],
        );

        $catalog = [];
        foreach (Report::catalog() as $key => $info) {
            $catalog[] = '- ' . $key . ': ' . $info['title'] . '. ' . $info['description']
                . ' Tham số: ' . ($info['params'] === [] ? 'không có' : implode(', ', $info['params'])) . '.';
        }

        $today = date('Y-m-d');
        $system = "Bạn là bộ phân tích yêu cầu thống kê cho hệ thống quản lý cửa hàng xe điện Tiệp Anh.\n"
            . "Nhiệm vụ: chọn ĐÚNG MỘT báo cáo trong danh mục dưới đây phù hợp nhất với câu hỏi của chủ cửa hàng "
            . "và điền tham số. Bạn không được tự tạo số liệu và không trả lời nội dung thống kê.\n\n"
            . "Hôm nay là $today (múi giờ Việt Nam). Tính khoảng ngày from/to (định dạng YYYY-MM-DD) từ các cách nói như "
            . "\"hôm nay\", \"7 ngày qua\", \"tháng này\", \"tháng trước\", \"quý này\", \"năm nay\". "
            . "Nếu câu hỏi không nêu thời gian, để from và to là chuỗi rỗng.\n"
            . "group: \"day\" hoặc \"month\" (chỉ dùng cho doanh thu theo thời gian; mặc định day). "
            . "limit: số dòng top (0 nếu không nêu). threshold: ngưỡng tồn kho (0 nếu không nêu).\n"
            . "Nếu không báo cáo nào trả lời được câu hỏi, đặt report là \"none\" và giải thích ngắn gọn bằng tiếng Việt, "
            . "gợi ý một câu hỏi phù hợp trong danh mục.\n\n"
            . "Danh mục báo cáo:\n" . implode("\n", $catalog);

        $message = $client->messages->create(
            model: (string)env('AI_MODEL', 'claude-opus-5-5'),
            maxTokens: 2000,
            system: $system,
            messages: [['role' => 'user', 'content' => $question]],
            outputConfig: [
                'effort' => 'low',
                'format' => [
                    'type' => 'json_schema',
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'report' => ['type' => 'string', 'enum' => array_merge(array_keys(Report::catalog()), ['none'])],
                            'from' => ['type' => 'string'],
                            'to' => ['type' => 'string'],
                            'group' => ['type' => 'string', 'enum' => ['day', 'month']],
                            'limit' => ['type' => 'integer'],
                            'threshold' => ['type' => 'integer'],
                            'explanation' => ['type' => 'string'],
                        ],
                        'required' => ['report', 'from', 'to', 'group', 'limit', 'threshold', 'explanation'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
        );

        if ($message->stopReason === 'refusal') {
            return null;
        }

        foreach ($message->content as $block) {
            if ($block->type !== 'text') {
                continue;
            }

            $data = json_decode($block->text, true);

            if (!is_array($data) || !isset($data['report'])) {
                return null;
            }

            if ($data['report'] === 'none' || !isset(Report::catalog()[$data['report']])) {
                return ['report' => '', 'params' => [], 'note' => (string)($data['explanation'] ?? 'Không tìm thấy báo cáo phù hợp.')];
            }

            return [
                'report' => $data['report'],
                'params' => self::cleanParams($data),
                'note' => (string)($data['explanation'] ?? ''),
            ];
        }

        return null;
    }

    /** @param array<string, mixed> $data */
    private static function cleanParams(array $data): array
    {
        $params = [];

        foreach (['from', 'to'] as $field) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($data[$field] ?? ''))) {
                $params[$field] = $data[$field];
            }
        }
        if (in_array($data['group'] ?? '', ['day', 'month'], true)) {
            $params['group'] = $data['group'];
        }
        if ((int)($data['limit'] ?? 0) > 0) {
            $params['limit'] = (int)$data['limit'];
        }
        if (isset($data['threshold']) && (int)$data['threshold'] > 0) {
            $params['threshold'] = (int)$data['threshold'];
        }

        return $params;
    }

    /** @return array{report:string, params:array<string, mixed>, note:string} */
    public static function heuristic(string $question): array
    {
        $q = ' ' . Text::plain($question) . ' ';
        $has = static fn(array $words): bool => (bool)array_filter($words, static fn(string $w): bool => str_contains($q, ' ' . $w));

        $report = match (true) {
            $has(['loi nhuan', 'lai gop', 'lai lo', 'lai ']) => 'profit_estimate',
            $has(['nha cung cap', 'nhap hang', 'chi phi nhap', 'mua hang']) => 'purchases_by_supplier',
            $has(['gia tri ton', 'von ton', 'tien ton']) => 'inventory_value',
            $has(['sap het', 'het hang', 'ton kho', 'ton thap', 'can nhap']) => 'low_stock',
            $has(['khach']) => 'top_customers',
            $has(['danh muc', 'loai xe', 'nhom']) => 'sales_by_category',
            $has(['trang thai', 'don huy', 'cho xac nhan', 'don hang']) && !$has(['doanh thu']) => 'orders_by_status',
            $has(['ban chay', 'san pham', 'mau xe', 'xe nao', 'top']) => 'top_products',
            default => 'revenue_by_period',
        };

        $today = new DateTimeImmutable('today');
        $from = $today->modify('-29 days');
        $to = $today;
        $label = '30 ngày gần nhất';

        if ($has(['hom nay'])) {
            $from = $to = $today;
            $label = 'hôm nay';
        } elseif ($has(['hom qua'])) {
            $from = $to = $today->modify('-1 day');
            $label = 'hôm qua';
        } elseif ($has(['tuan nay'])) {
            $from = $today->modify('monday this week');
            $label = 'tuần này';
        } elseif ($has(['thang truoc'])) {
            $from = $today->modify('first day of last month');
            $to = $today->modify('last day of last month');
            $label = 'tháng trước';
        } elseif ($has(['thang nay'])) {
            $from = $today->modify('first day of this month');
            $label = 'tháng này';
        } elseif ($has(['quy nay', 'quy '])) {
            $month = (int)$today->format('n');
            $from = $today->setDate((int)$today->format('Y'), (int)(floor(($month - 1) / 3) * 3 + 1), 1);
            $label = 'quý này';
        } elseif ($has(['nam nay'])) {
            $from = $today->setDate((int)$today->format('Y'), 1, 1);
            $label = 'năm nay';
        } elseif (preg_match('/(\d{1,3}) (ngay|tuan|thang)/', $q, $m)) {
            $days = (int)$m[1] * ['ngay' => 1, 'tuan' => 7, 'thang' => 30][$m[2]];
            $from = $today->modify('-' . max(0, min(1825, $days) - 1) . ' days');
            $label = $m[1] . ' ' . ['ngay' => 'ngày', 'tuan' => 'tuần', 'thang' => 'tháng'][$m[2]] . ' gần nhất';
        }

        $params = ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')];

        if (preg_match('/top (\d{1,2})|(\d{1,2}) (san pham|mau|xe|khach)/', $q, $m)) {
            $params['limit'] = (int)($m[1] ?: $m[2]);
        }
        if ($report === 'revenue_by_period' && $has(['theo thang', 'hang thang', 'tung thang'])) {
            $params['group'] = 'month';
        }

        $title = Report::catalog()[$report]['title'];

        return [
            'report' => $report,
            'params' => $params,
            'note' => 'Nhận diện từ khóa: "' . $title . '", khoảng thời gian ' . $label . '.',
        ];
    }
}
