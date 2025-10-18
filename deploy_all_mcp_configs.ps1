# Deploy All MCP Configurations Script
# Purpose: Deploy clean MCP configs to all CLI tools with verification
# Usage: powershell -NoProfile -ExecutionPolicy Bypass -File deploy_all_mcp_configs.ps1

param(
  [switch]$DryRun,
  [switch]$Backup = $true
)

$ErrorActionPreference = "Stop"

function Write-Info($msg) { Write-Host $msg -ForegroundColor Cyan }
function Write-Warn($msg) { Write-Host $msg -ForegroundColor Yellow }
function Write-Err($msg) { Write-Host $msg -ForegroundColor Red }
function Write-Ok($msg) { Write-Host $msg -ForegroundColor Green }

Write-Info "=== Multi-Model MCP Configuration Deployment ==="
Write-Info "DryRun: $($DryRun.IsPresent)"
Write-Info "Backup: $($Backup.IsPresent)"
Write-Host ""

# Configuration mappings
$configs = @(
  @{
    Name = "Codex CLI"
    Source = "codex_config_clean.toml"
    Dest = "$env:USERPROFILE\.codex\config.toml"
    Verify = "codex mcp list"
  },
  @{
    Name = "Cursor IDE"
    Source = "tools_config_files\cursor_mcp_clean.json"
    Dest = "$env:USERPROFILE\.cursor\mcp.json"
    Verify = $null
  },
  @{
    Name = "Claude CLI"
    Source = "tools_config_files\claude_mcp_template.json"
    Dest = "$env:USERPROFILE\.claude.json"
    Verify = $null  # Claude has errors, skip verification
  },
  @{
    Name = "Gemini CLI"
    Source = "tools_config_files\gemini_mcp_template.json"
    Dest = "$env:USERPROFILE\.gemini\settings.json"
    Verify = "gemini --version"
  },
  @{
    Name = "GitHub Copilot CLI"
    Source = "tools_config_files\copilot_mcp_template.json"
    Dest = "$env:USERPROFILE\.copilot\mcp-config.json"
    Verify = "copilot --version"
  },
  @{
    Name = "Grok CLI"
    Source = "tools_config_files\grok_mcp_template.json"
    Dest = "$env:USERPROFILE\.grok\settings.json"
    Verify = "grok --version"
  },
  @{
    Name = "Qwen CLI"
    Source = "tools_config_files\qwen_mcp_template.json"
    Dest = "$env:USERPROFILE\.qwen\settings.json"
    Verify = "qwen --version"
  }
)

$deployed = 0
$failed = 0
$skipped = 0

foreach ($cfg in $configs) {
  Write-Host ""
  Write-Info "Processing: $($cfg.Name)"
  
  # Check source exists
  if (-not (Test-Path $cfg.Source)) {
    Write-Err "  Source not found: $($cfg.Source)"
    $failed++
    continue
  }
  
  # Create destination directory if needed
  $destDir = Split-Path -Parent $cfg.Dest
  if (-not (Test-Path $destDir)) {
    if ($DryRun) {
      Write-Warn "  [DryRun] Would create directory: $destDir"
    } else {
      New-Item -ItemType Directory -Force -Path $destDir | Out-Null
      Write-Ok "  Created directory: $destDir"
    }
  }
  
  # Backup existing config
  if ($Backup -and (Test-Path $cfg.Dest)) {
    $backupPath = "$($cfg.Dest).bak"
    if ($DryRun) {
      Write-Warn "  [DryRun] Would backup to: $backupPath"
    } else {
      Copy-Item -Force -Path $cfg.Dest -Destination $backupPath
      Write-Ok "  Backed up to: $backupPath"
    }
  }
  
  # Deploy config
  if ($DryRun) {
    Write-Warn "  [DryRun] Would deploy: $($cfg.Source) -> $($cfg.Dest)"
    $skipped++
  } else {
    try {
      Copy-Item -Force -Path $cfg.Source -Destination $cfg.Dest
      Write-Ok "  Deployed: $($cfg.Dest)"
      $deployed++
      
      # Verify if command provided
      if ($cfg.Verify) {
        try {
          $verifyCmd = $cfg.Verify
          Write-Info "  Verifying with: $verifyCmd"
          Invoke-Expression $verifyCmd | Out-Null
          Write-Ok "  Verification passed"
        } catch {
          Write-Warn "  Verification failed (non-fatal): $($_.Exception.Message)"
        }
      }
    } catch {
      Write-Err "  Failed to deploy: $($_.Exception.Message)"
      $failed++
    }
  }
}

Write-Host ""
Write-Info "=== Deployment Summary ==="
Write-Host "  Deployed: $deployed" -ForegroundColor Green
Write-Host "  Failed: $failed" -ForegroundColor Red
Write-Host "  Skipped (DryRun): $skipped" -ForegroundColor Yellow

if ($deployed -gt 0) {
  Write-Host ""
  Write-Ok "Deployment completed successfully!"
  Write-Info "Next steps:"
  Write-Host "  1. Set required environment variables (OPENAI_API_KEY, GEMINI_API_KEY, etc.)" -ForegroundColor White
  Write-Host "  2. Test with: codex mcp list" -ForegroundColor White
  Write-Host "  3. Run cleanup if needed: powershell -File scripts\ai_cleanup.ps1 -DryRun" -ForegroundColor White
}

if ($failed -gt 0) {
  Write-Host ""
  Write-Err "Some deployments failed. Review errors above."
  exit 1
}