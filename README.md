# Graduate Custom Post Type
A custom post type to track graduates, designed originally for the Creative Writing MFA program in the English Department, but general enough to handle other departments, if it becomes necessary. Also includes a single post and post type archive template, which can be overridden by a template in a given theme.

## Templates
There is a basic single template and archive template included with the plugin, but those can be easily preempted by templates included in a given custom theme.

## Shortcode
The CPT also supports a shortcode that will return and display a list of graduates fitting certain criteria. The base shortcode is `[graduate-list]`, and it has a couple of options:

* `program`: This refers to the associated graduate program, to display only graduates from a specific one. This is currently designed to use the Category feature of the post type, and will accept either the Category ID or the Category slug.
* `year`: The graduation year (YYYY).
* `semester`: The graduation semester (Spring, Summer, or Fall). This is case insensitive.
* `img_shape`: What shape the headshot image should take. Options are `circle` (default), `round-square` (for a square image with rounded corners), and `square` (for a normal, square-shaped image).