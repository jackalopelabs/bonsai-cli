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
- accordion
- card
- cta
- featured-grid
- header
- hero
- list-item
- pricing-box
- widget

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

# Bonsai Script

A wrapper script for running Bonsai CLI commands across different environments with automatic asset rebuilding.

## Usage

From your project root:

```bash
./scripts/bonsai.sh <command> [--env=environment]
```

### Environments

- `--env=development` (default)
- `--env=staging`
- `--env=production`

You can also use the shorthand:
- `--development`
- `--staging`
- `--production`

### Examples

```bash
# Initialize Bonsai in development (default)
./scripts/bonsai.sh acorn bonsai:init

# Generate a site using the cypress template on staging
./scripts/bonsai.sh acorn bonsai:generate cypress --env=staging

# Clean up Bonsai files in production
./scripts/bonsai.sh acorn bonsai:cleanup --env=production
```

### Asset Building

- Development environment automatically rebuilds assets after Bonsai commands
- Staging and production environments skip asset rebuilding
- Asset rebuilding is only triggered for Bonsai-specific commands

### Command Structure

```
./scripts/bonsai.sh [command] [--env=environment]

command:          The Bonsai command to execute (e.g., acorn bonsai:init)
--env:           Target environment (development|staging|production)
```

## Features

- Environment-specific command execution
- Automatic asset rebuilding in development
- Clear feedback and error messages
- Defaults to development environment if none specified
- Maintains proper exit codes from WP-CLI commands

## Requirements

- WP-CLI with configured environments (@development, @staging, @production)
- Yarn for asset building (development only)
- Proper SSH access to remote environments

## Notes

- Run the script from your project root directory
- Make sure the script is executable (`chmod +x scripts/bonsai.sh`)
- Asset rebuilding only occurs in development and only for Bonsai commands

## Configuration Guide

### Understanding YAML Templates

Bonsai uses YAML configuration files to define components, layouts, and pages. Here's a comprehensive guide on creating and modifying these templates.

### Core Structure

Every Bonsai template requires these top-level keys:

```yaml
name: "Your Project Name"
description: "Project description"
version: "0.0.1"
components: []
sections: {}
layouts: {}
pages: {}
wordpress: {}
assets: {}
```

### Available Components

Bonsai includes these standard components:
- hero
- header
- card
- widget
- pricing-box

### Component Data Structures

#### Header Component
```yaml
site_header:
  component: header
  data:
    siteName: string
    iconComponent: string # heroicon format
    navLinks: 
      - url: string
        label: string
    primaryLink: string
    containerClasses: string
    containerInnerClasses: string
```

#### Hero Component
```yaml
home_hero:
  component: hero
  data:
    title: string
    subtitle: string
    description: string
    imagePaths: string # Single path, not array
    buttonText: string
    buttonLink: string
    secondaryText: string
    secondaryLink: string
    buttonLinkIcon: boolean
    secondaryIcon: boolean
    iconMappings:
      dropdownIcon: string
      buttonLinkIcon: string
      secondaryIcon: string
```

#### Card Component
```yaml
services_card:
  component: card
  data:
    title: string
    subtitle: string
    features:
      - icon: string
        title: string
        description: string
    featureItems:
      - icon: string
        title: string
        description: string
```

#### Widget Component
```yaml
features_widget:
  component: widget
  data:
    items:
      - id: string
        title: string
        icon: string
        content: string
        cta:
          title: string
          link: string
          imagePath: string # Single path, not array
        description: string
        listItems:
          - number: integer
            itemName: string
            text: string
```

#### Pricing Component
```yaml
pricing:
  component: pricing
  data:
    title: string
    subtitle: string
    description: string
    pricingBoxes:
      - icon: string
        iconColor: string
        planType: string
        price: string
        features: string[]
        ctaLink: string
        ctaText: string
        ctaColor: string
        iconBtn: string
        iconBtnColor: string
```

### Icon Usage

Bonsai uses Blade UI Kit's Heroicons implementation. Icons must be specified in one of these formats:

```yaml
# Correct icon formats:
iconComponent: "heroicon-o-medical-bag"    # Outline style
iconComponent: "heroicon-m-medical-bag"    # Mini style
iconComponent: "heroicon-s-medical-bag"    # Solid style

# For icon mappings:
iconMappings:
  dropdownIcon: "heroicon-o-chevron-down"      # Outline
  buttonLinkIcon: "heroicon-o-arrow-right"     # Outline
  secondaryIcon: "heroicon-o-information-circle" # Outline
```

Common icon names:
- `arrow-right`
- `chevron-down`
- `information-circle`
- `shopping-cart`
- `user`
- `cog`
- `home`
- `mail`

Best practices:
1. Use outline (`-o-`) icons for most cases
2. Avoid mixing styles within the same component
3. Test icons in development before deploying
4. Check [Heroicons.com](https://heroicons.com) for available icons
5. Use exact icon names from Heroicons (e.g., `information-circle` not `info-circle`)

### Common Pitfalls

1. Image paths must be strings, not arrays
2. Component names must match exactly
3. All required fields must be present
4. Maintain proper YAML indentation
5. Use consistent data types
6. **Use correct Heroicon format and names**

### Best Practices

1. Study working examples (bonsai.yml, cypress.yml)
2. Keep consistent formatting
3. Use descriptive names
4. Include all required sections
5. Test component compatibility
6. Maintain proper nesting levels

### Testing Your Configuration

1. Compare against working templates
2. Verify all required fields
3. Check data type consistency
4. Validate component names
5. Ensure proper section references in layouts

### Template Lookup Order

When running `bonsai:generate [template]`, Bonsai looks for template files in this order:

1. `/config/bonsai/templates/{template}.yml` - Local project templates
2. `/config/bonsai/{template}.yml` - Legacy local config
3. `/config/templates/{template}.yml` - Legacy templates
4. Package default templates

This means you can:
- Override any package template by creating a local version
- Keep your custom templates separate from package defaults
- Maintain your own template library in `/config/bonsai/templates/`
- Fall back to package templates when no local version exists

Example:
```bash
# Use a local template
wp acorn bonsai:generate custom

# Use a package template
wp acorn bonsai:generate cypress
```