<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Detectors;

use TechRaysLabs\DebtTracker\Detectors\Contracts\DetectorInterface;
use TechRaysLabs\DebtTracker\ValueObjects\DebtItem;

/**
 * Detects outdated or abandoned composer dependencies by querying the Packagist API.
 *
 * Only runs when the scanned file is composer.json; results are cached for 24 hours.
 */
class DependencyDetector implements DetectorInterface
{
    private const PACKAGIST_API = 'https://packagist.org/packages/%s.json';

    private const CACHE_TTL = 86400;

    private const BASE_SCORE_MAJOR = 10;

    private const BASE_SCORE_MINOR = 3;

    private const BASE_SCORE_ABANDONED = 20;

    private const REQUEST_INTERVAL = 200000; // microseconds between requests

    public function __construct(
        private readonly bool $enabled = true,
        private readonly string $projectRoot = '',
        private readonly int $httpTimeout = 5,
    ) {}

    public function getName(): string
    {
        return 'dependencies';
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return DebtItem[]
     */
    public function detect(string $filePath, array $context = []): array
    {
        if (! $this->enabled) {
            return [];
        }

        if (basename($filePath) !== 'composer.json') {
            return [];
        }

        $composerJson = @file_get_contents($filePath);

        if ($composerJson === false) {
            return [];
        }

        $composerData = json_decode($composerJson, true);
        unset($composerJson); // free raw string

        if (! is_array($composerData)) {
            return [];
        }

        $lockPath = dirname($filePath).DIRECTORY_SEPARATOR.'composer.lock';
        $lockData = $this->readLock($lockPath);

        $require = $composerData['require'] ?? [];
        unset($composerData); // free full decoded structure — we only need $require

        $items = [];
        $cache = $this->loadCache();
        $now = time();
        $first = true;

        foreach ($require as $package => $constraint) {
            if ($package === 'php' || str_starts_with($package, 'ext-')) {
                continue;
            }

            $installed = $lockData[$package] ?? null;

            if ($installed === null) {
                continue;
            }

            if (! $first) {
                usleep(self::REQUEST_INTERVAL);
            }

            $first = false;

            $cacheKey = $package;
            $cacheData = $cache[$cacheKey] ?? null;

            if ($cacheData === null || ($now - ($cacheData['fetched_at'] ?? 0)) > self::CACHE_TTL) {
                $packagistData = $this->fetchPackagistData($package);

                if ($packagistData !== null) {
                    $cache[$cacheKey] = array_merge($packagistData, ['fetched_at' => $now]);
                    $this->saveCache($cache);
                } else {
                    continue;
                }

                gc_collect_cycles(); // help PHP GC reclaim memory after each large API response
            }

            $entry = $cache[$cacheKey];

            // Abandoned check
            if (! empty($entry['abandoned'])) {
                $items[] = new DebtItem(
                    type: 'dependency',
                    filePath: $filePath,
                    className: null,
                    methodName: null,
                    lineNumber: 0,
                    description: "Package {$package} is abandoned",
                    baseScore: self::BASE_SCORE_ABANDONED,
                    ageMultiplier: 1.0,
                    ageBand: 'fresh',
                    ageDays: 0,
                    gitAuthor: null,
                );

                continue;
            }

            $latest = $entry['latest'] ?? null;

            if ($latest === null) {
                continue;
            }

            $installedParts = $this->parseVersion($installed);
            $latestParts = $this->parseVersion($latest);

            $majorGap = $this->getMajorVersionGap($installed, $latest);

            if ($majorGap > 0) {
                $items[] = new DebtItem(
                    type: 'dependency',
                    filePath: $filePath,
                    className: null,
                    methodName: null,
                    lineNumber: 0,
                    description: "Package {$package} is {$majorGap} major version(s) behind (installed: {$installed}, latest: {$latest})",
                    baseScore: self::BASE_SCORE_MAJOR * $majorGap,
                    ageMultiplier: 1.0,
                    ageBand: 'fresh',
                    ageDays: 0,
                    gitAuthor: null,
                );
            } elseif ($latestParts[1] > $installedParts[1]) {
                $items[] = new DebtItem(
                    type: 'dependency',
                    filePath: $filePath,
                    className: null,
                    methodName: null,
                    lineNumber: 0,
                    description: "Package {$package} has a minor update available (installed: {$installed}, latest: {$latest})",
                    baseScore: self::BASE_SCORE_MINOR,
                    ageMultiplier: 1.0,
                    ageBand: 'fresh',
                    ageDays: 0,
                    gitAuthor: null,
                );
            }
        }

        return $items;
    }

    /** @return array<string, string> */
    private function readLock(string $lockPath): array
    {
        if (! file_exists($lockPath)) {
            return [];
        }

        $content = @file_get_contents($lockPath);

        if ($content === false) {
            return [];
        }

        $lock = json_decode($content, true);
        $packages = array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []);
        $map = [];

        foreach ($packages as $pkg) {
            if (isset($pkg['name'], $pkg['version'])) {
                $map[$pkg['name']] = ltrim($pkg['version'], 'v');
            }
        }

        return $map;
    }

