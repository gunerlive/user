<?php

namespace ESign;

class Helpers
{
    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    public static function randomToken(int $bytes = 24): string
    {
        return bin2hex(random_bytes($bytes));
    }

    public static function clientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    public static function thaiDate(?string $datetime): string
    {
        if (!$datetime) {
            return '-';
        }
        $months = [
            1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.', 5 => 'พ.ค.', 6 => 'มิ.ย.',
            7 => 'ก.ค.', 8 => 'ส.ค.', 9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.',
        ];
        $ts = strtotime($datetime);
        $day = date('j', $ts);
        $month = $months[(int) date('n', $ts)];
        $year = (int) date('Y', $ts) + 543;
        $time = date('H:i', $ts);
        return "{$day} {$month} {$year} เวลา {$time} น.";
    }

    public static function statusLabel(string $status): array
    {
        return match ($status) {
            'draft' => ['ร่าง / ยังไม่เปิดให้เซ็น', '#64748b', '#f1f5f9'],
            'in_progress' => ['กำลังลงนาม', '#b45309', '#fef3c7'],
            'ready_for_review' => ['รอตรวจสอบ', '#166534', '#dcfce7'],
            'saved' => ['บันทึกเข้าระบบแล้ว', '#1e3a8a', '#dbeafe'],
            'cancelled' => ['ยกเลิกแล้ว', '#991b1b', '#fee2e2'],
            default => [$status, '#64748b', '#f1f5f9'],
        };
    }

    public static function redirect(string $path): never
    {
        header('Location: ' . $path);
        exit;
    }

    public static function jsonResponse(array $data, int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
