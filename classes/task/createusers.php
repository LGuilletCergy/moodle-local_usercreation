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
 * File : createusers.php
 * Create all users account
 */

namespace local_usercreation\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot .'/course/lib.php');
require_once($CFG->libdir .'/filelib.php');
require_once($CFG->libdir .'/accesslib.php');
require_once($CFG->dirroot .'/user/lib.php');

class createusers extends \core\task\scheduled_task {

    public function get_name() {

        return get_string('createusers', 'local_usercreation');
    }

    public function execute() {

        $processstart = time();

        $this->preprocess();
//        $this->givestudentnumber();
//        $this->createstudents($processstart);
//        $this->createteachers($processstart);
//        $this->createstaff($processstart);
        $this->createstudentseisti($processstart);
        $this->postprocess();
    }

    private function preprocess() {

        global $DB;

        $listtwins = $DB->get_records('local_usercreation_twins', array());

        foreach ($listtwins as $twin) {

            $twin->fixed = 1;

            $DB->update_record('local_usercreation_twins', $twin);
        }

        $listufr = $DB->get_records('local_usercreation_ufr', array());

        foreach ($listufr as $ufr) {

            $ufr->stillexists = 0;

            $DB->update_record('local_usercreation_ufr', $ufr);
        }

        $listvet = $DB->get_records('local_usercreation_vet', array());

        foreach ($listvet as $vet) {

            $vet->stillexists = 0;

            $DB->update_record('local_usercreation_vet', $vet);
        }
    }

    // Si un enseignant ou personnel a un numéro étudiant, on le lui attribue comme numéro d'identification.

    private function givestudentnumber() {

        global $DB;

        // D'abord les enseignants.

        $xmldocteacher = new \DOMDocument();
        $xmldocteacher->load('/home/referentiel/DOKEOS_Enseignants_Affectations.xml');
        $xpathvarteacher = new \Domxpath($xmldocteacher);
        $listteachers = $xpathvarteacher->query('//Teacher');

        foreach ($listteachers as $teacher) {

            $username = $teacher->getAttribute('StaffUID');
            $staffnumber = $teacher->getAttribute('StaffCode');

            if ($teacher->hasAttribute('StaffETU')) {

                $etunumber = $teacher->getAttribute('StaffETU');

                if ($DB->record_exists('user', array('username' => $username, 'idnumber' => $staffnumber))) {

                    $record = $DB->get_record('user', array('username' => $username, 'idnumber' => $staffnumber));
                    $record->idnumber = $etunumber;
                    $DB->update_record('user', $record);
                }

                if (!$DB->record_exists('local_usercreation_phdstu', array('studentcode' => $etunumber))
                        && $username != "") {

                    $phdstudentrecord = new \stdClass();
                    $phdstudentrecord->username = $username;
                    $phdstudentrecord->studentcode = $etunumber;
                    $phdstudentrecord->staffcode = $staffnumber;

                    $DB->insert_record('local_usercreation_phdstu', $phdstudentrecord);
                } else if ($username != "") {

                    $phdstudentrecord = $DB->get_record('local_usercreation_phdstu', array('studentcode' => $etunumber));
                    $phdstudentrecord->username = $username;
                    $phdstudentrecord->studentcode = $etunumber;
                    $phdstudentrecord->staffcode = $staffnumber;

                    $DB->update_record('local_usercreation_phdstu', $phdstudentrecord);
                }
            } else {

                $DB->delete_records('local_usercreation_phdstu',
                        array('username' => $username, 'staffcode' => $staffnumber));
            }
        }

        // Ensuite les personnels.

        $xmldocstaff = new \DOMDocument();
        $xmldocstaff->load('/home/referentiel/sefiap_personnel_composante.xml');
        $xpathvarstaff = new \Domxpath($xmldocstaff);
        $liststaff = $xpathvarstaff->query('//Composante/Service/Individu');

        foreach ($liststaff as $staff) {

            $username = $staff->getAttribute('UID');
            $staffnumber = $staff->getAttribute('NO_INDIVIDU');

            if ($staff->hasAttribute('CODE_ETUDIANT')) {

                $etunumber = $staff->getAttribute('CODE_ETUDIANT');

                if ($DB->record_exists('user', array('username' => $username, 'idnumber' => $staffnumber))) {

                    $record = $DB->get_record('user', array('username' => $username, 'idnumber' => $staffnumber));
                    $record->idnumber = $etunumber;
                    $DB->update_record('user', $record);
                }

                if (!$DB->record_exists('local_usercreation_phdstu', array('studentcode' => $etunumber))
                        && $username != "") {

                    $phdstudentrecord = new \stdClass();
                    $phdstudentrecord->username = $username;
                    $phdstudentrecord->studentcode = $etunumber;
                    $phdstudentrecord->staffcode = $staffnumber;

                    $DB->insert_record('local_usercreation_phdstu', $phdstudentrecord);
                } else if ($username != "") {

                    $phdstudentrecord = $DB->get_record('local_usercreation_phdstu', array('studentcode' => $etunumber));
                    $phdstudentrecord->username = $username;
                    $phdstudentrecord->studentcode = $etunumber;
                    $phdstudentrecord->staffcode = $staffnumber;

                    $DB->update_record('local_usercreation_phdstu', $phdstudentrecord);
                }
            } else {

                $DB->delete_records('local_usercreation_phdstu',
                        array('username' => $username, 'staffcode' => $staffnumber));
            }
        }
    }

