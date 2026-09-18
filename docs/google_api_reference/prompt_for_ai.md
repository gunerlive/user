# PROMPT สำหรับให้ AI สร้างระบบเชื่อม Google_API กลางของโรงเรียน

ให้คัดลอกข้อความนี้ไปวางใน AI แล้วแก้ไขข้อมูลในวงเล็บให้ตรงกับระบบของครู

---

ช่วยสร้างระบบเว็บ PHP ชื่อ “[ใส่ชื่อระบบ]” ให้เชื่อมต่อกับ Google_API Center กลางของโรงเรียน

## ข้อมูล API กลางของโรงเรียน

- Base URL: https://rpk24.ac.th/Google_API
- OAuth Callback กลาง: https://rpk24.ac.th/Google_API/oauth_callback.php
- Upload Endpoint: https://rpk24.ac.th/Google_API/api_upload.php

## ข้อกำหนดสำคัญ

1. ระบบใหม่ห้ามสร้าง OAuth Client ID ใหม่
2. ระบบใหม่ห้ามทำ OAuth เอง
3. ให้ส่งไฟล์ไปที่ API กลางของโรงเรียนเท่านั้น
4. ให้ใช้ endpoint นี้สำหรับอัปโหลดไฟล์:
   https://rpk24.ac.th/Google_API/api_upload.php

## ข้อมูลระบบย่อย

- system_key: [ใส่ system_key ที่ได้จากผู้ดูแล]
- api_secret: [ใส่ api_secret ที่ได้จากผู้ดูแล]
- allowed_extensions: jpg,png,pdf,doc,docx,xls,xlsx
- max_file_size_mb: 20

## วิธีส่งไฟล์

ให้ส่งข้อมูลแบบ POST multipart/form-data ประกอบด้วย:

- system_key
- api_secret
- uploader_name
- description
- file

## ผลลัพธ์ที่ต้องบันทึก

เมื่อ API ส่ง JSON กลับมา ให้บันทึกค่าต่อไปนี้ลงฐานข้อมูลของระบบใหม่:

- file_id
- webViewLink
- name
- mimeType
- size
- created_at

## UI และการใช้งาน

1. หน้าเว็บต้องรองรับมือถือ
2. มี Drag & Drop Upload
3. ตรวจสอบชนิดไฟล์และขนาดไฟล์ก่อนอัปโหลด
4. แสดงลิงก์เปิดไฟล์ Google Drive จาก webViewLink
5. ถ้าอัปโหลดไม่สำเร็จ ให้แสดงข้อความ error แบบเข้าใจง่าย
6. ขอไฟล์ระบบฉบับสมบูรณ์ พร้อมใช้งานบนโฮสต์ PHP

## ตัวอย่าง PHP cURL

```php
$apiUrl = 'https://rpk24.ac.th/Google_API/api_upload.php';

$postData = [
    'system_key'    => 'your_system_key',
    'api_secret'    => 'your_api_secret',
    'uploader_name' => 'ชื่อผู้ส่งไฟล์',
    'description'   => 'คำอธิบายไฟล์',
    'file'          => new CURLFile(
        $_FILES['file']['tmp_name'],
        $_FILES['file']['type'],
        $_FILES['file']['name']
    )
];

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $postData,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 120
]);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    die('เชื่อมต่อ API ไม่สำเร็จ: ' . $error);
}

$data = json_decode($response, true);

if (!empty($data['success'])) {
    echo 'อัปโหลดสำเร็จ: ' . $data['webViewLink'];
} else {
    echo 'อัปโหลดไม่สำเร็จ: ' . ($data['message'] ?? $response);
}
```
