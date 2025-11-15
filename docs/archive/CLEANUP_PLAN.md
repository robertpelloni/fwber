# Project Cleanup Plan - FWBer
Generated: 2025-11-15

## Executive Summary
This project has accumulated significant technical debt with:
- **50+ markdown documentation files** (many outdated/duplicate)
- **50+ test files in root directory** (should be in tests/)
- **35+ PowerShell scripts** (deployment, testing, configuration)
- **Multiple backup/log files**
- **Legacy PHP files not yet archived**

## Recommended Actions

### 1. REMOVE - Backup & Log Files
**Safe to delete immediately:**
```bash
rm .gitignore.bak
rm _names.php.bak
rm hs_err_pid38224.log
rm mcp_ps_seq.log
rm mcp_seq_wrapper.log
rm mcp_test_error.txt
rm mcp_test_output.txt
rm test_error.txt
rm test_output.txt
rm test_prompt.txt
rm final_test_prompt.txt
rm codex_mcp_test_prompt.txt
rm test-token.txt
rm tatus  # Appears to be corrupted/incomplete file
```

### 2. ARCHIVE - Outdated Documentation
**Move to `archive/docs/2025-01-migration/`:**

#### MCP Configuration Documentation (Outdated Jan 2025)
- `AI_MODEL_INSTRUCTIONS_AND_COMMANDS.md`
- `API_CONFIGURATION_SOLUTION_2025_01_21.md`
- `CHROMA_SETUP_GUIDE.md`
- `CODEX_MCP_FIXES_2025_01_19.md`
- `COMPREHENSIVE_DEVELOPMENT_STATUS_2025_01_19.md`
- `COMPREHENSIVE_MEMORY_STORAGE_SUMMARY.md`
- `DEVELOPMENT_STATUS_2025_01_19.md`
- `FINAL_MCP_CLI_STATUS_REPORT_2025_01_19.md`
- `FWBER_API_KEY_ISSUES_AND_SOLUTIONS.md`
- `GEMINI_API_KEY_TROUBLESHOOTING_GUIDE.md`
- `KILOCODE_AUTHENTICATION_FIX_2025_01_19.md`
- `MCP_AND_CLI_FIXES_SUMMARY_2025_01_19.md`
- `MCP_SERVER_CONNECTION_FIX_GUIDE.md`
- `MCP_TOOL_COMPREHENSIVE_EVALUATION_2025_01_21.md`

#### Historical Feature Documentation (Completed Features)
- `FWBER_ADVANCED_FEATURES_IMPLEMENTATION.md`
- `FWBER_ADVANCED_SECURITY_IMPLEMENTATION_COMPLETE.md`
- `FWBER_ADVANCED_SECURITY_IMPLEMENTATION_PLAN.md`
- `FWBER_BULLETIN_BOARD_DEMO.md`
- `FWBER_BULLETIN_BOARD_IMPLEMENTATION.md`
- `FWBER_CHATROOM_SYSTEM_IMPLEMENTATION.md`
- `FWBER_COMPLETE_SYSTEM_STATUS_2025_01_19.md`
- `FWBER_DEVELOPMENT_PROGRESS_2025_01_19.md`
- `FWBER_LOCATION_BASED_SOCIAL_FEATURES.md`
- `FWBER_LOCATION_TRACKING_IMPLEMENTATION_COMPLETE.md`
- `FWBER_MERCURE_SSE_IMPLEMENTATION.md`
- `FWBER_ML_CONTENT_GENERATION_COMPLETE.md`
- `FWBER_ML_CONTENT_GENERATION_IMPLEMENTATION.md`
- `FWBER_MULTI_AI_IMPLEMENTATION_COMPLETE.md`
- `FWBER_MULTI_AI_ORCHESTRATION_COMPLETE.md`
- `FWBER_MULTI_AI_ORCHESTRATION_FINAL_ACHIEVEMENT.md`
- `FWBER_NEXT_GENERATION_FEATURES.md`
- `FWBER_PROJECT_COMPLETE_DOCUMENTATION.md`
- `FWBER_PROXIMITY_CHATROOM_COMPLETE_DOCUMENTATION.md`
- `FWBER_PROXIMITY_CHATROOM_SYSTEM_IMPLEMENTATION.md`
- `FWBER_SYSTEM_INTEGRATION_GUIDE.md`
- `PROXIMITY_CHATROOM_IMPLEMENTATION_SUMMARY.md`

#### AI Orchestration Documentation (Historical)
- `AI_SKILLS_AND_ORCHESTRATION_COMPLETE_DOCUMENTATION.md`
- `AI_SKILLS_COMPREHENSIVE_SUMMARY.md`
- `MODEL_DOCUMENTATION_ENHANCEMENT_SUMMARY.md`
- `MULTI_AI_COLLABORATION_SUMMARY.md`
- `MULTI_AI_ORCHESTRATION_ANALYSIS_2025_01_19.md`
- `MULTI_MODEL_CONSENSUS_TOOLING_ANALYSIS_2025-01-19.md`
- `MULTI_MODEL_RECOMMENDATIONS_AND_ACTION_PLAN.md`
- `PROJECT_MEMORY_STORAGE_SUMMARY.md`

