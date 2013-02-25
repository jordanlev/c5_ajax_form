# Concrete5 Ajax Form
Improves the built-in form block so it submits via ajax. Also uses a table-less layout for easier styling, and
optional "placeholder" label functionality.

*REQUIRES CONCRETE 5.6 OR HIGHER!*

This is basically the same thing as the Ajax Form addon in the marketplace, but more efficient (only loads
jquery.form.js on pages that have a form block -- not every page on the site like the marketplace addon does).
It also adds the optional placeholder functionality and the ability to fire google analytics events on submission.

## Installation

 1. Click the "ZIP" button above
 2. Unzip the downloaded file
 3. Open the `blocks` folder 
 4. Move the `form` folder to your site's top-level `blocks` directory (*not* `concrete/blocks`)

That's it! Now any form blocks added to your site will automatically have ajax functionality (unless a custom
template is chosen, or unless there are file upload fields in the form).

To enable "placeholder" functionality (so field labels appear inside the fields themselves), set the `$enablePlaceholders`
variable to `true` near the top of the `controller.php` file.

To enable google analytics events, set the `$enableGoogleEvents` variable to `true` near the top of the file.  The event
uses the form name as the label with the category "Forms" and action "Submitted".