    /** @return array<string, mixed> */
    private function loadCache(): array
    {
        $path = $this->cachePath();

        if (! file_exists($path)) {
            return [];
        }

        $content = @file_get_contents($path);

        if ($content === false) {
            return [];
        }

        return json_decode($content, true) ?? [];
    }

    /** @param array<string, mixed> $data */
    private function saveCache(array $data): void
    {
        $path = $this->cachePath();
        $dir = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
    }

    private function cachePath(): string
    {
        $root = rtrim($this->projectRoot ?: getcwd(), DIRECTORY_SEPARATOR);

        return $root.'/storage/app/debt-tracker-cache.json';
    }

    /** @return array{abandoned: bool, latest: string|null}|null */
    protected function fetchPackagistData(string $package): ?array
    {
        $url = sprintf(self::PACKAGIST_API, $package);
        $context = stream_context_create([
            'http' => [
                'timeout' => $this->httpTimeout,
                'method' => 'GET',
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return null;
        }

        // Avoid json_decode entirely: for popular packages (e.g. laravel/framework) the raw
        // JSON is ~12MB and decoding it to a PHP array peaks at ~55MB — crashing 128MB limits.
        // Instead, extract only the two fields we need via regex on the raw string (~12MB), then
        // immediately free it. Peak memory drops from ~55MB to ~12MB per package.

        // 1. Abandoned flag — a simple top-level boolean or forwarding string.
        $abandoned = (bool) preg_match('/"abandoned"\s*:\s*(?:true|"[^"]+")/', $response);

        // 2. Version keys — match JSON object keys that look like semver strings followed by `{`
        //    (the opening of each version's metadata object).
        //    Pattern covers: "10.0.0", "v10.0.0", "10.0.0.0" etc.
        //    Values inside require/suggest are strings (not objects), so the `\{` guard prevents
        //    false positives like "illuminate/console": "^10.0".
        preg_match_all('/"(v?\d+\.\d+[\d.]*)"\s*:\s*\{/', $response, $matches);
        unset($response); // free the raw string immediately (~5-15MB)

        $latest = null;

        foreach ($matches[1] as $version) {
            // Skip dev / RC / beta versions
            if (preg_match('/[a-zA-Z]/', $version)) {
                continue;
            }

            $cleaned = ltrim($version, 'v');

            if ($latest === null || version_compare($cleaned, $latest, '>')) {
                $latest = $cleaned;
            }
        }

        return [
            'abandoned' => $abandoned,
            'latest' => $latest,
        ];
    }

    /** @return array{int, int, int} */
    private function parseVersion(string $version): array
    {
        $version = ltrim($version, 'v');
        $parts = explode('.', $version);

        return [
            (int) $parts[0],
            (int) ($parts[1] ?? 0),
            (int) ($parts[2] ?? 0),
        ];
    }

    private function getMajorVersionGap(string $installed, string $latest): int
    {
        $installedParts = $this->parseVersion($installed);
        $latestParts = $this->parseVersion($latest);

        return max(0, $latestParts[0] - $installedParts[0]);
    }
}
