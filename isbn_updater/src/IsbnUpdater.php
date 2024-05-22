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

    /**
     * Remove an ISBN from the list of existing ISBNs
     * It will return true if the isbn was removed, false if not.
     * False is not an indication of error, only that no change was made,
     * most likely the isbn may not exist.
     * 
     * @param string $oldIsbn
     * @return bool - true if the isbn was removed, false if not.
     */
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

    /**
     * Add an ISBN to the list of existing ISBNs
     * It will automatically determine if it is an ISBN-10 or ISBN-13
     * and add it to the appropriate list
     * 
     * It will return true if the isbn was added, false if not.
     * False is not necessarily an indication of error, it could be that the ISBN
     * is empty, invalid, or already in the list.  
     * The response indicates only that no change has been made.
     * 
     * @param string $newIsbn
     * @return bool - true if the isbn was added, false if not.
     */
    public function addIsbn(string $newIsbn): bool
    {
        if (!$this->isbnParser->isValid($newIsbn)) {
            return false;
        }
        $changeMade = false;
        if ($this->isbnParser->isValidIsbn13($newIsbn) && !in_array($newIsbn, $this->existingIsbn13s)) {
            $this->existingIsbn13s[] = $newIsbn;
            $changeMade = true;
        } elseif ($this->isbnParser->isValidIsbn10($newIsbn) && !in_array($newIsbn, $this->existingIsbn10s))  {
            $this->existingIsbn10s[] = $newIsbn;
            $changeMade = true;
        }
        return $changeMade;
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
