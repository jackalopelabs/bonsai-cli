
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

## ASCII Bonsai Tree Generator (Beta)

Generate beautiful ASCII art bonsai trees inspired by [cbonsai](https://gitlab.com/jallbrit/cbonsai). Each tree is unique, animated during growth, and can be associated with your site configurations.

### Current Status
- ✅ Basic tree generation with animation
- ✅ ANSI color support
- ✅ Tree persistence
- ✅ Command-line interface
- ⚠️ Growth patterns need improvement
- ⚠️ Branch distribution needs work
- ⚠️ Leaf clustering needs refinement

### Roadmap
1. Growth Algorithm (Priority)
   - [ ] Match cbonsai's organic growth patterns
   - [ ] Improve branch distribution
   - [ ] Better leaf clustering
   - [ ] Fix trunk proportions

2. Seasonal Variations
   - [ ] Spring: Flowering themes (`❀`, `✿`, `♠`)
   - [ ] Summer: Lush growth (`☘`, `❦`, `❧`)
   - [ ] Fall: Autumn colors (`✾`, `❁`, `⚘`)
   - [ ] Winter: Sparse, elegant (`❄`, `❆`, `❅`)

3. Age Categories
   - [ ] Young: Fresh growth, smaller size
   - [ ] Mature: Balanced proportions
   - [ ] Ancient: Complex branching, larger size

4. Styles
   - [ ] Formal upright (chokkan)
   - [ ] Informal upright (moyogi)
   - [ ] Slanting (shakan)
   - [ ] Cascade (kengai)
   - [ ] Semi-cascade (han-kengai)

### Usage

```bash
# Generate a new tree
wp @development acorn bonsai:tree generate --config=my-tree

# Generate with specific style
wp @development acorn bonsai:tree generate --config=my-tree --style=formal

# List all stored trees
wp @development acorn bonsai:tree list

# Age an existing tree
wp @development acorn bonsai:tree age --config=my-tree
```

### Configuration
```bash
--style    : Tree style (formal, informal, slanting)
--seed     : Random seed for reproducible trees
--speed    : Animation speed in seconds
--age      : Age category (young, mature, ancient)
--season   : Season variation (spring, summer, fall, winter)
```

### Contributing to Tree Generator
The tree generator is currently in beta. Key areas for contribution:
1. Growth algorithm improvements
2. Branch and leaf distribution
3. Animation refinement
4. Style variations
5. Seasonal implementations

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