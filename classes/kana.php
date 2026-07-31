<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_gojuon;

/**
 * Gojūon (五十音) row definitions and reading normalisation.
 *
 * Each row lists every leading character — hiragana, full-width katakana,
 * and half-width katakana, with voiced/semi-voiced/small variants — that
 * files under it. Real furigana data is dirty (half-width katakana from
 * SIS/CSV imports, leading spaces, decomposed dakuten), so callers should
 * pass the stored reading through {@see self::first_key()} which applies
 * the same normalisation the SQL bucketing relies on.
 *
 * @package   local_gojuon
 * @copyright 2026 Jetha Chan
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class kana {

    /** Row key reserved for readings with no leading kana or Latin letter. */
    const OTHER = 'other';

    /** Pseudo key: no filter applied. */
    const ALL = 'all';

    /**
     * Base gojūon rows (hiragana + full-width katakana). Half-width
     * katakana is folded in at build time by {@see self::rows()}.
     *
     * @var array<string, array{label: string, chars: string[]}>
     */
    const BASE_ROWS = [
        'a'  => ['label' => 'あ', 'chars' => ['あ', 'ぁ', 'い', 'ぃ', 'う', 'ぅ', 'ゔ', 'え', 'ぇ', 'お', 'ぉ',
                                              'ア', 'ァ', 'イ', 'ィ', 'ウ', 'ゥ', 'ヴ', 'エ', 'ェ', 'オ', 'ォ']],
        'ka' => ['label' => 'か', 'chars' => ['か', 'が', 'き', 'ぎ', 'く', 'ぐ', 'け', 'げ', 'こ', 'ご', 'ゕ', 'ゖ',
                                              'カ', 'ガ', 'キ', 'ギ', 'ク', 'グ', 'ケ', 'ゲ', 'コ', 'ゴ', 'ヵ', 'ヶ']],
        'sa' => ['label' => 'さ', 'chars' => ['さ', 'ざ', 'し', 'じ', 'す', 'ず', 'せ', 'ぜ', 'そ', 'ぞ',
                                              'サ', 'ザ', 'シ', 'ジ', 'ス', 'ズ', 'セ', 'ゼ', 'ソ', 'ゾ']],
        'ta' => ['label' => 'た', 'chars' => ['た', 'だ', 'ち', 'ぢ', 'つ', 'っ', 'づ', 'て', 'で', 'と', 'ど',
                                              'タ', 'ダ', 'チ', 'ヂ', 'ツ', 'ッ', 'ヅ', 'テ', 'デ', 'ト', 'ド']],
        'na' => ['label' => 'な', 'chars' => ['な', 'に', 'ぬ', 'ね', 'の', 'ナ', 'ニ', 'ヌ', 'ネ', 'ノ']],
        'ha' => ['label' => 'は', 'chars' => ['は', 'ば', 'ぱ', 'ひ', 'び', 'ぴ', 'ふ', 'ぶ', 'ぷ', 'へ', 'べ', 'ぺ',
                                              'ほ', 'ぼ', 'ぽ', 'ハ', 'バ', 'パ', 'ヒ', 'ビ', 'ピ', 'フ', 'ブ', 'プ',
                                              'ヘ', 'ベ', 'ペ', 'ホ', 'ボ', 'ポ']],
        'ma' => ['label' => 'ま', 'chars' => ['ま', 'み', 'む', 'め', 'も', 'マ', 'ミ', 'ム', 'メ', 'モ']],
        'ya' => ['label' => 'や', 'chars' => ['や', 'ゃ', 'ゆ', 'ゅ', 'よ', 'ょ', 'ヤ', 'ャ', 'ユ', 'ュ', 'ヨ', 'ョ']],
        'ra' => ['label' => 'ら', 'chars' => ['ら', 'り', 'る', 'れ', 'ろ', 'ラ', 'リ', 'ル', 'レ', 'ロ']],
        // ん (and archaic ゐゑ) traditionally sit with the wa-row tail.
        'wa' => ['label' => 'わ', 'chars' => ['わ', 'ゎ', 'ゐ', 'ゑ', 'を', 'ん', 'ワ', 'ヮ', 'ヰ', 'ヱ', 'ヲ', 'ン']],
    ];

    /**
     * All index rows: gojūon rows (with half-width katakana folded in),
     * then Latin A–Z (keys la–lz, matching upper/lower/full-width).
     *
     * @return array<string, array{label: string, chars: string[]}>
     */
    public static function rows(): array {
        static $rows = null;
        if ($rows !== null) {
            return $rows;
        }
        $rows = [];
        $claimed = []; // Every character already assigned to a row.
        foreach (self::BASE_ROWS as $key => $row) {
            foreach ($row['chars'] as $ch) {
                $claimed[$ch] = true;
            }
        }
        foreach (self::BASE_ROWS as $key => $row) {
            $chars = $row['chars'];
            foreach ($row['chars'] as $ch) {
                // Fold full-width katakana to its half-width lead so that
                // ｻﾄｳ-style imported readings bucket alongside サトウ. Voiced
                // half-width kana (ｶﾞ) is two codepoints; the leading base
                // (ｶ) is what the prefix match sees, which is what we want.
                // Skip a lead already claimed by another row: archaic kana
                // (ヰ) have no true half-width form and PHP approximates them
                // onto a modern lead (ｲ) that belongs elsewhere.
                $half = mb_convert_kana($ch, 'k', 'UTF-8');
                $lead = mb_substr($half, 0, 1, 'UTF-8');
                if ($lead !== '' && $lead !== $ch && empty($claimed[$lead])) {
                    $chars[] = $lead;
                    $claimed[$lead] = true;
                }
            }
            $row['chars'] = array_values(array_unique($chars));
            $rows[$key] = $row;
        }
        foreach (range('A', 'Z') as $letter) {
            $lower = strtolower($letter);
            $rows['l' . $lower] = [
                'label' => $letter,
                'chars' => [
                    $letter,
                    $lower,
                    mb_chr(0xFF21 + ord($letter) - ord('A'), 'UTF-8'), // Ａ.
                    mb_chr(0xFF41 + ord($letter) - ord('A'), 'UTF-8'), // ａ.
                ],
            ];
        }
        return $rows;
    }

    /**
     * Whether a row key is a real filterable row (not all/other).
     *
     * @param string $key
     * @return bool
     */
    public static function is_row(string $key): bool {
        return isset(self::rows()[$key]);
    }

    /**
     * Classify a stored reading into its row key. This is a faithful PHP
     * mirror of the SQL bucketing in
     * {@see \local_gojuon\table\participants}: match the leading character
     * against the (half-width-expanded) row sets; empty readings and
     * leading characters in no row — kanji, whitespace, symbols — fall to
     * OTHER, which is the true complement, so no reading is unclassified.
     *
     * Half-width katakana is handled because the row sets include it; a
     * genuinely normalising bucketing (folding decomposed dakuten or leading
     * whitespace) would require a maintained shadow column, which this
     * plugin deliberately does not add — see README.
     *
     * @param string|null $reading
     * @return string a row key or OTHER
     */
    public static function first_key(?string $reading): string {
        $reading = (string) $reading;
        if ($reading === '') {
            return self::OTHER;
        }
        $first = mb_substr($reading, 0, 1, 'UTF-8');
        foreach (self::rows() as $key => $row) {
            if (in_array($first, $row['chars'], true)) {
                return $key;
            }
        }
        return self::OTHER;
    }
}
