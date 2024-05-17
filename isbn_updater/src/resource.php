<?php

declare(strict_types=1);

class Resource
{
    private array $resMap;

    public function __construct(array | stdClass $resMap)
    {
        if ($resMap instanceof stdClass) {
            $resMap = objectToArray($resMap);
        }
        $this->resMap = $resMap;
    }

    public function getRaw(): array
    {
        return $this->resMap;
    }

    public function getId(): string
    {
        if (!isset($this->resMap['id'])) {
            return '';
        }
        return $this->resMap['id'];
    }

    public function getIsbn13s(): array
    {
        if (!isset($this->resMap['attributes']['isbn13s'])) {
            return [];
        }
        return $this->resMap['attributes']['isbn13s'];
    }

    public function getIsbn10s(): array
    {
        if (!isset($this->resMap['attributes']['isbn10s'])) {
            return [];
        }
        return $this->resMap['attributes']['isbn10s'];
    }

}

function objectToArray($object)
{
    if (!is_object($object) && !is_array($object)) {
        return $object;
    }

    if (is_object($object)) {
        $object = get_object_vars($object);
    }

    return array_map('objectToArray', $object);
}