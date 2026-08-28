# Markdown Admonitions for Grav

Adds Markdown admonition blocks to Grav 2.0+.

## Usage

Write an admonition with three exclamation marks, a type, and an indented body:

```markdown
!!! note
    This is a simple note.

!!! warning "Please read this first"
    The title is optional and can contain **Markdown**.
```

The plugin also supports collapsible blocks. Use `???` for a block that starts
closed and `???+` for one that starts open:

```markdown
???+ tip "A useful tip"
    This section is expanded initially, but visitors can collapse it.
```

The body must be indented by four spaces. An empty title (`""`) hides the
header on a regular admonition. Admonitions can be nested by indenting the
inner block:

```markdown
!!! note "Outer note"
    Regular content.

    !!! info "Inner note"
        This block is nested inside the outer one.
```

Built-in types are `note`, `info`, `tip`, `success`, `warning`, `danger`,
`question`, `failure`, `bug`, `example`, `quote`, and `abstract`. Unknown types
use a neutral fallback style.

## Installation

Download `markdown-admonitions.zip` from the latest GitHub release and extract
it into your Grav installation's `user/plugins` directory. The archive is a
drop-in package and creates:

```text
user/plugins/markdown-admonitions/
```

Then enable the plugin in **Admin → Plugins → Markdown Admonitions**. You can
also copy this directory directly to `user/plugins/markdown-admonitions`.

## Configuration

The default configuration is in `markdown-admonitions.yaml`. Override it in:

```text
user/config/plugins/markdown-admonitions.yaml
```

The Admin panel provides color pickers for every built-in type. Set
`built_in_css: false` to provide your own stylesheet. Public CSS variables
include `--admonition-radius`, `--admonition-padding`, and
`--admonition-accent`. Custom types and icons can also be configured; custom
SVG files are sanitized before rendering.

## Inspiration

The `!!!` / `???` authoring syntax and the set of common admonition types are
openly inspired by the [Admonitions documentation in Zensical](https://zensical.org/docs/authoring/admonitions/).
This Grav plugin is an independent implementation and is not affiliated with
Zensical.

## Development

Run `composer install` and `composer test` from a Grav 2 development
installation, which provides Grav's Markdown classes. The plugin requires PHP
8.3 or newer and Grav 2.0 or newer.

## License

The plugin code is MIT. See [LICENSE](LICENSE). The bundled icons are from
[Tabler Icons](https://github.com/tabler/tabler-icons), also under the MIT
license; see [assets/icons/LICENSE](assets/icons/LICENSE).
