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

/**
 * Injects the gojūon index bar onto the course participants page.
 *
 * @package   local_gojuon
 * @copyright 2026 Jetha Chan
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class before_footer_html_generation {

    /**
     * Add the bar markup + driver script when we're on user/index.php.
     *
     * @param \core\hook\output\before_footer_html_generation $hook
     */
    public static function callback(\core\hook\output\before_footer_html_generation $hook): void {
        global $PAGE;

        try {
            if (!$PAGE->has_set_url()) {
                return;
            }
            if (strpos($PAGE->url->get_path(), '/user/index.php') === false) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        $rows = [];
        foreach (kana::rows() as $key => $row) {
            $rows[] = ['key' => $key, 'label' => $row['label']];
        }
        $config = [
            'rows' => $rows,
            'other' => ['key' => kana::OTHER, 'label' => get_string('other', 'local_gojuon')],
            'all' => ['key' => 'all', 'label' => get_string('all', 'local_gojuon')],
            'arialabel' => get_string('barlabel', 'local_gojuon'),
            'bars' => [
                // Core's own strings, so the labels always match the
                // participants column headers in the viewer's language.
                ['filter' => 'kanalast', 'label' => get_string('lastname')],
                ['filter' => 'kanafirst', 'label' => get_string('firstname')],
            ],
        ];

        $html = '';
        if (get_config('local_gojuon', 'hidelatin')) {
            $html .= '<style>.initialbar, .initialbars { display: none !important; }</style>';
        }

        $html .= \html_writer::div('', '', [
            'id' => 'local-gojuon-config',
            'data-config' => json_encode($config),
            'hidden' => 'hidden',
        ]);

        $html .= <<<'JS'
<script>
(function() {
    var cfgel = document.getElementById('local-gojuon-config');
    if (!cfgel) { return; }
    var cfg = JSON.parse(cfgel.dataset.config);
    var findRoot = function() {
        return document.querySelector('[data-table-component][data-table-handler="participants"]');
    };
    var root = findRoot();
    if (!root) { return; }

    // Re-point the dynamic table at the gojuon-aware subclass.
    root.dataset.tableComponent = 'local_gojuon';

    var wrap = document.createElement('div');
    wrap.id = 'local-gojuon-bar';
    wrap.className = 'mb-3';
    wrap.setAttribute('role', 'navigation');
    wrap.setAttribute('aria-label', cfg.arialabel);

    var chips = [cfg.all].concat(cfg.rows, [cfg.other]);
    cfg.bars.forEach(function(barcfg) {
        var barrow = document.createElement('div');
        barrow.className = 'd-flex flex-wrap align-items-center mb-1';
        barrow.style.gap = '4px';
        barrow.dataset.filter = barcfg.filter;
        var lab = document.createElement('span');
        lab.className = 'me-2 text-muted';
        lab.textContent = barcfg.label;
        barrow.appendChild(lab);
        chips.forEach(function(chip) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'btn btn-secondary btn-sm local-gojuon-chip';
            b.dataset.row = chip.key;
            b.dataset.filter = barcfg.filter;
            b.textContent = chip.label;
            if (chip.key === 'all') {
                b.classList.add('active');
                b.setAttribute('aria-pressed', 'true');
            }
            barrow.appendChild(b);
        });
        wrap.appendChild(barrow);
    });
    root.insertAdjacentElement('beforebegin', wrap);

    wrap.addEventListener('click', function(e) {
        var btn = e.target.closest('.local-gojuon-chip');
        if (!btn) { return; }
        var row = btn.dataset.row;
        var filtername = btn.dataset.filter;
        require(['core_table/dynamic'], function(Dynamic) {
            var current = findRoot();
            if (!current) { return; }
            current.dataset.tableComponent = 'local_gojuon';
            var filters = Dynamic.getFilters(current);
            filters.filters = filters.filters || {};
            if (row === 'all') {
                delete filters.filters[filtername];
            } else {
                filters.filters[filtername] = {name: filtername, jointype: 1, values: [row]};
            }
            Dynamic.setFilters(current, filters).then(function() {
                wrap.querySelectorAll('.local-gojuon-chip[data-filter="' + filtername + '"]').forEach(function(c) {
                    var on = c.dataset.row === row;
                    c.classList.toggle('active', on);
                    c.setAttribute('aria-pressed', on ? 'true' : 'false');
                });
                return null;
            }).catch(function() { return null; });
        });
    });
})();
</script>
JS;

        $hook->add_html($html);
    }
}
