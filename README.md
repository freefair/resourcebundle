# Symfony Resource Bundle

## Configuration
```yml
resource_bundle:
    asset_dir: "assets"                             # path to directory where assets are located
    bower_dir: "assets/bower"                       # path to directory where to find bower-components
    bundles:
        style:
            minify: false                           # If true bundled files will minified before delivery
            debug: true                             # If true files are not bundled
            name: "TestCssBundle"                   # Just a free text
            type: "text/css"                        # Mime-Type of files containing in this bundle
            files:
                - "bower:angular-material"          # Bower component matching
                - "css/style.css"                   # Simple file matching
                - "css/*.css"                       # Wildcard file matching (** matching sub directories)
                - "file:regex:css/([^/]+).css"      # Regular expression file matching
```

## Usage in TWIG
```twig
{{ renderBundle("style") }}
```
