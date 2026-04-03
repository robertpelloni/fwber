<?php

namespace App\Services\Governance;

class MerkleTreeService
{
    /**
     * Generate a Merkle Root for a collection of vote data.
     * 
     * @param array $leaves Array of vote strings (e.g. "user_id:option:weight")
     */
    public function generateRoot(array $leaves): string
    {
        if (empty($leaves)) {
            return '';
        }

        $hashes = array_map(fn($l) => hash('sha256', $l), $leaves);
        sort($hashes);

        while (count($hashes) > 1) {
            $nextLevel = [];
            for ($i = 0; $i < count($hashes); $i += 2) {
                if (isset($hashes[$i + 1])) {
                    $nextLevel[] = hash('sha256', $hashes[$i] . $hashes[$i + 1]);
                } else {
                    $nextLevel[] = $hashes[$i];
                }
            }
            $hashes = $nextLevel;
        }

        return $hashes[0];
    }
}
