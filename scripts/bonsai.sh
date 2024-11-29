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

# Function to run yarn build
run_build() {
    echo -e "${GREEN}🏗️  Rebuilding assets locally...${NC}"
    if ! yarn build; then
        echo -e "${RED}❌ Asset build failed${NC}"
        return 1
    fi
    echo -e "${GREEN}✅ Asset build completed${NC}"
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
        fi
    fi
fi

exit 0 