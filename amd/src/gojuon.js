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

/**
 * Wires the server-rendered gojūon index bar to the participants dynamic table.
 *
 * The bar markup itself is rendered by the PHP hook (template local_gojuon/bar);
 * this module only re-points the participants dynamic table at the plugin's
 * table subclass and drives the per-column filters when a chip is clicked.
 *
 * @module     local_gojuon/gojuon
 * @copyright  2026 Jetha Chan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Dynamic from 'core_table/dynamic';
import Log from 'core/log';

const SELECTORS = {
    bar: '.local-gojuon-bar',
    table: '[data-table-component][data-table-handler="participants"]',
    chip: '.local-gojuon-chip',
};

/**
 * Recover from any failure: drop the bar so it cannot mislead the user, and log.
 *
 * @param {?Element} bar The bar element, if it was found.
 * @param {*} err The thrown value.
 */
const fail = (bar, err) => {
    if (bar) {
        bar.remove();
    }
    Log.error(`local_gojuon: ${err && err.message ? err.message : err}`);
};

/**
 * Reflect the active chip within a single bar row: exactly one chip per
 * data-filter carries the active state.
 *
 * @param {String} filter The filter name whose row was toggled.
 * @param {String} row The row key that is now active.
 */
const updateActive = (filter, row) => {
    const bar = document.querySelector(SELECTORS.bar);
    if (!bar) {
        return;
    }
    const chips = bar.querySelectorAll(`${SELECTORS.chip}[data-filter="${CSS.escape(filter)}"]`);
    chips.forEach((chip) => {
        const on = chip.dataset.row === row;
        chip.classList.toggle('active', on);
        chip.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
};

/**
 * Apply the filter behind a clicked chip to the participants table.
 *
 * The table node may be replaced by core on each refresh, so we re-find it and
 * re-assert its table component every time before calling setFilters.
 *
 * @param {Element} btn The clicked chip button.
 * @param {Object} config The init configuration.
 */
const handleChip = (btn, config) => {
    const row = btn.dataset.row;
    const filter = btn.dataset.filter;
    if (!row || !filter) {
        return;
    }

    const roots = document.querySelectorAll(SELECTORS.table);
    if (roots.length !== 1) {
        return;
    }
    const root = roots[0];
    root.dataset.tableComponent = config.tablecomponent;

    const filters = Dynamic.getFilters(root);
    filters.filters = filters.filters || {};
    if (row === 'all') {
        delete filters.filters[filter];
    } else {
        filters.filters[filter] = {name: filter, jointype: 1, values: [row]};
    }

    Dynamic.setFilters(root, filters).then(() => {
        updateActive(filter, row);
        return null;
    }).catch((err) => {
        Log.error(`local_gojuon: ${err && err.message ? err.message : err}`);
        return null;
    });
};

/**
 * Initialise the gojūon index bar.
 *
 * @param {Object} config Configuration supplied by the PHP hook.
 * @param {String} config.tablecomponent Dynamic-table component to re-point the participants table at.
 * @param {String} config.arialabel Localised nav aria-label (already rendered server-side).
 * @param {Object[]} config.bars Bar definitions ({filter, label}); already rendered server-side.
 * @param {Object[]} config.chips Chip definitions ({key, label}); already rendered server-side.
 */
export const init = (config) => {
    const bar = document.querySelector(SELECTORS.bar);
    try {
        // Applied here rather than via PHP add_body_class(), which cannot
        // run in the footer hook after the body tag is already emitted.
        if (config.hidelatin) {
            document.body.classList.add('local-gojuon-hidelatin');
        }
        if (!bar) {
            return;
        }

        // Idempotent: only wire the bar once even if init() runs twice.
        if (bar.dataset.gojuonInit === '1') {
            return;
        }

        // The bar is safe to wire only against exactly one participants table.
        const roots = document.querySelectorAll(SELECTORS.table);
        if (roots.length !== 1) {
            Log.warn(`local_gojuon: expected exactly one participants table, found ${roots.length}; not wiring.`);
            bar.style.display = 'none';
            return;
        }
        const root = roots[0];

        // Only take ownership of a table that is core's own or already ours.
        const owner = root.dataset.tableComponent;
        if (owner !== 'core_user' && owner !== config.tablecomponent) {
            Log.warn(`local_gojuon: participants table owned by "${owner}"; not wiring.`);
            bar.style.display = 'none';
            return;
        }
        root.dataset.tableComponent = config.tablecomponent;

        // Feature-probe the dynamic table API; leave the bar inert if missing.
        if (typeof Dynamic.getFilters !== 'function' || typeof Dynamic.setFilters !== 'function') {
            Log.warn('local_gojuon: core_table/dynamic lacks getFilters/setFilters; leaving bar inert.');
            bar.dataset.gojuonInit = '1';
            return;
        }

        bar.dataset.gojuonInit = '1';
        bar.addEventListener('click', (e) => {
            const btn = e.target.closest(SELECTORS.chip);
            if (!btn || !bar.contains(btn)) {
                return;
            }
            try {
                handleChip(btn, config);
            } catch (err) {
                fail(document.querySelector(SELECTORS.bar), err);
            }
        });
    } catch (err) {
        fail(bar, err);
    }
};
