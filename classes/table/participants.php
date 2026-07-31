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

namespace local_gojuon\table;

use local_gojuon\kana;
use local_gojuon\phonetic;

/**
 * Participants table with gojūon-row filtering on the phonetic name fields.
 *
 * A drop-in dynamic-table replacement for core's participants table: the
 * AMD module re-points the table's component at this class, so every core
 * filter, sort, and capability check keeps working (all inherited — this
 * class overrides neither set_filterset(), get_context(), nor
 * has_capability(), so access to the participant list is enforced exactly
 * as core enforces it) while two optional filters (kanalast, kanafirst)
 * narrow by the leading kana of the matching phonetic field.
 *
 * @package   local_gojuon
 * @copyright 2026 Jetha Chan
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class participants extends \core_user\table\participants {

    /** @var array<string, string> Filter name => phonetic column. */
    const AXES = [
        'kanalast' => 'lastnamephonetic',
        'kanafirst' => 'firstnamephonetic',
    ];

    /**
     * Append the kana-row conditions to the table-level WHERE, which core's
     * query_db() feeds into participants_search for both count and rows.
     *
     * Honoured only for a field this viewer may actually read (see
     * {@see phonetic}) and only when the plugin is enabled, so the filter
     * can never be an oracle for a hidden reading. The kana condition is
     * appended to the inner, roster-restricted subquery with an
     * unconditional AND (the same extension-point behaviour as core's own
     * initials bars); a filterset-level NONE/ALL jointype therefore applies
     * to core filters but not to this one — documented, and covered by
     * tests.
     *
     * @return array [$where, $params]
     */
    public function get_sql_where() {
        [$where, $params] = parent::get_sql_where();

        if (!get_config('local_gojuon', 'enabled')) {
            return [$where, $params];
        }

        foreach (self::AXES as $filter => $column) {
            if (!phonetic::viewer_can_see_field($this->context, $column)) {
                continue;
            }
            $row = $this->get_kanarow($filter);
            if ($row === null) {
                continue;
            }
            [$cond, $cparams] = $this->kana_condition($column, $row);
            if ($cond === null) {
                continue;
            }
            $where = $where !== '' ? "($where) AND $cond" : $cond;
            $params += $cparams;
        }
        return [$where, $params];
    }

    /**
     * Build the SQL condition for one kana row against one phonetic column.
     *
     * NOTE: this WHERE runs inside a subquery whose only table is {user}
     * (aliased udistinct), so the column is unqualified — the same
     * convention core's initials-bar conditions rely on. Matching uses a
     * case- and accent-sensitive prefix LIKE per candidate character: it is
     * index-usable (unlike SUBSTR(col,1,1)) and does not depend on the
     * site's default collation (が never collates equal to か). The `other`
     * bucket is the true complement — empty reading, or a leading character
     * in no row — so every participant lands in exactly one bucket and no
     * one silently vanishes.
     *
     * @param string $column lastnamephonetic or firstnamephonetic
     * @param string $row a row key from kana::rows(), or kana::OTHER
     * @return array [?string $condition, array $params]
     */
    protected function kana_condition(string $column, string $row): array {
        global $DB;

        if ($row === kana::OTHER) {
            $isempty = $DB->sql_isempty('user', $column, true, true);
            [$anyrow, $params] = $this->any_row_like($column);
            return ["($isempty OR NOT ($anyrow))", $params];
        }

        $rows = kana::rows();
        if (empty($rows[$row]['chars'])) {
            return [null, []];
        }
        return $this->chars_like($column, $rows[$row]['chars']);
    }

    /**
     * OR of prefix-LIKE conditions for a set of leading characters.
     *
     * @param string $column
     * @param string[] $chars
     * @return array [string $condition, array $params]
     */
    protected function chars_like(string $column, array $chars): array {
        global $DB;
        static $i = 0;
        $conds = [];
        $params = [];
        foreach ($chars as $char) {
            $key = 'gjn' . ($i++);
            // Case- and accent-sensitive so kana variants never conflate.
            $conds[] = $DB->sql_like($column, ":$key", true, true);
            $params[$key] = $DB->sql_like_escape($char) . '%';
        }
        return ['(' . implode(' OR ', $conds) . ')', $params];
    }

    /**
     * Condition matching a leading character in ANY row (for the complement).
     *
     * @param string $column
     * @return array [string $condition, array $params]
     */
    protected function any_row_like(string $column): array {
        $all = [];
        foreach (kana::rows() as $row) {
            foreach ($row['chars'] as $char) {
                $all[$char] = true;
            }
        }
        return $this->chars_like($column, array_keys($all));
    }

    /**
     * Read a validated kana filter value from the filterset, if present.
     *
     * Rejects (rather than coerces) unknown values and any join type other
     * than ANY — the only shape the bar produces.
     *
     * @param string $filtername kanalast or kanafirst
     * @return string|null a valid row key, kana::OTHER, or null
     */
    protected function get_kanarow(string $filtername): ?string {
        if (!$this->filterset || !$this->filterset->has_filter($filtername)) {
            return null;
        }
        $filter = $this->filterset->get_filter($filtername);
        if ($filter->get_join_type() !== $filter::JOINTYPE_ANY) {
            throw new \invalid_parameter_exception("The $filtername filter only supports JOINTYPE_ANY.");
        }
        $values = $filter->get_filter_values();
        if (empty($values)) {
            return null;
        }
        if (count($values) > 1) {
            throw new \invalid_parameter_exception("The $filtername filter accepts a single value.");
        }
        $value = (string) reset($values);
        if ($value === kana::OTHER || kana::is_row($value)) {
            return $value;
        }
        throw new \invalid_parameter_exception("Unknown kana row '$value'.");
    }
}
