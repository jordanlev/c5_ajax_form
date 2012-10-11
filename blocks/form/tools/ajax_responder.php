<?php
defined('C5_EXECUTE') or die(_("Access Denied."));

//NOTE: We are not checking permissions (like concrete/startup/process.php does for 'passthru' tasks).
//		I think this is okay because nobody would be able to get to the form to submit it
//		in the first place if they didn't have permissions on that block.
//		(and even if they somehow were able to submit the form on their own without
//		first being at the page with the form block on it, the validation token should
//		still prevent them from doing anything).


$success = false;
$message = '';
$errors = array();

$valt = Loader::helper('validation/token');
if (!$valt->validate()) {
	$message = t('Invalid form submission -- please try again');
} else if (empty($_POST['bID']) || (intval($_POST['bID']) != $_POST['bID'])) {
	$message = t('Invalid form submission -- please try again');
} else {
	$b = Block::GetById($_POST['bID']);
	$bc = new FormBlockController($b);
	$bc->noSubmitFormRedirect = true;
	
	try {
		$bc->action_submit_form();
	} catch (Exception $e) {
		$errors[] = $e->getMessage(); //Missing/Invalid qsID
	}
	
	$invalidIP = $bc->get('invalidIP');
	if (!empty($invalidIP)) {
		$errors[] = $invalidIP; //Not sure why this is error is handled separately
	}
	
	$field_errors = $bc->get('errors');
	if (is_array($field_errors)) {
		$errors = array_merge($errors, $field_errors);
	}
	
	$success = empty($errors);
	$message = $success ? $bc->thankyouMsg : $bc->get('formResponse'); // 'formResponse' is error header msg
}

//Send response
$json = Loader::helper('json');
$jsonData = array(
	'success' => $success,
	'message' => $message,
	'errors' => $errors,
);
echo $json->encode( $jsonData );

exit;
