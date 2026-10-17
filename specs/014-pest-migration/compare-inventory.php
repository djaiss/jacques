<?php

declare(strict_types=1);

if ($argc !== 3) {
    fwrite(STDERR, "Usage: php compare-inventory.php before.xml after.xml\n");

    exit(2);
}

function inventory(string $path): array
{
    $counts = [];

    foreach (simplexml_load_file($path)->xpath('//testcase') as $testcase) {
        $class = preg_replace('/^P\\\\/', '', (string) $testcase['class']);
        $relativePath = str_replace('\\', '/', substr($class, strlen('Tests\\')));
        $file = "tests/{$relativePath}.php";

        $counts[$file] ??= ['tests' => 0, 'assertions' => 0];
        $counts[$file]['tests']++;
        $counts[$file]['assertions'] += (int) $testcase['assertions'];
    }

    ksort($counts);

    return $counts;
}

$before = inventory($argv[1]);
$after = inventory($argv[2]);
$differences = 0;

foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $file) {
    $old = $before[$file] ?? ['tests' => 0, 'assertions' => 0];
    $new = $after[$file] ?? ['tests' => 0, 'assertions' => 0];

    if ($old === $new) {
        continue;
    }

    $differences++;

    echo "{$file}: tests {$old['tests']} -> {$new['tests']}, assertions {$old['assertions']} -> {$new['assertions']}\n";
}

$filesBefore = count($before);
$filesAfter = count($after);
$testsBefore = array_sum(array_column($before, 'tests'));
$testsAfter = array_sum(array_column($after, 'tests'));

echo "Files: {$filesBefore} -> {$filesAfter}, tests: {$testsBefore} -> {$testsAfter}, files with a difference: {$differences}\n";

exit($differences === 0 ? 0 : 1);
