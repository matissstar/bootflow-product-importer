#!/bin/bash
#
# Build script for Bootflow – Product XML & CSV Importer
# Creates FREE and PRO distribution builds
#
# The working directory is the PRO codebase. FREE version is generated at 
# build time by:
#   1. Copying all files
#   2. Overlaying free-overrides/ (features, config, processor)
#   3. Running free-strip.php (stubs PRO methods, removes PRO UI)
#   4. Excluding PRO-only files (AI providers, scheduler)
#
# Output structure (persistent, inspectable):
#   dist/
#     free/
#       bootflow-product-importer/       ← actual FREE plugin files
#       bootflow-product-importer.zip    ← ZIP for WordPress.org
#     pro/
#       bootflow-product-importer-pro/   ← actual PRO plugin files
#       bootflow-product-importer-pro.zip ← ZIP for bootflow.io
#
# Usage: ./build.sh
#

set -e

# Configuration
VERSION="0.9.2"
PLUGIN_SLUG="bootflow-product-importer"
PRO_SLUG="${PLUGIN_SLUG}-pro"

# Directories
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SRC_DIR="$SCRIPT_DIR"
DIST_DIR="$SCRIPT_DIR/dist"
FREE_DIR="$DIST_DIR/free"
PRO_DIR="$DIST_DIR/pro"
FREE_OVERRIDES="$SCRIPT_DIR/build-config/free-overrides"
FREE_STRIP="$SCRIPT_DIR/build-config/free-strip.php"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Building Bootflow Product Importer${NC}"
echo -e "${GREEN}Version: ${VERSION}${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

# Clean up previous builds
echo -e "${YELLOW}Cleaning previous builds...${NC}"
rm -rf "$DIST_DIR"
mkdir -p "$FREE_DIR" "$PRO_DIR"

# ════════════════════════════════════════════════════════════════════════════
# BUILD FREE VERSION (WordPress.org)
# ════════════════════════════════════════════════════════════════════════════
echo ""
echo -e "${GREEN}[1/4] Building FREE version...${NC}"

FREE_BUILD_DIR="$FREE_DIR/$PLUGIN_SLUG"
mkdir -p "$FREE_BUILD_DIR"

# Step 1: Copy all source files (excluding dev/build files and PRO-only files)
echo -e "${YELLOW}  Step 1: Copying source files...${NC}"
rsync -a \
    --exclude-from="$SCRIPT_DIR/build-config/free-exclude.txt" \
    --exclude=".git" \
    --exclude=".git/" \
    --exclude="dist/" \
    --exclude="build-temp/" \
    --exclude="build-config/" \
    --exclude="build.sh" \
    --exclude="build.sh.bak" \
    --exclude="PRO-features-list.html" \
    --exclude="PRO-features-list.odt" \
    --exclude="*.code-workspace" \
    --exclude="bootflow-product-page.html" \
    "$SRC_DIR/" "$FREE_BUILD_DIR/"

# Step 2: Overlay free-overrides (replaces PRO files with FREE stubs)
echo -e "${YELLOW}  Step 2: Applying FREE overrides...${NC}"
if [ -d "$FREE_OVERRIDES" ]; then
    for override_file in $(find "$FREE_OVERRIDES" -name '*.php' -o -name '*.txt' | sort); do
        rel_path="${override_file#$FREE_OVERRIDES/}"
        # Only copy files that were created from scratch (not copies for strip)
        case "$rel_path" in
            includes/class-bfpi-features.php|includes/class-bfpi-processor.php|includes/config/features.php|readme.txt)
                cp "$override_file" "$FREE_BUILD_DIR/$rel_path"
                echo "    Applied: $rel_path"
                ;;
        esac
    done
    echo -e "${GREEN}    Override files applied${NC}"
else
    echo -e "${RED}    WARNING: free-overrides directory not found!${NC}"
fi

