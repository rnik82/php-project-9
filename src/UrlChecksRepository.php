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
        $sql = "INSERT INTO url_checks (url_id, created_at, status_code)
                VALUES (:url_id, :created_at, :status_code)";

        $url_id = $check->getUrlId();
        $created_at = $check->getCreatedAt();
        $status_code = $check->getStatusCode();

        $stmt = $this->conn->prepare($sql);
    
        $stmt->bindParam(':url_id', $url_id);
        $stmt->bindParam(':created_at', $created_at);
        $stmt->bindParam(':status_code', $status_code);
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
        $row = $stmt->fetch(); // $row - асс массив либо false
        return $row === false ? [] : $row;
    }

    public function findChecksByUrlId(int $id): array
    {
        $url_checks = [];
        $sql = "SELECT * FROM url_checks WHERE url_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);

        while ($row = $stmt->fetch())  { // $row - асс массив либо false
            $check = ['id' => $row['id'], 'created_at' => $row['created_at'], 'status_code' => $row['status_code']];
            $url_checks[] = $check;
        }

        return $url_checks;
    }

    public function getEntities(): array // массив всех добавленных проверок (ckecks);
    {
        $url_checks = [];
        $sql = "SELECT * FROM url_checks";
        $stmt = $this->conn->query($sql);
        while ($row = $stmt->fetch()) {
            $check = ['id' => $row['id'], 'created_at' => $row['created_at'], 'status_code' => $row['status_code']];
            $url_checks[] = $check;
        }

        return $url_checks;
    }
}