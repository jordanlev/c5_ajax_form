<?php defined('C5_EXECUTE') or die("Access Denied.");

class FormBlockStatistics extends Concrete5_Controller_Block_FormStatistics {}
class MiniSurvey extends Concrete5_Controller_Block_FormMinisurvey {}
	
class FormBlockController extends Concrete5_Controller_Block_Form {
	
	public $enablePlaceholders = false;
	
	public function on_page_view() {
		$html = Loader::helper('html');
		
		if ($this->enablePlaceholders) {
			$bv = new BlockView();
			$bv->setBlockObject($this->getBlockObject());
			$blockURL = $bv->getBlockURL();
			$this->addFooterItem($html->javascript("{$blockURL}/jquery.placeholder.min.js", null, array('handle' => 'jquery.placeholder', 'version' => '2.0.7')));
		}
		
		//C5 only includes jquery.form.js when user is logged in.
		//This is safe to call even if C5 is including it, though, because it will catch the duplicate
		// and only output it once.
		$this->addFooterItem($html->javascript('jquery.form.js'));
		
		//DEV NOTE: We are intentionally *not* calling parent::on_page_view,
		// because in Concrete5.6 the parent class adds jquery ui to the footer
		// (js AND css!!), which makes less than zero sense.
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
				$replace = "placeholder=\"{$question['question']}\" {$search}";
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
		$errorHeader = $formResponse;
		$errors = is_array($errors) ? $errors : array();
		if ($invalidIP) {
			$errors[] = $invalidIP;
		}

		//Prep captcha
		$surveyBlockInfo = $miniSurvey->getMiniSurveyBlockInfoByQuestionId($qsID, $bID);
		$captcha = $surveyBlockInfo['displayCaptcha'] ? Loader::helper('validation/captcha') : false;

		//Localized labels
		$translatedCaptchaLabel = t('Please type the letters and numbers shown in the image.');
		$translatedSubmitLabel = t('Submit');
		$translatedProcessingLabel = t('Processing...');
		
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
		$this->set('translatedCaptchaLabel', $translatedCaptchaLabel);
		$this->set('translatedSubmitLabel', $translatedSubmitLabel);
		$this->set('translatedProcessingLabel', $translatedProcessingLabel);
		$this->set('enablePlaceholders', $this->enablePlaceholders);
	}
	
	
}	