    private function createstudents($processstart) {

        $xmldoc = new \DOMDocument();
        $xmldoc->load('/home/referentiel/DOKEOS_Etudiants_Inscriptions.xml');
        $xpathvar = new \Domxpath($xmldoc);
        $liststudents = $xpathvar->query('//Student');

        foreach ($liststudents as $student) {

            $this->studentline($processstart, $student);
        }
    }

    private function studentline($processstart, $student) {

        $studentuid = $student->getAttribute('StudentUID');

        if ($student->hasAttribute('StudentUID')) {

            echo 'studentuid = '.$studentuid."\n";

            $email = $student->getAttribute('StudentEmail');
            $idnumber = $student->getAttribute('StudentETU');
            $lastname = $this->nameprocessor(strtolower($student->getAttribute('StudentName')));
            $firstname = $this->nameprocessor(strtolower($student->getAttribute('StudentFirstName')));
            $universityyears = $student->childNodes;

            foreach ($universityyears as $universityyear) {

                if ($universityyear->nodeType !== 1 ) {

                    continue;
                }

                // Si l'utilisateur est inscrit à l'université pendant l'année en cours, on traite son cas.
                $year = $universityyear->getAttribute('AnneeUniv');
                $configyear = get_config('local_usercreation', 'year');

                if ($year == $configyear) {

                    $this->processstudent($processstart, $studentuid, $idnumber, $firstname,
                            $lastname, $email, $universityyear);
                }
            }
        }
    }

    private function processstudent($processstart, $studentuid, $idnumber, $firstname,
            $lastname, $email, $universityyear) {

        global $DB;

        $user = $DB->get_record('user', array('username' => $studentuid));

        if ($user) {

            // Si il n'est pas étudiant de l'établissement, lui donner le rôle.
            // Peut se produire si l'étudiant se connecte avant la création de son compte.

            echo "$user->id, 'localstudent', $studentuid, $idnumber, $firstname, $lastname\n";

            $this->givesystemrole($user->id, 'localstudent', $studentuid, $idnumber, $firstname, $lastname);

            if ($user->idnumber == $idnumber || $user->idnumber == "") {

                // Même utilisateur.

                if ($DB->record_exists('local_usercreation_twins', array('username' => $studentuid))) {

                    $twin = $DB->get_record('local_usercreation_twins', array('username' => $studentuid));
                    $twin->fixed = 2;
                    $DB->update_record('local_usercreation_twins', $twin);
                }

                $this->updateuser($processstart, 'localstudent', $studentuid, $idnumber, $firstname, $lastname, $email);

                // Pour chaque inscription de l'utilisateur sur l'année actuelle.
                $this->yearenrolments($universityyear, $user);
            } else {

                // Doublon.

                if ($user->timemodified < $processstart) {

                    $this->updateuser($processstart, 'localstudent', $studentuid, $idnumber,
                            $firstname, $lastname, $email);
                } else {

                    if ($DB->record_exists('local_usercreation_twins', array('username' => $studentuid))) {

                        $twin = $DB->get_record('local_usercreation_twins', array('username' => $studentuid));

                        if ($twin->fixed == 1) {

                            $twin->fixed = 2;

                            $DB->update_record('local_usercreation_twins', $twin);
                            $this->updateuser($processstart, 'localstudent', $studentuid, $idnumber,
                                    $firstname, $lastname, $email);

                            // Pour chaque inscription de l'utilisateur sur l'année actuelle.
                            $this->yearenrolments($universityyear, $user);

                            return null;
                        }
                        $twin->fixed = 0;

                        $DB->update_record('local_usercreation_twins', $twin);
                    } else {

                        $twin = new \stdClass();
                        $twin->username = $studentuid;
                        $twin->fixed = 0;

                        $DB->insert_record('local_usercreation_twins', $twin);
                    }
                }

                return null;
            }
        } else {

            $user = $this->newuser($processstart, 'localstudent', $studentuid, $idnumber, $firstname, $lastname, $email);
        }

        // Pour chaque inscription de l'utilisateur sur l'année actuelle.
        $this->yearenrolments($universityyear, $user);
    }