#### Phase Documentation (Milestone Markers)
- `DEVELOPMENT_MILESTONE_COMPLETE.md`
- `PHASE_3_DASHBOARD_COMPLETE.md`
- `PHASE_4_DOCUMENTATION_COMPLETE.md`
- `PHASE_4_IMPLEMENTATION_SUMMARY.md`

### 3. CONSOLIDATE - Test Files
**Move to `tests/` directory with organized subdirectories:**

#### PowerShell Test Scripts → `tests/scripts/powershell/`
```
final_codex_mcp_test.ps1
final_mcp_test.ps1
final_mcp_verification.ps1
simple_mcp_test.ps1
START_TESTING.ps1
test_codex_mcp_debug.ps1
test_individual_mcp_servers.ps1
test_mcp_functionality.ps1
test_mcp_handshake.ps1
test_mcp_protocol.ps1
test_mcp_servers.ps1
test_mcp_startup_times.ps1
test-all-mcp-servers.ps1
test-chroma.ps1
test-chroma-mcp-server.ps1
test-chroma-simple.ps1
test-codex-mcp-servers.ps1
test-environment.ps1
test-mcp-connection.ps1
test-mcp-servers-windows.ps1
test-optimized-setup.ps1
```

#### Bash Test Scripts → `tests/scripts/bash/`
```
test-advanced-security.sh
test-chatroom-system.sh
test-ml-content-generation.sh
test-proximity-chatroom-system.sh
```

#### JavaScript Test Files → `tests/integration/`
```
test-end-to-end.js
test-mcp-servers.js
test-performance.js
test-photo-upload.js
test-profile-form.js
```

#### PHP Test Files → `tests/unit/`
```
test-ai-matching-engine.php
test-api-login.php
test-api-register.php
test-api-user.php
test-authentication.php
test-avatar-generation.php
test-database-connection.php
test-db-connection.php
test-matching-algorithm.php
test-profile-form.php
test-secure-config.php
test-sqlite.php
```

### 4. CONSOLIDATE - Deployment Scripts
**Move to `tools/deployment/`:**
```
commit_changes.sh
deploy_all_mcp_configs.ps1
deploy-optimized-config.ps1
deploy-production.sh
deploy-secure-orchestrator.ps1
deploy-simple.ps1
deploy-single-process.ps1
init-production.sh
setup-dev.ps1
setup-dev.sh
setup-environment.ps1
```

### 5. CONSOLIDATE - Configuration Scripts
**Move to `tools/config/`:**
```
codex_config_clean.toml
codex_config_global_secure.toml
codex_mcp_diagnostic.ps1
comprehensive_mcp_test.ps1
kill-processes.ps1
npx_wrapper.ps1
optimized-cline-config.json
process-manager.ps1
start-chroma.ps1
unified-cline-config-secure.json
unified-copilot-config-secure.json
```

### 6. CONSOLIDATE - Orchestration Scripts
**Move to `tools/orchestration/`:**
```
consolidated-mcp-server.js
unified-ai-orchestrator-optimized.js
unified-ai-orchestrator-secure.js
zen_generated.code
```

### 7. ARCHIVE - Legacy PHP Files
**Move to `archive/legacy-php/`:**

These appear to be legacy files based on naming and structure:
```
editprofile.php  (replaced by edit-profile.php or Laravel backend)
f.php  (unclear purpose, likely helper)
h.php  (likely header/helper)
l.php  (unclear purpose)
head.php  (component file, should be in templates/)
```

**Keep but consider moving to templates/components/:**
```
head.php → templates/components/
```

### 8. KEEP - Essential Documentation (Root Level)
**These should remain in root for visibility:**
- `README.md` - Main project documentation
- `README-agents.md` - Agent-specific documentation
- `AGENTS.md` - Current agent configuration
- `CONTRIBUTING.md` - Contribution guidelines
- `LICENSE` - Legal
- `PRIVACY.md` - Privacy policy
- `TERMS.md` - Terms of service
- `DEPLOYMENT_CHECKLIST.md` - Active deployment reference
- `DEPLOYMENT_GUIDE.md` - Active deployment guide
- `DOCKER_SETUP.md` - Active Docker reference
- `MONITORING.md` - Active monitoring guide
- `DASHBOARD_IMPLEMENTATION.md` - Current dashboard docs
- `DASHBOARD_TESTING_GUIDE.md` - Current testing guide
- `PROJECT_ANALYSIS_AND_ROADMAP_2025-11-15.md` - Current roadmap

