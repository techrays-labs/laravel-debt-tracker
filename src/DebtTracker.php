<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker;

use Symfony\Component\Finder\Finder;
use TechRaysLabs\DebtTracker\Analyzers\AstParser;
use TechRaysLabs\DebtTracker\Analyzers\ClassAnalyzer;
use TechRaysLabs\DebtTracker\Analyzers\FileAnalyzer;
use TechRaysLabs\DebtTracker\Detectors\ComplexityDetector;
use TechRaysLabs\DebtTracker\Detectors\Contracts\DetectorInterface;
use TechRaysLabs\DebtTracker\Detectors\CoverageDetector;
use TechRaysLabs\DebtTracker\Detectors\DependencyDetector;
use TechRaysLabs\DebtTracker\Detectors\DeadCodeDetector;
use TechRaysLabs\DebtTracker\Detectors\N1QueryDetector;
use TechRaysLabs\DebtTracker\Detectors\SecuritySmellDetector;
use TechRaysLabs\DebtTracker\Detectors\TodoDetector;
use TechRaysLabs\DebtTracker\Git\GitBlameReader;
use TechRaysLabs\DebtTracker\Scoring\GradeResolver;
use TechRaysLabs\DebtTracker\Scoring\HoursEstimator;
use TechRaysLabs\DebtTracker\Scoring\ScoreCalculator;
use TechRaysLabs\DebtTracker\ValueObjects\FileDebtResult;
use TechRaysLabs\DebtTracker\ValueObjects\ScanResult;

/**
 * Main entry point that orchestrates the full debt scan.
 */