    private function updateuser($processstart, $rolename, $username, $idnumber, $firstname, $lastname, $email) {

        global $DB;
        $userdata = $DB->get_record('user', array('username' => $username));
        $userdata->firstname = $firstname;
        $userdata->lastname = $lastname;
        $userdata->idnumber = $idnumber;
        $userdata->email = $email;
        $userdata->timemodified = $processstart;
        $DB->update_record('user', $userdata);

        $systemcontext = \context_system::instance();
        $role = $DB->get_record('role', array('shortname' => $rolename));
        $assigned = $DB->record_exists('role_assignments', array('roleid' => $role->id,
            'contextid' => $systemcontext->id, 'userid' => $userdata->id));

        if (!$assigned) {

            role_assign($role->id, $userdata->id, $systemcontext->id);
        }
    }

    private function newuser($processstart, $rolename, $studentuid, $idnumber, $firstname, $lastname, $email) {

        global $DB;
        $user = new \stdClass();
        $user->auth = 'cas';
        $user->confirmed = 1;
        $user->mnethostid = 1;
        $user->email = $email;
        $user->username = $studentuid;
        $user->password = '';
        $user->lastname = $lastname;
        $user->firstname = $firstname;
        $user->idnumber = $idnumber;
        $user->timecreated = $processstart;
        $user->timemodified = $processstart;
        $user->lang = 'fr';
        $user->id = $DB->insert_record('user', $user);
        $this->givesystemrole($user->id, $rolename, $studentuid, $idnumber, $firstname, $lastname);
        return $user;
    }

    private function givesystemrole($userid, $rolename, $studentuid, $idnumber, $firstname, $lastname) {

        global $DB;

        $role = $DB->get_record('role', array('shortname' => $rolename));
        $systemcontext = \context_system::instance();

        if (!$DB->record_exists('role_assignments',
                array('roleid' => $role->id, 'contextid' => $systemcontext->id, 'userid' => $userid))) {

            role_assign($role->id, $userid, $systemcontext->id);
            echo "Nouveau $rolename : $firstname $lastname ($studentuid, $idnumber)\n";
        }
    }

    private function yearenrolments($universityyear, $user) {

        global $DB;
        $yearenrolments = $universityyear->childNodes;
        $year = $universityyear->getAttribute('AnneeUniv');
        foreach ($yearenrolments as $yearenrolment) {

            if ($yearenrolment->nodeType !== 1 ) {

                continue;
            }
            $codeetape = $yearenrolment->getAttribute('CodeEtape');
            $codeetapeyear = "Y$year-$codeetape";
            $ufrcode = substr($codeetape, 0, 1);
            $ufrcodeyear = "Y$year-$ufrcode";

            // Si cette inscription de l'utilisateur à cette composante
            // n'est pas encore dans mdl_local_ufrstudent, on l'y ajoute.

            if (!$DB->record_exists('local_usercreation_ufr', array('userid' => $user->id, 'ufrcode' => $ufrcodeyear))) {

                $ufrrecord = new \stdClass();
                $ufrrecord->userid = $user->id;
                $ufrrecord->ufrcode = $ufrcodeyear;
                $DB->insert_record('local_usercreation_ufr', $ufrrecord);
            } else {

                $ufrrecord = $DB->get_record('local_usercreation_ufr', array('userid' => $user->id,
                    'ufrcode' => $ufrcodeyear));
                $ufrrecord->stillexists = 1;
                $DB->update_record('local_usercreation_ufr', $ufrrecord);
            }

            // Idem pour la VET.

            if (!$DB->record_exists('local_usercreation_vet', array('studentid' => $user->id,
                'vetcode' => $codeetapeyear))) {

                $studentvetrecord = new \stdClass();
                $studentvetrecord->studentid = $user->id;
                $studentvetrecord->vetcode = $codeetapeyear;
                $studentvetrecord->vetname = $yearenrolment->getAttribute('LibEtape');
                $DB->insert_record('local_usercreation_vet', $studentvetrecord);
            } else {

                $vetrecord = $DB->get_record('local_usercreation_vet', array('studentid' => $user->id,
                    'vetcode' => $codeetapeyear));
                $vetrecord->stillexists = 1;
                $DB->update_record('local_usercreation_vet', $vetrecord);
            }
        }
    }

