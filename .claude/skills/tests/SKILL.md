---
name: tests
description: Conventions for defining tests. Use when the user wants to create or modify tests, including test structure, naming conventions, and best practices.
---

# Tests

- You MUST read and follow the testing rules in `.claude/rules/testing.md`.
- You MUST write tests with Pest, as `it('...', function () { ... })`. A test
  file holds no class.
- Test descriptions MUST be descriptive sentences in lower case, like
  `it('lists the api keys of the current user')`.
- You MUST assert with `expect()`, keep the Laravel database and authentication
  assertions on `$this`, and expect an exception with `->throws()`:

  ```php
  it('throws when the location belongs to another company', function () {
      $author = $this->grant(User::factory()->create(), PermissionEnum::LocationManage);
      $location = Location::factory()->create();

      new ArchiveLocation(author: $author, location: $location)->execute();
  })->throws(ModelNotFoundException::class);
  ```
- You MUST not test sanitization (i.e. `TextSanitizer`).
- You MUST not test casts (Model casting).
- You MUST test blade components: one file per component under
  `tests/Feature/Views/Components`, rendered with `$this->blade(...)`. See the
  view tests section of `.claude/rules/testing.md`.
