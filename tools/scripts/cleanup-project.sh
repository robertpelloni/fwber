#!/bin/bash
# FWBer Project Cleanup Script
# Generated: 2025-11-15
#
# This script reorganizes the project structure by:
# - Removing backup/log files
# - Archiving outdated documentation
# - Organizing test files
# - Consolidating tools and scripts
#
# IMPORTANT: Review CLEANUP_PLAN.md before running!

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Counters
FILES_REMOVED=0
FILES_MOVED=0
DIRS_CREATED=0

echo -e "${GREEN}=== FWBer Project Cleanup Script ===${NC}"
echo ""

# Safety check - ensure we're in the right directory
if [ ! -f "README.md" ] || [ ! -d "fwber-backend" ]; then
    echo -e "${RED}ERROR: Not in FWBer project root directory!${NC}"
    exit 1
fi

# Check if git is clean
if ! git diff-index --quiet HEAD --; then
    echo -e "${YELLOW}WARNING: You have uncommitted changes.${NC}"
    echo "It's recommended to commit or stash changes before cleanup."
    read -p "Continue anyway? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Cleanup cancelled."
        exit 1
    fi
fi

echo -e "${YELLOW}Creating backup...${NC}"
BACKUP_FILE="fwber-backup-$(date +%Y%m%d-%H%M%S).tar.gz"
tar --exclude=node_modules --exclude=vendor --exclude=.git -czf "../$BACKUP_FILE" .
echo -e "${GREEN}Backup created: ../$BACKUP_FILE${NC}"
echo ""

# Create cleanup branch
BRANCH_NAME="cleanup/$(date +%Y-%m-%d)"
echo -e "${YELLOW}Creating branch: $BRANCH_NAME${NC}"
git checkout -b "$BRANCH_NAME" 2>/dev/null || git checkout "$BRANCH_NAME"
echo ""

# Function to create directory if it doesn't exist
create_dir() {
    if [ ! -d "$1" ]; then
        mkdir -p "$1"
        ((DIRS_CREATED++))
        echo -e "${GREEN}Created directory: $1${NC}"
    fi
}

# Function to move file
move_file() {
    if [ -f "$1" ]; then
        mv "$1" "$2"
        ((FILES_MOVED++))
        echo "  Moved: $1 → $2"
    fi
}

# Function to remove file
remove_file() {
    if [ -f "$1" ]; then
        rm "$1"
        ((FILES_REMOVED++))
        echo "  Removed: $1"
    fi
}

echo -e "${YELLOW}=== Phase 1: Remove Backup & Log Files ===${NC}"
remove_file ".gitignore.bak"
remove_file "_names.php.bak"
remove_file "hs_err_pid38224.log"
remove_file "mcp_ps_seq.log"
remove_file "mcp_seq_wrapper.log"
remove_file "mcp_test_error.txt"
remove_file "mcp_test_output.txt"
remove_file "test_error.txt"
remove_file "test_output.txt"
remove_file "test_prompt.txt"
remove_file "final_test_prompt.txt"
remove_file "codex_mcp_test_prompt.txt"
remove_file "test-token.txt"
remove_file "tatus"
git add -A
git commit -m "chore: remove backup and log files" 2>/dev/null || echo "No changes to commit"
echo ""

echo -e "${YELLOW}=== Phase 2: Create Directory Structure ===${NC}"
create_dir "archive/docs/2025-01-migration"
create_dir "archive/docs/features"
create_dir "archive/docs/ai-orchestration"
create_dir "archive/test-results"
create_dir "docs/deployment"
create_dir "docs/monitoring"
create_dir "docs/dashboard"
create_dir "docs/roadmap"
create_dir "tools/deployment"
create_dir "tools/config"
create_dir "tools/orchestration"
create_dir "tools/tasks"
create_dir "tests/scripts/powershell"
create_dir "tests/scripts/bash"
create_dir "tests/integration"
create_dir "tests/unit"
echo ""

