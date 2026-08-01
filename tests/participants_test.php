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

use context_course;
use core_table\local\filter\integer_filter;
use core_table\local\filter\string_filter;
use core_user\table\participants_search;
use local_gojuon\table\participants;
use local_gojuon\table\participants_filterset;

/**
 * DB-backed tests for the gojūon participants filtering.
 *
 * @package   local_gojuon
 * @copyright 2026 Jetha Chan
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \local_gojuon\table\participants
 * @covers    \local_gojuon\table\participants_filterset
 * @covers    \local_gojuon\phonetic
 */
final class participants_test extends \advanced_testcase {
    /** @var \stdClass */
    protected $course;

    /** @var context_course */
    protected $context;

    /** @var \stdClass The teacher, who can see phonetic names. */
    protected $teacher;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        set_config('enabled', 1, 'local_gojuon');
        // Privileged viewers see phonetic readings; the default format does not.
        set_config(
            'alternativefullnameformat',
            'lastname firstname lastnamephonetic firstnamephonetic'
        );

        $gen = $this->getDataGenerator();
        $this->course = $gen->create_course();
        $this->context = context_course::instance($this->course->id);

        // Reading pairs and the last-row / first-row each should fall into.
        // Each pair is [lastnamephonetic, firstnamephonetic]: Kato (ka/ta),
        // Gato (ka voiced/ha), Sato (sa/sa), Sasaki (sa half-width/ya),
        // Tanaka (ta/ka), Smith (romaji ls/lj), a kanji reading (other/ka),
        // and an empty reading (other/other).
        $students = [
            ['かとう', 'たろう'],
            ['ガトウ', 'はなこ'],
            ['さとう', 'じろう'],
            ['ｻｻｷ', 'ゆき'],
            ['たなか', 'かなこ'],
            ['Smith', 'John'],
            ['佐藤', 'けん'],
            ['', ''],
        ];
        foreach ($students as [$last, $first]) {
            $u = $gen->create_user([
                'lastnamephonetic' => $last,
                'firstnamephonetic' => $first,
            ]);
            $gen->enrol_user($u->id, $this->course->id, 'student');
        }
        // Teacher reading is in the wa-row, kept out of the rows asserted
        // below so student counts stay predictable.
        $this->teacher = $gen->create_user([
            'lastnamephonetic' => 'わたなべ',
            'firstnamephonetic' => 'わたる',
        ]);
        $gen->enrol_user($this->teacher->id, $this->course->id, 'editingteacher');

        $this->setUser($this->teacher);
    }

    /**
     * Count participants matching a set of kana filters.
     *
     * @param array $kana Map of filter name to row key.
     * @return int
     */
    protected function count_filtered(array $kana): int {
        $fs = new participants_filterset();
        $fs->add_filter(new integer_filter('courseid', null, [(int) $this->course->id]));
        foreach ($kana as $name => $val) {
            $fs->add_filter(new string_filter($name, null, [$val]));
        }
        $table = new participants('test-' . $this->course->id);
        $table->set_filterset($fs);
        [$where, $params] = $table->get_sql_where();
        $search = new participants_search($this->course, $this->context, $fs);
        $rs = $search->get_participants($where, $params);
        $count = iterator_count($rs);
        $rs->close();
        return $count;
    }

    public function test_unfiltered_counts_everyone(): void {
        // Eight students plus the teacher.
        $this->assertSame(9, $this->count_filtered([]));
    }

    public function test_last_name_rows(): void {
        // Ka-row: Kato and Gato (voiced katakana).
        $this->assertSame(2, $this->count_filtered(['kanalast' => 'ka']));
        // Sa-row: Sato and Sasaki (half-width katakana).
        $this->assertSame(2, $this->count_filtered(['kanalast' => 'sa']));
        // Ta-row: Tanaka.
        $this->assertSame(1, $this->count_filtered(['kanalast' => 'ta']));
        // L-row (romaji): Smith.
        $this->assertSame(1, $this->count_filtered(['kanalast' => 'ls']));
    }

    public function test_other_is_the_true_complement(): void {
        // Kanji reading plus empty reading fall to the other bucket.
        $this->assertSame(2, $this->count_filtered(['kanalast' => kana::OTHER]));
    }

    public function test_totality_invariant(): void {
        $sum = $this->count_filtered(['kanalast' => kana::OTHER]);
        foreach (array_keys(kana::rows()) as $rowkey) {
            $sum += $this->count_filtered(['kanalast' => $rowkey]);
        }
        $this->assertSame(
            $this->count_filtered([]),
            $sum,
            'Every participant must land in exactly one bucket.'
        );
    }

    public function test_two_axes_compose(): void {
        // Kato Taro is the only ka-last plus ta-first participant.
        $this->assertSame(1, $this->count_filtered(['kanalast' => 'ka', 'kanafirst' => 'ta']));
        $this->assertSame(0, $this->count_filtered(['kanalast' => 'ka', 'kanafirst' => 'ma']));
    }

    public function test_disabled_plugin_removes_the_filter_surface(): void {
        // When disabled, the filterset does not advertise the kana filters,
        // so the webservice layer rejects them outright.
        set_config('enabled', 0, 'local_gojuon');
        $this->expectException(\InvalidArgumentException::class);
        $this->count_filtered(['kanalast' => 'ka']);
    }

    public function test_unknown_row_is_rejected(): void {
        $this->expectException(\invalid_parameter_exception::class);
        $this->count_filtered(['kanalast' => 'zzz']);
    }

    public function test_non_any_jointype_is_rejected(): void {
        $fs = new participants_filterset();
        $fs->add_filter(new integer_filter('courseid', null, [(int) $this->course->id]));
        $fs->add_filter(new string_filter(
            'kanalast',
            \core_table\local\filter\filter::JOINTYPE_NONE,
            ['ka']
        ));
        $table = new participants('t');
        $table->set_filterset($fs);
        $this->expectException(\invalid_parameter_exception::class);
        $table->get_sql_where();
    }

    public function test_visibility_gate_blocks_unprivileged_viewer(): void {
        // A student without moodle/site:viewfullnames cannot see the phonetic
        // reading, so the filter must be ignored (returns everyone) — no oracle.
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $this->course->id, 'student');
        $this->setUser($student);
        $this->assertSame(
            10,
            $this->count_filtered(['kanalast' => 'ka']),
            'Unprivileged viewer must not be able to filter by a hidden reading.'
        );

        $this->setUser($this->teacher);
        $this->assertSame(
            2,
            $this->count_filtered(['kanalast' => 'ka']),
            'Privileged viewer filters normally.'
        );
    }
}