# Step 3: Run free-strip.php (stubs PRO methods, removes PRO UI from large files)
echo -e "${YELLOW}  Step 3: Stripping PRO code...${NC}"
if [ -f "$FREE_STRIP" ]; then
    php "$FREE_STRIP" "$FREE_BUILD_DIR"
else
    echo -e "${RED}    WARNING: free-strip.php not found!${NC}"
fi

# Step 4: Update main plugin file for FREE edition
echo -e "${YELLOW}  Step 4: Setting FREE edition flags...${NC}"
MAIN_FILE="$FREE_BUILD_DIR/bootflow-product-importer.php"
if [ -f "$MAIN_FILE" ]; then
    sed -i "s/define('BFPI_VERSION', '[^']*');/define('BFPI_VERSION', '${VERSION}');/g" "$MAIN_FILE"
    sed -i "s/Version: .*/Version: ${VERSION}/g" "$MAIN_FILE"
fi

# Create FREE ZIP
echo -e "${YELLOW}  Creating ZIP archive...${NC}"
cd "$FREE_DIR"
zip -rq "$FREE_DIR/$PLUGIN_SLUG.zip" "$PLUGIN_SLUG"

FREE_SIZE=$(du -h "$FREE_DIR/$PLUGIN_SLUG.zip" | cut -f1)
echo -e "${GREEN}  ✓ FREE version: $PLUGIN_SLUG.zip ($FREE_SIZE)${NC}"
echo -e "${GREEN}    Directory: dist/free/$PLUGIN_SLUG/${NC}"

# ════════════════════════════════════════════════════════════════════════════
# BUILD PRO VERSION (bootflow.io)
# ════════════════════════════════════════════════════════════════════════════
echo ""
echo -e "${GREEN}[2/4] Building PRO version...${NC}"

PRO_BUILD_DIR="$PRO_DIR/$PRO_SLUG"
mkdir -p "$PRO_BUILD_DIR"

rsync -a \
    --exclude-from="$SCRIPT_DIR/build-config/pro-exclude.txt" \
    --exclude=".git" \
    --exclude=".git/" \
    --exclude="dist/" \
    --exclude="build-temp/" \
    --exclude="build-config/" \
    --exclude="build.sh" \
    --exclude="build.sh.bak" \
    --exclude="PRO-features-list.html" \
    --exclude="PRO-features-list.odt" \
    --exclude="*.code-workspace" \
    --exclude="bootflow-product-page.html" \
    "$SRC_DIR/" "$PRO_BUILD_DIR/"

PRO_MAIN="$PRO_BUILD_DIR/bootflow-product-importer.php"
if [ -f "$PRO_MAIN" ]; then
    sed -i "s/define('BFPI_VERSION', '[^']*');/define('BFPI_VERSION', '${VERSION}');/g" "$PRO_MAIN"
    sed -i "s/Plugin Name: .*/Plugin Name: Bootflow – Product XML \& CSV Importer Pro/g" "$PRO_MAIN"
    sed -i "s/Version: .*/Version: ${VERSION}/g" "$PRO_MAIN"
    mv "$PRO_MAIN" "$PRO_BUILD_DIR/$PRO_SLUG.php"
fi

cd "$PRO_DIR"
zip -rq "$PRO_DIR/$PRO_SLUG.zip" "$PRO_SLUG"

PRO_SIZE=$(du -h "$PRO_DIR/$PRO_SLUG.zip" | cut -f1)
echo -e "${GREEN}  ✓ PRO version: $PRO_SLUG.zip ($PRO_SIZE)${NC}"
echo -e "${GREEN}    Directory: dist/pro/$PRO_SLUG/${NC}"

# ════════════════════════════════════════════════════════════════════════════
# VERIFICATION
# ════════════════════════════════════════════════════════════════════════════
echo ""
echo -e "${GREEN}[3/4] Verifying builds...${NC}"

ERRORS=0

echo -e "${YELLOW}  Checking FREE version...${NC}"
cd "$FREE_DIR/$PLUGIN_SLUG"

