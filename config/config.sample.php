<?php
/**
 * คัดลอกไฟล์นี้เป็น config.php แล้วแก้ไขค่าให้ตรงกับระบบจริง
 * ห้าม commit ไฟล์ config.php ขึ้น git (มีอยู่ใน .gitignore แล้ว)
 */

return [

    // ตั้งค่าฐานข้อมูล MySQL/MariaDB
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'esign_db',
        'user'    => 'esign_user',
        'pass'    => 'change_me',
        'charset' => 'utf8mb4',
    ],

    // URL เต็มของระบบนี้ (ไม่มี / ปิดท้าย) ใช้สำหรับสร้างลิงก์เซ็นเอกสารให้ผู้ลงนาม
    'app_base_url' => 'https://sign.yourschool.ac.th',

    // ข้อมูลเชื่อมต่อ Google_API กลางของโรงเรียน (ตามคู่มือ api_connection_full_guide.html)
    // ระบบนี้จะไม่ทำ OAuth เอง และไม่สร้าง OAuth Client ID ใหม่ — ส่งไฟล์ไปที่ API กลางเท่านั้น
    'google_api' => [
        'base_url'        => 'https://rpk24.ac.th/Google_API',
        'upload_endpoint' => 'https://rpk24.ac.th/Google_API/api_upload.php',
        'system_key'      => 'ใส่ system_key ที่ได้จากผู้ดูแล',
        'api_secret'      => 'ใส่ api_secret ที่ได้จากผู้ดูแล',
        'uploader_name'   => 'ระบบเซ็นเอกสารออนไลน์',
        'timeout_seconds' => 120,
    ],

    // การอัปโหลดเอกสาร
    'upload' => [
        'allowed_extensions' => ['pdf'],
        'max_file_size_mb'   => 20,
    ],

    // ฟอนต์ไทยสำหรับพิมพ์ชื่อ-วันที่กำกับใต้ลายเซ็น (มาตรฐานราชการ: TH Sarabun New)
    // ดาวน์โหลดฟอนต์และวางไฟล์ .ttf ไว้ที่ path นี้ ถ้าไม่พบไฟล์ ระบบจะยังเซ็นได้
    // แต่จะไม่พิมพ์ชื่อ-วันที่กำกับใต้ลายเซ็น (แสดงเฉพาะลายเซ็นที่วาด)
    'thai_font_path' => __DIR__ . '/../src/fonts/THSarabunNew.ttf',

    // คีย์ลับสำหรับเข้ารหัส session/CSRF (สุ่มค่าใหม่ด้วยคำสั่ง: php -r "echo bin2hex(random_bytes(32));")
    'app_secret' => 'เปลี่ยนค่านี้เป็นสตริงสุ่มยาวๆ',
];
