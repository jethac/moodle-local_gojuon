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

use core_table\local\filter\string_filter;

/**
 * Participants filterset extended with the optional gojūon row filter.
 *
 * @package   local_gojuon
 * @copyright 2026 Jetha Chan
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class participants_filterset extends \core_user\table\participants_filterset {

    /**
     * Optional filters: everything core allows, plus the two kana axes.
     *
     * @return array
     */
    public function get_optional_filters(): array {
        $filters = parent::get_optional_filters();
        $filters['kanalast'] = string_filter::class;
        $filters['kanafirst'] = string_filter::class;
        return $filters;
    }
}
