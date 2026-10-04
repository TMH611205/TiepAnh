<?php

/**
 * Gửi email qua SMTP (Gmail, ...) không cần thư viện ngoài.
 * Cấu hình trong .env: SMTP_HOST, SMTP_PORT (587 = STARTTLS, 465 = SSL), SMTP_USER, SMTP_PASS.
 */
class Mailer
{
    public static function isConfigured(): bool
    {
        return env('SMTP_USER', '') !== '' && env('SMTP_PASS', '') !== '';
    }

    /**
     * @return string|null null nếu gửi thành công, ngược lại là thông báo lỗi.
     */
    public static function send(string $to, string $subject, string $body, ?string $replyTo = null): ?string
    {
        if (!self::isConfigured()) {
            return 'Chưa cấu hình SMTP_USER / SMTP_PASS trong file .env.';
        }

        $host = (string)env('SMTP_HOST', 'smtp.gmail.com');
        $port = (int)env('SMTP_PORT', '587');
        $user = (string)env('SMTP_USER');
        $pass = str_replace(' ', '', (string)env('SMTP_PASS'));
        $from = (string)env('SMTP_FROM', $user);

        $socket = @stream_socket_client(
            ($port === 465 ? 'ssl://' : 'tcp://') . $host . ':' . $port,
            $errno,
            $errstr,
            15
        );

        if (!$socket) {
            return "Không kết nối được máy chủ SMTP ($errstr).";
        }

        stream_set_timeout($socket, 15);

        try {
            self::expect($socket, 220);
            self::command($socket, 'EHLO tiepanh.local', 250);

            if ($port !== 465) {
                self::command($socket, 'STARTTLS', 220);

                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('Không bật được mã hóa TLS.');
                }

                self::command($socket, 'EHLO tiepanh.local', 250);
            }

            self::command($socket, 'AUTH LOGIN', 334);
            self::command($socket, base64_encode($user), 334);
            self::command($socket, base64_encode($pass), 235);
            self::command($socket, 'MAIL FROM:<' . $from . '>', 250);
            self::command($socket, 'RCPT TO:<' . $to . '>', 250);
            self::command($socket, 'DATA', 354);

            $headers = [
                'Date: ' . date('r'),
                'From: =?UTF-8?B?' . base64_encode('Website Tiệp Anh') . '?= <' . $from . '>',
                'To: <' . $to . '>',
                'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: base64',
            ];

            if ($replyTo !== null && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                $headers[] = 'Reply-To: <' . $replyTo . '>';
            }

            $message = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($body));
            // Chống dòng chỉ có dấu chấm kết thúc DATA sớm (base64 không có, nhưng giữ cho chắc).
            $message = preg_replace('/^\./m', '..', $message);

            self::command($socket, $message . "\r\n.", 250);
            self::command($socket, 'QUIT', 221);
        } catch (Throwable $e) {
            fclose($socket);

            return $e->getMessage();
        }

        fclose($socket);

        return null;
    }

    /** @param resource $socket */
    private static function command($socket, string $line, int $expectCode): void
    {
        fwrite($socket, $line . "\r\n");
        self::expect($socket, $expectCode);
    }

    /** @param resource $socket */
    private static function expect($socket, int $code): void
    {
        $response = '';

        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;

            // Dòng cuối của phản hồi nhiều dòng có dạng "250 ..." (dấu cách thay vì "-").
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }

        if ((int)substr($response, 0, 3) !== $code) {
            throw new RuntimeException('SMTP: ' . trim($response));
        }
    }
}
