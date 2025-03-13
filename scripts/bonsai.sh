#!/bin/bash

# scripts/bonsai.sh
# Default to development if no environment specified
ENV="development"
FRESH_INSTALL=false
TEMPLATE="cypress" # Default template for fresh install

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Function to display ASCII art
display_ascii_art() {
    local template=$1
    local config_path="vendor/jackalopelabs/bonsai-cli/config/templates/${template}.yml"
    
    if [ -f "$config_path" ]; then
        # Extract and display ASCII art section
        echo ""
        awk '/ascii_art:/,/components:/' "$config_path" | 
        sed -n '/default: |/,/components:/p' |
        sed 's/\${green}/\x1b[32m/g' |
        sed 's/\${brown}/\x1b[33m/g' |
        sed 's/\${reset}/\x1b[0m/g' |
        sed '/components:/d' |
        sed '/default: |/d'
        echo ""
    fi
}

# Function to display confirmation prompt
confirm() {
    read -p "⚠️  This will remove all existing Bonsai components. Are you sure? [y/N] " -n 1 -r
    echo
    [[ $REPLY =~ ^[Yy]$ ]]
}

# Parse arguments
ARGS=()
INSTALL_KANBAN=false
while [[ $# -gt 0 ]]; do
    case $1 in
        --env=*)
        ENV="${1#*=}"
        shift
        ;;
        --development|--staging|--production)
        ENV="${1#--}"
        shift
        ;;
        --fresh)
        FRESH_INSTALL=true
        shift
        ;;
        --template=*)
        TEMPLATE="${1#*=}"
        shift
        ;;
        --force)
        FORCE=true
        shift
        ;;
        --install-kanban)
        INSTALL_KANBAN=true
        shift
        ;;
        *)
        ARGS+=("$1")
        shift
        ;;
    esac
done

# Validate environment
case $ENV in
    development|staging|production)
        ALIAS="@$ENV"
        BUILD_NEEDED=$([[ "$ENV" == "development" ]] && echo "true" || echo "false")
        echo -e "${GREEN}🌳 Using environment: $ENV${NC}"
        ;;
    *)
        echo -e "${YELLOW}⚠️  Invalid environment: $ENV${NC}"
        echo "Usage: ./scripts/bonsai.sh [command] [--env=development|staging|production]"
        echo "Defaulting to development environment..."
        ENV="development"
        ALIAS="@development"
        BUILD_NEEDED=true
        ;;
esac

# Function to detect build system
detect_build_system() {
    if [ -f "vite.config.js" ] || [ -f "vite.config.ts" ]; then
        echo "vite"
    elif [ -f "bud.config.js" ] || [ -f "bud.config.ts" ]; then
        echo "bud"
    else
        echo "unknown"
    fi
}

# Function to check if kanban component is in config
check_kanban_in_config() {
    local template=$1
    local config_path="vendor/jackalopelabs/bonsai-cli/config/templates/${template}.yml"
    local local_config_path="config/bonsai/templates/${template}.yml"
    local legacy_config_path="config/bonsai/${template}.yml"
    
    # Check for config file in different possible locations
    if [ -f "$local_config_path" ]; then
        config_path="$local_config_path"
    elif [ -f "$legacy_config_path" ]; then
        config_path="$legacy_config_path"
    elif [ ! -f "$config_path" ]; then
        echo -e "${YELLOW}⚠️ Could not find config file for template: $template${NC}"
        return 1
    fi
    
    echo -e "${GREEN}✅ Found config file at: $config_path${NC}"
    
    # Check if kanban_board is in the components list
    if grep -q "kanban_board" "$config_path"; then
        echo -e "${GREEN}✅ Kanban component found in config${NC}"
        return 0
    else
        echo -e "${YELLOW}⚠️ Kanban component not found in config${NC}"
        return 1
    fi
}

