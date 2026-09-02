# Changelog

All notable changes to this plugin are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/), and the project adheres to
[Semantic Versioning](https://semver.org/).

## [1.0.3] - 2026-09-02

### Changed
- Prepared the Marketplace submission package with bundled GPL v3 licence text,
  listing screenshots, and English-only plugin strings for AMOS translation.

## [1.0.2] - 2026-08-01

### Fixed
- The chip for the currently-applied filter now shows a clear active state
  (filled with the theme's brand colour). The active chip was already tracked
  in markup (`aria-pressed`) and toggled at runtime, but nothing styled it, so
  the selected kana row was visually indistinguishable from the rest.

## [1.0.1] - 2026-08-01

### Fixed
- The index bar rendered into the page footer instead of above the
  participants table (a regression from the move to a Mustache template +
  AMD module); the module now relocates it above the table.
- The "Hide A–Z initials bars" setting no longer hid core's bars, which are
  marked with Bootstrap's `.d-flex` (`display: flex !important`); the CSS now
  wins with `!important` and targets the real `.initialbargroups` container.

### Added
- Behat coverage asserting the bar renders above the participants table.

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
