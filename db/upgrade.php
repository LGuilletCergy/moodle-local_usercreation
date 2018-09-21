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
 * Initially developped for :
 * Université de Cergy-Pontoise
 * 33, boulevard du Port
 * 95011 Cergy-Pontoise cedex
 * FRANCE
 *
 * Create the accounts of students, teachers and staff based on xml files and fill tables used for statistics.
 *
 * @package   local_usercreation
 * @copyright 2017 Laurent Guillet <laurent.guillet@u-cergy.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * File : upgrade.php
 * Upgrade file
 */

defined('MOODLE_INTERNAL') || die;

function xmldb_local_usercreation_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 2018041300) {

        // Define field fixed to be added to local_usercreation_twins.
        $table = new xmldb_table('local_usercreation_twins');
        $field = new xmldb_field('fixed', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'username');

        // Conditionally launch add field fixed.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Usercreation savepoint reached.
        upgrade_plugin_savepoint(true, 2018041300, 'local', 'usercreation');
    }
	
	if ($oldversion < 2018082901) {

        // Define field stillexists to be added to local_usercreation_vet.
        $table = new xmldb_table('local_usercreation_vet');
        $field = new xmldb_field('stillexists', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'vetcode');

        // Conditionally launch add field stillexists.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		
		// Define field stillexists to be added to local_usercreation_ufr.
        $table = new xmldb_table('local_usercreation_ufr');
        $field = new xmldb_field('stillexists', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'ufrcode');

        // Conditionally launch add field stillexists.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
		}

        // Usercreation savepoint reached.
        upgrade_plugin_savepoint(true, 2018082901, 'local', 'usercreation');
    }
	
	if ($oldversion < 2018090600) {

        // Define field checkedon to be added to local_usercreation_twins.
        $table = new xmldb_table('local_usercreation_twins');
        $field = new xmldb_field('checkedon', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'fixed');

        // Conditionally launch add field checkedon.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Usercreation savepoint reached.
        upgrade_plugin_savepoint(true, 2018090600, 'local', 'usercreation');
    }


    return true;
}