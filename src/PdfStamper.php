<?php

namespace ESign;

use setasign\Fpdi\Fpdi;

/**
 * วางลายเซ็น (รูปที่วาดด้วยนิ้ว/เมาส์ + ชื่อกำกับ) ลงบน PDF เดิม โดยไม่แก้ไขเนื้อหาอื่นของเอกสาร
 * ใช้ FPDI นำเข้าแต่ละหน้าของ PDF เดิมมาเป็นแม่แบบ แล้ววาดรูปทับเฉพาะตำแหน่งที่กำหนด
 */
class PdfStamper
{
    public static function pageCount(string $pdfPath): int
    {
        $pdf = new Fpdi();
        return $pdf->setSourceFile($pdfPath);
    }

    /**
     * สร้างรูปลายเซ็นแบบผสม (ลายเซ็นที่วาด + ชื่อ + วันที่) เป็นไฟล์ PNG พื้นหลังโปร่งใส
     * ถ้าไม่พบไฟล์ฟอนต์ไทย จะได้เฉพาะรูปลายเซ็นที่วาด (ไม่มีข้อความกำกับ)
     */
    public static function composeSignatureImage(
        string $signaturePngDataUrl,
        string $signerName,
        string $dateLabel,
        ?string $thaiFontPath,
        string $tmpDir
    ): string {
        if (!preg_match('/^data:image\/png;base64,(.+)$/', $signaturePngDataUrl, $matches)) {
            throw new \InvalidArgumentException('รูปแบบข้อมูลลายเซ็นไม่ถูกต้อง');
        }

        $binary = base64_decode($matches[1], true);
        if ($binary === false) {
            throw new \InvalidArgumentException('ไม่สามารถถอดรหัสรูปลายเซ็นได้');
        }

        $signatureImage = @imagecreatefromstring($binary);
        if ($signatureImage === false) {
            throw new \RuntimeException('ไม่สามารถอ่านรูปลายเซ็นได้ กรุณาวาดลายเซ็นใหม่อีกครั้ง');
        }

        $sigWidth = imagesx($signatureImage);
        $sigHeight = imagesy($signatureImage);
        $hasFont = $thaiFontPath !== null && is_file($thaiFontPath);
        $textAreaHeight = $hasFont ? 90 : 0;

        $canvas = imagecreatetruecolor($sigWidth, $sigHeight + $textAreaHeight);
        imagesavealpha($canvas, true);
        imagealphablending($canvas, false);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $transparent);
        imagealphablending($canvas, true);

        imagecopy($canvas, $signatureImage, 0, 0, 0, 0, $sigWidth, $sigHeight);
        imagedestroy($signatureImage);

        if ($hasFont) {
            $navy = imagecolorallocate($canvas, 15, 23, 42);
            $gray = imagecolorallocate($canvas, 100, 116, 139);
            self::centerText($canvas, $thaiFontPath, 20, $navy, '(' . $signerName . ')', $sigHeight + 6, $sigWidth);
            self::centerText($canvas, $thaiFontPath, 15, $gray, $dateLabel, $sigHeight + 40, $sigWidth);
        }

        $outputPath = rtrim($tmpDir, '/') . '/' . uniqid('sig_', true) . '.png';
        imagepng($canvas, $outputPath);
        imagedestroy($canvas);

        return $outputPath;
    }

    /**
     * วางรูปลายเซ็นแบบผสมลงบน PDF ที่ตำแหน่งเปอร์เซ็นต์ของหน้า (0-100)
     * แล้วเขียนทับไฟล์ PDF เดิม (working file)
     */
    public static function stampSignature(
        string $workingPdfPath,
        int $targetPage,
        float $xPercent,
        float $yPercent,
        float $widthPercent,
        string $signatureImagePath,
        string $tmpDir
    ): void {
        $imageInfo = getimagesize($signatureImagePath);
        if ($imageInfo === false) {
            throw new \RuntimeException('ไม่พบไฟล์รูปลายเซ็นที่จะวาง');
        }
        [$imgWidth, $imgHeight] = $imageInfo;

        $pdf = new Fpdi('P', 'pt');
        $pageCount = $pdf->setSourceFile($workingPdfPath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);

            if ($pageNo === $targetPage) {
                $boxWidth = $size['width'] * ($widthPercent / 100);
                $boxHeight = $boxWidth * ($imgHeight / $imgWidth);
                $x = $size['width'] * ($xPercent / 100);
                $y = $size['height'] * ($yPercent / 100);
                $pdf->Image($signatureImagePath, $x, $y, $boxWidth, $boxHeight, 'PNG');
            }
        }

        $outputPath = rtrim($tmpDir, '/') . '/' . uniqid('stamped_', true) . '.pdf';
        $pdf->Output('F', $outputPath);

        if (!@rename($outputPath, $workingPdfPath)) {
            copy($outputPath, $workingPdfPath);
            unlink($outputPath);
        }
    }

    private static function centerText($canvas, string $fontPath, int $fontSize, int $color, string $text, int $y, int $canvasWidth): void
    {
        $box = imagettfbbox($fontSize, 0, $fontPath, $text);
        $textWidth = abs($box[2] - $box[0]);
        $x = max(0, (int) (($canvasWidth - $textWidth) / 2));
        imagettftext($canvas, $fontSize, 0, $x, $y + $fontSize, $fontPath, $color, $text);
    }
}