    private function createteachers($processstart) {

        $xmldoc = new \DOMDocument();
        $xmldoc->load('/home/referentiel/DOKEOS_Enseignants_Affectations.xml');
        $xpathvar = new \Domxpath($xmldoc);
        $listteachers = $xpathvar->query('//Teacher');

        foreach ($listteachers as $teacher) {

            $this->teacherline($processstart, $teacher);
        }
    }

    private function teacherline($processstart, $teacher) {

        global $DB;

        $teacheruid = $teacher->getAttribute('StaffUID');

        if ($teacher->hasAttribute('StaffUID')) {

            $email = $teacher->getAttribute('StaffEmail');

            if ($teacher->hasAttribute('StaffETU')) {

                $idnumber = $teacher->getAttribute('StaffETU');
            } else {

                $idnumber = $teacher->getAttribute('StaffCode');
            }

            if ($DB->record_exists('local_usercreation_phdstu',
                    array('staffcode' => $teacher->getAttribute('StaffCode'), 'username' => $teacheruid))) {

                $idnumber = $DB->get_record('local_usercreation_phdstu',
                        array('staffcode' => $teacher->getAttribute('StaffCode'),
                            'username' => $teacheruid))->studentcode;
            }

            $lastname = $this->nameprocessor(strtolower($teacher->getAttribute('StaffCommonName')));
            $firstname = $this->nameprocessor(strtolower($teacher->getAttribute('StaffFirstName')));
            $affectations = $teacher->childNodes;

            foreach ($affectations as $affectation) {

                if ($affectation->nodeType !== 1 ) {

                    continue;
                }

                $position = $affectation->getAttribute('Position');

                if ($position != 'Sursitaire' && $position != "") {

                    echo 'position : '.$position."\n";
                    $this->processteacher($processstart, $teacheruid, $idnumber, $firstname, $lastname,
                            $email, $affectation, $teacher);
                }
            }
        }
    }

    private function processteacher($processstart, $teacheruid, $idnumber, $firstname, $lastname,
            $email, $affectation, $teacher) {

        global $DB;

        if ($DB->record_exists('user', array('username' => $teacheruid))) {

            $user = $DB->get_record('user', array('username' => $teacheruid));

            // Si il n'est pas enseignant de l'établissement, lui donner le rôle.
            // Peut se produire si l'enseignant se connecte avant la création de son compte.

            $this->givesystemrole($user->id, 'localteacher', $teacheruid, $idnumber, $firstname, $lastname);

            if ($user->idnumber == $idnumber || $user->idnumber == "") {

                // Même utilisateur.

                if ($DB->record_exists('local_usercreation_twins', array('username' => $teacheruid))) {

                    $twin = $DB->get_record('local_usercreation_twins', array('username' => $teacheruid));
                    $twin->fixed = 2;
                    $DB->update_record('local_usercreation_twins', $twin);
                }

                $this->updateuser($processstart, 'localteacher', $teacheruid, $idnumber, $firstname, $lastname, $email);
            } else {

                // Doublon.

                if ($user->timemodified < $processstart) {

                    $this->updateuser($processstart, 'localteacher', $teacheruid, $idnumber,
                            $firstname, $lastname, $email);
                } else {

                    if ($DB->record_exists('local_usercreation_twins', array('username' => $teacheruid))) {

                        $twin = $DB->get_record('local_usercreation_twins', array('username' => $teacheruid));

                        if ($twin->fixed == 1) {

                            $twin->fixed = 2;

                            $DB->update_record('local_usercreation_twins', $twin);
                            $this->updateuser($processstart, 'localteacher', $teacheruid, $idnumber, $firstname,
                                    $lastname, $email);

                            // Pour chaque inscription de l'utilisateur sur l'année actuelle.
                            $this->teachersync($affectation, $teacher);

                            return null;
                        }

                        $twin->fixed = 0;

                        $DB->update_record('local_usercreation_twins', $twin);
                    } else {

                        $twin = new \stdClass();
                        $twin->username = $teacheruid;
                        $twin->fixed = 0;

                        $DB->insert_record('local_usercreation_twins', $twin);
                    }
                }

                return null;
            }
        } else {

            $user = $this->newuser($processstart, 'localteacher', $teacheruid, $idnumber, $firstname, $lastname, $email);
        }

        // Pour chaque inscription de l'utilisateur sur l'année actuelle.
        $this->teachersync($affectation, $teacher);
    }