echo -e "${YELLOW}=== Phase 3: Archive MCP Configuration Documentation ===${NC}"
move_file "AI_MODEL_INSTRUCTIONS_AND_COMMANDS.md" "archive/docs/2025-01-migration/"
move_file "API_CONFIGURATION_SOLUTION_2025_01_21.md" "archive/docs/2025-01-migration/"
move_file "CHROMA_SETUP_GUIDE.md" "archive/docs/2025-01-migration/"
move_file "CODEX_MCP_FIXES_2025_01_19.md" "archive/docs/2025-01-migration/"
move_file "COMPREHENSIVE_DEVELOPMENT_STATUS_2025_01_19.md" "archive/docs/2025-01-migration/"
move_file "COMPREHENSIVE_MEMORY_STORAGE_SUMMARY.md" "archive/docs/2025-01-migration/"
move_file "DEVELOPMENT_STATUS_2025_01_19.md" "archive/docs/2025-01-migration/"
move_file "FINAL_MCP_CLI_STATUS_REPORT_2025_01_19.md" "archive/docs/2025-01-migration/"
move_file "FWBER_API_KEY_ISSUES_AND_SOLUTIONS.md" "archive/docs/2025-01-migration/"
move_file "GEMINI_API_KEY_TROUBLESHOOTING_GUIDE.md" "archive/docs/2025-01-migration/"
move_file "KILOCODE_AUTHENTICATION_FIX_2025_01_19.md" "archive/docs/2025-01-migration/"
move_file "MCP_AND_CLI_FIXES_SUMMARY_2025_01_19.md" "archive/docs/2025-01-migration/"
move_file "MCP_SERVER_CONNECTION_FIX_GUIDE.md" "archive/docs/2025-01-migration/"
move_file "MCP_TOOL_COMPREHENSIVE_EVALUATION_2025_01_21.md" "archive/docs/2025-01-migration/"
git add -A
git commit -m "chore: archive MCP configuration documentation" 2>/dev/null || echo "No changes to commit"
echo ""

echo -e "${YELLOW}=== Phase 4: Archive Feature Documentation ===${NC}"
move_file "FWBER_ADVANCED_FEATURES_IMPLEMENTATION.md" "archive/docs/features/"
move_file "FWBER_ADVANCED_SECURITY_IMPLEMENTATION_COMPLETE.md" "archive/docs/features/"
move_file "FWBER_ADVANCED_SECURITY_IMPLEMENTATION_PLAN.md" "archive/docs/features/"
move_file "FWBER_BULLETIN_BOARD_DEMO.md" "archive/docs/features/"
move_file "FWBER_BULLETIN_BOARD_IMPLEMENTATION.md" "archive/docs/features/"
move_file "FWBER_CHATROOM_SYSTEM_IMPLEMENTATION.md" "archive/docs/features/"
move_file "FWBER_COMPLETE_SYSTEM_STATUS_2025_01_19.md" "archive/docs/features/"
move_file "FWBER_DEVELOPMENT_PROGRESS_2025_01_19.md" "archive/docs/features/"
move_file "FWBER_LOCATION_BASED_SOCIAL_FEATURES.md" "archive/docs/features/"
move_file "FWBER_LOCATION_TRACKING_IMPLEMENTATION_COMPLETE.md" "archive/docs/features/"
move_file "FWBER_MERCURE_SSE_IMPLEMENTATION.md" "archive/docs/features/"
move_file "FWBER_ML_CONTENT_GENERATION_COMPLETE.md" "archive/docs/features/"
move_file "FWBER_ML_CONTENT_GENERATION_IMPLEMENTATION.md" "archive/docs/features/"
move_file "FWBER_MULTI_AI_IMPLEMENTATION_COMPLETE.md" "archive/docs/features/"
move_file "FWBER_MULTI_AI_ORCHESTRATION_COMPLETE.md" "archive/docs/features/"
move_file "FWBER_MULTI_AI_ORCHESTRATION_FINAL_ACHIEVEMENT.md" "archive/docs/features/"
move_file "FWBER_NEXT_GENERATION_FEATURES.md" "archive/docs/features/"
move_file "FWBER_PROJECT_COMPLETE_DOCUMENTATION.md" "archive/docs/features/"
move_file "FWBER_PROXIMITY_CHATROOM_COMPLETE_DOCUMENTATION.md" "archive/docs/features/"
move_file "FWBER_PROXIMITY_CHATROOM_SYSTEM_IMPLEMENTATION.md" "archive/docs/features/"
move_file "FWBER_SYSTEM_INTEGRATION_GUIDE.md" "archive/docs/features/"
move_file "PROXIMITY_CHATROOM_IMPLEMENTATION_SUMMARY.md" "archive/docs/features/"
move_file "DEVELOPMENT_MILESTONE_COMPLETE.md" "archive/docs/features/"
move_file "PHASE_3_DASHBOARD_COMPLETE.md" "archive/docs/features/"
move_file "PHASE_4_DOCUMENTATION_COMPLETE.md" "archive/docs/features/"
move_file "PHASE_4_IMPLEMENTATION_SUMMARY.md" "archive/docs/features/"
git add -A
git commit -m "chore: archive completed feature documentation" 2>/dev/null || echo "No changes to commit"
echo ""

