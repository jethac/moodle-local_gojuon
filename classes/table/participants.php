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
        global $DB;
        [$where, $params] = parent::get_sql_where();

        $row = $this->get_kanarow();
        if ($row === null) {
            return [$where, $params];
        }

        // NOTE: this WHERE is injected into a subquery whose only table is
        // {user} (aliased udistinct), so columns must be unqualified — the
        // same convention core's initials-bar conditions rely on.
        $surname = $DB->sql_substr("COALESCE(NULLIF(lastnamephonetic, ''), firstnamephonetic)", 1, 1);

        if ($row === kana::OTHER) {
            $cond = "(COALESCE(lastnamephonetic, '') = '' AND COALESCE(firstnamephonetic, '') = '')";
            $cparams = [];
        } else if (isset(kana::ROWS[$row])) {
            [$insql, $cparams] = $DB->get_in_or_equal(kana::ROWS[$row]['chars'], SQL_PARAMS_NAMED, 'gojuon');
            $cond = "($surname $insql)";
        } else {
            return [$where, $params];
        }

        $where = $where !== '' ? "($where) AND $cond" : $cond;
        return [$where, array_merge($params, $cparams)];
    }

    /**
     * Read the kanarow filter value from the filterset, if present.
     *
     * @return string|null
     */
    protected function get_kanarow(): ?string {
        if (!$this->filterset || !$this->filterset->has_filter('kanarow')) {
            return null;
        }
        $values = $this->filterset->get_filter('kanarow')->get_filter_values();
        if (empty($values)) {
            return null;
        }
        return clean_param((string) reset($values), PARAM_ALPHA);
    }
}