    private function teachersync($affectation, $teacher) {

        global $DB;

        // Ici, gérer local_usercreation_ufr et local_usercreation_type.

        $teacherdata = $DB->get_record('user', array('username' => $teacher->getAttribute('StaffUID')));


        /* Commenté pour le passage à la nouvelle année car la structure du fichier DOKEOS change */


//        $codestructure = $affectation->getAttribute('CodeStructure');
//
//        if (isset($codestructure)) {
//
//            $ufrcode = substr($codestructure, 0, 1);
//            if (!$DB->record_exists('local_usercreation_ufr',
//                    array('userid' => $teacherdata->id, 'ufrcode' => $ufrcode))) {
//
//                $ufrteacher = array();
//                $ufrteacher['userid'] = $teacherdata->id;
//                $ufrteacher['ufrcode'] = $ufrcode;
//                $DB->insert_record('local_usercreation_ufr', $ufrteacher);
//                if ($DB->record_exists('local_usercreation_ufr',
//                        array('userid' => $teacherdata->id, 'ufrcode' => '-1'))) {
//
//                    $DB->delete_record('local_usercreation_ufr',
//                            array('userid' => $teacherdata->id, 'ufrcode' => '-1'));
//                }
//            }
//        }

        if (!$DB->record_exists('local_usercreation_ufr', array('userid' => $teacherdata->id))) {

            $ufrteacher = array();
            $ufrteacher['userid'] = $teacherdata->id;
            $ufrteacher['ufrcode'] = '-1';
            $DB->insert_record('local_usercreation_ufr', $ufrteacher);
        }

        if ($teacher->getAttribute('LC_CORPS') != null &&
                $teacher->getAttribute('LC_CORPS') != "") {

            $sqlrecordexistsinfodata = "SELECT * FROM {local_usercreation_type} WHERE "
                    . "userid = ? AND typeteacher LIKE ?";

            if (!$DB->record_exists_sql($sqlrecordexistsinfodata,
                    array($teacherdata->id, $teacher->getAttribute('LC_CORPS')))) {

                $typeprofdata = array();
                $typeprofdata['userid'] = $teacherdata->id;
                $typeprofdata['typeteacher'] = $teacher->getAttribute('LC_CORPS');
                $DB->insert_record('local_usercreation_type', $typeprofdata);

                if ($DB->record_exists_sql($sqlrecordexistsinfodata,
                    array($teacherdata->id, "Non indiqué"))) {

                    $DB->delete_records('local_usercreation_type',
                            array('userid' => $teacherdata->id, 'typeteacher' => "Non indiqué"));
                }
            }
        }

        if (!$DB->record_exists('local_usercreation_type', array('userid' => $teacherdata->id))) {

            $typeprofdata = array();
            $typeprofdata['userid'] = $teacherdata->id;
            $typeprofdata['typeteacher'] = "Non indiqué";
            $DB->insert_record('local_usercreation_type', $typeprofdata);
        }
    }

    private function createstaff($processstart) {

        $xmldoc = new \DOMDocument();
        $xmldoc->load('/home/referentiel/sefiap_personnel_composante.xml');
        $xpathvar = new \Domxpath($xmldoc);
        $liststaff = $xpathvar->query('//Composante/Service/Individu');

        foreach ($liststaff as $staff) {

            $this->staffline($processstart, $staff);
        }
    }

    private function staffline ($processstart, $staff) {

        global $DB;

        $staffuid = $staff->getAttribute('UID');

        if ($staff->hasAttribute('UID')) {

            $email = $staff->getAttribute('MAIL');

            if ($staff->hasAttribute('CODE_ETUDIANT')) {

                $idnumber = $staff->getAttribute('CODE_ETUDIANT');
            } else {

                $idnumber = $staff->getAttribute('NO_INDIVIDU');
            }

            if ($DB->record_exists('local_usercreation_phdstu',
                    array('staffcode' => $staff->getAttribute('NO_INDIVIDU'), 'username' => $staffuid))) {

                    $idnumber = $DB->get_record('local_usercreation_phdstu',
                            array('staffcode' => $staff->getAttribute('NO_INDIVIDU'),
                                'username' => $staffuid))->studentcode;
            }

            $lastname = $this->nameprocessor(strtolower($staff->getAttribute('NOM_USUEL')));
            $firstname = $this->nameprocessor(strtolower($staff->getAttribute('PRENOM')));

            $this->processstaff($processstart, $staffuid, $idnumber, $firstname, $lastname, $email);
        }
    }

