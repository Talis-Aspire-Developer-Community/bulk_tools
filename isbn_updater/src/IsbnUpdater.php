<?php

declare(strict_types=1);

require_once 'IsbnParser.php';

class IsbnUpdater
{
    private array $existingIsbn10s = [];
    private array $existingIsbn13s = [];
    private IsbnParser $isbnParser;

    public function __construct(IsbnParser $isbnParser = new IsbnParser())
    {
        $this->isbnParser = $isbnParser;
    }

    public function setExistingIsbn10s(array $existingIsbns): void
    {
        $this->existingIsbn10s = $existingIsbns;
    }

    public function setExistingIsbn13s(array $existingIsbns): void
    {
        $this->existingIsbn13s = $existingIsbns;
    }

    public function removeIsbn(string $oldIsbn): bool
    {
        $removed = false;
        $newIsbn10s = [];
        for ($i = 0; $i < count($this->existingIsbn10s); $i++) {
            if ($this->existingIsbn10s[$i] === $oldIsbn) {
                $removed = true;
                continue;
            }
            $newIsbn10s[] = $this->existingIsbn10s[$i];
        }
        $this->existingIsbn10s = $newIsbn10s;
        $newIsbn13s = [];
        for ($i = 0; $i < count($this->existingIsbn13s); $i++) {
            if ($this->existingIsbn13s[$i] === $oldIsbn) {
                $removed = true;
                continue;
            }
            $newIsbn13s[] = $this->existingIsbn13s[$i];
        }
        
        $this->existingIsbn13s = $newIsbn13s;
        return $removed;
    }

    public function addIsbn(string $newIsbn): bool
    {
        if (!$this->isbnParser->isValid($newIsbn)) {
            return false;
        }
        if ($this->isbnParser->isValidIsbn13($newIsbn) && !in_array($newIsbn, $this->existingIsbn13s)) {
            $this->existingIsbn13s[] = $newIsbn;
        } elseif ($this->isbnParser->isValidIsbn10($newIsbn) && !in_array($newIsbn, $this->existingIsbn10s))  {
            $this->existingIsbn10s[] = $newIsbn;
        }
        return true;
    }

    public function getIsbn10s(): array
    {
        return $this->existingIsbn10s;
    }

    public function getIsbn13s(): array
    {
        return $this->existingIsbn13s;
    }

}
