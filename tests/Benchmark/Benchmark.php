<?php
use Jengo\H;

// Current implementation vs StringBuilder vs Native string concatenation
class Benchmark {
    private const ITERATIONS = 1000;
    private static function generateNestedDivs(int $depth, int $childrenPerLevel): string {
        static $depthTimes = [];
        static $depthCalls = [];
        static $printed = false;

        $start = microtime(true);

        $result = H::div(
            array_merge(
                [H::p("Content at depth $depth")],
                $depth > 0 ? array_fill(0, $childrenPerLevel, self::generateNestedDivs($depth - 1, $childrenPerLevel)) : []
            ),
            class: "depth-$depth"
        );

        $duration = (microtime(true) - $start) * 1000;
        $depthTimes[$depth] = ($depthTimes[$depth] ?? 0) + $duration;
        $depthCalls[$depth] = ($depthCalls[$depth] ?? 0) + 1;

        // Register shutdown function only once
        if (!$printed) {
            register_shutdown_function(function () use (&$depthTimes, &$depthCalls) {
                echo "\nFinal Generation Summary:\n";
                foreach ($depthTimes as $d => $time) {
                    $avg = $time / $depthCalls[$d];
                    echo sprintf(
                        "Depth %d: %d calls, total %.2fms, avg %.4fms\n",
                        $d,
                        $depthCalls[$d],
                        $time,
                        $avg
                    );
                }
            });
            $printed = true;
        }

        return $result;
    }

    private static function runTest(callable $fn, string $name): array {
        $memory_start = memory_get_usage();
        $time_start = microtime(true);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $fn();
        }

        $time_end = microtime(true);
        $memory_end = memory_get_usage();

        return [
            'name' => $name,
            'time' => ($time_end - $time_start) * 1000, // ms
            'memory' => ($memory_end - $memory_start) / 1024, // KB
            'memory_peak' => memory_get_peak_usage() / 1024 // KB
        ];
    }

    public static function run(): void {
        $scenarios = [
            'small' => ['depth' => 3, 'children' => 3],    // ~100 elements
            'medium' => ['depth' => 4, 'children' => 4],   // ~500 elements
            'large' => ['depth' => 5, 'children' => 3],    // ~1000 elements
        ];

        foreach ($scenarios as $size => $params) {
            echo "\nTesting $size document\n";
            echo str_repeat('-', 80) . "\n";

            $totalStart = microtime(true);

            // First pass
            $result1 = self::runTest(
                fn() => self::generateNestedDivs($params['depth'], $params['children']),
                'pass 1'
            );

            $midTime = microtime(true);

            // Second pass
            $result2 = self::runTest(
                fn() => self::generateNestedDivs($params['depth'], $params['children']),
                'pass 2'
            );

            $totalTime = (microtime(true) - $totalStart) * 1000;
            echo sprintf("\nTotal wall time: %.2fms\n", $totalTime);

            self::printResults([$result1, $result2]);
        }
    }

    private static function printResults(array $results): void {
        echo "<pre>";
        printf("%-15s %-15s %-15s %-15s\n", 'Method', 'Time (ms)', 'Memory (KB)', 'Peak (KB)');
        foreach ($results as $result) {
            printf(
                "%-15s %-15.2f %-15.2f %-15.2f\n",
                $result['name'],
                $result['time'],
                $result['memory'],
                $result['memory_peak']
            );
        }
        echo "</pre>";
    }
}