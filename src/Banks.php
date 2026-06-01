<?php

declare(strict_types=1);

namespace PhDevUtils\Banks;

/**
 * Philippine bank & e-money registry — SWIFT/BIC codes and InstaPay/PESONet
 * participation. Mirrors the JS package `@ph-dev-utils/banks`.
 *
 * Each bank is an associative array:
 *   [
 *     'name' => string, 'shortName' => ?string,
 *     'type' => 'universal_commercial'|'thrift'|'rural'|'digital'|'ewallet',
 *     'foreign' => bool, 'swift' => ?string,
 *     'swiftConfidence' => 'verified'|'single-source'|null,
 *     'instapay' => bool, 'instapayReceiverOnly' => bool, 'pesonet' => bool,
 *   ]
 */
final class Banks
{
    private const BIC_RE = '/^[A-Z]{6}[A-Z0-9]{2}([A-Z0-9]{3})?$/';

    /**
     * @return list<array<string, mixed>>
     */
    private static function all(): array
    {
        /** @var array{banks: list<array<string, mixed>>} $data */
        $data = DataLoader::load('banks');

        return $data['banks'];
    }

    /** The BSP participation lists' as-of date (YYYY-MM-DD). */
    public static function participationAsOf(): string
    {
        /** @var array{_meta: array{participation_as_of: string}} $data */
        $data = DataLoader::load('banks');

        return $data['_meta']['participation_as_of'];
    }

    /**
     * List institutions, optionally filtered.
     *
     * @param array{type?: string, instapay?: bool, pesonet?: bool, foreign?: bool, hasSwift?: bool} $filter
     * @return list<array<string, mixed>>
     */
    public static function listBanks(array $filter = []): array
    {
        $out = [];
        foreach (self::all() as $b) {
            if (isset($filter['type']) && $b['type'] !== $filter['type']) {
                continue;
            }
            if (isset($filter['instapay']) && $b['instapay'] !== $filter['instapay']) {
                continue;
            }
            if (isset($filter['pesonet']) && $b['pesonet'] !== $filter['pesonet']) {
                continue;
            }
            if (isset($filter['foreign']) && $b['foreign'] !== $filter['foreign']) {
                continue;
            }
            if (isset($filter['hasSwift']) && (($b['swift'] !== null) !== $filter['hasSwift'])) {
                continue;
            }
            $out[] = $b;
        }

        return $out;
    }

    private static function norm(string $s): string
    {
        $s = mb_strtolower($s);
        $s = str_replace('&', 'and', $s);
        $s = str_replace(['.', ','], '', $s);
        $s = (string) preg_replace('/\s+/', ' ', $s);

        return trim($s);
    }

    /**
     * Find one institution by SWIFT/BIC, exact/normalized name, or short name.
     *
     * @return array<string, mixed>|null
     */
    public static function findBank(string $query): ?array
    {
        $q = trim($query);
        if ($q === '') {
            return null;
        }

        if (preg_match('/^[A-Za-z]{6}[A-Za-z0-9]{2}([A-Za-z0-9]{3})?$/', $q) === 1) {
            $bic8 = substr(strtoupper($q), 0, 8);
            foreach (self::all() as $b) {
                if ($b['swift'] === $bic8) {
                    return $b;
                }
            }
        }

        $n = self::norm($q);
        $banks = self::all();
        foreach ($banks as $b) {
            if (self::norm((string) $b['name']) === $n) {
                return $b;
            }
        }
        foreach ($banks as $b) {
            if ($b['shortName'] !== null && self::norm((string) $b['shortName']) === $n) {
                return $b;
            }
        }
        if (mb_strlen($n) >= 3) {
            foreach ($banks as $b) {
                if (str_contains(self::norm((string) $b['name']), $n)) {
                    return $b;
                }
            }
        }

        return null;
    }

    /**
     * Find the institution whose head-office SWIFT/BIC matches (8 or 11 char input).
     *
     * @return array<string, mixed>|null
     */
    public static function findBySwift(string $swift): ?array
    {
        $s = trim($swift);
        if ($s === '') {
            return null;
        }
        $bic8 = substr(strtoupper($s), 0, 8);
        foreach (self::all() as $b) {
            if ($b['swift'] === $bic8) {
                return $b;
            }
        }

        return null;
    }

    /** Format-level SWIFT/BIC validation per ISO 9362 (8 or 11 chars). */
    public static function validateBIC(string $code): bool
    {
        return preg_match(self::BIC_RE, strtoupper(trim($code))) === 1;
    }

    /**
     * Structurally parse a SWIFT/BIC into its parts, or null if malformed.
     *
     * @return array{institution: string, country: string, location: string, branch: ?string}|null
     */
    public static function parseBIC(string $code): ?array
    {
        if (! self::validateBIC($code)) {
            return null;
        }
        $c = strtoupper(trim($code));

        return [
            'institution' => substr($c, 0, 4),
            'country' => substr($c, 4, 2),
            'location' => substr($c, 6, 2),
            'branch' => strlen($c) === 11 ? substr($c, 8, 3) : null,
        ];
    }
}
