<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * An enum whose wording is translated where it is read, through __($case->label())
 * and the like. The localize command cannot find those strings in the code, so
 * the enum lists them itself.
 */
interface Translatable
{
    /** @return list<string> */
    public function translationKeys(): array;
}
