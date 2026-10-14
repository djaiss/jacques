<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\ScopeEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ScopeEnumTest extends TestCase
{
    #[Test]
    public function it_names_every_scope_twice(): void
    {
        foreach (ScopeEnum::cases() as $scope) {
            $this->assertNotEmpty($scope->label());
            $this->assertNotEmpty($scope->shortLabel());
        }
    }

    #[Test]
    public function it_keeps_the_short_name_to_one_word(): void
    {
        $this->assertEquals('Self', ScopeEnum::Self->shortLabel());
        $this->assertEquals('Locations', ScopeEnum::Location->shortLabel());
        $this->assertEquals('Company', ScopeEnum::Company->shortLabel());
    }
}
