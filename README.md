# Concrete5 Ajax Form
Improves the built-in form block so it submits via ajax. Also uses a table-less layout for easier styling, and optional "placeholder" label functionality.

*REQUIRES CONCRETE 5.6 OR HIGHER!*

This is basically the same thing as the Ajax Form addon in the marketplace, but more efficient (only loads jquery.form.js on pages that have a form block -- not every page on the site like the marketplace addon does). It also adds the optional placeholder functionality.

To enable "placeholder" functionality (so field labels appear inside the fields themselves), set the `$enablePlaceholders` variable to `true` near the top of the `controller.php` file.
