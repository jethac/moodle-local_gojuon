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

use context;

/**
 * Visibility rules for the phonetic name fields.
 *
 * The kana bar filters on lastnamephonetic / firstnamephonetic. Filtering
 * on a field the viewer cannot read is an information-disclosure risk: even
 * without seeing the value, a viewer could binary-search a hidden reading
 * by watching the result count change across ~46 chips per axis. So both
 * the bar and the server-side filter are gated on whether the phonetic
 * field actually appears in the name format that applies to this viewer —
 * you can only filter by what you can already see.
 *
 * @package   local_gojuon
 * @copyright 2026 Jetha Chan
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class phonetic {
    /**
     * The effective full-name format string for the current viewer in a
     * context: the privileged alternative format if the viewer may see it,
     * otherwise the default display format, resolving the 'language'
     * sentinel to the language pack's format.
     *
     * @param context $context
     * @return string
     */
    protected static function effective_format(context $context): string {
        global $CFG;
        $format = $CFG->fullnamedisplay ?? 'language';
        if (
            !empty($CFG->alternativefullnameformat)
            && $CFG->alternativefullnameformat !== 'language'
            && has_capability('moodle/site:viewfullnames', $context)
        ) {
            $format = $CFG->alternativefullnameformat;
        }
        if ($format === 'language' || trim($format) === '') {
            $format = get_string('fullnamedisplay');
        }
        return $format;
    }

    /**
     * Whether the given phonetic field is visible to the viewer here.
     *
     * @param context $context
     * @param string $field lastnamephonetic or firstnamephonetic
     * @return bool
     */
    public static function viewer_can_see_field(context $context, string $field): bool {
        return strpos(self::effective_format($context), $field) !== false;
    }

    /**
     * Whether either phonetic field is visible to the viewer here (used to
     * decide whether to render the bar at all).
     *
     * @param context $context
     * @return bool
     */
    public static function viewer_can_see(context $context): bool {
        $format = self::effective_format($context);
        return strpos($format, 'lastnamephonetic') !== false
            || strpos($format, 'firstnamephonetic') !== false;
    }
}
