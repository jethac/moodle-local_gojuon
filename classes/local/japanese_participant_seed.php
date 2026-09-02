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

namespace local_gojuon\local;

use context_course;

/**
 * Idempotent Japanese participant fixtures for reviewers and local testing.
 *
 * @package   local_gojuon
 * @copyright 2026 Jetha Chan
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class japanese_participant_seed {
    /** @var string Default fixture course shortname. */
    public const DEFAULT_SHORTNAME = 'GOJUON-JA-FIXTURES';

    /** @var string Default fixture course fullname. */
    public const DEFAULT_FULLNAME = 'Gojūon Japanese participant fixtures';

    /**
     * Fixture users spanning kana rows, romaji readings, and missing readings.
     *
     * @return array<int, array<string, string>>
     */
    public static function fixtures(): array {
        return [
            [
                'username' => 'gojuon.ando',
                'firstname' => '葵',
                'lastname' => '安藤',
                'firstnamephonetic' => 'あおい',
                'lastnamephonetic' => 'あんどう',
            ],
            [
                'username' => 'gojuon.kato',
                'firstname' => '健',
                'lastname' => '加藤',
                'firstnamephonetic' => 'けん',
                'lastnamephonetic' => 'かとう',
            ],
            [
                'username' => 'gojuon.sato',
                'firstname' => '美咲',
                'lastname' => '佐藤',
                'firstnamephonetic' => 'みさき',
                'lastnamephonetic' => 'サトウ',
            ],
            [
                'username' => 'gojuon.tanaka',
                'firstname' => 'かな子',
                'lastname' => '田中',
                'firstnamephonetic' => 'かなこ',
                'lastnamephonetic' => 'たなか',
            ],
            [
                'username' => 'gojuon.nakamura',
                'firstname' => '直子',
                'lastname' => '中村',
                'firstnamephonetic' => 'なおこ',
                'lastnamephonetic' => 'なかむら',
            ],
            [
                'username' => 'gojuon.hayashi',
                'firstname' => '亮',
                'lastname' => '林',
                'firstnamephonetic' => 'りょう',
                'lastnamephonetic' => 'はやし',
            ],
            [
                'username' => 'gojuon.mori',
                'firstname' => '優',
                'lastname' => '森',
                'firstnamephonetic' => 'ゆう',
                'lastnamephonetic' => 'もり',
            ],
            [
                'username' => 'gojuon.yamada',
                'firstname' => '太郎',
                'lastname' => '山田',
                'firstnamephonetic' => 'たろう',
                'lastnamephonetic' => 'やまだ',
            ],
            [
                'username' => 'gojuon.watanabe',
                'firstname' => 'Naoko',
                'lastname' => '渡辺',
                'firstnamephonetic' => 'Naoko',
                'lastnamephonetic' => 'Watanabe',
            ],
            [
                'username' => 'gojuon.blank',
                'firstname' => '未設定',
                'lastname' => '読み',
                'firstnamephonetic' => '',
                'lastnamephonetic' => '',
            ],
        ];
    }

    /**
     * Create or update the fixture course and enrol all fixture users.
     *
     * @param string $shortname Course shortname to create or reuse.
     * @param string $fullname Course fullname to create or apply.
     * @param bool $configureplugin Whether to enable this plugin's demo settings.
     * @return array{course: \stdClass, users: array<string, \stdClass>, createdusers: int, updatedusers: int}
     */
    public static function seed(
        string $shortname = self::DEFAULT_SHORTNAME,
        string $fullname = self::DEFAULT_FULLNAME,
        bool $configureplugin = true
    ): array {
        global $CFG;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->libdir . '/enrollib.php');

        $course = self::ensure_course($shortname, $fullname);
        $manual = self::manual_enrolment_instance($course);
        $studentroleid = self::student_role_id();
        $users = [];
        $created = 0;
        $updated = 0;

        foreach (self::fixtures() as $fixture) {
            [$user, $wascreated] = self::ensure_user($fixture);
            $users[$fixture['username']] = $user;
            $created += $wascreated ? 1 : 0;
            $updated += $wascreated ? 0 : 1;
            self::ensure_enrolled($manual, (int) $user->id, $studentroleid);
        }

        if ($configureplugin) {
            set_config('enabled', 1, 'local_gojuon');
            set_config('hidelatin', 1, 'local_gojuon');
        }

        return [
            'course' => $course,
            'users' => $users,
            'createdusers' => $created,
            'updatedusers' => $updated,
        ];
    }

    /**
     * Create or update the target fixture course.
     *
     * @param string $shortname
     * @param string $fullname
     * @return \stdClass
     */
    private static function ensure_course(string $shortname, string $fullname): \stdClass {
        global $DB;

        if ($course = $DB->get_record('course', ['shortname' => $shortname])) {
            if ($course->fullname !== $fullname) {
                update_course((object) [
                    'id' => $course->id,
                    'fullname' => $fullname,
                ]);
                $course = $DB->get_record('course', ['id' => $course->id], '*', MUST_EXIST);
            }
            return $course;
        }

        return create_course((object) [
            'fullname' => $fullname,
            'shortname' => $shortname,
            'category' => 1,
            'visible' => 1,
        ]);
    }

    /**
     * Create or update one fixture user.
     *
     * @param array<string, string> $fixture
     * @return array{0: \stdClass, 1: bool}
     */
    private static function ensure_user(array $fixture): array {
        global $CFG, $DB;

        $record = (object) [
            'username' => $fixture['username'],
            'auth' => 'manual',
            'confirmed' => 1,
            'suspended' => 0,
            'mnethostid' => $CFG->mnet_localhost_id,
            'firstname' => $fixture['firstname'],
            'lastname' => $fixture['lastname'],
            'firstnamephonetic' => $fixture['firstnamephonetic'],
            'lastnamephonetic' => $fixture['lastnamephonetic'],
            'email' => $fixture['username'] . '@example.invalid',
        ];

        if ($user = $DB->get_record('user', [
                'username' => $fixture['username'],
                'mnethostid' => $CFG->mnet_localhost_id,
                'deleted' => 0,
            ])) {
            $record->id = $user->id;
            user_update_user($record, false, false);
            return [$DB->get_record('user', ['id' => $user->id], '*', MUST_EXIST), false];
        }

        $record->password = generate_password(20);
        $userid = user_create_user($record, false, false);
        return [$DB->get_record('user', ['id' => $userid], '*', MUST_EXIST), true];
    }

    /**
     * Get or create the manual enrolment instance for the fixture course.
     *
     * @param \stdClass $course
     * @return \stdClass
     */
    private static function manual_enrolment_instance(\stdClass $course): \stdClass {
        global $DB;

        $manual = enrol_get_plugin('manual');
        if (!$manual) {
            throw new \moodle_exception('Manual enrolment plugin is not available.');
        }

        foreach (enrol_get_instances($course->id, false) as $instance) {
            if ($instance->enrol === 'manual') {
                return $instance;
            }
        }

        $instanceid = $manual->add_instance($course);
        return $DB->get_record('enrol', ['id' => $instanceid], '*', MUST_EXIST);
    }

    /**
     * Enrol the user as a student if not already enrolled.
     *
     * @param \stdClass $instance Manual enrolment instance.
     * @param int $userid
     * @param int $roleid
     */
    private static function ensure_enrolled(\stdClass $instance, int $userid, int $roleid): void {
        $context = context_course::instance($instance->courseid);
        if (is_enrolled($context, $userid, '', true)) {
            return;
        }

        enrol_get_plugin('manual')->enrol_user($instance, $userid, $roleid);
    }

    /**
     * Resolve the site's student role id.
     *
     * @return int
     */
    private static function student_role_id(): int {
        global $DB;

        return (int) $DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);
    }
}
