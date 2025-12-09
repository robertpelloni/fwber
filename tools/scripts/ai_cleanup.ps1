# AI Cleanup Script - safely terminate lingering AI-related processes
# Purpose: Kill orphaned or stuck processes spawned by MCP servers/CLIs (node/cmd/conhost/uv/java/python)
# Usage:
#   Dry run (see what would be killed):
#     powershell -NoProfile -ExecutionPolicy Bypass -File scripts\ai_cleanup.ps1 -DryRun
#   Actually kill matched processes:
#     powershell -NoProfile -ExecutionPolicy Bypass -File scripts\ai_cleanup.ps1
#   Aggressive (also kill orphans older than threshold):
#     powershell -NoProfile -ExecutionPolicy Bypass -File scripts\ai_cleanup.ps1 -IncludeOrphans -OlderThanMinutes 60
# Notes:
# - Targets processes whose CommandLine contains known MCP server/tool signatures
# - Defaults to safe matching and excludes current PowerShell PID
# - Logs actions and prints a summary

[CmdletBinding(SupportsShouldProcess=$true)]
param(
  [switch] $DryRun,
  [switch] $IncludeOrphans,
  [int] $OlderThanMinutes = 120,
  [string[]] $ExtraMatchPatterns = @()
)

# Known process names we care about (case-insensitive)
$targetNames = @(
  'node.exe','node',
  'cmd.exe','cmd',
  'conhost.exe','conhost',
  'python.exe','python','python3.exe','python3',
  'uv.exe','uv',
  'java.exe','java',
  'deno.exe','deno',
  'bun.exe','bun',
  'npx.cmd','npx'
)

# Command line patterns that identify MCP servers and related tools
$defaultPatterns = @(
  'mcp-server-filesystem',
  'mcp-server-sequential-thinking',
  'mcp-server-memory',
  'mcp-server-everything',
  'gemini-mcp-tool',
  'codex-mcp-server',
  'puppeteer-mcp-server',
  '@playwright/mcp',
  'chrome-devtools-mcp',
  'mcp-smart-crawler',
  '@bolide-ai/mcp',
  'terry-mcp',
  'serena start-mcp-server',
  'zen-mcp-server'
)

$patterns = $defaultPatterns + $ExtraMatchPatterns

function Write-Info($msg) { Write-Host $msg -ForegroundColor Cyan }
function Write-Warn($msg) { Write-Host $msg -ForegroundColor Yellow }
function Write-Err ($msg) { Write-Host $msg -ForegroundColor Red }
function Write-Ok  ($msg) { Write-Host $msg -ForegroundColor Green }

Write-Info "AI Cleanup starting..."
Write-Info ("  DryRun: {0}" -f ($DryRun.IsPresent))
Write-Info ("  IncludeOrphans: {0} (OlderThanMinutes={1})" -f ($IncludeOrphans.IsPresent, $OlderThanMinutes))
Write-Info ("  ExtraMatchPatterns: {0}" -f (($ExtraMatchPatterns -join ', ') ?? '(none)'))

# Get candidate processes with command lines
try {
  $all = Get-CimInstance Win32_Process
} catch {
  Write-Err "Failed to enumerate processes: $($_.Exception.Message)"
  exit 1
}

$me = $PID
$now = Get-Date
$olderThan = $now.AddMinutes(-1 * [Math]::Abs($OlderThanMinutes))

# Helper: determine if command line matches any known pattern
function MatchesPatterns([string]$cmd) {
  if ([string]::IsNullOrWhiteSpace($cmd)) { return $false }
  foreach ($p in $patterns) {
    if ($cmd -match [Regex]::Escape($p)) { return $true }
  }
  return $false
}

# Helper: try to parse start time from CIM (CreationDate is WMI datetime)
function Get-StartTime($cimProc) {
  if ($cimProc.CreationDate) {
    try { return [System.Management.ManagementDateTimeConverter]::ToDateTime($cimProc.CreationDate) }
    catch { return $null }
  }
  return $null
}

# Select processes by name and pattern
$candidates = $all | Where-Object {
  $n = $_.Name
  if (-not $n) { return $false }
  ($targetNames -contains $n.ToLower()) -and ($_.ProcessId -ne $me)
}

$matched = @()
foreach ($p in $candidates) {
  $cmd = $p.CommandLine
  if (MatchesPatterns $cmd) {
    $matched += $p
  }
}

# Include orphan criteria if requested (no window, older than threshold, target name)
if ($IncludeOrphans) {
  foreach ($p in $candidates) {
    if ($matched | Where-Object { $_.ProcessId -eq $p.ProcessId }) { continue }
    $start = Get-StartTime $p
    $ageOk = $false
    if ($start) { $ageOk = ($start -lt $olderThan) }
    # Heuristic for no-window: conhost pairs with cmd; treat orphaned cmd/node without recent start as candidates
    $name = $p.Name.ToLower()
    $isLikelyOrphan = $ageOk -and ($name -in @('node.exe','node','cmd.exe','cmd','conhost.exe','conhost','uv.exe','uv','java.exe','java'))
    if ($isLikelyOrphan) {
      $matched += $p
    }
  }
}

# De-duplicate by PID
$matched = $matched | Sort-Object ProcessId -Unique

if (-not $matched -or $matched.Count -eq 0) {
  Write-Ok "No matching AI-related processes found."
  exit 0
}

Write-Warn ("Matched {0} process(es):" -f $matched.Count)
$matched | ForEach-Object {
  $start = Get-StartTime $_
  $age   = if ($start) { (New-TimeSpan -Start $start -End $now).ToString() } else { 'unknown' }
  Write-Host (" - PID {0}  Name {1}  Age {2}" -f $_.ProcessId, $_.Name, $age) -ForegroundColor Yellow
  if ($_.CommandLine) {
    $cl = $_.CommandLine
    if ($cl.Length -gt 200) { $cl = $cl.Substring(0,200) + ' ...' }
    Write-Host ("   Cmd: {0}" -f $cl) -ForegroundColor DarkGray
  }
}

$killed = 0
$failed = 0

foreach ($p in $matched) {
  $procId = $p.ProcessId
  $name = $p.Name
  if ($DryRun) {
    Write-Host ("[DryRun] Would kill PID {0} ({1})" -f $procId, $name) -ForegroundColor Magenta
    continue
  }
  try {
    Stop-Process -Id $procId -Force -ErrorAction Stop
    Write-Ok ("Killed PID {0} ({1})" -f $procId, $name)
    $killed++
  } catch {
    Write-Err ("Failed to kill PID {0} ({1}): {2}" -f $procId, $name, $_.Exception.Message)
    $failed++
  }
}

Write-Host ""
Write-Info "Cleanup summary:"
Write-Host ("  Killed: {0}" -f $killed) -ForegroundColor Green
Write-Host ("  Failed: {0}" -f $failed) -ForegroundColor Red
Write-Host ("  Skipped (DryRun): {0}" -f ($(if ($DryRun) { $matched.Count } else { 0 }))) -ForegroundColor Magenta
Write-Ok "AI Cleanup completed."