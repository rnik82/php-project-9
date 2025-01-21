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
        $sql = "INSERT INTO url_checks (url_id, created_at) VALUES (:url_id, :created_at)";
        $stmt = $this->conn->prepare($sql);
        //$status_code = $url->getStatusCode() and so on ...
        $url_id = $check->getUrlId();
        $created_at = $check->getCreatedAt();
        //$stmt->bindParam(':status_code', $status_code) and so on ...
        $stmt->bindParam(':url_id', $url_id);
        $stmt->bindParam(':created_at', $created_at);
        $stmt->execute();
        
        $id = (int) $this->conn->lastInsertId();
        $check->setId($id);
    }

    public function getLatestCheck(array $checks, int $id)//: string
    {
        $sql = "SELECT MAX(created_at) as url_check_date FROM url_checks WHERE url_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row;
    }

    public function findChecksByUrlId(int $id): array
    {
        $url_checks = [];
        $sql = "SELECT * FROM url_checks WHERE url_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        //dump('не дошло до while в function findChecksByUrlId(int $id)');
        while ($row = $stmt->fetch())  { // $row - асс массив либо false
            $check = ['id' => $row['id'], 'created_at' => $row['created_at']];
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
            $check = ['id' => $row['id'], 'created_at' => $row['created_at']];
            $url_checks[] = $check;
        }

        return $url_checks;
    }
}