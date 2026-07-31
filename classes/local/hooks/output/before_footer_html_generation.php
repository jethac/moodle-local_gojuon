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

namespace local_gojuon\local\hooks\output;

use local_gojuon\kana;
use local_gojuon\phonetic;
use local_gojuon\table\participants;

/**
 * Injects the gojūon index bar onto the course participants page.
 *
 * The bar markup is rendered from a Mustache template and the behaviour is
 * an AMD module — no inline JavaScript or CSS — so the plugin honours the
 * Moodle output guidelines and strict-CSP sites.
 *
 * @package   local_gojuon
 * @copyright 2026 Jetha Chan
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class before_footer_html_generation {

    /**
     * Add the bar + module when we're on the course participants page and
     * the viewer may see at least one phonetic name field.
     *
     * The whole body is guarded: a cosmetic index bar must never break the
     * participants page, so any failure degrades to no bar.
     *
     * @param \core\hook\output\before_footer_html_generation $hook
     */
    public static function callback(\core\hook\output\before_footer_html_generation $hook): void {
        global $PAGE, $OUTPUT;

        try {
            // Never emit during the dynamic-table webservice, AJAX, or CLI.
            if (AJAX_SCRIPT || CLI_SCRIPT || (defined('WS_SERVER') && WS_SERVER)) {
                return;
            }
            if (!get_config('local_gojuon', 'enabled')) {
                return;
            }
            if (!self::is_participants_page()) {
                return;
            }
            $context = $PAGE->context;
            if (!$context instanceof \context_course) {
                return;
            }
            if (!phonetic::viewer_can_see($context)) {
                return;
            }

            if (get_config('local_gojuon', 'hidelatin')) {
                $PAGE->add_body_class('local-gojuon-hidelatin');
            }

            // Chips in display order: all, kana rows, Latin A–Z, other.
            $chips = [['key' => kana::ALL, 'label' => get_string('all', 'local_gojuon')]];
            foreach (kana::rows() as $key => $row) {
                $chips[] = ['key' => $key, 'label' => $row['label']];
            }
            $chips[] = ['key' => kana::OTHER, 'label' => get_string('other', 'local_gojuon')];

            // One bar per visible axis. aria-pressed carries string booleans
            // so the server-rendered initial ARIA state is valid.
            $bars = [];
            foreach (participants::AXES as $filter => $column) {
                if (!phonetic::viewer_can_see_field($context, $column)) {
                    continue;
                }
                $barchips = [];
                foreach ($chips as $chip) {
                    $isall = $chip['key'] === kana::ALL;
                    $barchips[] = [
                        'key' => $chip['key'],
                        'label' => $chip['label'],
                        'filter' => $filter,
                        'active' => $isall,
                        'ariapressed' => $isall ? 'true' : 'false',
                    ];
                }
                $bars[] = [
                    'filter' => $filter,
                    'label' => $filter === 'kanalast' ? get_string('lastname') : get_string('firstname'),
                    'chips' => $barchips,
                ];
            }
            if (empty($bars)) {
                return;
            }

            $hook->add_html($OUTPUT->render_from_template('local_gojuon/bar', [
                'arialabel' => get_string('barlabel', 'local_gojuon'),
                'bars' => $bars,
            ]));

            $PAGE->requires->js_call_amd('local_gojuon/gojuon', 'init', [[
                'tablecomponent' => 'local_gojuon',
            ]]);
        } catch (\Throwable $e) {
            debugging('local_gojuon: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Whether the current page is the course participants list.
     *
     * @return bool
     */
    protected static function is_participants_page(): bool {
        global $PAGE;
        if (!$PAGE->has_set_url()) {
            return false;
        }
        return $PAGE->url->compare(new \moodle_url('/user/index.php'), URL_MATCH_BASE);
    }
}
