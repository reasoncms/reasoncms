# Require any additional compass plugins here.
add_import_path "foundation/bower_components/foundation/scss"

# Set this to the root of your project when deployed:
http_path = "/"
css_dir = "stylesheets"
sass_dir = "stylesheets/scss"
images_dir = "images"
javascripts_dir = "javascripts"

# Add additional folders for compass to watch
add_import_path "stylesheets/scss/dependencies"
add_import_path "stylesheets/scss/modules"
add_import_path "stylesheets/scss/font-awesome/"
add_import_path "stylesheets/scss/sites"

# You can select your preferred output style here (can be overridden via the command line):
# output_style = :expanded or :nested or :compact or :compressed
output_style = :expanded

# To enable relative paths to assets via compass helper functions. Uncomment:
# relative_assets = true

# To disable debugging comments that display the original location of your selectors. Uncomment:
line_comments = true

# If you prefer the indented syntax, you might want to regenerate this
# project again passing --syntax sass, or you can uncomment this:
# preferred_syntax = :sass
# and then run:
# sass-convert -R --from scss --to sass sass scss && rm -rf sass && mv scss sass