    private function processstaff($processstart, $staffuid, $idnumber, $firstname, $lastname, $email) {

        global $DB;

        if ($DB->record_exists('user', array('username' => $staffuid))) {

            $user = $DB->get_record('user', array('username' => $staffuid));

            // Si il n'est pas personnel de l'établissement, lui donner le rôle.
            // Peut se produire si le personnel se connecte avant la création de son compte.

            $this->givesystemrole($user->id, 'localstaff', $staffuid, $idnumber, $firstname, $lastname);

            if ($user->idnumber == $idnumber || $user->idnumber == "") {

                // Même utilisateur.

                if ($DB->record_exists('local_usercreation_twins', array('username' => $staffuid))) {

                        $twin = $DB->get_record('local_usercreation_twins', array('username' => $staffuid));
                        $twin->fixed = 2;
                        $DB->update_record('local_usercreation_twins', $twin);
                }

                $this->updateuser($processstart, 'localstaff', $staffuid, $idnumber, $firstname, $lastname, $email);
            } else {

                // Doublon.

                if ($user->timemodified < $processstart) {

                    $this->updateuser($processstart, 'localstaff', $staffuid, $idnumber, $firstname,
                                        $lastname, $email);
                } else {

                    if ($DB->record_exists('local_usercreation_twins', array('username' => $staffuid))) {

                        $twin = $DB->get_record('local_usercreation_twins', array('username' => $staffuid));

                        if ($twin->fixed == 1) {

                                $twin->fixed = 2;

                                $DB->update_record('local_usercreation_twins', $twin);
                                $this->updateuser($processstart, 'localstaff', $staffuid, $idnumber, $firstname,
                                        $lastname, $email);

                                return null;
                        }

                        $twin->fixed = 0;

                        $DB->update_record('local_usercreation_twins', $twin);
                    } else {

                        $twin = new \stdClass();
                        $twin->username = $staffuid;
                        $twin->fixed = 0;

                        $DB->insert_record('local_usercreation_twins', $twin);
                    }
                }
                return null;
            }
        } else {

            $user = $this->newuser($processstart, 'localstaff', $staffuid, $idnumber, $firstname, $lastname, $email);
        }
    }

    private function createstudentseisti($processstart) {

        $xmldoc = new \DOMDocument();
        $xmldoc->load('/home/referentiel/DOKEOS_Etudiants_Inscriptions_eisti.xml');
        $xpathvar = new \Domxpath($xmldoc);
        $liststudents = $xpathvar->query('//Student');

        foreach ($liststudents as $student) {

            $this->studentlineeisti($processstart, $student);
        }
    }

    private function studentlineeisti($processstart, $student) {

        if (!$student->hasAttribute('StudentUIDGE') && $student->hasAttribute('StudentUIDEisti')
                && $student->hasAttribute('StudentName') && $student->hasAttribute('StudentFirstName')) {

            $studentuid = 'i-'.$student->getAttribute('StudentUIDEisti');

            echo 'StudentUIDEisti = '.$studentuid."\n";

            $email = $student->getAttribute('StudentEmailEisti');
            $idnumber = $student->getAttribute('StudentETUEisti');
            $lastname = $this->nameprocessor(strtolower($student->getAttribute('StudentName')));
            $firstname = $this->nameprocessor(strtolower($student->getAttribute('StudentFirstName')));
            $universityyears = $student->childNodes;

            foreach ($universityyears as $universityyear) {

                if ($universityyear->nodeType !== 1 ) {

                    continue;
                }

                // Si l'utilisateur est inscrit à l'université pendant l'année en cours, on traite son cas.
                $year = $universityyear->getAttribute('AnneeUniv');
                $configyear = get_config('local_usercreation', 'year');

                if ($year == $configyear) {

                    $processstudenteisti = false;

                    $yearenrolments = $universityyear->childNodes;

                    foreach ($yearenrolments as $yearenrolment) {

                        if ($yearenrolment->nodeType !== 1 ) {

                            continue;
                        }

                        if ($yearenrolment->getAttribute('CodeCycle') == 'c0_bachelor01' ||
                                $yearenrolment->getAttribute('CodeCycle') == 'c0_bachelor02') {

                            $processstudenteisti = true;
                        }
                    }

                    if ($processstudenteisti == true) {

                        $this->processstudenteisti($processstart, $studentuid, $idnumber, $firstname,
                            $lastname, $email, $universityyear);
                    }
                }
            }
        }
    }

