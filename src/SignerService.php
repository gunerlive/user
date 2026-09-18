<?php

namespace ESign;

use PDO;

class SignerService
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * ลบผู้ลงนามเดิมทั้งหมดของเอกสาร แล้วเพิ่มรายชื่อใหม่ตามลำดับที่ส่งเข้ามา
     * ใช้ตอนสร้าง/แก้ไขเอกสารในสถานะร่างเท่านั้น (ก่อนเปิดให้เซ็นจริง)
     *
     * @param array<int, array{full_name:string, position_title:string, sig_page:int, sig_x_pct:float, sig_y_pct:float, sig_w_pct:float}> $signers
     */
    public function replaceSigners(int $documentId, array $signers): void
    {
        $delete = $this->db->prepare('DELETE FROM signflow_signers WHERE document_id = :document_id');
        $delete->execute(['document_id' => $documentId]);

        $insert = $this->db->prepare(
            'INSERT INTO signflow_signers (document_id, order_no, full_name, position_title, token, sig_page, sig_x_pct, sig_y_pct, sig_w_pct)
             VALUES (:document_id, :order_no, :full_name, :position_title, :token, :sig_page, :sig_x_pct, :sig_y_pct, :sig_w_pct)'
        );

        $order = 1;
        foreach ($signers as $signer) {
            $insert->execute([
                'document_id'     => $documentId,
                'order_no'        => $order,
                'full_name'       => $signer['full_name'],
                'position_title'  => $signer['position_title'],
                'token'           => Helpers::randomToken(24),
                'sig_page'        => $signer['sig_page'],
                'sig_x_pct'       => $signer['sig_x_pct'],
                'sig_y_pct'       => $signer['sig_y_pct'],
                'sig_w_pct'       => $signer['sig_w_pct'],
            ]);
            $order++;
        }
    }

    public function updatePosition(int $signerId, int $documentId, int $page, float $xPct, float $yPct, float $wPct): void
    {
        $stmt = $this->db->prepare(
            'UPDATE signflow_signers SET sig_page = :page, sig_x_pct = :x, sig_y_pct = :y, sig_w_pct = :w
             WHERE id = :id AND document_id = :document_id'
        );
        $stmt->execute([
            'page'        => $page,
            'x'           => $xPct,
            'y'           => $yPct,
            'w'           => $wPct,
            'id'          => $signerId,
            'document_id' => $documentId,
        ]);
    }

    public function listByDocument(int $documentId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM signflow_signers WHERE document_id = :document_id ORDER BY order_no ASC');
        $stmt->execute(['document_id' => $documentId]);
        return $stmt->fetchAll();
    }

    public function findByToken(string $token): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT s.*, d.title AS document_title, d.document_number, d.status AS document_status,
                    d.working_file_path, d.page_count, d.drive_web_view_link
             FROM signflow_signers s
             JOIN signflow_documents d ON d.id = s.document_id
             WHERE s.token = :token LIMIT 1'
        );
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function currentSigner(int $documentId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM signflow_signers WHERE document_id = :document_id AND status = "pending" ORDER BY order_no ASC LIMIT 1'
        );
        $stmt->execute(['document_id' => $documentId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function isSignerTurn(array $signer): bool
    {
        $current = $this->currentSigner((int) $signer['document_id']);
        return $current !== null && (int) $current['id'] === (int) $signer['id'];
    }

    public function markSigned(int $signerId, string $ip): void
    {
        $stmt = $this->db->prepare('UPDATE signflow_signers SET status = "signed", signed_at = NOW(), signed_ip = :ip WHERE id = :id');
        $stmt->execute(['ip' => $ip, 'id' => $signerId]);
    }

    public function allSigned(int $documentId): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM signflow_signers WHERE document_id = :document_id AND status = "pending"');
        $stmt->execute(['document_id' => $documentId]);
        return (int) $stmt->fetchColumn() === 0;
    }
}
