<?php

namespace Hexlet\Code;

class UrlChecksRepository
{
    private \PDO $conn;

    public function __construct(\PDO $conn)
    {
        $this->conn = $conn;
    }

    public function save(Check $check): void
    {
        $sql = "INSERT INTO 
                url_checks (url_id, created_at, status_code, h1, title, description)
                VALUES 
                (:url_id, :created_at, :status_code, :h1, :title, :description)";

        $urlId = $check->getUrlId();
        $createdAt = $check->getCreatedAt();
        $statusCode = $check->getStatusCode();
        $h1 = $check->getH1();
        $title = $check->getTitle();
        $description = $check->getDescription();

        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(':url_id', $urlId);
        $stmt->bindParam(':created_at', $createdAt);
        $stmt->bindParam(':status_code', $statusCode);
        $stmt->bindParam(':h1', $h1);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->execute();

        $id = (int) $this->conn->lastInsertId();
        $check->setId($id);
    }

    public function getLatestCheckInfo(int $id): array
    {
        $sql = "SELECT DISTINCT ON (url_id)
                status_code as url_check_status_code,
                created_at as url_check_date
                FROM url_checks
                WHERE url_id = ?
                ORDER BY url_id, created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? [] : $row;
    }

    public function findChecksByUrlId(int $id): array
    {
        $urlChecks = [];
        $sql = "SELECT * FROM url_checks WHERE url_id = ? ORDER BY id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);

        while ($row = $stmt->fetch()) {
            $check = [
                'id' => $row['id'], 'created_at' => $row['created_at'],
                'status_code' => $row['status_code'], 'h1' => $row['h1'],
                'title' => $row['title'], 'description' => $row['description']
            ];
            $urlChecks[] = $check;
        }

        return $urlChecks;
    }

    public function getEntities(): array
    {
        $urlChecks = [];
        $sql = "SELECT * FROM url_checks";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        while ($row = $stmt->fetch()) {
            $check = [
                'id' => $row['id'],
                'url_id' => $row['url_id'],
                'created_at' => $row['created_at'],
                'status_code' => $row['status_code'],
                'h1' => $row['h1'],
                'title' => $row['title'],
                'description' => $row['description']
            ];
            $urlChecks[] = $check;
        }

        return $urlChecks;
    }
}
