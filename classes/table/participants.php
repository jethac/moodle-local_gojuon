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

/**
 * Participants table with gojūon-row filtering on the phonetic surname.
 *
 * A drop-in dynamic-table replacement for core's participants table: the
 * page JS re-points the table's component/handler at this class, so all
 * core filters keep working while an optional `kanarow` filter narrows by
 * the leading character of lastnamephonetic (falling back to
 * firstnamephonetic when the surname reading is empty).
 *
 * @package   local_gojuon
 * @copyright 2026 Jetha Chan
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class participants extends \core_user\table\participants {

    /**
     * Append the kana-row condition to the table-level WHERE, which core's
     * query_db() feeds into participants_search for both count and rows.
     *
     * @return array [$where, $params]
     */
    public function get_sql_where() {
        [$where, $params] = parent::get_sql_where();

        // Two independent axes, mirroring core's last/first initials bars.
        foreach (['kanalast' => 'lastnamephonetic', 'kanafirst' => 'firstnamephonetic'] as $filter => $column) {
            $row = $this->get_kanarow($filter);
            if ($row === null) {
                continue;
            }
            [$cond, $cparams] = $this->kana_condition($column, $row);
            if ($cond === null) {
                continue;
            }
            $where = $where !== '' ? "($where) AND $cond" : $cond;
            $params = array_merge($params, $cparams);
        }
        return [$where, $params];
    }

    /**
     * Build the SQL condition for one kana row against one phonetic column.
     *
     * NOTE: this WHERE is injected into a subquery whose only table is
     * {user} (aliased udistinct), so columns must be unqualified — the
     * same convention core's initials-bar conditions rely on.
     *
     * @param string $column lastnamephonetic or firstnamephonetic
     * @param string $row row key from kana::ROWS, or kana::OTHER
     * @return array [?string $condition, array $params]
     */
    protected function kana_condition(string $column, string $row): array {
        global $DB;
        if ($row === kana::OTHER) {
            return ["(COALESCE($column, '') = '')", []];
        }
        $rows = kana::rows();
        if (!isset($rows[$row])) {
            return [null, []];
        }
        $initial = $DB->sql_substr($column, 1, 1);
        [$insql, $params] = $DB->get_in_or_equal($rows[$row]['chars'], SQL_PARAMS_NAMED, 'gojuon' . $column);
        return ["($initial $insql)", $params];
    }

    /**
     * Read a kana filter value from the filterset, if present.
     *
     * @param string $filtername kanalast or kanafirst
     * @return string|null
     */
    protected function get_kanarow(string $filtername): ?string {
        if (!$this->filterset || !$this->filterset->has_filter($filtername)) {
            return null;
        }
        $values = $this->filterset->get_filter($filtername)->get_filter_values();
        if (empty($values)) {
            return null;
        }
        return clean_param((string) reset($values), PARAM_ALPHA);
    }
}
