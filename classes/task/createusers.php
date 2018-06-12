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
 * File : createstudents.php
 * Create the students account
 */

namespace local_usercreation\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot .'/course/lib.php');
require_once($CFG->libdir .'/filelib.php');
require_once($CFG->libdir .'/accesslib.php');

class createusers extends \core\task\scheduled_task {

    public function get_name() {

        return get_string('createusers', 'local_usercreation');
    }

    public function execute() {

        $this->fixtwins();
        $this->createstudents();
        $this->createteachers();
        $this->createstaff();
        $this->deletefixedtwins();
    }

    private function fixtwins() {

        global $DB;

        $listtwins = $DB->get_records('local_usercreation_twins', array());

        foreach ($listtwins as $twin) {

            $twin->fixed = 1;

            $DB->update_record('local_usercreation_twins', $twin);
        }
    }

    private function createstudents() {

        $xmldoc = new \DOMDocument();
        $xmldoc->load('/home/referentiel/DOKEOS_Etudiants_Inscriptions.xml');
        $xpathvar = new \Domxpath($xmldoc);
        $liststudents = $xpathvar->query('//Student');

        foreach ($liststudents as $student) {

            $this->studentline($student);
        }
    }

    private function studentline($student) {

        $studentuid = $student->getAttribute('StudentUID');
        echo 'studentuid = '.$studentuid."\n";

        if ($studentuid) {

            $email = $student->getAttribute('StudentEmail');
            $idnumber = $student->getAttribute('StudentETU');
            $lastname = ucwords(strtolower($student->getAttribute('StudentName')));
            $firstname = ucwords(strtolower($student->getAttribute('StudentFirstName')));
            $universityyears = $student->childNodes;

            foreach ($universityyears as $universityyear) {

                if ($universityyear->nodeType !== 1 ) {

                    continue;
                }

                // Si l'utilisateur est inscrit à l'université pendant l'année en cours, on traite son cas.
                $year = $universityyear->getAttribute('AnneeUniv');
                $configyear = get_config('local_usercreation', 'year');

                if ($year == $configyear) {

                    $this->processstudent($studentuid, $idnumber, $firstname,
                            $lastname, $email, $universityyear);
                }
            }
        }
    }

    private function processstudent($studentuid, $idnumber, $firstname, $lastname, $email, $universityyear) {

        global $DB;

        $user = $DB->get_record('user', array('username' => $studentuid));
        if ($user) {

            if ($user->idnumber == $idnumber) {

                // Même utilisateur.

                $this->updateuser('localstudent', $studentuid, $idnumber, $firstname, $lastname, $email);
            } else {

                // Doublon.

                if ($DB->record_exists('local_usercreation_twins', array('username' => $studentuid))) {

                    $twin = $DB->get_record('local_usercreation_twins', array('username' => $studentuid));
                    $twin->fixed = 0;

                    $DB->update_record('local_usercreation_twins', $twin);
                } else {

                    $twin = new \stdClass();
                    $twin->username = $studentuid;
                    $twin->fixed = 0;

                    $DB->insert_record('local_usercreation_twins', $twin);
                }
            }
        } else {

            $user = $this->newuser('localstudent', $studentuid, $idnumber, $firstname, $lastname, $email);
        }

        $listtempstudentufr = $this->getstudentufr($user);
        $listtempstudentvet = $this->getstudentvet($user);

        // Pour chaque inscription de l'utilisateur sur l'année actuelle.
        $this->yearenrolments($universityyear, $user, $listtempstudentufr, $listtempstudentvet);
    }

    private function updateuser($rolename, $username, $idnumber, $firstname, $lastname, $email) {

        global $DB;
        $userdata = $DB->get_record('user', array('username' => $username));
        $userdata->firstname = $firstname;
        $userdata->lastname = $lastname;
        $userdata->idnumber = $idnumber;
        $userdata->email = $email;
        $DB->update_record('user', $userdata);

        $systemcontext = \context_system::instance();
        $role = $DB->get_record('role', array('shortname' => $rolename));
        $assigned = $DB->record_exists('role_assignments', array('roleid' => $role->id,
            'contextid' => $systemcontext->id, 'userid' => $userdata->id));

        if (!$assigned) {

            role_assign($role->id, $userdata->id, $systemcontext->id);
        }
    }

    private function newuser($rolename, $studentuid, $idnumber, $firstname, $lastname, $email) {

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
        $user->timecreated = time();
        $user->timemodified = time();
        $user->lang = 'fr';
        $user->id = $DB->insert_record('user', $user);
        echo "Nouveau $rolename : $firstname $lastname ($studentuid, $idnumber)\n";
        $role = $DB->get_record('role', array('shortname' => $rolename));
        $systemcontext = \context_system::instance();
        role_assign($role->id, $user->id, $systemcontext->id);
        return $user;
    }

