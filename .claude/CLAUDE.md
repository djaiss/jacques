## General

- You MUST NOT tell me I am right all the time. Be critical. We're equals. You
  MUST stay neutral and objective.
- You MUST NOT use emojis excessively.
- You MUST remove all mannered prose.
- You MUST add quiet flags to noisy commands.

## Which rules win

When two sources disagree, you MUST follow this order.

1. What I ask you in the conversation.
2. The code that is already there, checked in the files sitting next to the one
   you are editing.
3. This file, then `.claude/rules/`, then the project skills in
   `.claude/skills/`.
4. The `laravel-best-practices` skill and the Laravel Boost guidelines at the
   bottom of this file. They are defaults for a question the project has never
   answered, and for a real correctness or security defect. When you follow one
   of them against a project rule, you MUST say so.
5. Framework defaults.

## Coding instructions

- You MUST write code as simply as possible. You MUST NOT over-engineer, so that
  anyone can understand it.
- You MUST NOT extract a private method just because a few lines are repeated
  twice. If the repeated code is short and not business critical, you SHOULD
  repeat it inline rather than naming and hiding it behind a helper. You SHOULD
  extract only when the logic is non-trivial, reused in several places, or its
  own concept worth naming.
- You MUST follow the Laravel best practices and how we structure our codebase.
- If you are unsure about a specific implementation, you MUST ask for
  clarification before proceeding.
- When you write tests, you MUST read the testing rules in `rules/testing.md`
  and follow them.
- When you write code, you MUST follow the coding standards in
  `rules/code-style.md`.
- You MUST warn users before making a destructive action in the UI.

## Naming

Every name you choose (for files, scripts, directories, functions, variables,
classes, commits, branches, and anything else) MUST use precise, professional
vocabulary.

You MUST use terminology that would be appropriate in a formal engineering
specification. You MUST prefer literal, unambiguous names over slang, casual
shorthand, cute or clever names, metaphors, or terminology borrowed from chat
culture.

Naming MUST also follow the principles of ASD-STE100 Simplified Technical
English where applicable:

- You MUST use one word for one meaning.
- You MUST prefer approved, common, concrete terminology.
- You MUST NOT use synonyms when an established term already exists.
- You MUST NOT use ambiguous words and expressions.
- You MUST NOT use unnecessary abbreviations and contractions.
- You MUST use verbs that describe the actual operation being performed.
- You MUST use nouns that identify the actual object or concept.
- You MUST keep terminology consistent throughout the codebase.

This applies universally, not only to the example that follows.

A script that deploys dashboards is `deploy_dashboards.sh`, not
`push_dashboards.sh`. `Deploy` describes the operation precisely. `Push` is
informal and can have several meanings.

You MUST treat this example as an illustration of the principle, not as the
extent of the rule.

## Tech Stack & Architecture

- Backend: PHP 8.4+ / latest version of Laravel
- Frontend: Blade / Tailwind CSS / Alpine Ajax / Alpine.js

## Guidelines for git and Github

- You MUST create a new branch when doing a new task, unless stated otherwise,
  based off of main branch. You MUST make sure main is always up-to-date.
- Branch names MUST be of the format YYYY-MM-DD-{name}.
- You MUST follow conventional commits for commit messages.
- You MUST NEVER mention Claude Code in commit messages, PR descriptions, PR
  comments, or issue comments. A `Co-Authored-By` trailer naming Claude counts
  as mentioning it, whatever your own defaults say.

## Guidelines for writing a commit message

- You MUST provide a simple sentence when submitting a PR. You MUST NOT write
  `feat: allow deleting the last vault of an account`. Instead, write
  `feat: delete last vault in account`.

DO NOT
```
feat: draw the top bar controls so they read against the bar
```

DO
```
feat: better header buttons
```

## Writing something

You MUST NEVER use dashes (— or -) as punctuation in documentation or README
files. You MUST rephrase sentences using periods, commas, or parentheses
instead.

## Checklist for each feature or change

- You MUST run `php artisan jacques:localize` and translate what it reports
  as missing, in every configured locale. You MUST NEVER edit `lang/*.json` keys
  by hand.
- You MUST make sure what you wrote is covered by a test.
