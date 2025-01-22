<?php

namespace Hexlet\Code;

class Check
{
    private ?int $id = null;
    private ?int $url_id = null;
    private ?string $status_code = null; // добавить код ниже
    // private ?string $h1 = null;
    // private ?string $title = null;
    // private ?string $description = null;
    private ?string $created_at = null;

    public static function fromArray(array $checkData): Check
    {
        [$url_id, $created_at, $status_code] = $checkData;
        $check = new Check();
        $check->setUrlId($url_id);
        $check->setCreatedAt($created_at);
        $check->setStatusCode($status_code);
        //$check->setDescription($description) and so on
        
        return $check;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrlId(): ?string
    {
        return $this->url_id;
    }

    public function getCreatedAt(): ?string
    {
        return $this->created_at;
    }

    public function getStatusCode(): ?string
    {
        return $this->status_code;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setUrlId(int $url_id): void
    {
        $this->url_id = $url_id;
    }

    public function setCreatedAt(string $created_at): void
    {
        $this->created_at = $created_at;
    }

    public function setStatusCode(string $status_code): void
    {
        $this->status_code = $status_code;
    }

    // public function exists(): bool
    // {
    //     return !is_null($this->getId());
    // }
}