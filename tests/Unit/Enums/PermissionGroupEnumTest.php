<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\PermissionGroupEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PermissionGroupEnumTest extends TestCase
{
    #[Test]
    public function it_names_every_group(): void
    {
        foreach (PermissionGroupEnum::cases() as $group) {
            $this->assertNotEmpty($group->label());
        }
    }

    #[Test]
    public function it_says_what_every_group_holds(): void
    {
        foreach (PermissionGroupEnum::cases() as $group) {
            $this->assertNotEmpty($group->note());
        }
    }

    #[Test]
    public function it_calls_the_groups_what_the_screen_calls_them(): void
    {
        $this->assertEquals('Administration', PermissionGroupEnum::Administration->label());
        $this->assertEquals('Locations', PermissionGroupEnum::Locations->label());
        $this->assertEquals('Team', PermissionGroupEnum::Team->label());
        $this->assertEquals('Appointments', PermissionGroupEnum::Appointments->label());
    }
}
