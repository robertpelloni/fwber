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

    /**
     * Generate a Merkle Proof for a specific leaf.
     */
    public function getProof(array $leaves, string $leaf): array
    {
        if (empty($leaves)) return [];

        $hashes = array_map(fn($l) => hash('sha256', $l), $leaves);
        sort($hashes);

        $targetHash = hash('sha256', $leaf);
        $proof = [];

        while (count($hashes) > 1) {
            $nextLevel = [];
            for ($i = 0; $i < count($hashes); $i += 2) {
                if (isset($hashes[$i + 1])) {
                    $left = $hashes[$i];
                    $right = $hashes[$i + 1];

                    if ($left === $targetHash) {
                        $proof[] = ['right' => $right];
                        $targetHash = hash('sha256', $left . $right);
                    } elseif ($right === $targetHash) {
                        $proof[] = ['left' => $left];
                        $targetHash = hash('sha256', $left . $right);
                    }

                    $nextLevel[] = hash('sha256', $left . $right);
                } else {
                    $nextLevel[] = $hashes[$i];
                }
            }
            $hashes = $nextLevel;
        }

        return $proof;
    }
}
