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

# In the normal command execution section, after build:
if [[ "${ARGS[1]}" == "bonsai:"* ]]; then
    if [ "$BUILD_NEEDED" = true ]; then
        run_build || exit 1
        # Display ASCII art after successful build
        if [[ "${ARGS[1]}" == "bonsai:generate" ]]; then
            display_ascii_art "${ARGS[2]}"
        fi
    fi
fi 