class DebtTracker
{
    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config) {}

    /**
     * Runs a full debt scan and returns the aggregated result.
     *
     * @param  string[]  $paths
     * @param  string[]  $excludePaths
     * @param  string[]  $onlyDetectors
     */
    public function scan(
        array $paths = [],
        array $excludePaths = [],
        array $onlyDetectors = [],
    ): ScanResult {
        $projectRoot = $this->config['project_root'] ?? getcwd();
        $scanPaths = $paths ?: ($this->config['scan_paths'] ?? ['app']);
        $exclude = array_merge(
            $excludePaths,
            $this->config['exclude_paths'] ?? [],
        );

        $astParser = new AstParser;
        $gitReader = new GitBlameReader(
            $projectRoot,
            $this->config['git']['blame_timeout'] ?? 30,
        );
        $scoreCalc = new ScoreCalculator;
        $gradeResolver = new GradeResolver;
        $estimator = new HoursEstimator;
        $classAnalyzer = new ClassAnalyzer;

        $detectors = $this->buildDetectors($onlyDetectors, $projectRoot);

        $fileAnalyzer = new FileAnalyzer(
            detectors: $detectors,
            astParser: $astParser,
            gitReader: $gitReader,
            scoreCalculator: $scoreCalc,
            projectRoot: $projectRoot,
        );

        // Scan PHP files
        $phpFiles = $this->collectFiles($projectRoot, $scanPaths, $exclude);
        $fileResults = [];

        foreach ($phpFiles as $file) {
            $result = $fileAnalyzer->analyze($file);

            if ($result->itemCount > 0) {
                $fileResults[] = $result;
            }
        }

        // Run DependencyDetector against composer.json separately
        $composerPath = rtrim($projectRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'composer.json';

        if (file_exists($composerPath)) {
            foreach ($detectors as $detector) {
                if ($detector->getName() === 'dependencies' && $detector->isEnabled()) {
                    $depItems = $detector->detect($composerPath, []);

                    if (! empty($depItems)) {
                        $depScore = $scoreCalc->calculate($depItems);
                        $fileResults[] = new FileDebtResult(
                            filePath: $composerPath,
                            relativePath: 'composer.json',
                            items: $depItems,
                            totalScore: $depScore,
                            itemCount: count($depItems),
                        );
                    }

                    break;
                }
            }
        }

        // Collect all items for class analysis and scoring
        $allItems = [];

        foreach ($fileResults as $fr) {
            $allItems = array_merge($allItems, $fr->items);
        }

        $classResults = $classAnalyzer->extractClassResults($allItems);
        $totalScore = $scoreCalc->calculate($allItems);
        $byCategory = $scoreCalc->byCategory($allItems);
        $grade = $gradeResolver->resolve($totalScore);
        $hoursPerPoint = $this->config['cost']['hours_per_point'] ?? 0.25;
        $hours = $estimator->estimate($totalScore, $hoursPerPoint);

        // Compute byAuthor — group all items by git blame author
        $byAuthor = [];

        foreach ($allItems as $item) {
            $author = $item->gitAuthor ?? 'Unknown';
            $byAuthor[$author] = ($byAuthor[$author] ?? 0) + $item->finalScore();
        }

        return new ScanResult(
            fileResults: $fileResults,
            classResults: $classResults,
            totalScore: $totalScore,
            grade: $grade,
            estimatedHours: $hours,
            byCategory: $byCategory,
            generatedAt: new \DateTimeImmutable,
            projectPath: $projectRoot,
            byAuthor: $byAuthor,
        );
    }

    /**
     * @param  string[]  $onlyDetectors
     * @return DetectorInterface[]
     */
    private function buildDetectors(array $onlyDetectors, string $projectRoot): array
    {
        $enabled = $this->config['detectors'] ?? [];
        $thresholds = $this->config['thresholds'] ?? [];
        $all = [];

        $isEnabled = static function (string $name) use ($enabled, $onlyDetectors): bool {
            if (! empty($onlyDetectors) && ! in_array($name, $onlyDetectors, true)) {
                return false;
            }

            return (bool) ($enabled[$name] ?? true);
        };

        $all[] = new TodoDetector(enabled: $isEnabled('todos'));

        $all[] = new ComplexityDetector(
            enabled: $isEnabled('complexity'),
            methodLengthThreshold: $thresholds['method_length'] ?? 30,
            classLengthThreshold: $thresholds['class_length'] ?? 500,
            maxPublicMethods: $thresholds['max_public_methods'] ?? 20,
            nestingDepthThreshold: $thresholds['nesting_depth'] ?? 4,
            complexityThreshold: $thresholds['complexity_per_method'] ?? 10,
        );

        $all[] = new CoverageDetector(
            enabled: $isEnabled('coverage'),
            projectRoot: $projectRoot,
            excludePaths: $this->config['exclude_paths'] ?? [],
        );

        $all[] = new DependencyDetector(
            enabled: $isEnabled('dependencies'),
            projectRoot: $projectRoot,
        );

        $all[] = new N1QueryDetector(
            enabled: $isEnabled('n1_queries'),
            ignoreProperties: $this->config['n1_ignore_properties'] ?? ['id', 'uuid', 'created_at', 'updated_at', 'deleted_at'],
        );

        $all[] = new SecuritySmellDetector(
            enabled: $isEnabled('security'),
            excludePaths: $this->config['security_exclude_paths'] ?? ['tests', 'database/seeders'],
        );

        $all[] = new DeadCodeDetector(
            enabled: $isEnabled('dead_code'),
            ignoreMethods: $this->config['dead_code_ignore_methods'] ?? [],
        );

        return $all;
    }

    /**
     * @param  string[]  $paths
     * @param  string[]  $excludePaths
     * @return string[]
     */
    private function collectFiles(string $projectRoot, array $paths, array $excludePaths): array
    {
        $finder = new Finder;
        $finder->files()->name('*.php');

        $absolutePaths = array_map(
            static fn (string $p) => rtrim($projectRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.ltrim($p, DIRECTORY_SEPARATOR),
            $paths
        );

        $existing = array_filter($absolutePaths, 'is_dir');

        if (empty($existing)) {
            return [];
        }

        $finder->in($existing);

        $defaultExclude = ['vendor', 'node_modules', 'storage', 'bootstrap/cache'];

        foreach (array_merge($defaultExclude, $excludePaths) as $excluded) {
            $finder->exclude($excluded);
        }

        $files = [];

        foreach ($finder as $file) {
            $files[] = $file->getRealPath();
        }

        return $files;
    }
}
