<?php defined('C5_EXECUTE') or die("Access Denied.");

class FormBlockStatistics extends Concrete5_Controller_Block_FormStatistics {}
class MiniSurvey extends Concrete5_Controller_Block_FormMinisurvey {}
	
class FormBlockController extends Concrete5_Controller_Block_Form {
	
	public $enablePlaceholders = false;
	public $enableSpamHoneypot = true;
	
	public function on_page_view() {
		$html = Loader::helper('html');
		
		//C5 only includes jquery.form.js when user is logged in.
		//This is safe to call even if C5 is including it, though,
		// because C5 will catch the duplicate and only output it once.
		$this->addFooterItem($html->javascript('jquery.form.js'));
		
		if ($this->enablePlaceholders) {
			$bv = new BlockView();
			$bv->setBlockObject($this->getBlockObject());
			$blockURL = $bv->getBlockURL();
			$this->addFooterItem($html->javascript("{$blockURL}/jquery.placeholder.min.js", null, array('handle' => 'jquery.placeholder', 'version' => '2.0.7')));
			$this->addFooterItem($html->script('$(document).ready(function() { $(".formblock input, .formblock textarea").placeholder(); });'));
		}
		
		parent::on_page_view();
	}
	
	public function view() {
		//Set up nice clean variables for the view to use.
		//Note that we don't call parent::view(), because built-in form block controller doesn't have one(!!)
		
		$miniSurvey = new MiniSurvey();
		$miniSurvey->frontEndMode = true;

		$bID = intval($this->bID);
		$qsID = intval($this->questionSetId);
		
		$formDomId = "miniSurveyView{$bID}";
		$hasFileUpload = false;

		$questionsRS = $miniSurvey->loadQuestions($qsID, $bID);
		$questions = array();
		while ($questionRow = $questionsRS->fetchRow()) {
			$question = $questionRow;
			$question['input'] = $miniSurvey->loadInputType($questionRow, false);
			if ($questionRow['inputType'] == 'fileupload') {
				$hasFileUpload = true;
			}
	
			//Make type names common-sensical
			if ($questionRow['inputType'] == 'text') {
				$question['type'] = 'textarea';
			} else if ($questionRow['inputType'] == 'field') {
				$question['type'] = 'text';
			} else {
				$question['type'] = $questionRow['inputType'];
			}
	
			//Construct label "for" (and misc. hackery for checkboxlist / radio lists)
			if ($question['type'] == 'checkboxlist') {
				$question['input'] = str_replace('<div class="checkboxPair">', '<div class="checkboxPair"><label>', $question['input']);
				$question['input'] = str_replace("</div>\n", "</label></div>\n", $question['input']); //include linebreak in find/replace string so we don't replace the very last closing </div> (the one that closes the "checkboxList" wrapper div that's around this whole question)
			} else if ($question['type'] == 'radios') {
				//Put labels around each radio items (super hacky string replacement -- this might break in future versions of C5)
				$question['input'] = str_replace('<div class="radioPair">', '<div class="radioPair"><label>', $question['input']);
				$question['input'] = str_replace('</div>', '</label></div>', $question['input']);
		
				//Make radioList wrapper consistent with checkboxList wrapper
				$question['input'] = "<div class=\"radioList\">\n{$question['input']}\n</div>\n";
			} else {
				$question['labelFor'] = 'for="Question' . $questionRow['msqID'] . '"';
			}
	
			//Remove hardcoded style on textareas
			if ($question['type'] == 'textarea') {
				$question['input'] = str_replace('style="width:95%"', '', $question['input']);
			}
	
			//Add placeholder attributes
			if ($this->enablePlaceholders) {
				$search = 'id="Question';
				$replace = 'placeholder="' . $question['question'] . ($question['required'] ? ' *' : '') . '" ' . $search;
				$question['input'] = str_replace($search, $replace, $question['input']);
			}
			
			//Hide some field labels if showing placeholders
			$question['labelClasses'] = '';
			if ($this->enablePlaceholders && in_array($question['type'], array('text', 'textarea', 'email', 'telephone', 'url'))) {
				$question['labelClasses'] .= ' visuallyhidden';
			}
			
			
			$questions[] = $question;
		}

		//Prep thank-you message
		$success = ($_GET['surveySuccess'] && $_GET['qsid'] == intval($qsID));
		$thanksMsg = $this->thankyouMsg;

		//Prep error message(s)
		$errorHeader = $this->get('formResponse');
		$errors = $this->get('errors');
		$errors = is_array($errors) ? $errors : array();
		if ($invalidIP) {
			$errors[] = $invalidIP;
		}

		//Prep captcha
		$surveyBlockInfo = $miniSurvey->getMiniSurveyBlockInfoByQuestionId($qsID, $bID);
		$captcha = $surveyBlockInfo['displayCaptcha'] ? Loader::helper('validation/captcha') : false;
		
		//Send data to the view
		$this->set('formDomId', $formDomId);
		$this->set('hasFileUpload', $hasFileUpload);
		$this->set('qsID', $qsID);
		$this->set('pURI', $pURI);
		$this->set('success', $success);
		$this->set('thanksMsg', $thanksMsg);
		$this->set('errorHeader', $errorHeader);
		$this->set('errors', $errors);
		$this->set('questions', $questions);
		$this->set('captcha', $captcha);
		$this->set('enablePlaceholders', $this->enablePlaceholders);
		$this->set('enableSpamHoneypot', $this->enableSpamHoneypot);
		$this->set('formName', $surveyBlockInfo['surveyName']); //for GA event tracking
	}
	
	public function action_submit_form() {
		if ($this->enableSpamHoneypot) {
			if (!empty($_POST['message1'])) {
				//It's possible that an auto-fill helper or someone using a screenreader filled out this field,
				// so let them know that it should be left blank.
				$this->set('formResponse', t('Please correct the following errors:'));
				$this->set('errors', array(t('Error: It looks like you might be a spammer because you filled out the "Leave Blank" field. If you\'re not a spammer, please leave that field blank and try submitting again. Thanks!')));
				return;
			} else if (empty($_POST['message2']) || $_POST['message2'] != '1') {
				//It's fairly impossible that this form field got altered by accident (because it's an <input type="hidden">),
				// so don't even bother saying that there's a problem.
				$errorResponse = '<span class="confirmation">Thank you.</span>';
				$this->set('formResponse', t('Thank you.'));
				$this->set('errors', array());
				return;
			}
		}
		
		//submission passed the honeypot checks (or honeypot is disabled),
		// so continue on to normal form processing...
		parent::action_submit_form();
	}

	
}	
