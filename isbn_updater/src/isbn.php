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
     * Check if this is a valid isbn13
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
     * Check if this is a valid isbn10
     */
    public function isValidIsbn10(): bool
    {
        $isbn = $this->clean();

        // ISBN-10 must be 10 characters long
        if (strlen($isbn) != 10) {
            return false;
        }

        // Calculate the sum of the first 9 digits multiplied by their position (1 to 9)
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            if (!is_numeric($isbn[$i])) {
                return false;  // Ensure characters 1-9 are digits
            }
            $sum += ($i + 1) * $isbn[$i];
        }

        // The last character, which is the check digit, can also be 'X'
        $lastChar = $isbn[9];
        if ($lastChar == 'X') {
            $sum += 10 * 10;
        } elseif (is_numeric($lastChar)) {
            $sum += 10 * $lastChar;
        } else {
            return false;  // Last character must be a digit or 'X'
        }

        // Valid ISBN-10 must be divisible by 11
        return $sum % 11 == 0;
    }

    /**
     * Check if this is valid (either ISBN10 or ISBN13)
     */
    public function isValid(): bool
    {
        return $this->isValidIsbn10() || $this->isValidIsbn13();
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