echo -e "${YELLOW}=== Phase 5: Archive AI Orchestration Documentation ===${NC}"
move_file "AI_SKILLS_AND_ORCHESTRATION_COMPLETE_DOCUMENTATION.md" "archive/docs/ai-orchestration/"
move_file "AI_SKILLS_COMPREHENSIVE_SUMMARY.md" "archive/docs/ai-orchestration/"
move_file "MODEL_DOCUMENTATION_ENHANCEMENT_SUMMARY.md" "archive/docs/ai-orchestration/"
move_file "MULTI_AI_COLLABORATION_SUMMARY.md" "archive/docs/ai-orchestration/"
move_file "MULTI_AI_ORCHESTRATION_ANALYSIS_2025_01_19.md" "archive/docs/ai-orchestration/"
move_file "MULTI_MODEL_CONSENSUS_TOOLING_ANALYSIS_2025-01-19.md" "archive/docs/ai-orchestration/"
move_file "MULTI_MODEL_RECOMMENDATIONS_AND_ACTION_PLAN.md" "archive/docs/ai-orchestration/"
move_file "PROJECT_MEMORY_STORAGE_SUMMARY.md" "archive/docs/ai-orchestration/"
git add -A
git commit -m "chore: archive AI orchestration documentation" 2>/dev/null || echo "No changes to commit"
echo ""

echo -e "${YELLOW}=== Phase 6: Organize Active Documentation ===${NC}"
move_file "DEPLOYMENT_CHECKLIST.md" "docs/deployment/"
move_file "DEPLOYMENT_GUIDE.md" "docs/deployment/"
move_file "DOCKER_SETUP.md" "docs/deployment/"
move_file "MONITORING.md" "docs/monitoring/"
move_file "DASHBOARD_IMPLEMENTATION.md" "docs/dashboard/"
move_file "DASHBOARD_TESTING_GUIDE.md" "docs/dashboard/"
move_file "PROJECT_ANALYSIS_AND_ROADMAP_2025-11-15.md" "docs/roadmap/"
git add -A
git commit -m "docs: organize active documentation into subdirectories" 2>/dev/null || echo "No changes to commit"
echo ""

echo -e "${YELLOW}=== Phase 7: Organize PowerShell Test Scripts ===${NC}"
move_file "final_codex_mcp_test.ps1" "tests/scripts/powershell/"
move_file "final_mcp_test.ps1" "tests/scripts/powershell/"
move_file "final_mcp_verification.ps1" "tests/scripts/powershell/"
move_file "simple_mcp_test.ps1" "tests/scripts/powershell/"
move_file "START_TESTING.ps1" "tests/scripts/powershell/"
move_file "test_codex_mcp_debug.ps1" "tests/scripts/powershell/"
move_file "test_individual_mcp_servers.ps1" "tests/scripts/powershell/"
move_file "test_mcp_functionality.ps1" "tests/scripts/powershell/"
move_file "test_mcp_handshake.ps1" "tests/scripts/powershell/"
move_file "test_mcp_protocol.ps1" "tests/scripts/powershell/"
move_file "test_mcp_servers.ps1" "tests/scripts/powershell/"
move_file "test_mcp_startup_times.ps1" "tests/scripts/powershell/"
move_file "test-all-mcp-servers.ps1" "tests/scripts/powershell/"
move_file "test-chroma.ps1" "tests/scripts/powershell/"
move_file "test-chroma-mcp-server.ps1" "tests/scripts/powershell/"
move_file "test-chroma-simple.ps1" "tests/scripts/powershell/"
move_file "test-codex-mcp-servers.ps1" "tests/scripts/powershell/"
move_file "test-environment.ps1" "tests/scripts/powershell/"
move_file "test-mcp-connection.ps1" "tests/scripts/powershell/"
move_file "test-mcp-servers-windows.ps1" "tests/scripts/powershell/"
move_file "test-optimized-setup.ps1" "tests/scripts/powershell/"
git add -A
git commit -m "test: organize PowerShell test scripts" 2>/dev/null || echo "No changes to commit"
echo ""

echo -e "${YELLOW}=== Phase 8: Organize Bash Test Scripts ===${NC}"
move_file "test-advanced-security.sh" "tests/scripts/bash/"
move_file "test-chatroom-system.sh" "tests/scripts/bash/"
move_file "test-ml-content-generation.sh" "tests/scripts/bash/"
move_file "test-proximity-chatroom-system.sh" "tests/scripts/bash/"
git add -A
git commit -m "test: organize bash test scripts" 2>/dev/null || echo "No changes to commit"
echo ""

echo -e "${YELLOW}=== Phase 9: Organize JavaScript Test Files ===${NC}"
move_file "test-end-to-end.js" "tests/integration/"
move_file "test-mcp-servers.js" "tests/integration/"
move_file "test-performance.js" "tests/integration/"
move_file "test-photo-upload.js" "tests/integration/"
move_file "test-profile-form.js" "tests/integration/"
git add -A
git commit -m "test: organize JavaScript integration tests" 2>/dev/null || echo "No changes to commit"
echo ""