# Function to install kanban dependencies
install_kanban_deps() {
    echo -e "${GREEN}📦 Installing Kanban Board dependencies...${NC}"
    
    # For Radicle/Sage projects, we can install directly in the current directory
    if [ -f "package.json" ] && [ -d "resources" ]; then
        echo -e "${GREEN}✅ Detected Radicle/Sage theme structure in current directory${NC}"
        
        # Install SortableJS
        echo -e "${GREEN}📦 Installing SortableJS...${NC}"
        if command -v yarn &> /dev/null; then
            yarn add sortablejs
        else
            npm install sortablejs --save
        fi
        
        # Check if we need to install Alpine.js sort plugin
        if grep -q "@alpinejs/sort" "package.json"; then
            echo -e "${GREEN}✅ @alpinejs/sort is already installed.${NC}"
        else
            echo -e "${GREEN}📦 Installing @alpinejs/sort...${NC}"
            if command -v yarn &> /dev/null; then
                yarn add @alpinejs/sort
            else
                npm install @alpinejs/sort --save
            fi
        fi
        
        echo -e "${GREEN}✅ Kanban dependencies installed successfully!${NC}"
        echo -e "${YELLOW}⚠️ Don't forget to import and register the Alpine.js sort plugin in your main JS file:${NC}"
        echo -e "${YELLOW}
import Alpine from 'alpinejs'
import sort from '@alpinejs/sort'

// Register the plugin
Alpine.plugin(sort)
${NC}"
        
        # Check if resources/js/app.js exists and offer to add the import
        if [ -f "resources/js/app.js" ]; then
            echo -e "${GREEN}✅ Found resources/js/app.js${NC}"
            echo -e "${YELLOW}Would you like to automatically add the Alpine.js sort plugin import to your app.js? [y/N]${NC}"
            read -n 1 -r
            echo
            if [[ $REPLY =~ ^[Yy]$ ]]; then
                # Check if Alpine is already imported (both uppercase and lowercase)
                if grep -q "import [Aa]lpine from ['\"]alpinejs['\"]" "resources/js/app.js" || grep -q "import sort from ['\"]@alpinejs/sort['\"]" "resources/js/app.js"; then
                    # If sort is already imported, just notify the user
                    if grep -q "import sort from ['\"]@alpinejs/sort['\"]" "resources/js/app.js"; then
                        echo -e "${GREEN}✅ Alpine.js sort plugin is already imported in app.js${NC}"
                    else
                        # Add sort import after Alpine import
                        sed -i.bak '/import [Aa]lpine from/a import sort from '"'"'@alpinejs/sort'"'"'' "resources/js/app.js"
                        echo -e "${GREEN}✅ Added Alpine.js sort import to app.js${NC}"
                    fi
                    
                    # If plugin is already registered, just notify the user
                    if grep -q "[Aa]lpine.plugin(sort)" "resources/js/app.js"; then
                        echo -e "${GREEN}✅ Alpine.js sort plugin is already registered in app.js${NC}"
                    else
                        # Add plugin registration before Alpine.start()
                        sed -i.bak '/[Aa]lpine.start/i Alpine.plugin(sort)' "resources/js/app.js"
                        echo -e "${GREEN}✅ Added Alpine.js sort plugin registration to app.js${NC}"
                    fi
                else
                    echo -e "${YELLOW}⚠️ Could not find Alpine.js import in app.js. Please add the import manually.${NC}"
                fi
            fi
        fi
        
        return 0
    fi
    
    # If we're not in a Radicle/Sage theme, try to find the theme directory
    THEME_DIR=""
    if [ -d "web/app/themes" ]; then
        # Bedrock structure
        THEME_DIR="web/app/themes"
    elif [ -d "wp-content/themes" ]; then
        # Standard WordPress structure
        THEME_DIR="wp-content/themes"
    else
        echo -e "${YELLOW}⚠️ Could not find themes directory. Make sure you're in a Roots project.${NC}"
        return 0
    fi
    
    # Try to find the active theme
    ACTIVE_THEME=$(wp $ALIAS option get template 2>/dev/null)
    
    if [ -z "$ACTIVE_THEME" ]; then
        echo -e "${YELLOW}⚠️ Could not determine active theme. Skipping kanban dependencies.${NC}"
        return 0
    fi
    
    THEME_PATH="$THEME_DIR/$ACTIVE_THEME"
    
    if [ ! -d "$THEME_PATH" ]; then
        echo -e "${YELLOW}⚠️ Theme directory not found: $THEME_PATH. Skipping kanban dependencies.${NC}"
        return 0
    fi
    
    echo -e "${GREEN}✅ Found theme at: $THEME_PATH${NC}"
    
    # Check if package.json exists
    if [ ! -f "$THEME_PATH/package.json" ]; then
        echo -e "${YELLOW}⚠️ No package.json found in theme directory. Skipping kanban dependencies.${NC}"
        return 0
    fi
    
    # Install SortableJS
    echo -e "${GREEN}📦 Installing SortableJS...${NC}"
    cd "$THEME_PATH"
    if command -v yarn &> /dev/null; then
        yarn add sortablejs
    else
        npm install sortablejs --save
    fi
    
    # Check if we need to install Alpine.js sort plugin
    if grep -q "@alpinejs/sort" "package.json"; then
        echo -e "${GREEN}✅ @alpinejs/sort is already installed.${NC}"
    else
        echo -e "${GREEN}📦 Installing @alpinejs/sort...${NC}"
        if command -v yarn &> /dev/null; then
            yarn add @alpinejs/sort
        else
            npm install @alpinejs/sort --save
        fi
    fi
    
    echo -e "${GREEN}✅ Kanban dependencies installed successfully!${NC}"
    echo -e "${YELLOW}⚠️ Don't forget to import and register the Alpine.js sort plugin in your main JS file:${NC}"
    echo -e "${YELLOW}
import Alpine from 'alpinejs'
import sort from '@alpinejs/sort'

// Register the plugin
Alpine.plugin(sort)
${NC}"
    
    return 0
}

