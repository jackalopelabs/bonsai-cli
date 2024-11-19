# 🌳 Bonsai CLI

A WordPress CLI tool for generating and managing reusable components, sections, and layouts in Roots-based WordPress projects.

## Installation

Install via Composer:

```bash
composer require jackalopelabs/bonsai-cli
```

## Features

- ⚡️ Quick setup with `bonsai:init`
- 🧱 Pre-built components library
- 📑 Template generation system
- 🎨 Section builder with dynamic data
- 📐 Layout management
- 🧹 Cleanup utilities

## Commands

### Initialize Project

```bash
wp acorn bonsai:init
```

This sets up your project with:
- Base component library
- Example sections
- Default layouts
- Components page
- Local configuration directory

### Generate Site Template

```bash
wp acorn bonsai:generate [template]
```

Available templates:
- `cypress` - Modern SaaS landing page
- `jackalope` - Agency/portfolio site

Options:
- `--config=path/to/config.yml` - Use custom config file

### Create Components

```bash
wp acorn bonsai:component [name]
```

Available components:
- hero
- faq
- cta
- card-featured
- slideshow
- widget
- table

### Create Sections

```bash
wp acorn bonsai:section [name] --component=[component] [--default]
```

Options:
- `--component` - Specify component type
- `--default` - Use default configuration without prompting

### Create Layouts

```bash
wp acorn bonsai:layout [name] --sections=[section1,section2]
```

### Cleanup

```bash
wp acorn bonsai:cleanup [--force]
```

Removes all generated:
- Components
- Sections
- Layouts
- Pages
- Templates
- Menu items

## Project Structure

```
resources/views/
├── bonsai/
│   ├── components/
│   ├── sections/
│   └── layouts/
└── templates/
```

## Configuration

Create custom site configurations in `config/bonsai/`:

```yaml
name: My Site
description: Site description
version: 1.0.0

components:
  - hero
  - faq
  - slideshow

sections:
  home_hero:
    component: hero
    data:
      title: "Welcome"
      # ... component-specific data

layouts:
  main:
    sections:
      - home_hero
      - features

pages:
  home:
    title: "Home"
    layout: main
```

## Best Practices

1. Always run `bonsai:cleanup` before regenerating a site
2. Use version control to track your configurations
3. Store sensitive data in `.env` rather than config files
4. Create reusable sections for common patterns

## Compatibility

- WordPress 6.0+
- Roots Stack (Sage, Bedrock, or Radicle)
- PHP 8.0+
- Composer 2.0+

## License

MIT License. See [LICENSE](LICENSE.md) for more information.