<?php

namespace ESign;

use PDO;

/**
 * ตั้งค่าระบบที่แก้ไขได้ผ่านหน้าเว็บ (public/settings.php) โดยไม่ต้องแก้ไฟล์ config.php บนโฮสต์
 * ค่าที่บันทึกในฐานข้อมูลจะ "ทับ" ค่าเริ่มต้นจาก config.php เสมอ (ดูการ merge ใน bootstrap.php)
 */
class SettingsService
{
    private array $cache;

    public function __construct(private PDO $db)
    {
        $this->cache = [];
        foreach ($this->db->query('SELECT setting_key, setting_value FROM signflow_settings') as $row) {
            $this->cache[$row['setting_key']] = $row['setting_value'];
        }
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $value = $this->cache[$key] ?? null;
        return ($value !== null && $value !== '') ? $value : $default;
    }

    public function set(string $key, string $value): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO signflow_settings (setting_key, setting_value) VALUES (:k, :v)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $stmt->execute(['k' => $key, 'v' => $value]);
        $this->cache[$key] = $value;
    }

    /** @param array<string, string> $pairs */
    public function setMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * นำค่าที่บันทึกไว้ในฐานข้อมูลไปทับค่าเริ่มต้นใน $config (ถ้ามีการตั้งค่าไว้)
     */
    public function applyTo(array $config): array
    {
        $config['app_base_url'] = $this->get('app_base_url', $config['app_base_url']);
        $config['system_name'] = $this->get('system_name', $config['system_name'] ?? 'ระบบเซ็นเอกสารออนไลน์');

        $config['google_api']['system_key'] = $this->get('google_api_system_key', $config['google_api']['system_key']);
        $config['google_api']['api_secret'] = $this->get('google_api_api_secret', $config['google_api']['api_secret']);
        $config['google_api']['uploader_name'] = $this->get('google_api_uploader_name', $config['google_api']['uploader_name']);

        $config['upload']['max_file_size_mb'] = (int) $this->get(
            'upload_max_file_size_mb',
            (string) $config['upload']['max_file_size_mb']
        );

        return $config;
    }
}