# Function to run yarn build
run_build() {
    echo -e "${GREEN}🏗️  Rebuilding assets locally...${NC}"
    
    BUILD_SYSTEM=$(detect_build_system)
    
    if [ "$BUILD_SYSTEM" = "vite" ]; then
        if ! yarn build; then
            echo -e "${RED}❌ Vite asset build failed${NC}"
            return 1
        fi
        echo -e "${GREEN}✅ Vite asset build completed${NC}"
    elif [ "$BUILD_SYSTEM" = "bud" ]; then
        if ! yarn bud build production; then
            echo -e "${RED}❌ Bud asset build failed${NC}"
            return 1
        fi
        echo -e "${GREEN}✅ Bud asset build completed${NC}"
    else
        echo -e "${YELLOW}⚠️ Unknown build system, attempting default build...${NC}"
        if ! yarn build; then
            echo -e "${RED}❌ Asset build failed${NC}"
            return 1
        fi
        echo -e "${GREEN}✅ Asset build completed${NC}"
    fi
    
    return 0
}

# Function to run a Bonsai command with proper error handling
run_bonsai_command() {
    local cmd=$1
    local step_name=$2
    echo -e "${GREEN}🌳 Running $step_name...${NC}"
    echo -e "${YELLOW}$cmd${NC}"
    
    if ! wp $ALIAS $cmd; then
        echo -e "${RED}❌ $step_name failed${NC}"
        return 1
    fi
    
    if [ "$BUILD_NEEDED" = true ]; then
        run_build || return 1
    fi
    
    echo -e "${GREEN}✅ $step_name completed${NC}"
    return 0
}

# Handle explicit kanban installation
if [ "$INSTALL_KANBAN" = true ]; then
    echo -e "${GREEN}🌳 Installing Kanban dependencies...${NC}"
    
    # If a template was specified, check that template
    if [ -n "$TEMPLATE" ]; then
        if check_kanban_in_config "$TEMPLATE"; then
            install_kanban_deps
        else
            echo -e "${YELLOW}⚠️ The specified template does not use the kanban component.${NC}"
            exit 1
        fi
    else
        # Otherwise, just install the dependencies without checking
        install_kanban_deps
    fi
    
    exit 0
fi

# Handle fresh install
if [ "$FRESH_INSTALL" = true ]; then
    echo -e "${GREEN}🌱 Preparing fresh Bonsai installation...${NC}"
    
    # Ask for confirmation unless --force is used
    if [ "$FORCE" != true ] && ! confirm; then
        echo -e "${YELLOW}❌ Fresh installation cancelled${NC}"
        exit 1
    fi
    
    # Run cleanup
    if ! run_bonsai_command "acorn bonsai:cleanup --force" "Cleanup"; then
        echo -e "${RED}❌ Fresh installation failed during cleanup${NC}"
        exit 1
    fi
    
    # Run init
    if ! run_bonsai_command "acorn bonsai:init" "Initialization"; then
        echo -e "${RED}❌ Fresh installation failed during initialization${NC}"
        exit 1
    fi
    
    # Run generate with template
    if ! run_bonsai_command "acorn bonsai:generate $TEMPLATE" "Template generation"; then
        echo -e "${RED}❌ Fresh installation failed during template generation${NC}"
        exit 1
    fi
    
    # Install kanban dependencies if the template uses the kanban component
    echo -e "${GREEN}🌳 Checking if template uses kanban component...${NC}"
    if check_kanban_in_config "$TEMPLATE"; then
        install_kanban_deps
    fi
    
    echo -e "${GREEN}✅ Fresh installation completed successfully!${NC}"
    exit 0
fi

# Normal command execution
echo -e "${GREEN}🌳 Running Bonsai command in $ENV environment...${NC}"
if ! wp $ALIAS "${ARGS[@]}"; then
    echo -e "${RED}❌ Command failed${NC}"
    exit 1
fi

# Check if this is a Bonsai command
if [[ "${ARGS[1]}" == "bonsai:"* ]]; then
    if [ "$BUILD_NEEDED" = true ]; then
        run_build || exit 1
        # Display ASCII art after successful build for generate command
        if [[ "${ARGS[1]}" == "bonsai:generate" ]]; then
            display_ascii_art "${ARGS[2]}"
            
            # Check if the template uses the kanban component and install dependencies if needed
            echo -e "${GREEN}🌳 Checking if template uses kanban component...${NC}"
            if check_kanban_in_config "${ARGS[2]}"; then
                install_kanban_deps
            fi
        fi
    fi
fi

exit 0 