<?php defined('C5_EXECUTE') or die("Access Denied.");

$formAction = $this->action('submit_form').'#'.$qsID; //NOTE: This only works here (not in the controller)!

$isAjax = (empty($redirectCID) && !$hasFileUpload);
if ($isAjax):
	$ajax_url = REL_DIR_FILES_TOOLS_BLOCKS . '/form/ajax_responder';
	$unencoded_form_action = str_replace('&amp;', '&', $formAction);
	parse_str(parse_url($unencoded_form_action, PHP_URL_QUERY), $formActionQuerystring); //NOTE: $formActionQuerystring is uninitialized (this function is really weird about how it returns values!).
	$ccm_token = $formActionQuerystring['ccm_token'];
	$form_processing_varname = "processing_form_{$bID}";
	$template_onsubmit_funcname = "form_{$bID}_onsubmit";
	$template_onsuccess_funcname = "form_{$bID}_onsuccess";
	$template_onerror_funcname = "form_{$bID}_onerror";
	?>
	<script type="text/javascript">
	var <?php echo $form_processing_varname; ?> = false;
	$(document).ready(function() {
		$('#<?php echo $formDomId; ?>').ajaxForm({
			'url': '<?php echo $ajax_url; ?>',
			'dataType': 'json',
			'data': {
				'bID': <?php echo $bID; ?>,
				'ccm_token': '<?php echo $ccm_token; ?>'
			 },
			'beforeSubmit': function() {
				if (<?php echo $form_processing_varname; ?>) {
					return false; //prevent re-submission while waiting for response
				}
				<?php echo $form_processing_varname; ?> = true;
				<?php echo $template_onsubmit_funcname; ?>('#<?php echo $formDomId; ?>');
			},
			'success': function(response) {
				<?php echo $form_processing_varname; ?> = false;
				if (response.success) {
					$('#<?php echo $formDomId; ?>').clearForm();
					<?php echo $template_onsuccess_funcname; ?>('#<?php echo $formDomId; ?>', response.message);
				} else {
					<?php
					/* CAPTCHA NOTES:
					 * We must update the captcha image upon form validation error, because the server will have generated
					 * a new image by now. Fortunately there is one Tools URL that always outputs the latest captcha image,
					 * so all we need to do is reload that URL in the image's src. Note that we append a new timestamp
					 * to that URL to prevent browser from showing a cached version of the old image (and that the timestamp
					 * is generated in javascript, not php).
					 */ ?>
					var timestamp = Math.round(new Date().getTime() / 1000).toString();
					$('#<?php echo $formDomId; ?> .ccm-captcha-image').attr('src', '<?php echo Loader::helper("concrete/urls")->getToolsURL("captcha"); ?>?nocache="' + timestamp);
					$('#<?php echo $formDomId; ?> input[name="ccmCaptchaCode"]').val(''); //clear user's prior entry because it is now wrong
					<?php echo $template_onerror_funcname; ?>('#<?php echo $formDomId; ?>', response.message, response.errors);
				}

			}
		});
	});
	</script>
	
<?php /****************************************************************************************/ ?>
	
	<script type="text/javascript">
		function <?php echo $template_onsubmit_funcname; ?>(form) {
			//This js code happens when user submits the form...
			$(form).find('div.errors').hide().html('');
			$(form).find('input.submit').hide();
			$(form).find('div.indicator').show();
		}
	
		function <?php echo $template_onsuccess_funcname; ?>(form, thanks) {
			//This js code happens after form is successfully processed...
			$(form).find('div.success').html(thanks).show();
			$(form).find('div.indicator').hide();
			$(form).find('div.fields').hide();
		}
	
		function <?php echo $template_onerror_funcname; ?>(form, errorHeader, errors) {
			//This js code happens after form is rejected due to validation errors...
			$(form).find('div.indicator').hide();
			$(form).find('input.submit').show();
		
			var errorHtml = errorHeader;
			errorHtml += '<ul>';
			$.each(errors, function() {
				errorHtml += '<li>' + this + '</li>';
			});
			errorHtml += '</ul>';
			$(form).find('div.errors').html(errorHtml).show();
		}
	</script>
<?php endif; ?>

<div id="formblock<?php echo $bID; ?>" class="formblock">
<form id="<?php echo $formDomId; ?>" method="post" action="<?php echo $formAction; ?>" <?php echo ($hasFileUpload ? 'enctype="multipart/form-data"' : ''); ?>>

	<div class="success" <?php echo !$success ? 'style="display: none;"' : ''; ?>>
		<?php echo $thanksMsg; ?>
	</div>
	
	<div class="errors" <?php echo !$errors ? 'style="display: none;"' : ''; ?>>
		<?php echo $errorHeader; ?>
		<ul>
			<?php foreach ($errors as $error): ?>
			<li><?php echo $error; ?></li>
			<?php endforeach; ?>
		</ul>
	</div>

	<div class="fields">

		<?php foreach ($questions as $question): ?>
			<div class="field field-<?php echo $question['type']; ?>">
				<label <?php echo $question['labelFor']; ?> class="<?php echo $question['labelClasses']; ?>">
					<?php echo $question['question']; ?>
					<?php if ($question['required']): ?>
						<span class="required">*</span>
					<?php endif; ?>
				</label>

				<?php echo $question['input']; ?>
			</div>
		<?php endforeach; ?>

		<?php if ($captcha): ?>
			<div class="field field-captcha">
				<label>Please type the letters and numbers shown in the image.</label>
				<?php $captcha->display(); ?>
				<?php $captcha->showInput(); ?>
			</div>
		<?php endif; ?>

	</div><!-- .fields -->

	<input type="submit" name="Submit" class="submit" value="Submit" />

	<div class="indicator" style="display: none;">
		<img src="<?php echo ASSETS_URL_IMAGES; ?>/throbber_white_16.gif" width="16" height="16" alt="" />
		<span>Processing...</span>
	</div>

	<input name="qsID" type="hidden" value="<?php echo $qsID; ?>" />
	<input name="pURI" type="hidden" value="<?php echo $pURI; ?>" />

</form>
</div><!-- .formblock -->
