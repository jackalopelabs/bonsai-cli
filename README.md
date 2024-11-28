# 🌳 Bonsai CLI

A WordPress CLI tool for generating and managing reusable components in Roots-based projects.

## Features

- 🎨 Component Generation
- 📦 Section Builder
- 🏗️ Template System
- 🔧 WordPress Integration

## Installation

```bash
composer require jackalopelabs/bonsai-cli
```

## Basic Usage

```bash
# Initialize a new Bonsai project
./scripts/bonsai.sh acorn bonsai:init

# Generate a site from a template
./scripts/bonsai.sh acorn bonsai:generate bonsai --env=development

# Clean up generated files
./scripts/bonsai.sh acorn bonsai:cleanup --force
```

## Configuration

### Local Configuration
Create a local configuration file in your project:
```yaml
# config/bonsai/custom.yml
name: Custom Template
components:
  - hero
  - header
sections:
  site_header:
    component: header
    data:
      siteName: "Your Site"
# ...
```

### Using Templates
Bonsai comes with pre-built templates:
- bonsai.yml (Default template)
- cypress.yml (Modern SaaS template)

## Component Development

### Adding New Components

1. Create component template:
```php
// templates/components/pricing-box.blade.php
@props([
    'title',
    'price',
    'features'
])
// Component markup...
```

2. Add to bonsai.yml:
```yaml
components:
  - pricing-box

sections:
  pricing:
    component: pricing
    data:
      title: "Pricing"
      # ...
```

3. Register in BonsaiServiceProvider:
```php
Blade::component('bonsai.components.pricing-box', 'pricing-box');
```

### Component Structure
- Components should be self-contained
- Use props for data injection
- Follow Blade component conventions
- Include fallback options for icons/images

### Icons Support
Bonsai supports both Heroicons and custom SVG icons:

```bash
# Install Heroicons (optional)
composer require blade-ui-kit/blade-heroicons

# Publish configuration
wp @development acorn vendor:publish --tag=blade-icons
```

## Contributing

### Development Setup
1. Clone the repository
2. Install dependencies
3. Run tests

### Adding Components to Bonsai CLI
1. Create component in templates/components/
2. Add section template if needed
3. Update bonsai.yml with component configuration
4. Add documentation
5. Submit PR

### Testing
```bash
composer test
```

## License

MIT License. See [LICENSE](LICENSE) for more information.