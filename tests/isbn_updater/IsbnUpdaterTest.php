<?php

declare(strict_types=1);

require_once __DIR__ . '/../../isbn_updater/src/IsbnUpdater.php';

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IsbnUpdaterTest extends TestCase
{
    public static function addIsbnDataProvider(): array
    {
        return [
            'add ISBN-10' => [
                'existingIsbn10s' => ['0-9752298-0-X', '0205080057'],
                'existingIsbn13s' => ['978-3161484100', '9780439023481'],
                'isbnToAdd' => '0-19-853453-1',
                'expectedIsbn10s' => ['0-9752298-0-X', '0205080057', '0-19-853453-1'],
                'expectedIsbn13s' => ['978-3161484100', '9780439023481'],
                'wantSuccess' => true
            ],
            'add existing ISBN-10 (should not duplicate)' => [
                'existingIsbn10s' => ['0-9752298-0-X', '0205080057'],
                'existingIsbn13s' => ['978-3161484100', '9780439023481'],
                'isbnToAdd' => '0205080057',
                'expectedIsbn10s' => ['0-9752298-0-X', '0205080057'],
                'expectedIsbn13s' => ['978-3161484100', '9780439023481'],
                'wantSuccess' => true                
            ],
            'add ISBN-13' => [
                'existingIsbn10s' => ['0-9752298-0-X', '0205080057'],
                'existingIsbn13s' => ['978-3161484100', '9780439023481'],
                'isbnToAdd' => '978-0198534532',
                'expectedIsbn10s' => ['0-9752298-0-X', '0205080057'],
                'expectedIsbn13s' => ['978-3161484100', '9780439023481', '978-0198534532'],
                'wantSuccess' => true
            ],
            'add existing ISBN-13 (should not duplicate)' => [
                'existingIsbn10s' => ['0-9752298-0-X', '0205080057'],
                'existingIsbn13s' => ['978-3161484100', '9780439023481'],
                'isbnToAdd' => '978-3161484100',
                'expectedIsbn10s' => ['0-9752298-0-X', '0205080057'],
                'expectedIsbn13s' => ['978-3161484100', '9780439023481'],
                'wantSuccess' => true
            ],
            'add invalid ISBN13 should fail' => [
                'existingIsbn10s' => ['0-9752298-0-X', '0205080057'],
                'existingIsbn13s' => ['978-3161484100', '9780439023481'],
                'isbnToAdd' => '978-3161484101',
                'expectedIsbn10s' => ['0-9752298-0-X', '0205080057'],
                'expectedIsbn13s' => ['978-3161484100', '9780439023481'],
                'wantSuccess' => false
            ],
            'add invalid ISBN10 should fail' => [
                'existingIsbn10s' => ['0-9752298-0-X', '0205080057'],
                'existingIsbn13s' => ['978-3161484100', '9780439023481'],
                'isbnToAdd' => '0205080058',
                'expectedIsbn10s' => ['0-9752298-0-X', '0205080057'],
                'expectedIsbn13s' => ['978-3161484100', '9780439023481'],
                'wantSuccess' => false
            ],
        ];
    }

    #[DataProvider('addIsbnDataProvider')]
    public function testAddIsbn(
        array $existingIsbn10s, 
        array $existingIsbn13s, 
        string $isbnToAdd, 
        array $expectedIsbn10s, 
        array $expectedIsbn13s, 
        bool $wantSuccess
    ): void
    {
        $sut = new IsbnUpdater();
        $sut->setExistingIsbn10s($existingIsbn10s);
        $sut->setExistingIsbn13s($existingIsbn13s);
        $result = $sut->addIsbn($isbnToAdd);
        $this->assertEquals($wantSuccess, $result);
        $this->assertEquals($expectedIsbn10s, $sut->getIsbn10s());
        $this->assertEquals($expectedIsbn13s, $sut->getIsbn13s());
    }

    public static function removeIsbnDataProvider(): array
    {
        return [
            'remove ISBN-10' => [
                'existingIsbn10s' => ['0-9752298-0-X', '0205080057'],
                'existingIsbn13s' => ['978-3161484100', '9780439023481'],
                'isbnToRemove' => '0-9752298-0-X',
                'expectedIsbn10s' => ['0205080057'],
                'expectedIsbn13s' => ['978-3161484100', '9780439023481'],
                'wantSuccess' => true
            ],
            'remove ISBN-13' => [
                'existingIsbn10s' => ['0-9752298-0-X', '0205080057'],
                'existingIsbn13s' => ['978-3161484100', '9780439023481'],
                'isbnToRemove' => '978-3161484100',
                'expectedIsbn10s' => ['0-9752298-0-X', '0205080057'],
                'expectedIsbn13s' => ['9780439023481'],
                'wantSuccess' => true
            ],
            'remove non-existing ISBN should fail' => [
                'existingIsbn10s' => ['0-9752298-0-X', '0205080057'],
                'existingIsbn13s' => ['978-3161484100', '9780439023481'],
                'isbnToRemove' => '978-0198534532',
                'expectedIsbn10s' => ['0-9752298-0-X', '0205080057'],
                'expectedIsbn13s' => ['978-3161484100', '9780439023481'],
                'wantSuccess' => false
            ],
        ];
    }

    #[DataProvider('removeIsbnDataProvider')]
    public function testRemoveIsbn(
        array $existingIsbn10s, 
        array $existingIsbn13s, 
        string $isbnToRemove, 
        array $expectedIsbn10s, 
        array $expectedIsbn13s, 
        bool $wantSuccess
    ): void
    {
        $sut = new IsbnUpdater();
        $sut->setExistingIsbn10s($existingIsbn10s);
        $sut->setExistingIsbn13s($existingIsbn13s);
        $result = $sut->removeIsbn($isbnToRemove);
        $this->assertEquals($wantSuccess, $result);
        $this->assertEquals($expectedIsbn10s, $sut->getIsbn10s());
        $this->assertEquals($expectedIsbn13s, $sut->getIsbn13s());
    }
}