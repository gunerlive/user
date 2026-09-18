<?php

namespace ESign;

use PDO;

class DocumentService
{
    public function __construct(private PDO $db)
    {
    }

    public function create(array $data, int $userId): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO documents (document_number, title, description, original_filename, working_file_path, page_count, status, created_by)
             VALUES (:document_number, :title, :description, :original_filename, :working_file_path, :page_count, "draft", :created_by)'
        );
        $stmt->execute([
            'document_number'    => $data['document_number'],
            'title'              => $data['title'],
            'description'        => $data['description'],
            'original_filename'  => $data['original_filename'],
            'working_file_path'  => $data['working_file_path'],
            'page_count'         => $data['page_count'],
            'created_by'         => $userId,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM documents WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function listAll(?string $statusFilter = null): array
    {
        if ($statusFilter && $statusFilter !== 'all') {
            $stmt = $this->db->prepare('SELECT * FROM documents WHERE status = :status ORDER BY created_at DESC');
            $stmt->execute(['status' => $statusFilter]);
        } else {
            $stmt = $this->db->query('SELECT * FROM documents ORDER BY created_at DESC');
        }
        return $stmt->fetchAll();
    }

    public function setStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare('UPDATE documents SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function markSaved(int $id, array $driveInfo): void
    {
        $stmt = $this->db->prepare(
            'UPDATE documents SET status = "saved", drive_file_id = :file_id, drive_web_view_link = :web_view_link,
             drive_name = :name, drive_mime_type = :mime_type, drive_size = :size, saved_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            'file_id'       => $driveInfo['file_id'] ?? null,
            'web_view_link' => $driveInfo['webViewLink'] ?? null,
            'name'          => $driveInfo['name'] ?? null,
            'mime_type'     => $driveInfo['mimeType'] ?? null,
            'size'          => $driveInfo['size'] ?? null,
            'id'            => $id,
        ]);
    }

    public function logActivity(int $documentId, string $message): void
    {
        $stmt = $this->db->prepare('INSERT INTO activity_log (document_id, message) VALUES (:document_id, :message)');
        $stmt->execute(['document_id' => $documentId, 'message' => $message]);
    }

    public function getActivity(int $documentId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM activity_log WHERE document_id = :document_id ORDER BY created_at ASC');
        $stmt->execute(['document_id' => $documentId]);
        return $stmt->fetchAll();
    }

    public function counts(): array
    {
        $stmt = $this->db->query('SELECT status, COUNT(*) AS total FROM documents GROUP BY status');
        $result = ['all' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['status']] = (int) $row['total'];
            $result['all'] += (int) $row['total'];
        }
        return $result;
    }
}