    private function processstudenteisti($processstart, $studentuid, $idnumber, $firstname,
            $lastname, $email, $universityyear) {

        global $DB;

        $user = $DB->get_record('user', array('username' => $studentuid));

        if ($user) {

            // Si il n'est pas étudiant de l'établissement, lui donner le rôle.
            // Peut se produire si l'étudiant se connecte avant la création de son compte.
            // Probablement pas ici pour l'EISTI mais ça ne devrait pas faire de mal de garder.

            echo "$user->id, 'localstudent', $studentuid, $idnumber, $firstname, $lastname\n";

            $this->givesystemrole($user->id, 'localstudent', $studentuid, $idnumber, $firstname, $lastname);

            if ($user->idnumber == $idnumber || $user->idnumber == "") {

                // Même utilisateur.

                if ($DB->record_exists('local_usercreation_twins', array('username' => $studentuid))) {

                    $twin = $DB->get_record('local_usercreation_twins', array('username' => $studentuid));
                    $twin->fixed = 2;
                    $DB->update_record('local_usercreation_twins', $twin);
                }

                $this->updateuser($processstart, 'localstudent', $studentuid, $idnumber, $firstname, $lastname, $email);

                // Pour chaque inscription de l'utilisateur sur l'année actuelle.
                $this->yearenrolmentseisti($universityyear, $user);
            } else {

                // Doublon.

                if ($user->timemodified < $processstart) {

                    $this->updateuser($processstart, 'localstudent', $studentuid, $idnumber,
                            $firstname, $lastname, $email);
                } else {

                    if ($DB->record_exists('local_usercreation_twins', array('username' => $studentuid))) {

                        $twin = $DB->get_record('local_usercreation_twins', array('username' => $studentuid));

                        if ($twin->fixed == 1) {

                            $twin->fixed = 2;

                            $DB->update_record('local_usercreation_twins', $twin);
                            $this->updateuser($processstart, 'localstudent', $studentuid, $idnumber,
                                    $firstname, $lastname, $email);

                            // Pour chaque inscription de l'utilisateur sur l'année actuelle.
                            $this->yearenrolmentseisti($universityyear, $user);

                            return null;
                        }
                        $twin->fixed = 0;

                        $DB->update_record('local_usercreation_twins', $twin);
                    } else {

                        $twin = new \stdClass();
                        $twin->username = $studentuid;
                        $twin->fixed = 0;

                        $DB->insert_record('local_usercreation_twins', $twin);
                    }
                }

                return null;
            }
        } else {

            $user = $this->newusereisti($processstart, 'localstudent', $studentuid, $idnumber,
                    $firstname, $lastname, $email);
        }
    }

    private function newusereisti($processstart, $rolename, $studentuid, $idnumber, $firstname, $lastname, $email) {

        global $DB;
        $user = new \stdClass();
        $user->auth = 'manual';
        $user->confirmed = 1;
        $user->mnethostid = 1;
        $user->email = $email;
        $user->username = $studentuid;
        $user->lastname = $lastname;
        $user->firstname = $firstname;
        $user->idnumber = $idnumber;
        $user->timecreated = $processstart;
        $user->timemodified = $processstart;
        $user->lang = 'fr';

        $user->id = user_create_user($user);

        $newuser = $DB->get_record('user', array('id' => $user->id));

        setnew_password_and_mail($newuser);

        $this->givesystemrole($user->id, $rolename, $studentuid, $idnumber, $firstname, $lastname);
        return $user;
    }

