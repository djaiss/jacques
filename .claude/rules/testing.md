# Testing rules

## Core philosophy

- Every model, controller, action, helper, job, mail, service and view model
  should have a corresponding test.
- Tests should cover the main functionality and important edge cases, not every
  single line of code.
- Use the popular tv show The Office (US version) when you need data to prove a
  point.
- We use Pest for testing. A test file holds Pest functions and no class.

## Conventions (all tests)

- Start every test file with `declare(strict_types=1);`.
- Mirror the app folders under `tests/Unit/` (e.g. `app/Actions` →
  `tests/Unit/Actions`), one file per class under test, named after it with a
  `Test` suffix.
- Write each test as `it('...', function () { ... });`, the description a
  sentence in lower case describing the behavior: `it('creates a role')`.
- `tests/Pest.php` applies `Tests\TestCase` and `RefreshDatabase` to `tests/Unit`
  and `tests/Feature`. A test file MUST NOT call `uses()` or `pest()` for them.
- Inside a test, call the test case with `$this` (`$this->actingAs()`,
  `$this->grant()`, `$this->giveDefaultRole()`).
- A helper is a function at the top level of its file, after the imports. A file
  holding one MUST declare a namespace made of its path and its file name (e.g.
  `namespace Tests\Unit\Actions\CreateStaffAbsenceTest;`), so two files can
  each have an `owner()`. In that file, import every global class you name
  (`use InvalidArgumentException;`), or `::class` resolves inside the namespace.
  A helper reaches the test case with `test()->` (`test()->grant(...)`), since a
  function has no `$this`. A file without helper declares no namespace.
- A constant shared by the tests of a file is a `const` at the top level of that
  file, in its namespace.
- Several cases of one test go through a dataset: `->with('name')` and
  `dataset('name', ...)` in the same file.
- Use named arguments when instantiating classes and calling actions.
- Build models with `factory()->create()` for persisted records and
  `factory()->make()` for in-memory ones.

## Assertions

- Assert values with `expect($actual)`, the value under test inside `expect()`:
  `toEqual()` for `==`, `toBe()` for `===`, `toBeTrue()`, `toBeFalse()`,
  `toBeNull()`, `toHaveCount()`, `toBeInstanceOf()`, `toContain()`,
  `toHaveKey()`, `toEqualCanonicalizing()`, `toEqualWithDelta()`, and `->not->`
  for the negative form. Never replace a `toBe()` with a `toEqual()`.
- Keep the Laravel assertions on `$this`: `assertDatabaseHas()`,
  `assertDatabaseMissing()`, `assertDatabaseCount()`, `assertModelExists()`,
  `assertModelMissing()`, `assertSoftDeleted()`, `assertGuest()`,
  `assertAuthenticated()`, `assertAuthenticatedAs()`. Assertions on a response
  stay on the response (`$response->assertRedirect()`).
- Expect an exception with `->throws(Exception::class)` chained after the test,
  with the message as second argument when it matters. It covers the whole test.
- `toHaveKey()` reads a dot as nesting; for a key holding a dot, assert
  `array_key_exists()` with `toBeTrue()`.

## Model tests

- Assert relationships exist with `$model->relation()->exists()` (belongs to,
  has many).
- Test accessors and computed attributes, including their fallback/null cases.

## Action tests

- Fake the queue with `Queue::fake()` and assert dispatched jobs with
  `Queue::assertPushedOn()`.
- Assert persisted state with `assertDatabaseHas()` and the returned type with
  `toBeInstanceOf()`.
- Test authorization failures: `->throws(ModelNotFoundException::class)` when
  the user is not in the vault or lacks the role.
- Cover important business logic edge cases (e.g. auto-incrementing position).

## Job tests

- Dispatch the job, then assert its side effects on the database.
- Use `toEqualWithDelta()` for timestamp assertions to avoid flakiness.

## Mail tests

- Assert the envelope subject and that `render()` contains the expected dynamic
  content.

## Helper tests

- Test pure functions across normal input, edge cases, and null/empty handling.

## Controller tests (Feature)

- Place controller tests under `Tests\Feature\Controllers`, mirroring the `App`,
  `Api` and `Marketing` controller structure.
- Authenticate web requests with `actingAs($user)` and make calls with `get()`,
  `post()`, `put()` and `delete()`.
- Authenticate API requests with `Sanctum::actingAs($user)` and make calls with
  `$this->json(METHOD, url, payload)`.
- Assert web responses with `assertStatus()`, `assertRedirect()` and
  `assertSessionHas('status', ...)`.
- Assert API responses with `assertOk()`, `assertCreated()`,
  `assertNoContent()`, `assertNotFound()` and `assertUnprocessable()`.
- Define the JSON structure shared by the file as a top level
  `const JSON_STRUCTURE` and assert it with `assertJsonStructure()`; assert values with `assertJsonPath()` and
  `assertJsonCount()`.
- Assert validation failures with `assertUnprocessable()` and
  `assertJsonValidationErrors()`.
- Test vault scoping: requests for another vault's resource return not found (or
  an empty list).
- Test role restrictions: a non-owner performing an owner-only action gets
  `assertNotFound()`.
- Assert persisted changes via `refresh()` and deletions via
  `assertModelMissing()`.
- Fake the queue with `Queue::fake()` when the controller dispatches jobs.
