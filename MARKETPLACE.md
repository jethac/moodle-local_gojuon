# Moodle Marketplace listing copy

Ready-to-paste text for the Moodle Marketplace submission form.

## Plugin name

Gojūon participants index

## Frankenstyle component

`local_gojuon`

## Short description (one or two sentences)

A gojūon (五十音) index bar for the course participants page that filters by
students' phonetic name readings — the kana navigation the Latin A–Z bar never
provided for Japanese names.

## Full description

On a Japanese-language Moodle site, the participants page still offers a Latin
A–Z index bar that matches on the *kanji* name column, so it finds nobody: every
button returns an empty list. This plugin adds the index Japanese users actually
expect — a gojūon bar (あ か さ た な は ま や ら わ, hiragana and katakana, plus
A–Z for romaji readings and 他 for anyone with no reading recorded) that filters
on the phonetic name fields Moodle has carried since 2.6.

It provides separate 姓 (last name) and 名 (first name) axes that combine with
each other and with every core participants filter. 他 is the true complement, so
no participant ever silently disappears from the index. Matching is case- and
accent-sensitive (voiced kana such as が never fold into their base か) and stays
index-usable on large rosters.

The plugin makes no changes to Moodle core — it is a dynamic-table subclass plus
a footer hook — and is careful about privacy and accessibility: the kana filter
is only offered for a name reading the viewer is already permitted to see, and
the bar announces filter changes to screen readers, labels each axis as a group,
and marks kana chips with `lang="ja"` for correct pronunciation.

Optional settings let an administrator hide the redundant A–Z bars entirely or
disable the kana filter site-wide.

## Category

Admin tools (or: Course / participants).

## Licence

GNU GPL v3 or later.

The release ZIP includes `COPYING.txt`.

## Translations

The plugin ships English strings only. Japanese translations should be submitted
through AMOS after the plugin is approved and registered.

## Repository / tracker / documentation

- Source: https://github.com/jethac/moodle-local_gojuon
- Issues: https://github.com/jethac/moodle-local_gojuon/issues
- Documentation: the repository README.

## Screenshots for the listing

1. `screenshots/participants-bars.png` — the participants page of a Japanese-
   language course showing both the 姓 and 名 bars above the list, with A–Z hidden.
2. `screenshots/participants-filter-ka.png` — the list filtered to か, showing
   only matching students.
3. `screenshots/admin-settings.png` — the admin settings page.
