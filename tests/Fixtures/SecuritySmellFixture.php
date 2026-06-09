<?php

// This fixture intentionally contains security smells for testing.
// Excluded from real scans via security_exclude_paths config.

class SecuritySmellFixture
{
    public function dangerousEval(string $code): mixed
    {
        return eval($code); // eval() — score 20
    }

    public function hardcodedCredential(): void
    {
        $password = 'hunter2'; // hardcoded credential — score 15
    }

    public function weakHash(string $password): string
    {
        return md5($password); // weak hash on credential — score 10
    }

    public function sqlConcatenation(string $userId): string
    {
        return "SELECT * FROM users WHERE id = " . $userId; // SQL concat — score 15
    }

    public function unsafeUnserialize(string $input): mixed
    {
        return unserialize($input); // unsafe unserialize — score 20
    }

    public function debugLeakage(mixed $result): void
    {
        dd($result); // debug leakage — score 5
    }
}
