<?php

namespace ESign;

/**
 * ไคลเอนต์เชื่อมต่อ Google_API กลางของโรงเรียน (https://rpk24.ac.th/Google_API)
 * ระบบนี้ไม่ทำ OAuth เอง และไม่สร้าง OAuth Client ID ใหม่ — ส่งไฟล์ไปที่ API กลางเท่านั้น
 * ตามข้อกำหนดใน api_connection_full_guide.html / prompt_for_ai.md
 */
class GoogleApiClient
{
    public function __construct(private array $config)
    {
    }

    /**
     * อัปโหลดไฟล์ไปยัง Google_API กลาง แบบ POST multipart/form-data
     *
     * @throws \RuntimeException เมื่อเชื่อมต่อไม่สำเร็จ หรือ API แจ้งว่าอัปโหลดไม่สำเร็จ
     */
    public function uploadFile(string $filePath, string $description, ?string $uploaderName = null): array
    {
        if (!is_file($filePath)) {
            throw new \RuntimeException('ไม่พบไฟล์เอกสารที่จะอัปโหลด');
        }

        $mimeType = mime_content_type($filePath) ?: 'application/pdf';

        $postData = [
            'system_key'    => $this->config['system_key'],
            'api_secret'    => $this->config['api_secret'],
            'uploader_name' => $uploaderName ?? $this->config['uploader_name'],
            'description'   => $description,
            'file'          => new \CURLFile($filePath, $mimeType, basename($filePath)),
        ];

        $ch = curl_init($this->config['upload_endpoint']);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->config['timeout_seconds'] ?? 120,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException('เชื่อมต่อ Google_API ไม่สำเร็จ: ' . $error);
        }

        $data = json_decode((string) $response, true);

        if (!is_array($data)) {
            throw new \RuntimeException('Google_API ตอบกลับข้อมูลที่ไม่ถูกต้อง: ' . substr((string) $response, 0, 300));
        }

        if (empty($data['success'])) {
            throw new \RuntimeException('อัปโหลดขึ้น Google Drive ไม่สำเร็จ: ' . ($data['message'] ?? 'ไม่ทราบสาเหตุ'));
        }

        return $data;
    }
}
