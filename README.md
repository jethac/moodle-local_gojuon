# local_gojuon — Gojūon participants index for Moodle

A kana index bar (あ か さ た な は ま や ら わ) for the course participants page, filtering by the **phonetic name fields** (`lastnamephonetic`, falling back to `firstnamephonetic`) that Moodle has carried since 2.6 — the fields the Latin A–Z initials bars ignore entirely.

On a Japanese-language site, the stock A–Z bars are 26 buttons that each match nobody: they `LIKE 'A%'` against kanji surnames. This plugin adds the index Japanese users actually expect — one chip per gojūon row (hiragana *and* katakana leading characters, voiced/semi-voiced/small variants included), plus すべて and 他 (users with no reading recorded).

## How it works — no core modifications

Moodle 3.9+ participants tables are *dynamic tables*, addressable by component + handler over AJAX. This plugin:

1. ships `local_gojuon\table\participants` extending `\core_user\table\participants`, overriding `get_sql_where()` to add the kana-row condition (which core routes through both the row query and the count);
2. ships a matching filterset accepting an optional `kanarow` string filter alongside every core filter;
3. injects the bar via the `before_footer_html_generation` hook on `user/index.php` only, re-pointing the page's table at the subclass — all core filtering, sorting, paging and the (optionally hidden) A–Z bars keep working.

## Install

```bash
cp -r . /path/to/moodle/public/local/gojuon
php admin/cli/upgrade.php --non-interactive
php admin/cli/purge_caches.php
```

Setting: Site administration → Plugins → Local plugins → Gojūon participants index → *Hide A–Z initials bars*.

Pairs nicely with [theme_lozenge](https://github.com/jethac/moodle-theme_lozenge). GPLv3, like Moodle.
