<?php

define('CLI_SCRIPT', true);
require_once( __DIR__.'/../../config.php');

require_once($CFG->dirroot .'/course/lib.php');
require_once($CFG->libdir .'/filelib.php');
require_once($CFG->libdir .'/accesslib.php');

newuser('localstudent', 'e-rberange', 21813238, 'ROMUALD', 'BERANGER', 'romuald.beranger@etu.u-cergy.fr');
newuser('localstudent', 'e-mbonnin', 21813227, 'MARIE', 'BONNIN', 'marie.bonnin@etu.u-cergy.fr');
newuser('localstudent', 'e-ccordoba', 21813226, 'CEDRIC', 'CORDOBA', 'cedric.cordoba@etu.u-cergy.fr');
newuser('localstudent', 'adeshaie', 21812263, 'AURORE', 'DESHAIES', 'aurore.deshaies@etu.u-cergy.fr');
newuser('localstudent', 'belabbas', 21718710, 'BRAHIM', 'EL ABBASSI', 'brahim.el-abbassi@etu.u-cergy.fr');
newuser('localstudent', 'jeuller', 21720961, 'JEAN-MARC', 'EULLER', 'jean-marc.euller@etu.u-cergy.fr');
newuser('localstudent', 'sboulfaa', 20905343, 'SARAH', 'BOULFAAT', 'sarah.boulfaat@etu.u-cergy.fr');
newuser('localstudent', 'nfloret', 20701736, 'NELLY', 'FLORET', 'nelly.floret@etu.u-cergy.fr');
newuser('localstudent', 'e-lfusibet', 19400852, 'LAURENT', 'FUSIBET', 'laurent.fusibet@etu.u-cergy.fr');
newuser('localstudent', 'rgarnier', 21812259, 'RUDY', 'GARNIER', 'rudy.garnier@etu.u-cergy.fr');
newuser('localstudent', 'e-egaulier', 21813815, 'EDOUARD', 'GAULIER', 'edouard.gaulier@etu.u-cergy.fr');
newuser('localstudent', 'eribeiro', 19900578, 'EMMANUELLE', 'HAMON', 'emmanuelle.ribeiro-vidal@etu.u-cergy.fr');
newuser('localstudent', 'e-fmenaa', 21813214, 'MENAA', 'FAYÇAL', 'faycal.menaa@etu.u-cergy.fr');
newuser('localstudent', 'rsarrouf', 21718700, 'RODOLPHE', 'SARROUF', 'rodolphe.sarrouf@etu.u-cergy.fr');
newuser('localstudent', 'vtrilla', 21718683, 'VIVIEN', 'TRILLA', 'vivien.trilla@etu.u-cergy.fr');



function newuser($rolename, $studentuid, $idnumber, $firstname, $lastname, $email) {

	global $DB;
	$user = new stdClass();
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
	$systemcontext = context_system::instance();
	role_assign($role->id, $user->id, $systemcontext->id);
	return $user;
}