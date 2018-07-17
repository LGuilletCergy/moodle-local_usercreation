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
 * File : lang/en/local_usercreation.php
 * English language file
 */

$string['createusers'] = "Create all users accounts";
$string['pluginname'] = "Creation of users accounts";
$string['year'] = "Year to use when creating students accounts";
$string['youretwin'] = '<br>Another user has the same user than you.'
        . ' As such, we are unable to distinguish between the two of you.<br>'
        . '<br>'
        . 'Please contact the DISI to solve this problem.';
$string['privacy:metadata:local_usercreation_type'] = 'Table storing the type of teacher of the user.';
$string['privacy:metadata:local_usercreation_type:userid'] = 'User ID';
$string['privacy:metadata:local_usercreation_type:typeteacher'] = 'Teacher type of the user.';
$string['privacy:metadata:local_usercreation_vet'] = 'Table storing the VET of the user.';
$string['privacy:metadata:local_usercreation_vet:studentid'] = 'Student ID';
$string['privacy:metadata:local_usercreation_vet:vetname'] = 'Name of the VET.';
$string['privacy:metadata:local_usercreation_vet:vetcode'] = 'Code of the VET.';
$string['privacy:metadata:local_usercreation_ufr'] = 'Table storing the UFR of the user.';
$string['privacy:metadata:local_usercreation_ufr:userid'] = 'User ID.';
$string['privacy:metadata:local_usercreation_ufr:ufrcode'] = 'Code of the UFR.';