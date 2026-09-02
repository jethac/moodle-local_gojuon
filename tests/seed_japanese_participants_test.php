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

use local_gojuon\local\japanese_participant_seed;

/**
 * Tests for the Japanese participant fixture helper used by reviewers.
 *
 * @package   local_gojuon
 * @copyright 2026 Jetha Chan
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class seed_japanese_participants_test extends \advanced_testcase {
    public function test_fixtures_cover_representative_japanese_name_data(): void {
        $fixtures = japanese_participant_seed::fixtures();
        $lastphonetics = array_column($fixtures, 'lastnamephonetic');
        $firstphonetics = array_column($fixtures, 'firstnamephonetic');

        $this->assertCount(10, $fixtures);
        $this->assertContains('かとう', $lastphonetics);
        $this->assertContains('サトウ', $lastphonetics);
        $this->assertContains('Watanabe', $lastphonetics);
        $this->assertContains('', $lastphonetics);
        $this->assertContains('かなこ', $firstphonetics);
        $this->assertContains('Naoko', $firstphonetics);
    }

    public function test_seed_is_idempotent_and_enrols_all_fixtures_as_students(): void {
        global $DB;

        $this->resetAfterTest();

        $first = japanese_participant_seed::seed('GOJUONTEST', 'Gojūon participant fixture test');
        $second = japanese_participant_seed::seed('GOJUONTEST', 'Gojūon participant fixture test');
        $course = $DB->get_record('course', ['shortname' => 'GOJUONTEST'], '*', MUST_EXIST);

        $this->assertSame((int) $first['course']->id, (int) $second['course']->id);
        $this->assertSame((int) $course->id, (int) $first['course']->id);
        $this->assertSame(10, $first['createdusers']);
        $this->assertSame(0, $second['createdusers']);
        $this->assertSame(10, count($second['users']));
        $this->assertSame(10, $this->count_manual_student_enrolments((int) $course->id));
    }

    /**
     * Count manual student enrolments in a course.
     *
     * @param int $courseid
     * @return int
     */
    private function count_manual_student_enrolments(int $courseid): int {
        global $DB;

        $studentroleid = (int) $DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);

        $sql = "SELECT COUNT(1)
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                  JOIN {role_assignments} ra ON ra.userid = ue.userid
                 WHERE e.courseid = :courseid
                   AND e.enrol = :enrol
                   AND ra.roleid = :roleid";

        return (int) $DB->count_records_sql($sql, [
            'courseid' => $courseid,
            'enrol' => 'manual',
            'roleid' => $studentroleid,
        ]);
    }
}
