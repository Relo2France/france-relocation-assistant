#!/bin/bash
# embed-logo.sh
# Embeds the relo2france logo into a JSX file
# Usage: ./embed-logo.sh input.jsx output.jsx

if [ $# -lt 2 ]; then
    echo "Usage: $0 <input.jsx> <output.jsx>"
    echo "Example: $0 dordogne-overview.jsx /mnt/user-data/outputs/dordogne-overview.jsx"
    exit 1
fi

INPUT_FILE="$1"
OUTPUT_FILE="$2"
LOGO_BASE64="/home/claude/logo_png_base64.txt"

# Check if logo base64 exists, if not, generate it
if [ ! -f "$LOGO_BASE64" ]; then
    echo "Generating logo base64..."
    convert /mnt/user-data/uploads/relo2france_updated_logo.png /home/claude/logo.png
    base64 -w 0 /home/claude/logo.png > "$LOGO_BASE64"
fi

# Embed logo using Node.js
node -e "
const fs = require('fs');
const logoBase64 = fs.readFileSync('$LOGO_BASE64', 'utf8');
let jsx = fs.readFileSync('$INPUT_FILE', 'utf8');
const logoDataUri = 'data:image/png;base64,' + logoBase64;
jsx = jsx.replace(/const logo = \"[^\"]*\";/, 'const logo = \"' + logoDataUri + '\";');
fs.writeFileSync('$OUTPUT_FILE', jsx);
console.log('Logo embedded successfully: $OUTPUT_FILE');
"
