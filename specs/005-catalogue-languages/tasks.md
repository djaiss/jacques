# Tasks: Catalogue Languages

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/actions.md](contracts/actions.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution principle III and `.claude/rules/testing.md`; each task writes or updates the test that mirrors its file, with data from The Office.

## Phase 1: Setup (configuration and schema)

- [X] T001 [P] In config/jacques.php add `content_languages` (`code => ['label' => native name, 'flag' => CSS background]`) for `en` English, `fr` Français, `es` Español, `pt` Português, `it` Italiano, `de` Deutsch, `ar` العربية, `zh` 中文, reusing the `en`, `fr_FR`, `de_DE`, `es_ES` flags of `locales` and drawing the four others the same way; and `country_languages` (`'CA' => ['en', 'fr']`, `'US' => ['en']`, `'FR' => ['fr']`, `'BE' => ['fr']`, `'CH' => ['fr', 'de']`), each with a comment block like `locales`
- [X] T002 [P] After `onboarding_step` in database/migrations/0000_01_01_000000_create_companies_table.php add `$table->json('languages')->nullable()->comment('ordered list of the languages the salon writes its catalogue in, the first one being the fallback');` (edit in place)

## Phase 2: Foundational (blocks every story)

- [X] T003 In app/Models/Company.php add `@property list<string> $languages`, `languages` in `$fillable` and cast to `array`; in database/factories/CompanyFactory.php write `'languages' => ['en']`; assert the cast in tests/Unit/Models/CompanyTest.php
- [X] T004 Write `'languages' => ['en']` when creating the company in app/Actions/CreateCompany.php; assert it in tests/Unit/Actions/CreateCompanyTest.php
- [X] T005 [P] Add `CompanyLanguagesUpdated = 'company_languages_updated'` with label "Changed the languages of the salon" to app/Enums/UserActionEnum.php; update its test in tests/Unit/Enums/

## Phase 3: User Story 1, the owner chooses the salon's languages during onboarding (P1) MVP

**Independent test**: sign up, choose Canada on step 1, keep English and French, submit, and the company `languages` is `["en", "fr"]`.

- [X] T006 [US1] Create app/Actions/UpdateCompanyLanguages.php (`author: User`, `company: Company`, `languages: list<string>`), class docblock of one or two sentences; authorize `PermissionEnum::CompanyManage` for the company like app/Actions/UpdateCompany.php; throw `InvalidArgumentException` for an empty list, a code not in `config('jacques.content_languages')`, or a duplicate; write the list as given; dispatch `LogUserAction` with `CompanyLanguagesUpdated` and `['languages' => implode(', ', $languages)]` on the `low` queue; with tests/Unit/Actions/UpdateCompanyLanguagesTest.php covering order kept, each refusal, a non-owner refused, and the log pushed on `low`
- [X] T007 [US1] Add `private readonly array $languages` (`@param list<string>`) after `timezone` in app/Actions/SetUpSalon.php and run `UpdateCompanyLanguages` inside the transaction after `renameCompany()`; update tests/Unit/Actions/SetUpSalonTest.php
- [X] T008 [P] [US1] In app/ViewModels/Onboarding/LocationViewModel.php add `languages` to `form()` (`old('languages')`, else `company->languages`), `languages()` returning per code of `content_languages` its `code`, `label` and `flag`, `languagesByCountry()` returning for every code of `countries()` its list from `country_languages`, else `['en']`, and `languageCounters()` returning for 1 to the number of offered languages `trans_choice(':count language|:count languages', $count)`; cover them in tests/Unit/ViewModels/Onboarding/LocationViewModelTest.php
- [X] T009 [US1] In app/Http/Controllers/App/Onboarding/LocationController.php validate `languages` `['required', 'array', 'min:1']` and `languages.*` `['required', 'string', 'distinct', Rule::in(array_keys(config('jacques.content_languages')))]` with the message "Choose at least one language." for `languages.required` and `languages.min`, and pass `languages: array_values($validated['languages'])` to `SetUpSalon`
- [X] T010 [US1] In resources/views/app/onboarding/location.blade.php add, after the time zone, the block "Languages spoken at the salon" with a required mark: a counter read from `languageCounters()` passed with `@js` and indexed by the number selected, the text "Keep at least one." shown when one remains, a `role="group"` labelled by the label, one `button type="button"` per language with `:aria-pressed`, a check box drawn as in the design, the flag as an `aria-hidden` span with the `flag` background, the native name with `lang="{code}"`, a hidden `languages[]` input per selected code in selection order, the help "Clients can book in these languages, and your team can say which ones they speak.", and `x-error` for `languages`; the last selected language cannot be deselected; `chooseCountry()` also sets the selection to `languagesByCountry()[country]`
- [X] T011 [US1] In tests/Feature/Controllers/App/Onboarding/LocationControllerTest.php cover: languages saved in the submitted order; no language refused with "Choose at least one language."; an unknown code and a duplicate refused; "Edit the salon" shows the saved languages selected

## Phase 4: User Story 2, the owner writes the catalogue in each salon language (P1)

**Independent test**: with `["en", "fr"]`, create a category and a service with names in both languages, and a name in one language only is refused.

- [X] T012 [P] [US2] In database/migrations/2026_10_14_000000_create_service_categories_table.php and database/migrations/2026_10_14_000001_create_services_table.php make `name` `json` (comment "name in each salon language, keyed by language code") and `description` `json` nullable (comment "description in each salon language, keyed by language code"), and remove `$table->unique(['company_id', 'name'])`
- [X] T013 [P] [US2] Create app/Helpers/CatalogueText.php with static `resolve(array $texts, array $languages, string $locale): string` (the text in `Str::before($locale, '_')` when it is a company language and non-empty, else in the first company language, else in the first company language holding a non-empty text, else `''`) and `missingLanguages(array $texts, array $languages): array` (company languages whose text is absent or empty, in company order); with tests/Unit/Helpers/CatalogueTextTest.php covering each fallback, an empty text, and a key of a removed language never returned as missing
- [X] T014 [US2] In app/Models/ServiceCategory.php and app/Models/Service.php change `@property` of `name` to `array<string, string>` and `description` to `array<string, string|null>|null`, cast both to `array`, and add `localizedName(string $locale): string` and `missingLanguages(): array` through `CatalogueText` with `$this->company->languages`; in database/factories/ServiceCategoryFactory.php and database/factories/ServiceFactory.php write `['en' => ...]`; cover both methods in tests/Unit/Models/ServiceCategoryTest.php and tests/Unit/Models/ServiceTest.php
- [X] T015 [US2] In app/Actions/CreateServiceCategory.php and app/Actions/UpdateServiceCategory.php type `name` as `array<string, string>` and `description` as `array<string, ?string>`; sanitize each text; throw `{code} is not a language of the company` for a key outside `company->languages`, `A name is needed in {code}` for a company language missing or empty, and the existing taken message when `where("name->{$code}", $text)` exists (excluding itself on update); on update merge the given texts over the stored ones so keys of removed languages stay; log the name in the first company language; update tests/Unit/Actions/CreateServiceCategoryTest.php and tests/Unit/Actions/UpdateServiceCategoryTest.php with each refusal and the same name allowed across languages
- [X] T016 [US2] Apply the same changes as T015 to app/Actions/CreateService.php and app/Actions/UpdateService.php, steps unchanged; update tests/Unit/Actions/CreateServiceTest.php and tests/Unit/Actions/UpdateServiceTest.php
- [X] T017 [US2] Replace every read of `service->name` or `category->name` as a string with `localizedName(company->languages[0])` in app/Actions/DestroyLocationService.php, app/Actions/AssignTeamMemberService.php and any other action found by `grep -rn -- "->name" app/Actions`; update every test passing a string `name` to a service or category factory or action, found with `grep -rln "'name' =>" tests/Unit`

## Phase 5: User Story 3, languages change after onboarding (P2)

**Independent test**: add Spanish to an English and French salon with services, check nothing changed and Spanish is missing; remove it, confirm the dialog, add it back, and the Spanish texts return.

- [X] T018 [P] [US3] Add `languagesWithTexts(): array` to app/ViewModels/Onboarding/LocationViewModel.php returning each company language that holds a non-empty name or description in at least one category or service of the company, with its label; cover it in tests/Unit/ViewModels/Onboarding/LocationViewModelTest.php
- [X] T019 [US3] In resources/views/app/onboarding/location.blade.php intercept the submit: when a code of `languagesWithTexts()` is no longer selected, open `x-confirm-dialog` titled "Stop using these languages?" naming them, with the text "Their texts stay saved, and come back if you add the language again." and the actions "Cancel" and "Remove the language", which submits the form
- [X] T020 [US3] In tests/Unit/Actions/UpdateServiceTest.php and tests/Unit/Actions/SetUpSalonTest.php cover: adding a language leaves every text unchanged and `missingLanguages()` returns it; removing it then adding it back restores its texts; updating a service after a language was removed keeps that language's key

## Phase 6: Polish

- [X] T021 Run `php artisan jacques:localize` and translate every reported string in all lang/*.json locales
- [X] T022 Run `vendor/bin/pint -q`, `vendor/bin/phpstan analyse --no-progress -q`, `php artisan test --compact` and `php artisan migrate:fresh --seed -q`; fix every finding
- [ ] T023 Run the quickstart.md scenarios on desktop, tablet and phone width, light and dark, keyboard only and with a screen reader

## Dependencies

- Phase 1, then Phase 2: T003 needs T002; T004 needs T003.
- US1 needs Phase 2. T006 needs T005. T007 needs T006. T009 needs T007. T010 needs T008. T011 needs T009 and T010.
- US2 needs Phase 2, not US1. T014 needs T012 and T013. T015 and T016 need T014. T017 needs T015 and T016.
- US3 needs US1 and US2. T019 needs T018. T020 needs T016.
- Phase 6 comes last.

## Parallel examples

- Phase 1: T001 with T002. Phase 2: T005 with T003.
- US1: T008 with T006.
- US2: T012 with T013; T015 with T016 once T014 is done.
- Across stories: US1 and US2 run side by side after Phase 2.

## Strategy

MVP is Phase 1, Phase 2 and US1: the salon chooses its languages on step 1. US2 makes the catalogue actions take one text per language, and US3 handles adding and removing a language. Phase 6 runs before each merge.
