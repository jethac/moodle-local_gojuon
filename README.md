# local_gojuon — Gojūon participants index for Moodle

A kana index bar (あ か さ た な は ま や ら わ) for the course participants page, filtering by the **phonetic name fields** (`lastnamephonetic`, falling back to `firstnamephonetic`) that Moodle has carried since 2.6 — the fields the Latin A–Z initials bars ignore entirely.

On a Japanese-language site, the stock A–Z bars are 26 buttons that each match nobody: they `LIKE 'A%'` against kanji surnames. This plugin adds the index Japanese users actually expect — one chip per gojūon row (hiragana *and* katakana leading characters, voiced/semi-voiced/small variants included), plus すべて and 他 (users with no reading recorded).

## How it works — no core modifications

Moodle 3.9+ participants tables are *dynamic tables*, addressable by component + handler over AJAX. This plugin:

1. ships `local_gojuon\table\participants` extending `\core_user\table\participants`, overriding `get_sql_where()` to add the kana-row condition (which core routes through both the row query and the count);
2. ships a matching filterset accepting an optional `kanarow` string filter alongside every core filter;
3. injects the bar via the `before_footer_html_generation` hook on `user/index.php` only, re-pointing the page's table at the subclass — all core filtering, sorting, paging and the (optionally hidden) A–Z bars keep working.

Requires Moodle 4.5+ (PHP 8.3+, PostgreSQL 16+ / MariaDB 10.6+). Tested against Moodle 4.5 and 5.2.

## Install

**From the release ZIP (recommended).** Download the ZIP from the [latest release](https://github.com/jethac/moodle-local_gojuon/releases/latest), then in Moodle go to *Site administration → Plugins → Install plugins*, upload the ZIP, and complete the upgrade. The ZIP already unpacks to the correct `gojuon/` folder, so Moodle detects it as `local_gojuon` automatically. (Do not use GitHub's auto-generated "Source code" ZIP for this — its top folder is named for the tag, which Moodle won't place correctly.)

**Manually.** Unpack into your Moodle tree as `local/gojuon/` (on Moodle 5.x that is `public/local/gojuon/`), then:

```bash
php admin/cli/upgrade.php --non-interactive
php admin/cli/purge_caches.php
```

Setting: Site administration → Plugins → Local plugins → Gojūon participants index (*Enable kana filtering*, *Hide A–Z initials bars*).

> Moodle's own Plugins Directory went read-only in mid-2026 ahead of the new Moodle Marketplace, so for now the GitHub release ZIP above is the distribution channel.

Pairs nicely with [theme_lozenge](https://github.com/jethac/moodle-theme_lozenge). GPLv3, like Moodle.

## Security & scope notes

- **Filter visibility gate.** The kana filters and the bar are shown only for a phonetic field the viewer may actually read (present in the name format that applies to them). This prevents using the filter as an oracle to binary-search a hidden reading. Note that core's own `keywords` participants filter runs an *ungated* `LIKE '%…%'` over the same two phonetic columns, so this plugin is at parity with core, and stricter by default.
- **Collation.** Matching is a case- and accent-sensitive prefix `LIKE`, so voiced kana never conflate with their base (が ≠ か) regardless of the site's default collation, and the predicate is index-usable.
- **Completeness.** 他 is the true complement (empty reading, or a leading character in no row), so every participant lands in exactly one bucket — nobody silently vanishes. Half-width katakana is bucketed; leading whitespace / decomposed dakuten fall to 他 (a maintained shadow reading column would be needed to normalise those, deliberately out of scope).
- **Disable switch.** *Enable kana filtering* (on by default) removes the filters from the participants webservice entirely when off — the surface is gone, not merely hidden.

## Testing

- **PHPUnit** (`tests/`): `kana_test` proves the row model (pairwise-disjoint buckets, dirty-data classification); `participants_test` is DB-backed over generator fixtures — per-row counts, the true-complement 他, the totality invariant (every participant in exactly one bucket), two-axis composition, unknown-value and non-ANY-jointype rejection, the disabled-plugin filter-surface removal, and the visibility gate (an unprivileged viewer cannot filter by a hidden reading).
- **Behat** (`tests/behat/filter.feature`, `@javascript`): the teacher filter/clear/compose flow, plus a `wcag2aa` axe-core assertion on the bar.
- **CI**: `.github/workflows/ci.yml` runs `moodle-plugin-ci` (phplint, phpmd, phpcs, phpdoc, validate, mustache, grunt, phpunit, behat) across PHP 8.3 × Moodle 4.5/5.2 on PostgreSQL 16 — all green. The `amd/build` files are real grunt output (`grunt amd`), so the grunt conformance check passes.

## Author

Jetha Chan — [jethachan.net](https://jethachan.net) · [@jetha on X](https://x.com/jetha) · [jethachan@gmail.com](mailto:jethachan@gmail.com)

Bug reports and pull requests welcome on [GitHub](https://github.com/jethac/moodle-local_gojuon/issues). GPLv3, like Moodle.
