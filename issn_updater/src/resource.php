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

    public function getIssns(): array
    {
        if (!isset($this->resMap['attributes']['issns'])) {
            return [];
        }
        return $this->resMap['attributes']['issns'];
    }

    public function getEIssns(): array
    {
        if (!isset($this->resMap['attributes']['eissns'])) {
            return [];
        }
        return $this->resMap['attributes']['eissns'];
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