    private function yearenrolmentseisti($universityyear, $user) {

        global $DB;
        $yearenrolments = $universityyear->childNodes;
        $year = $universityyear->getAttribute('AnneeUniv');
        foreach ($yearenrolments as $yearenrolment) {

            if ($yearenrolment->nodeType !== 1 ) {

                continue;
            }

            $codecycle = $yearenrolment->getAttribute('CodeCycle');

            if ($codecycle == 'c0_bachelor01') {

                $codeetape = '5C32A1';
                $codeetapeyear = "Y$year-$codeetape";
                $ufrcode = substr($codeetape, 0, 1);
                $ufrcodeyear = "Y$year-$ufrcode";

                // Si cette inscription de l'utilisateur à cette composante
                // n'est pas encore dans mdl_local_ufrstudent, on l'y ajoute.

                if (!$DB->record_exists('local_usercreation_ufr',
                        array('userid' => $user->id, 'ufrcode' => $ufrcodeyear))) {

                    $ufrrecord = new \stdClass();
                    $ufrrecord->userid = $user->id;
                    $ufrrecord->ufrcode = $ufrcodeyear;
                    $DB->insert_record('local_usercreation_ufr', $ufrrecord);
                } else {

                    $ufrrecord = $DB->get_record('local_usercreation_ufr', array('userid' => $user->id,
                        'ufrcode' => $ufrcodeyear));
                    $ufrrecord->stillexists = 1;
                    $DB->update_record('local_usercreation_ufr', $ufrrecord);
                }

                // Idem pour la VET.

                if (!$DB->record_exists('local_usercreation_vet', array('studentid' => $user->id,
                    'vetcode' => $codeetapeyear))) {

                    $studentvetrecord = new \stdClass();
                    $studentvetrecord->studentid = $user->id;
                    $studentvetrecord->vetcode = $codeetapeyear;
                    $studentvetrecord->vetname = $yearenrolment->getAttribute('LibEtape');
                    $DB->insert_record('local_usercreation_vet', $studentvetrecord);
                } else {

                    $vetrecord = $DB->get_record('local_usercreation_vet', array('studentid' => $user->id,
                        'vetcode' => $codeetapeyear));
                    $vetrecord->stillexists = 1;
                    $DB->update_record('local_usercreation_vet', $vetrecord);
                }
            }

            if ($codecycle == 'c0_bachelor02') {

                $codeetape = '5C32A2';
                $codeetapeyear = "Y$year-$codeetape";
                $ufrcode = substr($codeetape, 0, 1);
                $ufrcodeyear = "Y$year-$ufrcode";

                // Si cette inscription de l'utilisateur à cette composante
                // n'est pas encore dans mdl_local_ufrstudent, on l'y ajoute.

                if (!$DB->record_exists('local_usercreation_ufr',
                        array('userid' => $user->id, 'ufrcode' => $ufrcodeyear))) {

                    $ufrrecord = new \stdClass();
                    $ufrrecord->userid = $user->id;
                    $ufrrecord->ufrcode = $ufrcodeyear;
                    $DB->insert_record('local_usercreation_ufr', $ufrrecord);
                } else {

                    $ufrrecord = $DB->get_record('local_usercreation_ufr', array('userid' => $user->id,
                        'ufrcode' => $ufrcodeyear));
                    $ufrrecord->stillexists = 1;
                    $DB->update_record('local_usercreation_ufr', $ufrrecord);
                }

                // Idem pour la VET.

                if (!$DB->record_exists('local_usercreation_vet', array('studentid' => $user->id,
                    'vetcode' => $codeetapeyear))) {

                    $studentvetrecord = new \stdClass();
                    $studentvetrecord->studentid = $user->id;
                    $studentvetrecord->vetcode = $codeetapeyear;
                    $studentvetrecord->vetname = $yearenrolment->getAttribute('LibEtape');
                    $DB->insert_record('local_usercreation_vet', $studentvetrecord);
                } else {

                    $vetrecord = $DB->get_record('local_usercreation_vet', array('studentid' => $user->id,
                        'vetcode' => $codeetapeyear));
                    $vetrecord->stillexists = 1;
                    $DB->update_record('local_usercreation_vet', $vetrecord);
                }
            }
        }
    }

    private function postprocess() {

        global $DB;

        $DB->delete_records('local_usercreation_twins', array('fixed' => 1));
        $DB->delete_records('local_usercreation_twins', array('fixed' => 2));
        $DB->delete_records('local_usercreation_ufr', array('stillexists' => 0));
        $DB->delete_records('local_usercreation_vet', array('stillexists' => 0));
    }

    private function nameprocessor($name) {

        $newname = str_replace(',', '', $name);

        $lowername = strtolower($newname);
        $tabname = explode('-', $lowername);
        $processedname = "";

        foreach($tabname as $tabelement) {



            $processedname .= ucfirst($tabelement)."-";
        }

        $finalname = substr($processedname, 0, -1);

        return $finalname;
    }
}
