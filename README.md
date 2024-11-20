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

## Detailed Examples

### 1. Building a Landing Page

```bash
# Initialize Bonsai
wp acorn bonsai:init

# Create sections using default content
wp acorn bonsai:section home_hero --component=hero --default
wp acorn bonsai:section features --component=card-featured --default
wp acorn bonsai:section faq --component=faq --default

# Create a layout combining the sections
wp acorn bonsai:layout landing --sections=home_hero,features,faq

# Generate the complete site
wp acorn bonsai:generate custom --config=config/bonsai/landing.yml
```

### 2. Custom Section Configuration

```yaml
# config/bonsai/custom-hero.yml
sections:
  home_hero:
    component: hero
    data:
      title: "Welcome to Our Platform"
      subtitle: "The Future of Web Development"
      description: "Build better websites faster with our tools"
      imagePath: "images/hero-main.jpg"
      l1: "Easy to Use"
      l2: "Fully Customizable"
      l3: "Built for Speed"
      l4: "SEO Optimized"
      primaryText: "Get Started"
      primaryLink: "#signup"
      secondaryText: "Learn More"
```

### 3. Dynamic Component Generation

```php
// Create a custom FAQ section
wp acorn bonsai:section product_faq --component=faq

// Example responses to prompts:
// Title: Product FAQ
// Number of FAQs: 3
// FAQ #1 Question: How do I install the product?
// FAQ #1 Answer: Installation is simple...
```

### 4. Advanced Layout Configuration

```php
// Create a complex page layout
wp acorn bonsai:layout documentation --sections=doc_header,api_reference,code_examples,support_faq

// The layout will be generated at:
// resources/views/bonsai/layouts/documentation.blade.php
```

## Troubleshooting

### Common Issues

1. **WSOD (White Screen of Death) After Generation**
   ```bash
   # First, check theme settings
   wp option get template
   wp option get stylesheet
   
   # If incorrect, clean up and regenerate
   wp acorn bonsai:cleanup --force
   wp acorn bonsai:generate [template]
   ```

2. **Missing Components**
   ```bash
   # Verify component installation
   ls resources/views/bonsai/components
   
   # Reinstall specific component
   wp acorn bonsai:component [name]
   ```

3. **Layout Not Finding Sections**
   ```bash
   # Check section paths
   ls resources/views/bonsai/sections
   
   # Regenerate sections if missing
   wp acorn bonsai:section [name] --component=[type] --default
   ```

4. **Asset Path Issues**
   ```bash
   # Verify public path in bud.config.ts
   .setPublicPath(`/content/themes/[theme-name]/public/`)
   ```

5. **Database Cleanup Issues**
   ```bash
   # Force cleanup and reset
   wp acorn bonsai:cleanup --force
   wp cache flush
   ```

### Debug Steps

1. **Component Generation**
   - Check component exists in package templates
   - Verify component schema in SectionCommand
   - Ensure proper permissions on directories

2. **Section Building**
   - Validate section data format
   - Check for missing dependencies
   - Verify blade template syntax

3. **Layout Issues**
   - Confirm section files exist
   - Check section naming consistency
   - Verify blade includes syntax

4. **General Debug**
   ```bash
   # Enable WordPress debug mode
   wp config set WP_DEBUG true --raw
   wp config set WP_DEBUG_LOG true --raw
   
   # Check logs
   tail -f wp-content/debug.log
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