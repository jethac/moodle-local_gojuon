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

/**
 * Create Japanese participant fixtures for reviewers and local testing.
 *
 * @package   local_gojuon
 * @copyright 2026 Jetha Chan
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

$dir = __DIR__;
while ($dir !== dirname($dir)) {
    $config = $dir . '/config.php';
    if (is_readable($config)) {
        require_once($config);
        break;
    }
    $dir = dirname($dir);
}

if (!isset($CFG)) {
    fwrite(STDERR, "Could not find Moodle config.php above this script.\n");
    exit(1);
}

require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params([
    'help' => false,
    'shortname' => \local_gojuon\local\japanese_participant_seed::DEFAULT_SHORTNAME,
    'fullname' => \local_gojuon\local\japanese_participant_seed::DEFAULT_FULLNAME,
    'no-plugin-config' => false,
], [
    'h' => 'help',
]);

if ($unrecognized) {
    $unrecognized = implode(PHP_EOL . '  ', $unrecognized);
    cli_error("Unknown option(s):" . PHP_EOL . "  " . $unrecognized);
}

if ($options['help']) {
    cli_writeln("Create Japanese participant fixtures for local_gojuon review/testing.");
    cli_writeln("");
    cli_writeln("Options:");
    cli_writeln("  --shortname=SHORTNAME      Course shortname to create or reuse.");
    cli_writeln("  --fullname=FULLNAME        Course fullname to create or apply.");
    cli_writeln("  --no-plugin-config         Do not enable local_gojuon demo settings.");
    cli_writeln("  -h, --help                 Show this help.");
    exit(0);
}

$result = \local_gojuon\local\japanese_participant_seed::seed(
    $options['shortname'],
    $options['fullname'],
    !$options['no-plugin-config']
);

cli_writeln("Japanese participant fixtures ready.");
cli_writeln("Course: {$result['course']->fullname} ({$result['course']->shortname}), id {$result['course']->id}");
cli_writeln("Users: " . count($result['users']) . " total, {$result['createdusers']} created, {$result['updatedusers']} updated");
if (!$options['no-plugin-config']) {
    cli_writeln("local_gojuon settings: enabled=1, hidelatin=1");
}