### 9. EVALUATE - AI Configuration Files
**Keep but consider consolidating:**
- `copilot-instructions.md` - Active
- `WARP.md` - Purpose unclear, may archive

### 10. ORGANIZE - Data Files
**Current status:**
```
fwber_collaboration_tasks.json → tools/tasks/
mcp-test-results.json → archive/test-results/
```

## Proposed New Structure

```
fwber/
├── README.md                          # Main documentation
├── CONTRIBUTING.md                    # Contribution guide
├── LICENSE                            # License
├── PRIVACY.md                         # Privacy policy
├── TERMS.md                           # Terms of service
├── AGENTS.md                          # AI agent config
├── README-agents.md                   # Agent documentation
├── copilot-instructions.md            # AI tooling config
│
├── docs/                              # Active documentation only
│   ├── deployment/
│   │   ├── DEPLOYMENT_CHECKLIST.md
│   │   ├── DEPLOYMENT_GUIDE.md
│   │   └── DOCKER_SETUP.md
│   ├── monitoring/
│   │   └── MONITORING.md
│   ├── dashboard/
│   │   ├── DASHBOARD_IMPLEMENTATION.md
│   │   └── DASHBOARD_TESTING_GUIDE.md
│   └── roadmap/
│       └── PROJECT_ANALYSIS_AND_ROADMAP_2025-11-15.md
│
├── archive/                           # Historical/legacy content
│   ├── docs/
│   │   ├── 2025-01-migration/         # Jan 2025 migration docs
│   │   ├── features/                  # Completed feature docs
│   │   └── ai-orchestration/          # Historical AI config
│   ├── legacy-php/                    # Legacy PHP code
│   ├── old_configs/                   # Old configuration files
│   └── test-results/                  # Historical test results
│
├── tools/                             # Development tooling
│   ├── deployment/                    # Deployment scripts
│   ├── config/                        # Configuration scripts
│   ├── orchestration/                 # AI orchestration tools
│   └── tasks/                         # Task management files
│
├── tests/                             # All test files
│   ├── scripts/
│   │   ├── powershell/                # PowerShell tests
│   │   └── bash/                      # Bash tests
│   ├── integration/                   # JS integration tests
│   └── unit/                          # PHP unit tests
│
├── fwber-backend/                     # Laravel backend
├── fwber-frontend/                    # Next.js frontend
│
└── [other active files remain in root]
```

## Execution Plan

### Phase 1: Safety First (Backup)
1. Commit current state to git
2. Create cleanup branch: `git checkout -b cleanup/2025-11-15`
3. Create backup: `tar -czf fwber-backup-$(date +%Y%m%d).tar.gz .`

### Phase 2: Remove Safe Files
1. Delete backup files (.bak, .log, empty test outputs)
2. Commit: "chore: remove backup and log files"

### Phase 3: Archive Documentation
1. Create `archive/docs/2025-01-migration/`
2. Move outdated documentation
3. Commit: "chore: archive historical documentation"

### Phase 4: Reorganize Tests
1. Organize tests into subdirectories
2. Update any test runner scripts
3. Commit: "chore: reorganize test files into tests/ directory"

### Phase 5: Reorganize Tools
1. Move deployment scripts
2. Move configuration scripts
3. Move orchestration scripts
4. Commit: "chore: organize development tools"

### Phase 6: Archive Legacy PHP
1. Move legacy PHP files to archive
2. Update documentation
3. Commit: "chore: archive legacy PHP files"

### Phase 7: Cleanup Docs
1. Move active docs to docs/ with subdirectories
2. Update references in README
3. Commit: "docs: reorganize active documentation"

### Phase 8: Final Review
1. Test that nothing broke
2. Update .gitignore if needed
3. Merge to main
4. Tag: `v1.0-cleanup`

## Risk Assessment

**LOW RISK:**
- Removing .bak, .log files
- Moving test files (if paths updated)
- Archiving old documentation

**MEDIUM RISK:**
- Moving deployment scripts (may break CI/CD)
- Moving configuration files (may break tooling)
- Archiving legacy PHP (ensure truly unused)

**HIGH RISK:**
- Deleting any .php file still referenced in active code
- Removing any config file still in use

## Validation Checklist

Before merging cleanup:
- [ ] All tests pass
- [ ] Deployment still works
- [ ] No broken links in documentation
- [ ] CI/CD pipeline still functions
- [ ] Development environment setup works
- [ ] Production deployment checklist validated

## Post-Cleanup Maintenance

1. **Add to .gitignore:**
   ```
   *.bak
   *.log
   *_test_output.txt
   *_test_error.txt
   ```

2. **Update CONTRIBUTING.md:**
   - Document new directory structure
   - Specify where to put new tests
   - Specify where to put new docs

3. **Create ARCHITECTURE.md:**
   - Document current architecture
   - Reference historical docs in archive

4. **Periodic Review:**
   - Quarterly review of docs/ for outdated content
   - Archive completed feature docs after 6 months
