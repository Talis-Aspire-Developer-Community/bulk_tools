<?php

declare(strict_types=1);

require_once __DIR__ . '/../../isbn_updater/src/IsbnParser.php';

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IsbnParserTest extends TestCase
{
    // Clean ISBN
    public static function cleanISBNValues(): array
    {
        return [
            'should remove dashes'                          => ['978-31614-84100', '9783161484100',],
            'should leave untouched if cleaned already'     => ['9783161484100', '9783161484100'],
            'should not remove whitespace in the middle'    => ['978 31614 84100', '978 31614 84100'],
            'should trim whitespace'                        => [' 9780439023481 ', '9780439023481'],
        ];
    }

    #[DataProvider('cleanISBNValues')]
    public function testClean(string $isbn, string $expected): void
    {
        $sut = new IsbnParser();
        $this->assertEquals($expected, $sut->clean($isbn));
    }

    // ISBN13 Validation
    public static function validISBN13Values(): array
    {
        return [
            // valid
            'pre-cleaned'           => ['9783161484100', true],
            'dashes'                => ['978-31614-84100', true],
            'more dashes'           => ['978-0-439-02348-1', true],
            'untrimmed whitespace'  => [' 9783161484100 ', true],
            '979 prefix'            => ['9790001012966', true],

            // invalid
            'invalid - whitespace in middle' => ['978 31614 84100', false],
            'invalid - too short'            => ['978316148410', false],
            'invalid - too long'             => ['97831614841000', false],
            'invalid - bad check digit'      => ['9783161484101', false],
            'invalid - extra characters'     => ['9783161484100(pbk.)', false],
            'invalid - bad prefix'           => ['9773161484100', false],
        ];
    }

    #[DataProvider('validISBN13Values')]
    public function testIsValidIsbn13(string $isbn, $want): void
    {
        $sut = new IsbnParser();
        $this->assertEquals($want, $sut->isValidIsbn13($isbn));
    }

    // ISBN10 Validation
    public static function validISBN10Values(): array
    {
        return [
            // valid
            'pre-cleaned'           => ['097522980X', true],
            'dashes'                => ['0-9752298-0-X', true],
            'more dashes'           => ['0-975-22980-X', true],
            'untrimmed whitespace'  => [' 097522980X ', true],

            // invalid
            'invalid - whitespace in middle' => ['09752298 0X', false],
            'invalid - too short'            => ['097522980', false],
            'invalid - too long'             => ['097522980XX', false],
            'invalid - bad check digit'      => ['0975229801', false],
            'invalid - extra characters'     => ['097522980X(pbk.)', false],
        ];
    }

    #[DataProvider('validISBN10Values')]
    public function testIsValidIsbn10(string $isbn, $want): void
    {
        $sut = new ISBNParser();
        $this->assertEquals($want, $sut->isValidIsbn10($isbn));
    }

    // ISBN Validation (Both ISBN10 and ISBN13)
    public static function validISBNValues(): array
    {
        return [
            // valid
            'isbn13' => ['9783161484100', true],
            'isbn10' => ['097522980X', true],

            // invalid
            'invalid - whitespace in middle'    => ['978 31614 84100', false],
            'invalid - too short'               => ['978316148410', false],
            'invalid - too long'                => ['97831614841000', false],
            'invalid - bad check digit isbn13'  => ['9783161484101', false],
            'invalid - bad check digit isbn10'  => ['0975229809', false],
            'invalid - extra characters isbn13' => ['9783161484100(pbk.)', false],
            'invalid - extra characters isbn10' => ['097522980X(pbk.)', false],
        ];
    }

    #[DataProvider('validISBNValues')]
    public function testIsValid(string $isbn, $want): void
    {
        $sut = new ISBNParser();
        $this->assertEquals($want, $sut->isValid($isbn));
    }
}
