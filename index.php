<?php
// ไฟล์นี้มีไว้เผื่อกรณีที่โฮสต์ไม่สามารถตั้งค่า Document Root ให้ชี้ไปที่โฟลเดอร์ public/ ได้
// (เช่น อัปโหลดทั้งโปรเจกต์ไปที่ public_html ตรงๆ) จะช่วย redirect ไปหน้าเข้าสู่ระบบให้อัตโนมัติ
// ถ้าตั้ง Document Root เป็น public/ ได้แล้ว ไฟล์นี้จะไม่ถูกใช้งาน
header('Location: public/index.php');
exit;
