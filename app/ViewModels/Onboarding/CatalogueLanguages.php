<?php

declare(strict_types=1);

namespace App\ViewModels\Onboarding;

class CatalogueLanguages
{
    /**
     * @param  list<string>  $languages
     * @param  list<array<string, string|null>>  $texts
     */
    public function __construct(
        private readonly array $languages,
        private readonly array $texts,
    ) {}

    /** @return list<array{code: string, label: string, flag: string, isDefault: bool, percent: int, isComplete: bool, hasNothingMissing: bool, progress: string, status: string}> */
    public function cards(): array
    {
        $total = count($this->texts);
        $cards = [];

        foreach ($this->languages as $index => $code) {
            $missing = count(array_filter($this->texts, fn (array $text): bool => ($text[$code] ?? '') === ''));
            $written = $total - $missing;

            $cards[] = [
                'code' => $code,
                'label' => config("jacques.content_languages.{$code}.label", $code),
                'flag' => config("jacques.content_languages.{$code}.flag", 'transparent'),
                'isDefault' => $index === 0,
                'percent' => $total === 0 ? 0 : (int) round($written / $total * 100),
                'isComplete' => $total > 0 && $missing === 0,
                'hasNothingMissing' => $missing === 0,
                'progress' => trans_choice(':written of :count field translated|:written of :count fields translated', $total, ['written' => $written]),
                'status' => $this->status($total, $missing),
            ];
        }

        return $cards;
    }

    public function summary(): string
    {
        $total = count($this->texts);

        if ($total === 0) {
            return trans_choice(':count language|:count languages', count($this->languages));
        }

        $incomplete = count(array_filter($this->cards(), fn (array $card): bool => ! $card['isComplete']));

        if ($incomplete === 0) {
            return __('Every language is complete');
        }

        return trans_choice(':incomplete of :count language still needs translations|:incomplete of :count languages still need translations', count($this->languages), ['incomplete' => $incomplete]);
    }

    private function status(int $total, int $missing): string
    {
        if ($total === 0) {
            return __('Nothing to translate');
        }

        if ($missing === 0) {
            return __('All translated');
        }

        return trans_choice(':count missing|:count missing', $missing);
    }
}
