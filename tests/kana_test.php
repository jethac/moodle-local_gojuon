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
 * Tests for the kana row model and reading classification.
 *
 * @package   local_gojuon
 * @copyright 2026 Jetha Chan
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \local_gojuon\kana
 */
final class kana_test extends \advanced_testcase {

    /**
     * Every candidate character belongs to exactly one row — buckets must
     * not overlap, or a person appears twice and counts stop summing.
     */
    public function test_rows_are_pairwise_disjoint(): void {
        $seen = [];
        foreach (kana::rows() as $key => $row) {
            // Each row's own set must already be duplicate-free.
            $this->assertSame(array_values($row['chars']), array_values(array_unique($row['chars'])),
                "Row '$key' has duplicate characters.");
            foreach ($row['chars'] as $char) {
                if (isset($seen[$char])) {
                    $this->fail("Character '$char' is in both '{$seen[$char]}' and '$key' rows.");
                }
                $seen[$char] = $key;
            }
        }
        $this->assertNotEmpty($seen);
    }

    /**
     * Classification of representative readings, including the dirty-data
     * cases that used to make people vanish.
     *
     * @dataProvider reading_provider
     * @param string|null $reading
     * @param string $expected row key
     */
    public function test_first_key(?string $reading, string $expected): void {
        $this->assertSame($expected, kana::first_key($reading));
    }

    /**
     * @return array<string, array{0: ?string, 1: string}>
     */
    public static function reading_provider(): array {
        return [
            'hiragana ka' => ['かとう', 'ka'],
            'voiced ga stays in ka-row' => ['がとう', 'ka'],
            'katakana sa' => ['サトウ', 'sa'],
            'half-width katakana sa' => ['ｻﾄｳ', 'sa'],
            'leading full-width space falls to other (matches SQL)' => ['　たなか', 'other'],
            'leading ascii space falls to other (matches SQL)' => [' たなか', 'other'],
            'romaji upper' => ['Sato', 'ls'],
            'romaji lower' => ['sato', 'ls'],
            'full-width romaji' => ['Ｓato', 'ls'],
            'kanji reading falls to other' => ['佐藤', 'other'],
            'empty is other' => ['', 'other'],
            'null is other' => [null, 'other'],
            'n in wa-row' => ['んま', 'wa'],
        ];
    }

    /**
     * Row keys validate, non-keys and pseudo-keys do not.
     */
    public function test_is_row(): void {
        $this->assertTrue(kana::is_row('ka'));
        $this->assertTrue(kana::is_row('lz'));
        $this->assertFalse(kana::is_row(kana::OTHER));
        $this->assertFalse(kana::is_row(kana::ALL));
        $this->assertFalse(kana::is_row('nope'));
        $this->assertFalse(kana::is_row(''));
    }
}
