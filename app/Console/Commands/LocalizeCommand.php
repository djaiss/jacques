<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Interfaces\Translatable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Translation\MessageSelector;
use Symfony\Component\Finder\SplFileInfo;

#[Signature('jacques:localize {--check : Report what is out of date, and write nothing}')]
#[Description('Collect the strings to translate and write them to the locale files')]
class LocalizeCommand extends Command
{
    private const string QUOTED = '(?:\'((?:\\\\.|[^\'\\\\])*)\'|"((?:\\\\.|[^"\\\\])*)")';

    /** @var list<string> */
    private array $problems = [];

    public function handle(): int
    {
        $files = $this->files();
        $keys = $this->stringsToTranslate($files);

        $this->line(sprintf('Read %d files and found %d strings to translate.', count($files), count($keys)));

        $this->reportChanges($keys);

        $outOfDate = false;
        $missing = [];
        $rows = [];

        foreach ($this->locales() as $locale) {
            $result = $this->synchronize($locale, $keys);

            $outOfDate = $outOfDate || $result['changed'];
            $missing[$locale] = $result['missing'];
            $rows[] = [$locale, $result['translated'], count($result['missing']) ?: ''];
        }

        $this->table(['Locale', 'Translated', 'Missing'], $rows);

        foreach ($missing as $locale => $strings) {
            $this->listStrings(sprintf('Missing in %s', $locale), $strings);
        }

        $this->listStrings('Problems', $this->problems);

        if ($this->problems !== []) {
            $this->error('Fix the problems listed above.');

            return self::FAILURE;
        }

        if (! $this->option('check')) {
            $this->info('Locale files synchronized.');

            return self::SUCCESS;
        }

        if ($outOfDate || array_filter($missing) !== []) {
            $this->error('The locale files are out of date. Run php artisan jacques:localize, then translate what is missing.');

            return self::FAILURE;
        }

        $this->info('The locale files are up to date.');

        return self::SUCCESS;
    }

    /**
     * @param  list<SplFileInfo>  $files
     * @return list<string>
     */
    private function stringsToTranslate(array $files): array
    {
        $keys = [];

        foreach ($files as $file) {
            $code = $this->codeOf($file);
            $where = $file->getRelativePathname();

            foreach ($this->calls($code, '(?:__|trans|trans_choice|@lang)') as [$phrase]) {
                $keys[$phrase] = true;
            }

            $this->checkPlurals($where, $code);
        }

        foreach (File::files(app_path('Enums')) as $file) {
            $enum = 'App\\Enums\\'.$file->getFilenameWithoutExtension();

            if (! enum_exists($enum) || ! is_a($enum, Translatable::class, true)) {
                continue;
            }

            foreach ($enum::cases() as $case) {
                foreach ($case->translationKeys() as $key) {
                    $keys[$key] = true;
                }
            }
        }

        $keys = array_map(strval(...), array_keys($keys));
        sort($keys, SORT_STRING);

        return $keys;
    }

    /** @return list<SplFileInfo> */
    private function files(): array
    {
        return array_values(array_filter(
            [...File::allFiles(app_path()), ...File::allFiles(resource_path('views'))],
            fn (SplFileInfo $file): bool => $file->getExtension() === 'php',
        ));
    }

    private function codeOf(SplFileInfo $file): string
    {
        $contents = $file->getContents();

        if (str_ends_with($file->getFilename(), '.blade.php')) {
            return (string) preg_replace('/\{\{--.*?--\}\}/s', '', $contents);
        }

        $code = '';

        foreach (token_get_all($contents) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            $code .= is_array($token) ? $token[1] : $token;
        }

        return $code;
    }

