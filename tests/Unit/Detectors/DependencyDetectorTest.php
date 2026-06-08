<?php

declare(strict_types=1);

use TechRaysLabs\DebtTracker\Detectors\DependencyDetector;

/**
 * Creates a temp dir with composer.json + composer.lock and returns the composer.json path.
 *
 * @param  array<string, string>  $require
 * @param  array<array{name: string, version: string}>  $packages
 */
function makeTempComposer(array $require, array $packages): array
{
    $dir = sys_get_temp_dir().'/debt-tracker-test-'.uniqid();
    mkdir($dir, 0755, true);

    $composerJson = $dir.'/composer.json';
    $composerLock = $dir.'/composer.lock';

    file_put_contents($composerJson, json_encode(['require' => $require]));
    file_put_contents($composerLock, json_encode([
        'packages' => $packages,
        'packages-dev' => [],
    ]));

    return [$composerJson, $dir];
}

function cleanupTempDir(string $dir): void
{
    foreach (glob($dir.'/*') as $file) {
        @unlink($file);
    }
    @rmdir($dir);
}

it('skips non-composer.json files', function () {
    $detector = new DependencyDetector;
    $items = $detector->detect('/app/Services/Foo.php');

    expect($items)->toBeEmpty();
});

it('returns empty when detector is disabled', function () {
    $detector = new DependencyDetector(enabled: false);
    $items = $detector->detect('/path/to/composer.json');

    expect($items)->toBeEmpty();
});

it('returns empty for unreadable composer.json', function () {
    $detector = new DependencyDetector;
    $items = $detector->detect('/non/existent/path/composer.json');

    expect($items)->toBeEmpty();
});

it('handles packagist API timeout gracefully', function () {
    [$composerJson, $dir] = makeTempComposer(
        ['vendor/some-package' => '^1.0'],
        [['name' => 'vendor/some-package', 'version' => '1.0.0']]
    );

    $detector = new class(projectRoot: $dir) extends DependencyDetector
    {
        protected function fetchPackagistData(string $package): ?array
        {
            return null; // simulate timeout
        }
    };

    $items = $detector->detect($composerJson);
    cleanupTempDir($dir);

    expect($items)->toBeEmpty();
});

it('returns empty when all deps are current', function () {
    [$composerJson, $dir] = makeTempComposer(
        ['vendor/current-package' => '^1.0'],
        [['name' => 'vendor/current-package', 'version' => '1.0.0']]
    );

    $detector = new class(projectRoot: $dir) extends DependencyDetector
    {
        protected function fetchPackagistData(string $package): ?array
        {
            return ['abandoned' => false, 'latest' => '1.0.0'];
        }
    };

    $items = $detector->detect($composerJson);
    cleanupTempDir($dir);

    expect($items)->toBeEmpty();
});

it('flags abandoned package', function () {
    [$composerJson, $dir] = makeTempComposer(
        ['vendor/dead-package' => '^1.0'],
        [['name' => 'vendor/dead-package', 'version' => '1.0.0']]
    );

    $detector = new class(projectRoot: $dir) extends DependencyDetector
    {
        protected function fetchPackagistData(string $package): ?array
        {
            return ['abandoned' => true, 'latest' => '1.0.0'];
        }
    };

    $items = $detector->detect($composerJson);
    cleanupTempDir($dir);

    $abandonedItems = array_filter($items, fn ($i) => str_contains($i->description, 'abandoned'));
    expect(count($abandonedItems))->toBe(1);
});

it('flags package with major version behind (mocked HTTP)', function () {
    [$composerJson, $dir] = makeTempComposer(
        ['vendor/old-package' => '^1.0'],
        [['name' => 'vendor/old-package', 'version' => '1.0.0']]
    );

    $detector = new class(projectRoot: $dir) extends DependencyDetector
    {
        protected function fetchPackagistData(string $package): ?array
        {
            return ['abandoned' => false, 'latest' => '3.0.0'];
        }
    };

    $items = $detector->detect($composerJson);
    cleanupTempDir($dir);

    $majorItems = array_filter($items, fn ($i) => str_contains($i->description, 'major'));
    expect(count($majorItems))->toBeGreaterThanOrEqual(1);
});

it('does not flag package when installed version is newer than packagist latest', function () {
    [$composerJson, $dir] = makeTempComposer(
        ['laravel/framework' => '^13.0'],
        [['name' => 'laravel/framework', 'version' => '13.14.0']]
    );

    $detector = new class(projectRoot: $dir) extends DependencyDetector
    {
        protected function fetchPackagistData(string $package): ?array
        {
            // Simulate Packagist returning an older version as "latest"
            // (can happen with stale cache or version parsing edge cases)
            return ['abandoned' => false, 'latest' => '10.50.2'];
        }
    };

    $items = $detector->detect($composerJson);
    cleanupTempDir($dir);

    expect($items)->toBeEmpty();
});

it('uses cached results on second call', function () {
    [$composerJson, $dir] = makeTempComposer(
        ['vendor/cached-package' => '^1.0'],
        [['name' => 'vendor/cached-package', 'version' => '1.0.0']]
    );

    $callCount = 0;

    $detector = new class(projectRoot: $dir) extends DependencyDetector
    {
        public int $callCount = 0;

        protected function fetchPackagistData(string $package): ?array
        {
            $this->callCount++;

            return ['abandoned' => false, 'latest' => '1.0.0'];
        }
    };

    $detector->detect($composerJson);
    $firstCount = $detector->callCount;

    $detector->detect($composerJson);
    $secondCount = $detector->callCount;

    cleanupTempDir($dir);

    // Second call should not call fetchPackagistData again (cache hit)
    expect($secondCount)->toBe($firstCount);
});
