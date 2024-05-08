<?php

declare(strict_types=1);
final class ISBN
{
    private string $isbnStr;

    public function __construct(string $isbnStr)
    {
        $this->isbnStr = $isbnStr;
    }

    public function getRaw(): string
    {
        return $this->isbnStr;
    }

    /**
     * Check if the ISBN13 is valid
     * 
     * @return bool
     */
    public function isValidIsbn13(): bool
    {
        $isbn = $this->clean();
        $check = 0;
        // if it looks like an ISBN13
        if (preg_match("/^97[89]\d{9}[\dxX]$/", $isbn)) {
            for ($i = 0; $i < 13; $i += 2) {
                $check += (int)$isbn[$i];
            }

            for ($i = 1; $i < 12; $i += 2) {
                $check += 3 * $isbn[$i];
            }
            // and if the check digit is valid.
            return (0 === ($check % 10)) ? true : false;
        }

        // else it can't be an ISBN13
        return false;
    }

    /**
     * Clean the ISBN string
     * 
     * Removes any dash or line characters from the ISBN string
     * trims whitespace
     * @return string
     */
    public function clean(): string
    {
        $trimmed = trim($this->isbnStr);
        return preg_replace('/[-]/', '', $trimmed);
    }
}
