<?php

declare(strict_types=1);

require_once __DIR__ . '/../../isbn_updater/src/resource.php';

use PHPUnit\Framework\TestCase;

final class ResourceTest extends TestCase
{
    public function testGetIsbn13s(): void
    {
        $sut = new Resource($this->getTestMap());
        $isbn13s = $sut->getIsbn13s();
        $this->assertEquals(['9783161484100', '9780439023481'], $isbn13s);
    }

    public function testGetIsbn13s_NotSet(): void
    {
        $sut = new Resource([]);
        $isbn13s = $sut->getIsbn13s();
        $this->assertEquals([], $isbn13s);
    }

    public function testGetIsbn10s(): void
    {
        $sut = new Resource($this->getTestMap());
        $isbn10s = $sut->getIsbn10s();
        $this->assertEquals(['316148410X', '0439023483'], $isbn10s);
    }

    public function testGetIsbn10s_NotSet(): void
    {
        $sut = new Resource([]);
        $isbn10s = $sut->getIsbn10s();
        $this->assertEquals([], $isbn10s);
    }

    public function getId(): void
    {
        $sut = new Resource($this->getTestMap());
        $id = $sut->getId();
        $this->assertEquals('02BAB114-5C64-2133-313E-52E588299822', $id);
    }

    public function getId_NotSet(): void
    {
        $sut = new Resource([]);
        $id = $sut->getId();
        $this->assertEquals('', $id);
    }

    public function testObjectPassedIn(): void
    {
        $sut = new Resource($this->getTestObject());
        $rawMap = $sut->getRaw();
        $this->assertEquals($this->getTestMap(), $rawMap);
        $this->assertEquals('02BAB114-5C64-2133-313E-52E588299822', $sut->getId());
        $this->assertEquals(['9783161484100', '9780439023481'], $sut->getIsbn13s());
        $this->assertEquals(['316148410X', '0439023483'], $sut->getIsbn10s());
    }

    private function getTestObject(): stdClass
    {
        return (object)$this->getTestMap();
    }

    private function getTestMap()
    {
        return [
            'type' => 'resources',
            'id' => '02BAB114-5C64-2133-313E-52E588299822',
            'attributes' => [
                'authors' => [],
                'editors' => [],
                'isbn10s' => ['316148410X', '0439023483'],
                'isbn13s' => ['9783161484100', '9780439023481'],
                'dates' => [],
                'doi' => null,
                'edition' => null,
                'eissns' => [],
                'issns' => [
                    '00935301'
                ],
                'issue' => null,
                'lcn' => null,
                'format' => null,
                'online_resource' => null,
                'page_start' => null,
                'page_end' => null,
                'places_of_publication' => [],
                'publisher_names' => [],
                'resource_type' => 'Journal',
                'title' => 'Journal of Consumer Research',
                'volume' => null,
                'web_addresses' => null,
                'source_uri' => null
            ],
            'meta' => [
                'all_online_resources' => [
                    [
                        'external_url' => '',
                        'original_url' => '',
                        'proxied_url' => '',
                        'type' => 'openurl'
                    ]
                ]
            ]
        ];
    }
}
