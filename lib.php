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
require_once("$CFG->dirroot/group/lib.php");
require_once($CFG->libdir .'/filelib.php');

function local_usercreation_extend_navigation(global_navigation $nav) {

    global $DB, $USER, $PAGE;

    if ($PAGE->has_set_url()) {

        $url = $PAGE->url;

        $twinpageurl = new moodle_url('/local/usercreation/twin.php');

        if ($url == $twinpageurl) {

            $ontwinpage = true;
        } else {

            $ontwinpage = false;
        }
    } else {

        $ontwinpage = false;
    }

    if ($USER && isset($USER->username) && !$ontwinpage) {

        $hastwin = $DB->record_exists('local_usercreation_twins', array('username' => $USER->username));

        if ($hastwin) {
			
			$twin = $DB->get_record('local_usercreation_twins', array('username' => $USER->username));
			
			// Si ça fait plus de 24 heures que le dernier mail a été envoyé.
			
			if ($twin->checkedon < (time() - 24 * 3600) || !isset($twin->checkedon)) {
			
				$twin->checkedon = time();
				
				$DB->update_record('local_usercreation_twins', $twin);
				
				$mailcontent = "Bonjour,\nLes personnes suivantes ont le même login :\n";
				$nbtwin = 1;
				
				$studentxml = new DOMDocument();
				$studentxml->load('/home/referentiel/DOKEOS_Etudiants_Inscriptions.xml');
				
				// En théorie, il serait mieux de chercher uniquement les Students avec le bon StudentUID dans le XML puis de les parcourir mais ...
				// CA MARCHE PAS ! Et je ne sais pas pourquoi...
				
				$studentxpathvar = new Domxpath($studentxml);
				$liststudents = $studentxpathvar->query("//Student");
				
				foreach ($liststudents as $student) {
					
					if ($student->getAttribute('StudentUID') == $USER->username) {
					
						// Récupérer les infos et envoyer le mail.
						
						$username = $student->getAttribute('StudentUID');
						$firstname = ucwords(strtolower($student->getAttribute('StudentFirstName')));
						$commonname = ucwords(strtolower($student->getAttribute('StudentName')));
						$idnumber = $student->getAttribute('StudentETU');
						$mail = $student->getAttribute('StudentEmail');
						
						$mailcontent .= "\nPersonne $nbtwin : ".formatdata($username, $firstname, $commonname, $idnumber, $mail, 'Etudiant')."\n\n";
						$nbtwin++;
					}
				}
				
				$teacherxml = new DOMDocument();
				$teacherxml->load('/home/referentiel/DOKEOS_Enseignants_Affectations.xml');
				$teacherxpathvar = new Domxpath($teacherxml);
				$listteachers = $teacherxpathvar->query("//Teacher");
				
				foreach ($listteachers as $teacher) {
					
					if ($teacher->getAttribute('StaffUID') == $USER->username) {
					
						// Récupérer les infos et envoyer le mail.
						
						$username = $teacher->getAttribute('StaffUID');
						$firstname = ucwords(strtolower($teacher->getAttribute('StaffFirstName')));
						$commonname = ucwords(strtolower($teacher->getAttribute('StaffCommonName')));
						$idnumber = $teacher->getAttribute('StaffCode');
						$mail = $teacher->getAttribute('StaffEmail');
						
						$mailcontent .= "\nPersonne $nbtwin : ".formatdata($username, $firstname, $commonname, $idnumber, $mail, 'Enseignant')."\n\n";
						$nbtwin++;
					}
				}
				
				$staffxml = new DOMDocument();
				$staffxml->load('/home/referentiel/sefiap_personnel_composante.xml');
				$staffxpathvar = new Domxpath($staffxml);
				$liststaffs = $staffxpathvar->query("//Composante/Service/Individu");
				
				foreach ($liststaffs as $staff) {
					
					if ($staff->getAttribute('UID') == $USER->username) {
					
						// Récupérer les infos et envoyer le mail.
						
						$username = $staff->getAttribute('UID');
						$firstname = ucwords(strtolower($staff->getAttribute('PRENOM')));
						$commonname = ucwords(strtolower($staff->getAttribute('NOM_USUEL')));
						$idnumber = $staff->getAttribute('NO_INDIVIDU');
						$mail = $staff->getAttribute('MAIL');
						
						$mailcontent .= "\nPersonne $nbtwin : ".formatdata($username, $firstname, $commonname, $idnumber, $mail, 'Personnel')."\n";
						$nbtwin++;
					}
				}
				
				$mailcontent .= "\nL'une de ces personnes a tenté de se connecter sur la plateforme CoursUCP et n'a pas pu à la date : "
					.date("d/m/Y à G:i")."\n\nCoursUCP, Service d'ingénierie pédagogique";
				
				$mailrecipients = 'laurent.guillet@u-cergy.fr, noa.randriamalaka@u-cergy.fr, brice.errandonea@u-cergy.fr,'.
					'samira.kheloufi@u-cergy.fr, guillaume.renier@u-cergy.fr, daniel.fouquet@u-cergy.fr, jean-thierry.graveaud@u-cergy.fr';
				
				mail($mailrecipients, 'Doublon de logins détecté par CoursUCP', $mailcontent);
			}

            $redirecturl = new moodle_url('/local/usercreation/twin.php');

            redirect($redirecturl);
        }
    }
}

function formatdata ($username, $firstname, $commonname, $idnumber, $mail, $type) {
	
	$partialmail = "Nom d'utilisateur : $username\nPrénom: $firstname\nNom : $commonname\n".
		"Numéro d'identification : $idnumber\nAdresse mail : $mail\nType : $type";
	
	return $partialmail;
}