    private function getstudentufr($user) {

        global $DB;
        $liststudentufr = $DB->get_records('local_usercreation_ufr', array('userid' => $user->id));
        $listtempstudentufr = array();

        foreach ($liststudentufr as $studentufr) {

            $tempstudentufr = new \stdClass();
            $tempstudentufr->ufrcode = $studentufr->ufrcode;
            $tempstudentufr->stillexists = 0;
            $listtempstudentufr[] = $tempstudentufr;
        }

        return $listtempstudentufr;
    }

    private function getstudentvet($user) {

        global $DB;
        $liststudentvet = $DB->get_records('local_usercreation_vet', array('studentid' => $user->id));
        $listtempstudentvet = array();

        foreach ($liststudentvet as $studentvet) {

            $tempstudentvet = new \stdClass();
            $tempstudentvet->vetcode = $studentvet->vetcode;
            $tempstudentvet->stillexists = 0;
            $listtempstudentvet[] = $tempstudentvet;
        }
        return $listtempstudentvet;
    }

    private function yearenrolments($universityyear, $user, $listtempstudentufr, $listtempstudentvet) {

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
            $nbufrstudent = $DB->count_records('local_usercreation_ufr',
                    array('userid' => $user->id, 'ufrcode' => $ufrcodeyear));
            if ($nbufrstudent == 0) {

                $ufrrecord = new \stdClass();
                $ufrrecord->userid = $user->id;
                $ufrrecord->ufrcode = $ufrcodeyear;
                $DB->insert_record('local_usercreation_ufr', $ufrrecord);
            } else {

                foreach ($listtempstudentufr as $tempstudentufr) {

                    if ($tempstudentufr->ufrcode == $ufrcodeyear) {

                            $tempstudentufr->stillexists = 1;
                    }
                }
            }

            // Idem pour la VET.

            $nbstudentvet = $DB->count_records('local_usercreation_vet',
                    array('studentid' => $user->id, 'vetcode' => $codeetapeyear));
            if ($nbstudentvet == 0) {

                $studentvetrecord = new \stdClass();
                $studentvetrecord->studentid = $user->id;
                $studentvetrecord->vetcode = $codeetapeyear;
                $studentvetrecord->vetname = $yearenrolment->getAttribute('LibEtape');
                $DB->insert_record('local_usercreation_vet', $studentvetrecord);
            } else {

                foreach ($listtempstudentvet as $tempstudentvet) {

                    if ($tempstudentvet->categoryid == $vet->id) {

                        $tempstudentvet->stillexists = 1;
                    }
                }
            }
        }

