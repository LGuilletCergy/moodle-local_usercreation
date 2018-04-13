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
 *
 * @package   local_usercreation
 * @copyright 2018 Brice Errandonea <brice.errandonea@u-cergy.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * File : lib.php
 * Functions library
 */

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->dirroot/course/lib.php");
require_once($CFG->dirroot.'/group/lib.php');

/**
 * Adds the plugin to the Course administration menu.
 * @param settings_navigation $nav
 * @param context $context
 */
function local_usercreation_extend_navigation(global_navigation $nav) {

    global $DB, $USER;

    if ($USER && isset($USER->username)) {

        $hastwin = $DB->record_exists('local_usercreation_twins', array('username' => $USER->username));

        if ($hastwin) {

            header('Location: https://sefiap.u-cergy.fr');
        }
    }
}
