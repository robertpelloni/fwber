# AI Orchestration Demo Script
# This script demonstrates how to use multiple AI CLI tools in parallel

Write-Host "=== AI Multi-Model Orchestration Demo ===" -ForegroundColor Cyan
Write-Host ""

# Define the task
$task = "Analyze the FWBer project structure and suggest improvements"
$projectPath = "C:\Users\hyper\fwber"

Write-Host "Task: $task" -ForegroundColor Yellow
Write-Host "Project: $projectPath" -ForegroundColor Yellow
Write-Host ""

# Test 1: Simple parallel execution
Write-Host "=== Test 1: Parallel Model Execution ===" -ForegroundColor Green
Write-Host "Sending the same task to multiple AI models..." -ForegroundColor Gray
Write-Host ""

# Create output directory
$outputDir = "AI_COORDINATION\multi_model_results"
New-Item -ItemType Directory -Force -Path $outputDir | Out-Null

Write-Host "Starting Codex analysis..." -ForegroundColor Cyan
$codexJob = Start-Job -ScriptBlock {
    param($task, $outputPath)
    $result = codex -p "$task" 2>&1
    $result | Out-File -FilePath $outputPath
} -ArgumentList $task, "$outputDir\codex_result.txt"

Write-Host "Starting Claude analysis..." -ForegroundColor Cyan
$claudeJob = Start-Job -ScriptBlock {
    param($task, $outputPath)
    $result = claude -p "$task" 2>&1
    $result | Out-File -FilePath $outputPath
} -ArgumentList $task, "$outputDir\claude_result.txt"

Write-Host ""
Write-Host "Jobs started. Waiting for completion..." -ForegroundColor Yellow
Write-Host "This may take a minute or two..." -ForegroundColor Gray
Write-Host ""

# Wait for jobs with timeout
$timeout = 120 # 2 minutes
$timer = [Diagnostics.Stopwatch]::StartNew()

while (($codexJob.State -eq 'Running' -or $claudeJob.State -eq 'Running') -and $timer.Elapsed.TotalSeconds -lt $timeout) {
    Start-Sleep -Seconds 2
    Write-Host "." -NoNewline -ForegroundColor Gray
}
Write-Host ""

$timer.Stop()

# Check results
if ($codexJob.State -eq 'Completed') {
    Write-Host "✓ Codex completed" -ForegroundColor Green
} else {
    Write-Host "✗ Codex timed out or failed" -ForegroundColor Red
}

if ($claudeJob.State -eq 'Completed') {
    Write-Host "✓ Claude completed" -ForegroundColor Green
} else {
    Write-Host "✗ Claude timed out or failed" -ForegroundColor Red
}

Write-Host ""

# Clean up jobs
Remove-Job -Job $codexJob -Force
Remove-Job -Job $claudeJob -Force

# Test 2: Sequential with context passing
Write-Host "=== Test 2: Sequential Execution with Context Passing ===" -ForegroundColor Green
Write-Host "Using output from one model as input to another..." -ForegroundColor Gray
Write-Host ""

Write-Host "Step 1: Codex analyzes project structure..." -ForegroundColor Cyan
$step1Output = "$outputDir\step1_codex_analysis.txt"
codex -p "List the main PHP files in the FWBer project and describe their purpose" | Out-File -FilePath $step1Output

if (Test-Path $step1Output) {
    Write-Host "✓ Step 1 completed" -ForegroundColor Green
    
    Write-Host "Step 2: Claude suggests improvements based on Codex's analysis..." -ForegroundColor Cyan
    $codexAnalysis = Get-Content $step1Output -Raw
    $step2Prompt = "Based on this project analysis, suggest 3 specific improvements:`n`n$codexAnalysis"
    $step2Output = "$outputDir\step2_claude_suggestions.txt"
    
    claude -p $step2Prompt | Out-File -FilePath $step2Output
    
    if (Test-Path $step2Output) {
        Write-Host "✓ Step 2 completed" -ForegroundColor Green
    } else {
        Write-Host "✗ Step 2 failed" -ForegroundColor Red
    }
} else {
    Write-Host "✗ Step 1 failed" -ForegroundColor Red
}

Write-Host ""

# Test 3: MCP Server Verification
Write-Host "=== Test 3: MCP Server Verification ===" -ForegroundColor Green
Write-Host "Checking MCP servers across all CLI tools..." -ForegroundColor Gray
Write-Host ""

Write-Host "Codex MCP servers:" -ForegroundColor Cyan
codex mcp list

Write-Host ""
Write-Host "Claude MCP servers:" -ForegroundColor Cyan
claude mcp list

Write-Host ""

# Summary
Write-Host "=== Demo Complete ===" -ForegroundColor Green
Write-Host ""
Write-Host "Results saved to: $outputDir" -ForegroundColor Yellow
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Cyan
Write-Host "1. Review the results in the output directory" -ForegroundColor Gray
Write-Host "2. Compare outputs from different models" -ForegroundColor Gray
Write-Host "3. Create custom orchestration scripts for your workflow" -ForegroundColor Gray
Write-Host "4. Use MCP servers for enhanced capabilities" -ForegroundColor Gray
Write-Host ""
Write-Host "For more advanced orchestration, see AI_ORCHESTRATION_SETUP_GUIDE.md" -ForegroundColor Yellow
