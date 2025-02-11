<?php

namespace Hexlet\Code;

class Check
{
    private ?int $id = null;
    private ?int $urlId = null;
    private ?string $statusCode = null;
    private ?string $h1 = null;
    private ?string $title = null;
    private ?string $description = null;
    private ?string $createdAt = null;

    public static function fromArray(array $checkData): Check
    {
        [$urlId, $createdAt, $statusCode, $h1, $title, $description] = $checkData;
        $check = new Check();
        $check->setUrlId($urlId);
        $check->setCreatedAt($createdAt);
        $check->setStatusCode($statusCode);
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
        return $this->urlId;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getStatusCode(): ?string
    {
        return $this->statusCode;
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

    public function setUrlId(int $urlId): void
    {
        $this->urlId = $urlId;
    }

    public function setStatusCode(string $statusCode): void
    {
        $this->statusCode = $statusCode;
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

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