# eval() check
EVAL_COUNT=$(grep -rn '\beval\s*(' --include='*.php' . 2>/dev/null | grep -v 'preg_\|wp_kses\|sanitize' | wc -l)
if [ "$EVAL_COUNT" -gt 0 ]; then
    echo -e "${RED}  ✗ eval() found in FREE version!${NC}"
    grep -rn '\beval\s*(' --include='*.php' . | grep -v 'preg_\|wp_kses\|sanitize'
    ERRORS=$((ERRORS + 1))
else
    echo -e "${GREEN}  ✓ No eval()${NC}"
fi

# AI providers check
[ -f "includes/class-bfpi-ai-providers.php" ] && { echo -e "${RED}  ✗ AI providers file present!${NC}"; ERRORS=$((ERRORS + 1)); } || echo -e "${GREEN}  ✓ No AI providers file${NC}"

# Scheduler check
[ -f "includes/class-bfpi-scheduler.php" ] && { echo -e "${RED}  ✗ Scheduler file present!${NC}"; ERRORS=$((ERRORS + 1)); } || echo -e "${GREEN}  ✓ No scheduler file${NC}"

# .git check
[ -d "$FREE_DIR/$PLUGIN_SLUG/.git" ] && { echo -e "${RED}  ✗ .git in FREE!${NC}"; ERRORS=$((ERRORS + 1)); } || echo -e "${GREEN}  ✓ No .git${NC}"

# build-config check
[ -d "$FREE_DIR/$PLUGIN_SLUG/build-config" ] && { echo -e "${RED}  ✗ build-config in FREE!${NC}"; ERRORS=$((ERRORS + 1)); } || echo -e "${GREEN}  ✓ No build-config${NC}"

# PHP syntax check
echo -e "${YELLOW}  PHP syntax check...${NC}"
cd "$FREE_DIR/$PLUGIN_SLUG"
SYNTAX_ERRORS=$(find . -name '*.php' -exec php -l {} \; 2>&1 | grep -c 'Parse error' || true)
if [ "$SYNTAX_ERRORS" -gt 0 ]; then
    echo -e "${RED}  ✗ $SYNTAX_ERRORS syntax errors!${NC}"
    find . -name '*.php' -exec php -l {} \; 2>&1 | grep 'Parse error'
    ERRORS=$((ERRORS + 1))
else
    echo -e "${GREEN}  ✓ All PHP syntax OK${NC}"
fi

echo -e "${YELLOW}  Checking PRO version...${NC}"
[ -d "$PRO_DIR/$PRO_SLUG/.git" ] && { echo -e "${RED}  ✗ .git in PRO!${NC}"; ERRORS=$((ERRORS + 1)); } || echo -e "${GREEN}  ✓ No .git in PRO${NC}"
[ -d "$PRO_DIR/$PRO_SLUG/build-config" ] && { echo -e "${RED}  ✗ build-config in PRO!${NC}"; ERRORS=$((ERRORS + 1)); } || echo -e "${GREEN}  ✓ No build-config in PRO${NC}"

# ════════════════════════════════════════════════════════════════════════════
# DONE
# ════════════════════════════════════════════════════════════════════════════
echo ""
echo -e "${GREEN}[4/4] Done — directories kept for inspection${NC}"

echo ""
if [ "$ERRORS" -gt 0 ]; then
    echo -e "${RED}Build completed with $ERRORS error(s)!${NC}"
    exit 1
fi

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Build Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "  � dist/free/${PLUGIN_SLUG}/     — FREE files (inspect directly)"
echo -e "  📦 dist/free/${PLUGIN_SLUG}.zip  — FREE ZIP (WordPress.org)"
echo -e "  📁 dist/pro/${PRO_SLUG}/    — PRO files (inspect directly)"
echo -e "  📦 dist/pro/${PRO_SLUG}.zip — PRO ZIP (bootflow.io)"
echo ""
