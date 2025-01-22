<?php

namespace Hexlet\Code;

class UrlRepository
{
    private \PDO $conn;

    public function __construct(\PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getEntities(): array // массив всех добавленных url (было Url)
    {
        $urls = [];
        $sql = "SELECT * FROM urls";
        $stmt = $this->conn->query($sql);

        while ($row = $stmt->fetch()) {
            // $url = Url::fromArray([$row['name'], $row['created_at']]);
            // $url->setId($row['id']);
            $url = ['id' => $row['id'], 'name' => $row['name'], 'created_at' => $row['created_at']];
            $urls[] = $url;
        }

        return $urls;
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM urls WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        if ($row = $stmt->fetch())  { // $row - асс массив либо false
            // $url = Url::fromArray([$row['name'], $row['created_at']]);
            // $url->setId($row['id']);
            $url = ['id' => $row['id'], 'name' => $row['name'], 'created_at' => $row['created_at']];
            return $url;
        }

        return null;
    }

    public function findByName(string $url): ?Url
    {
        $sql = "SELECT * FROM urls WHERE name = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$url]);
        if ($row = $stmt->fetch())  { // $row - асс массив либо false
            $url = Url::fromArray([$row['name'], $row['created_at']]);
            $url->setId($row['id']);
            return $url;
        }

        return null;
    }

    public function save(Url $url): void
    {
        $sql = "INSERT INTO urls (name, created_at) VALUES (:name, :created_at)";
        $stmt = $this->conn->prepare($sql);
        $name = $url->getName();
        $created_at = $url->getCreatedAt();
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':created_at', $created_at);
        $stmt->execute();
        
        $id = (int) $this->conn->lastInsertId();
        $url->setId($id);
    }
}