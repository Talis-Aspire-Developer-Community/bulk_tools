<?php

declare(strict_types=1);

class IssnUpdater
{
    private array $existingIssns = [];
    private array $existingEIssns = [];

    public function setExistingIssns(array $existingIssns): void
    {
        $this->existingIssns = $existingIssns;
    }

    public function setExistingEIssns(array $existingEIssns): void
    {
        $this->existingEIssns = $existingEIssns;
    }

    /**
     * Remove an ISSN from the list of existing ISSNs
     *
     * The function uses the provided $idType parameter to decide which field to update.
     *
     * It will return true if the issn was removed, false if not.
     * False is not an indication of error, only that no change was made,
     * most likely the issn may not exist.
     * 
     * @param string $oldIssn string $idType
     * @return bool - true if the issn was removed, false if not.
     */
    public function removeIssn(string $oldIssn): bool
    {
        $removed = false;
        $newIssns = [];
        for ($i = 0; $i < count($this->existingIssns); $i++) {
            if ($this->existingIssns[$i] === $oldIssn) {
                $removed = true;
                continue;
            }
            $newIssns[] = $this->existingIssns[$i];
        }
        $this->existingIssns = $newIssns;
        return $removed;
    }

    public function removeEIssn(string $oldIssn): bool
    {
        $removed = false;
        $newIssns = [];
        for ($i = 0; $i < count($this->existingEIssns); $i++) {
            if ($this->existingEIssns[$i] === $oldIssn) {
                $removed = true;
                continue;
            }
            $newIssns[] = $this->existingEIssns[$i];
        }
        $this->existingEIssns = $newIssns;
        return $removed;
    }

    /**
     * Add an ISSN to the list of existing ISSNs
     *
     * The function uses the provided $idType parameter to decide which field to update.
     * 
     * It will return true if the issn was added, false if not.
     * False is not necessarily an indication of error, it could be that the ISSN
     * is empty, invalid, or already in the list.  
     * The response indicates only that no change has been made.
     * 
     * @param string $oldIssn string $idType
     * @return bool - true if the issn was added, false if not.
     */
    public function addIssn(string $newIssn): bool
    {
        $changeMade = false;
        if (!in_array($newIssn, $this->existingIssns)) {
            $this->existingIssns[] = $newIssn;
            $changeMade = true;
        }
        return $changeMade;
    }

    public function addEIssn(string $newIssn): bool
    {
        $changeMade = false;
        if (!in_array($newIssn, $this->existingEIssns)) {
            $this->existingEIssns[] = $newIssn;
            $changeMade = true;
        }
        return $changeMade;
    }

    public function getIssns(): array
    {
        return $this->existingIssns;
    }

    public function getEIssns(): array
    {
        return $this->existingEIssns;
    }

}
