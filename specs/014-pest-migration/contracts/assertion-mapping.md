# Contract: Assertion Mapping

Binding for every batch (FR-003a). `$a` is the actual value, `$e` the expected one. A form absent from this table stops the batch and comes back to the supervisor.

## Rewritten to `expect()`

| PHPUnit (count in suite) | Pest |
| --- | --- |
| `assertEquals($e, $a)` (685) | `expect($a)->toEqual($e)` |
| `assertSame($e, $a)` (39) | `expect($a)->toBe($e)` |
| `assertNotEquals($e, $a)` (3) | `expect($a)->not->toEqual($e)` |
| `assertNotSame($e, $a)` (1) | `expect($a)->not->toBe($e)` |
| `assertTrue($a)` (189), `assertFalse($a)` (140) | `->toBeTrue()`, `->toBeFalse()` |
| `assertNull($a)` (66), `assertNotNull($a)` (16) | `->toBeNull()`, `->not->toBeNull()` |
| `assertCount($n, $a)` (63) | `expect($a)->toHaveCount($n)` |
| `assertInstanceOf(C::class, $a)` (57) | `expect($a)->toBeInstanceOf(C::class)` |
| `assertNotEmpty($a)` (12) | `expect($a)->not->toBeEmpty()` |
| `assertStringContainsString($n, $a)` (11), `assertStringNotContainsString` (3) | `expect($a)->toContain($n)`, `->not->toContain($n)` |
| `assertStringStartsWith($p, $a)` (3) | `expect($a)->toStartWith($p)` |
| `assertContains($n, $a)` (2), `assertNotContains($n, $a)` (5) | `expect($a)->toContain($n)`, `->not->toContain($n)` |
| `assertArrayHasKey($k, $a)` (7), `assertArrayNotHasKey` (3) | `expect($a)->toHaveKey($k)`, `->not->toHaveKey($k)` |
| `assertEqualsCanonicalizing($e, $a)` (8) | `expect($a)->toEqualCanonicalizing($e)` |
| `assertEqualsWithDelta($e, $a, $d)` (7) | `expect($a)->toEqualWithDelta($e, $d)` |
| `assertGreaterThan($limit, $a)` (1) | `expect($a)->toBeGreaterThan($limit)` (arguments swap) |
| `assertIsString($a)`, `assertIsBool($a)` | `->toBeString()`, `->toBeBool()` |
| `expectException(C::class)` (221) + `expectExceptionMessage($m)` (9) | `->throws(C::class, $m)` on the test, always: PHPUnit applies `expectException` to the whole test wherever it stands, and so does `->throws()`; `toThrow()` would narrow it to one call |

Two checks per rewrite: the actual value goes into `expect()`, and strictness never drops (`toBe` stays `toBe`). `toHaveKey` reads a dot as nesting; no literal key in the suite holds a dot today, and one that does would need `toHaveKey` replaced by `array_key_exists`.

## Kept as they are

| Form | Why |
| --- | --- |
| `$this->assertDatabaseHas/Missing/Count`, `assertModelExists/Missing`, `assertSoftDeleted` | Laravel state, no `expect()` equivalent (FR-003b) |
| `$this->assertGuest`, `assertAuthenticated`, `assertAuthenticatedAs` | Laravel authentication state |
| `$response->assert*()` (499) | Methods of the response, not of the test case |
| `$this->actingAs`, `get`, `post`, `put`, `delete`, `from`, `withSession`, `travel`, `grant`, `giveDefaultRole` | Called on `$this` inside a test; `test()->` inside a helper (R3) |
| `$this->fail()` (2) | Same call in Pest |
| Facade fakes and their assertions (`Queue::fake`, `Queue::assertPushedOn`, ...) | Unchanged |
| A custom `assert*` helper of a file (`assertAnnouncesTheFavicons`) | Becomes a function of that file (R3); its body is mapped with this table |
