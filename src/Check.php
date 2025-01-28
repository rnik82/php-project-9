<?php

namespace Hexlet\Code;

class Check
{
    private ?int $id = null;
    private ?int $url_id = null;
    private ?string $status_code = null;
    private ?string $h1 = null;
    private ?string $title = null;
    private ?string $description = null;
    private ?string $created_at = null;

    public static function fromArray(array $checkData): Check
    {
        [$url_id, $created_at, $status_code, $h1, $title, $description] = $checkData;
        $check = new Check();
        $check->setUrlId($url_id);
        $check->setCreatedAt($created_at);
        $check->setStatusCode($status_code);
        $check->setH1($h1);
        $check->setTitle($title);
        $check->setDescription($description);

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getH1(): ?string
    {
        return $this->h1;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setUrlId(int $url_id): void
    {
        $this->url_id = $url_id;
    }

    public function setStatusCode(string $status_code): void
    {
        $this->status_code = $status_code;
    }

    public function setH1(?string $h1): void
    {
        $this->h1 = $h1;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function setCreatedAt(string $created_at): void
    {
        $this->created_at = $created_at;
    }
}
