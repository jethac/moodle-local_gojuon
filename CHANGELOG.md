# Changelog

All notable changes to this plugin are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/), and the project adheres to
[Semantic Versioning](https://semver.org/).

## [1.0.0] - 2026-08-01

First stable release.

### Added
- Gojūon (五十音) index bar on the course participants page, filtering by the
  phonetic name fields (`lastnamephonetic` / `firstnamephonetic`).
- Separate 姓 (last-name) and 名 (first-name) axes, each with すべて / kana rows
  (hiragana and katakana, including half-width) / A–Z / 他.
- 他 as the true complement (empty reading, or a leading character in no row),
  so every participant lands in exactly one bucket.
- Case- and accent-sensitive prefix matching (voiced kana never conflate with
  their base) that stays index-usable.
- Visibility gate: the filter is honoured only for a phonetic field the viewer
  may already read, so it cannot be used to probe a hidden reading.
- `Enable kana filtering` and `Hide A–Z initials bars` admin settings.
- Screen-reader support: a polite live region announces filter changes,
  `role="group"` labelling per axis, `lang="ja"` on kana chips, and
  `aria-controls` linking chips to the participants table.
- Privacy provider (stores no personal data), English and Japanese language
  packs, PHPUnit and Behat coverage, and a `moodle-plugin-ci` workflow.

### Notes
- No Moodle core modifications: a dynamic-table subclass plus a footer hook.
- Requires Moodle 4.5+ (PHP 8.3+, PostgreSQL 16+ / MariaDB 10.6+).
