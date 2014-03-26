# Concrete5 Ajax Form
Improves the built-in form block so it submits via ajax. Also uses a table-less layout for easier styling, and optional "placeholder" label functionality.

*REQUIRES CONCRETE 5.6 OR HIGHER!*

## Installation

 1. [Download the ZIP file](https://github.com/jordanlev/c5_ajax_form/archive/master.zip)
 2. Unzip the downloaded file
 3. Open the `blocks` folder 
 4. Move the `form` folder to your site's top-level `blocks` directory (*not* `concrete/blocks`)

That's it! Now any form blocks added to your site will automatically have ajax functionality (unless a custom template is chosen, or unless there are file upload fields in the form).

### Placeholder Labels
To enable "placeholder" functionality (so field labels appear inside the fields themselves), set the `$enablePlaceholders` variable to `true` near the top of `blocks/form/controller.php`.

### Google Analytics Event Tracking
If you're tracking site events with Google Analytics, you'll want to add some code like the following to `blocks/form/view.php`, directly under the `} else if (response.success) {` line:
	
	if ('undefined' !== typeof _gaq) {
		_gaq.push(['_trackEvent', 'Forms', 'Submitted', '<?php echo addslashes($formName); ?>']);
	}

### PHP Errors/Warnings?
Some users have reported getting the following error on pages that have a form block:
`Warning: Invalid argument supplied for foreach() in /PATH/TO/YOUR/SITE/blocks/form/view.php on line 111`.
This error is caused by the "Overrides Cache" (it gets confused if there was already a form block on the page and then you add this ajax form template to your site).
To resolve the problem, temporarily disable the Overrides Cache (via Dashbard > System & Settings > Cache & Speed Settings), then visit / reload any pages on your site that have a form block on them, then re-enabled the overrides cache.
_Or better yet, if the site is in development, you should just leave all caching disabled until you're ready to go live._