echo -e "${YELLOW}=== Phase 10: Organize PHP Test Files ===${NC}"
move_file "test-ai-matching-engine.php" "tests/unit/"
move_file "test-api-login.php" "tests/unit/"
move_file "test-api-register.php" "tests/unit/"
move_file "test-api-user.php" "tests/unit/"
move_file "test-authentication.php" "tests/unit/"
move_file "test-avatar-generation.php" "tests/unit/"
move_file "test-database-connection.php" "tests/unit/"
move_file "test-db-connection.php" "tests/unit/"
move_file "test-matching-algorithm.php" "tests/unit/"
move_file "test-profile-form.php" "tests/unit/"
move_file "test-secure-config.php" "tests/unit/"
move_file "test-sqlite.php" "tests/unit/"
git add -A
git commit -m "test: organize PHP unit tests" 2>/dev/null || echo "No changes to commit"
echo ""

echo -e "${YELLOW}=== Phase 11: Organize Deployment Scripts ===${NC}"
move_file "commit_changes.sh" "tools/deployment/"
move_file "deploy_all_mcp_configs.ps1" "tools/deployment/"
move_file "deploy-optimized-config.ps1" "tools/deployment/"
move_file "deploy-production.sh" "tools/deployment/"
move_file "deploy-secure-orchestrator.ps1" "tools/deployment/"
move_file "deploy-simple.ps1" "tools/deployment/"
move_file "deploy-single-process.ps1" "tools/deployment/"
move_file "init-production.sh" "tools/deployment/"
move_file "setup-dev.ps1" "tools/deployment/"
move_file "setup-dev.sh" "tools/deployment/"
move_file "setup-environment.ps1" "tools/deployment/"
git add -A
git commit -m "chore: organize deployment scripts" 2>/dev/null || echo "No changes to commit"
echo ""

echo -e "${YELLOW}=== Phase 12: Organize Configuration Scripts ===${NC}"
move_file "codex_config_clean.toml" "tools/config/"
move_file "codex_config_global_secure.toml" "tools/config/"
move_file "codex_mcp_diagnostic.ps1" "tools/config/"
move_file "comprehensive_mcp_test.ps1" "tools/config/"
move_file "kill-processes.ps1" "tools/config/"
move_file "npx_wrapper.ps1" "tools/config/"
move_file "optimized-cline-config.json" "tools/config/"
move_file "process-manager.ps1" "tools/config/"
move_file "start-chroma.ps1" "tools/config/"
move_file "unified-cline-config-secure.json" "tools/config/"
move_file "unified-copilot-config-secure.json" "tools/config/"
git add -A
git commit -m "chore: organize configuration scripts" 2>/dev/null || echo "No changes to commit"
echo ""

echo -e "${YELLOW}=== Phase 13: Organize Orchestration Scripts ===${NC}"
move_file "consolidated-mcp-server.js" "tools/orchestration/"
move_file "unified-ai-orchestrator-optimized.js" "tools/orchestration/"
move_file "unified-ai-orchestrator-secure.js" "tools/orchestration/"
move_file "zen_generated.code" "tools/orchestration/"
git add -A
git commit -m "chore: organize AI orchestration tools" 2>/dev/null || echo "No changes to commit"
echo ""

echo -e "${YELLOW}=== Phase 14: Organize Task Files ===${NC}"
move_file "fwber_collaboration_tasks.json" "tools/tasks/"
move_file "mcp-test-results.json" "archive/test-results/"
git add -A
git commit -m "chore: organize task and test result files" 2>/dev/null || echo "No changes to commit"
echo ""

echo -e "${YELLOW}=== Phase 15: Archive Legacy PHP Files ===${NC}"
# Only move files that are confirmed legacy and not in use
# Being conservative here - these should be manually reviewed
echo "Skipping legacy PHP cleanup - requires manual review"
echo "Check these files manually:"
echo "  - editprofile.php"
echo "  - f.php, h.php, l.php"
echo "  - head.php"
echo ""

echo -e "${GREEN}=== Cleanup Summary ===${NC}"
echo "Files removed: $FILES_REMOVED"
echo "Files moved: $FILES_MOVED"
echo "Directories created: $DIRS_CREATED"
echo ""
echo "Backup location: ../$BACKUP_FILE"
echo "Branch: $BRANCH_NAME"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Review the changes: git log --oneline"
echo "2. Test that everything still works"
echo "3. If satisfied, merge to main: git checkout main && git merge $BRANCH_NAME"
echo "4. If issues, revert: git checkout main && git branch -D $BRANCH_NAME"
echo ""
echo -e "${GREEN}Cleanup complete!${NC}"
