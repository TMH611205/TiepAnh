<?php

/**
 * Điểm vào chung của mọi trang admin: nạp cấu hình, xác thực và các hàm hiển thị dùng lại.
 * Trang con: require file này, rồi `$admin = Auth::require($pdo);` (trừ login.php).
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Xlsx.php';
require_once __DIR__ . '/../models/Text.php';

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function money(float|int|string|null $amount): string
{
    return number_format((float)$amount, 0, ',', '.') . ' ₫';
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e($_SESSION['csrf_token']) . '">';
}

function flash(string $type, string $message): void
{
    $_SESSION['admin_flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $flash = $_SESSION['admin_flash'] ?? null;
    unset($_SESSION['admin_flash']);

    return $flash;
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function slugify(string $text): string
{
    return Text::slug($text);
}

function order_status_labels(): array
{
    return [
        'pending' => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'processing' => 'Đang chuẩn bị',
        'shipping' => 'Đang giao',
        'completed' => 'Hoàn tất',
        'cancelled' => 'Đã hủy',
    ];
}

function payment_status_labels(): array
{
    return [
        'pending' => 'Chưa thanh toán',
        'paid' => 'Đã thanh toán',
        'failed' => 'Thất bại',
        'refunded' => 'Đã hoàn tiền',
    ];
}

function badge(string $text, string $tone): string
{
    return '<span class="admin-badge tone-' . e($tone) . '">' . e($text) . '</span>';
}

function order_status_badge(string $status): string
{
    $tones = [
        'pending' => 'warn', 'confirmed' => 'info', 'processing' => 'info',
        'shipping' => 'info', 'completed' => 'ok', 'cancelled' => 'bad',
    ];

    return badge(order_status_labels()[$status] ?? $status, $tones[$status] ?? 'muted');
}

function payment_status_badge(string $status): string
{
    $tones = ['pending' => 'warn', 'paid' => 'ok', 'failed' => 'bad', 'refunded' => 'muted'];

    return badge(payment_status_labels()[$status] ?? $status, $tones[$status] ?? 'muted');
}

/** Chuẩn hóa đường dẫn ảnh (DB có thể lưu dấu \) để dùng trong URL. */
function image_url(?string $path): string
{
    $path = ltrim(str_replace(chr(92), '/', (string)$path), '/');

    return $path === '' ? ASSET_URL . '/images/xe (1).jpg' : ASSET_URL . '/../' . $path;
}

function admin_page_header(array $admin, string $title, string $active): void
{
    require __DIR__ . '/../views/admin/header.php';
}

function admin_page_footer(): void
{
    require __DIR__ . '/../views/admin/footer.php';
}

/**
 * Khoảng ngày lọc báo cáo từ ?from=YYYY-MM-DD&to=YYYY-MM-DD (mặc định: 30 ngày gần nhất).
 * `end_exclusive` là ngày kế tiếp của `to`, dùng cho điều kiện `created_at < :end`.
 *
 * @return array{from:string, to:string, end_exclusive:string}
 */
function date_range(int $defaultDays = 30): array
{
    $valid = static function (mixed $value): ?string {
        $value = (string)$value;
        $date = DateTime::createFromFormat('!Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value ? $value : null;
    };

    $to = $valid($_GET['to'] ?? '') ?? date('Y-m-d');
    $from = $valid($_GET['from'] ?? '') ?? date('Y-m-d', strtotime($to . ' -' . ($defaultDays - 1) . ' days'));

    if ($from > $to) {
        [$from, $to] = [$to, $from];
    }

    return ['from' => $from, 'to' => $to, 'end_exclusive' => date('Y-m-d', strtotime($to . ' +1 day'))];
}

/** Link tới trang hiện tại kèm các tham số lọc hiện có và export=xlsx. */
function export_url(array $extra = []): string
{
    $query = $_GET;
    unset($query['page'], $query['export']);

    return '?' . http_build_query($extra + $query + ['export' => 'xlsx']);
}

function export_button(): string
{
    return '<a class="admin-button ghost" href="' . e(export_url(['export' => 'xlsx'])) . '">'
        . '<span class="material-symbols-outlined" aria-hidden="true">download</span> Xuất Excel</a>';
}

function wants_export(): bool
{
    return ($_GET['export'] ?? '') === 'xlsx';
}

/** Ngày giờ hiển thị kiểu Việt Nam. */
function vn_datetime(?string $value): string
{
    return $value ? date('d/m/Y H:i', strtotime($value)) : '';
}

/** Ô lọc theo khoảng ngày dùng chung cho form GET. */
function date_range_fields(array $range): string
{
    return '<input class="admin-input" type="date" name="from" value="' . e($range['from']) . '" aria-label="Từ ngày">'
        . '<input class="admin-input" type="date" name="to" value="' . e($range['to']) . '" aria-label="Đến ngày">';
}