    /**
     * Every call to one of the functions whose first argument is a literal
     * string, as the phrase and where the phrase starts in the code.
     *
     * @return list<array{string, int}>
     */
    private function calls(string $code, string $functions): array
    {
        preg_match_all('/(?<![\w>:$])'.$functions.'\(\s*'.self::QUOTED.'/', $code, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        return array_map(function (array $match): array {
            [$phrase, $offset] = isset($match[2]) && $match[2][1] !== -1 ? $match[2] : $match[1];

            return [stripcslashes($phrase), $offset];
        }, $matches);
    }

    /**
     * Enforce the rules about phrases that show a number, written down in
     * .claude/rules/code-style.md. They are read from the code as text, so an
     * empty report is not a guarantee.
     */
    private function checkPlurals(string $where, string $code): void
    {
        foreach ($this->calls($code, '(?:__|trans|@lang)') as [$phrase]) {
            if ($this->showsTheCount($phrase)) {
                $this->problems[] = "{$where}: \"{$phrase}\" shows a number. Ask for it with trans_choice().";
            } elseif ($this->carriesACondition($phrase) || str_contains($phrase, '|')) {
                $this->problems[] = "{$where}: \"{$phrase}\" carries several wordings or a condition. Ask for it with trans_choice().";
            }
        }

        foreach ($this->calls($code, 'trans_choice') as [$phrase, $offset]) {
            if (! $this->hasAPinnedWording($phrase)) {
                continue;
            }

            if (str_ends_with($where, '.blade.php')) {
                $this->problems[] = "{$where}: \"{$phrase}\" pins a wording to one number in a view. Build it where the screen gets its words.";
            } elseif (! $this->guardsZeroBefore($code, $offset)) {
                $this->problems[] = "{$where}: \"{$phrase}\" pins a wording to one number without keeping zero away from it.";
            }
        }

        preg_match_all('/(?<![!<>=])={2,3}\s*1\b(?!\.)/', $code, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as [, $offset]) {
            $window = substr($code, $offset, 240);

            if ($this->showsTheCount($window) && preg_match('/(?:__|trans|trans_choice)\(/', $window) === 1) {
                $this->problems[] = "{$where}: picks a wording by comparing a count to 1. Use trans_choice().";
            }
        }
    }

    private function guardsZeroBefore(string $code, int $offset): bool
    {
        $before = substr($code, 0, $offset);
        $start = strrpos($before, 'function ');
        $body = $start === false ? substr($before, -600) : substr($before, $start);

        return preg_match('/(?:===|==|!==|!=)\s*0\b|>\s*0\b|(?:===|!==)\s*\[\]|empty\(/', $body) === 1;
    }

    /**
     * @param  list<string>  $keys
     * @return array{translated: int, missing: list<string>, changed: bool}
     */
    private function synchronize(string $locale, array $keys): array
    {
        $existing = $this->translations($locale);
        $isSource = $locale === config('app.locale');
        $translations = [];
        $missing = [];

        foreach ($keys as $key) {
            $translation = $isSource ? $key : ($existing[$key] ?? '');

            if ($translation === '') {
                $missing[] = $key;

                continue;
            }

            $translations[$key] = $translation;

            if ($this->isCountedPhrase($key)) {
                $this->checkWordings($locale, $key, $translation);
            }
        }

        ksort($translations, SORT_STRING);

        $path = lang_path("{$locale}.json");
        $contents = json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT).PHP_EOL;
        $changed = ! is_file($path) || file_get_contents($path) !== $contents;

        if ($changed && ! $this->option('check')) {
            file_put_contents($path, $contents);
        }

        return ['translated' => count($translations), 'missing' => $missing, 'changed' => $changed];
    }

    private function checkWordings(string $locale, string $key, string $translation): void
    {
        $wordings = explode('|', $translation);
        $expected = str_starts_with($key, '[0,*]') ? 1 : $this->wordingsNeeded($locale);

        if (count($wordings) !== $expected) {
            $this->problems[] = "{$locale}: \"{$key}\" needs {$expected} wording(s) and has ".count($wordings).'.';
        }

        foreach ($wordings as $wording) {
            if (! $this->isPinnedToOneNumber($wording) && ! $this->showsTheCount($wording)) {
                $this->problems[] = "{$locale}: \"{$key}\" has a wording without :count. Write the placeholder, never the digit.";
            }
        }
    }

    private function wordingsNeeded(string $locale): int
    {
        $selector = new MessageSelector;
        $highest = 0;

        foreach (range(0, 200) as $number) {
            $highest = max($highest, $selector->getPluralIndex($locale, $number));
        }

        return $highest + 1;
    }

    private function showsTheCount(string $text): bool
    {
        return preg_match('/:count\b/', $text) === 1;
    }

    private function isCountedPhrase(string $key): bool
    {
        return str_contains($key, '|') || str_starts_with($key, '[0,*]');
    }

    private function carriesACondition(string $wording): bool
    {
        return preg_match('/^[{\[][-?\d|*,.]*[}\]]/', $wording) === 1;
    }

    private function hasAPinnedWording(string $phrase): bool
    {
        foreach (explode('|', $phrase) as $wording) {
            if ($this->isPinnedToOneNumber($wording)) {
                return true;
            }
        }

        return false;
    }

    private function isPinnedToOneNumber(string $wording): bool
    {
        if (preg_match('/^\{\s*-?\d+\s*\}/', $wording) === 1) {
            return true;
        }

        return preg_match('/^\[\s*(-?\d+)\s*,\s*(-?\d+)\s*\]/', $wording, $ends) === 1 && $ends[1] === $ends[2];
    }

    /** @param  list<string>  $keys */
    private function reportChanges(array $keys): void
    {
        $known = array_keys($this->translations((string) config('app.locale')));
        $stale = [];

        foreach ($this->locales() as $locale) {
            $stale = [...$stale, ...array_diff(array_keys($this->translations($locale)), $keys)];
        }

        $this->listStrings('Added', array_values(array_diff($keys, $known)));
        $this->listStrings('Removed, no longer found in the code', array_values(array_unique($stale)));
    }

    /** @param  list<string>  $strings */
    private function listStrings(string $title, array $strings): void
    {
        if ($strings === []) {
            return;
        }

        $this->newLine();
        $this->line(sprintf('%s (%d):', $title, count($strings)));

        foreach ($strings as $string) {
            $this->line("  {$string}");
        }
    }

    /** @return array<string, string> */
    private function translations(string $locale): array
    {
        $path = lang_path("{$locale}.json");

        return is_file($path) ? (array) json_decode((string) file_get_contents($path), true) : [];
    }

    /** @return list<string> The source locale first, so the others are compared against what it holds. */
    private function locales(): array
    {
        $source = (string) config('app.locale');
        $others = array_values(array_diff(array_keys(config('jacques.locales')), [$source]));

        return [$source, ...$others];
    }
}