        // Supprimer les anciennes ufr/vet.
        $this->cleanufrvet($listtempstudentufr, $listtempstudentvet, $user);
    }

    private function cleanufrvet($listtempstudentufr, $listtempstudentvet, $user) {

        global $DB;
        if (isset($listtempstudentufr)) {

            foreach ($listtempstudentufr as $tempstudentufr) {
                if ($tempstudentufr->stillexists == 0) {

                    echo "Désinscription de l'utilisateur $user->id de l'ufr $tempstudentufr->ufrcode\n";
                    $DB->delete_records('local_usercreation_ufr', array('userid' => $user->id,
                                        'ufrcode' => $tempstudentufr->ufrcode));
                    echo "Utilisateur désinscrit\n";
                }
            }
        }

        if (isset($listtempstudentvet)) {

            foreach ($listtempstudentvet as $tempstudentvet) {

                if ($tempstudentvet->stillexists == 0) {

                    echo "Désinscription de l'utilisateur $user->id de la vet"
                                            . " $tempstudentvet->vetcode\n";
                    $DB->delete_records('local_usercreation_vet', array('userid' => $user->id,
                                        'categoryid' => $tempstudentvet->vetcode));
                    echo "Utilisateur désinscrit\n";
                }
            }
        }
    }

    private function createteachers() {

        $xmldoc = new \DOMDocument();
        $xmldoc->load('/home/referentiel/DOKEOS_Enseignants_Affectations.xml');
        $xpathvar = new \Domxpath($xmldoc);
        $listteachers = $xpathvar->query('//Teacher');

        foreach ($listteachers as $teacher) {

            $this->teacherline($teacher);
        }
    }

    private function teacherline($teacher) {

        $teacheruid = $teacher->getAttribute('StaffUID');

        if ($teacheruid) {

            $email = $teacher->getAttribute('StaffEmail');

            if ($teacher->hasAttribute('StaffETU')) {

                $idnumber = $teacher->getAttribute('StaffETU');
            } else {

                $idnumber = $teacher->getAttribute('StaffCode');
            }
            $lastname = ucwords(strtolower($teacher->getAttribute('StaffCommonName')));
            $firstname = ucwords(strtolower($teacher->getAttribute('StaffFirstName')));
            $affectations = $teacher->childNodes;

            foreach ($affectations as $affectation) {

                if ($affectation->nodeType !== 1 ) {

                    continue;
                }

                $position = $affectation->getAttribute('Position');

                if ($position != 'Sursitaire' && $position != "") {

                    echo 'position : '.$position."\n";
                    $this->processteacher($teacheruid, $idnumber, $firstname, $lastname,
                            $email, $affectation, $teacher);
                }
            }
        }
    }

    private function processteacher($teacheruid, $idnumber, $firstname, $lastname,
            $email, $affectation, $teacher) {

        global $DB;
        $user = $DB->record_exists('user', array('username' => $teacheruid));

        if ($user) {

            if ($user->idnumber == $idnumber) {

                // Même utilisateur.

                $this->updateuser('localteacher', $teacheruid, $idnumber, $firstname, $lastname, $email);
            } else {

                // Doublon.

                if ($DB->record_exists('local_usercreation_twins', array('username' => $teacheruid))) {

                    $twin = $DB->get_record('local_usercreation_twins', array('username' => $teacheruid));
                    $twin->fixed = 0;

                    $DB->update_record('local_usercreation_twins', $twin);
                } else {

                    $twin = new \stdClass();
                    $twin->username = $teacheruid;
                    $twin->fixed = 0;

                    $DB->insert_record('local_usercreation_twins', $twin);
                }
            }
        } else {

            $user = $this->newuser('localteacher', $teacheruid, $idnumber, $firstname, $lastname, $email);
        }

        // Pour chaque inscription de l'utilisateur sur l'année actuelle.
        $this->teachersync($affectation, $teacher);
    }

    private function teachersync($affectation, $teacher) {

        global $DB;

        // Ici, gérer local_usercreation_ufr et local_usercreation_type.

        $teacherdata = $DB->get_record('user',
                array('username' => $teacher->getAttribute('StaffUID')));

        $codestructure = $affectation->getAttribute('CodeStructure');

        if (isset($codestructure)) {

            $ufrcode = substr($codestructure, 0, 1);
            if (!$DB->record_exists('local_usercreation_ufr',
                    array('userid' => $teacherdata->id, 'ufrcode' => $ufrcode))) {

                $ufrteacher = array();
                $ufrteacher['userid'] = $teacherdata->id;
                $ufrteacher['ufrcode'] = $ufrcode;
                $DB->insert_record('local_usercreation_ufr', $ufrteacher);
                if ($DB->record_exists('local_usercreation_ufr',
                    array('userid' => $teacherdata->id, 'ufrcode' => '-1'))) {

                    $DB->delete_record('local_usercreation_ufr',
                            array('userid' => $teacherdata->id, 'ufrcode' => '-1'));
                }
            }
        }

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

    private function createstaff() {

        $xmldoc = new \DOMDocument();
        $xmldoc->load('/home/referentiel/sefiap_personnel_composante.xml');
        $xpathvar = new \Domxpath($xmldoc);
        $liststaff = $xpathvar->query('//Composante/Individu');

        foreach ($liststaff as $staff) {

            $this->staffline($staff);
        }
    }

    private function staffline ($staff) {

        $staffuid = $staff->getAttribute('UID');

        if ($staffuid) {

            $email = $staff->getAttribute('MAIL');

            if ($staff->hasAttribute('StaffETU')) {

                $idnumber = $staff->getAttribute('StaffETU');
            } else {

                $idnumber = $staff->getAttribute('NO_INDIVIDU');
            }
            $lastname = ucwords(strtolower($staff->getAttribute('NOM_USUEL')));
            $firstname = ucwords(strtolower($staff->getAttribute('PRENOM')));

            $this->processstaff($staffuid, $idnumber, $firstname, $lastname, $email);
        }
    }

    private function processstaff($staffuid, $idnumber, $firstname, $lastname, $email) {

        global $DB;

        $user = $DB->record_exists('user', array('username' => $staffuid));

        if ($user) {

            if ($user->idnumber == $idnumber) {

                // Même utilisateur.

                $this->updateuser('localstaff', $staffuid, $idnumber, $firstname, $lastname, $email);
            } else {

                // Doublon.

                if ($DB->record_exists('local_usercreation_twins', array('username' => $staffuid))) {

                    $twin = $DB->get_record('local_usercreation_twins', array('username' => $staffuid));
                    $twin->fixed = 0;

                    $DB->update_record('local_usercreation_twins', $twin);
                } else {

                    $twin = new \stdClass();
                    $twin->username = $staffuid;
                    $twin->fixed = 0;

                    $DB->insert_record('local_usercreation_twins', $twin);
                }
            }
        } else {

            $user = $this->newuser('localstaff', $staffuid, $idnumber, $firstname, $lastname, $email);
        }
    }

    private function deletefixedtwins() {

        global $DB;

        $DB->delete_records('local_usercreation_twins', array('fixed' => 1));
    }
}
