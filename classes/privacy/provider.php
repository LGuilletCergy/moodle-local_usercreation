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
 * Create the accounts of students and teachers based on xml files and fill tables used for statistics.
 *
 * @package   local_usercreation
 * @copyright 2017 Laurent Guillet <laurent.guillet@u-cergy.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
  * File : provider.php
 * RGPD file
 */

namespace local_usercreation\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;

class provider implements
        // This plugin does store personal user data.
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider {

    public static function get_metadata(collection $collection) : collection {

        $collection->add_database_table(
            'local_usercreation_type',
            [
                'userid' => 'privacy:metadata:local_usercreation_type:userid',
                'typeteacher' => 'privacy:metadata:local_usercreation_type:typeteacher',

            ],
            'privacy:metadata:local_usercreation_type'
        );

        $collection->add_database_table(
            'local_usercreation_vet',
            [
                'studentid' => 'privacy:metadata:local_usercreation_vet:studentid',
                'vetname' => 'privacy:metadata:local_usercreation_vet:vetname',
                'vetcode' => 'privacy:metadata:local_usercreation_vet:vetcode',

            ],
            'privacy:metadata:local_usercreation_vet'
        );

        $collection->add_database_table(
            'local_usercreation_ufr',
            [
                'userid' => 'privacy:metadata:local_usercreation_ufr:userid',
                'ufrcode' => 'privacy:metadata:local_usercreation_ufr:ufrcode',

            ],
            'privacy:metadata:local_usercreation_ufr'
        );

        return $collection;
    }

    public static function get_contexts_for_userid(int $userid) : contextlist {

        $contextlist = new \core_privacy\local\request\contextlist();

        $sql = "SELECT c.id FROM {context} WHERE (contextlevel = :contextlevel)";

        $params = [
            'contextlevel' => CONTEXT_SYSTEM,
        ];

        $contextlist->add_from_sql($sql, $params);
    }

    public static function export_user_data(approved_contextlist $contextlist) {

        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {

            $sqltypeteacher = "SELECT * FROM {local_usercreation_type} WHERE userid = $userid";
            $resultstypeteacher = $DB->get_records_sql($sqltypeteacher);

            foreach ($resultstypeteacher as $result) {
                $data = (object) [
                    'userid' => $result->userid,
                    'typeteacher' => $result->typeteacher,
                ];

                \core_privacy\local\request\writer::with_context(
                        $context)->export_data([
                            get_string('pluginname', 'local_usercreation')], $data);
            }

            $sqlvet = "SELECT * FROM {local_usercreation_type} WHERE userid = $userid";
            $resultsvet = $DB->get_records_sql($sqlvet);

            foreach ($resultsvet as $result) {
                $data = (object) [
                    'studentid' => $result->studentid,
                    'vetname' => $result->vetname,
                    'vetcode' => $result->vetcode,
                ];

                \core_privacy\local\request\writer::with_context(
                        $context)->export_data([
                            get_string('pluginname', 'local_usercreation')], $data);
            }

            $sqlufr = "SELECT * FROM {local_usercreation_type} WHERE userid = $userid";
            $resultsufr = $DB->get_records_sql($sqlufr);

            foreach ($resultsufr as $result) {
                $data = (object) [
                    'userid' => $result->userid,
                    'ufrcode' => $result->ufrcode,
                ];

                \core_privacy\local\request\writer::with_context(
                        $context)->export_data([
                            get_string('pluginname', 'local_usercreation')], $data);
            }
        }
    }

    public static function delete_data_for_user(approved_contextlist $contextlist) {

        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        $sqltype = "SELECT * FROM {local_usercreation_type} WHERE userid = $userid";
        $DB->delete_records_sql($sqltype);

        $sqlvet = "SELECT * FROM {local_usercreation_vet} WHERE studentid = $userid";
        $DB->delete_records_sql($sqlvet);

        $sqlufr = "SELECT * FROM {local_usercreation_ufr} WHERE userid = $userid";
        $DB->delete_records_sql($sqlufr);
    }

    public static function delete_data_for_all_users_in_context(\context $context) {

        global $DB;

        if ($context->contextlevel != CONTEXT_SYSTEM) {

            return;
        }

        $sqltype = "SELECT * FROM {local_usercreation_type}";
        $DB->delete_records_sql($sqltype);

        $sqlvet = "SELECT * FROM {local_usercreation_vet}";
        $DB->delete_records_sql($sqlvet);

        $sqlufr = "SELECT * FROM {local_usercreation_ufr}";
        $DB->delete_records_sql($sqlufr);
    }
}
