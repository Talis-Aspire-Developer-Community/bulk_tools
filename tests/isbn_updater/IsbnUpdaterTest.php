<?php

declare(strict_types=1);

require_once __DIR__ . '/../../isbn_updater/src/isbn.php';

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IsbnUpdaterTest extends TestCase
{
    public function testRaw(): void
    {
        $isbn = new ISBN('9783161484100');
        $this->assertEquals('9783161484100', $isbn->getRaw());
    }

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
        $isbn = new ISBN($isbn);
        $this->assertEquals($expected, $isbn->clean());
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
        ];
    }

    #[DataProvider('validISBN13Values')]
    public function testIsValidIsbn13(string $isbn, $want): void
    {
        $isbn = new ISBN($isbn);
        $this->assertEquals($want, $isbn->isValidIsbn13());
    }
}
