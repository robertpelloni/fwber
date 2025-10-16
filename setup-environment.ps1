# Setup Environment Variables for AI Orchestration
# This script helps set up environment variables for the secure orchestrator

Write-Host "🔐 Setting up environment variables for AI Orchestration..." -ForegroundColor Green

# Create .env file template
$envTemplate = @"
# AI Orchestration Environment Variables
# Copy this file to .env and fill in your actual API keys

# OpenAI/Codex
OPENAI_API_KEY=your_openai_api_key_here

# OpenRouter (for multiple model providers)
OPENROUTER_API_KEY=your_openrouter_api_key_here

# Google/Gemini
GEMINI_API_KEY=your_gemini_api_key_here
GOOGLE_API_KEY=your_google_api_key_here

# Anthropic/Claude
ANTHROPIC_API_KEY=your_anthropic_api_key_here

# xAI/Grok
XAI_API_KEY=your_xai_api_key_here

# Groq
GROQ_API_KEY=your_groq_api_key_here

# Database (optional)
POSTGRES_CONNECTION_STRING=postgresql://user:password@localhost:5432/fwber

# Orchestrator settings
ORCHESTRATOR_PORT=8081
NODE_ENV=development
"@

$envTemplate | Out-File -FilePath ".env.template" -Encoding UTF8
Write-Host "✅ Created .env.template file" -ForegroundColor Green

# Check if .env file exists
if (Test-Path ".env") {
    Write-Host "✅ .env file already exists" -ForegroundColor Green
} else {
    Write-Host "⚠️  .env file not found" -ForegroundColor Yellow
    Write-Host "💡 Copy .env.template to .env and fill in your API keys" -ForegroundColor Cyan
}

# Function to set environment variable
function Set-EnvVar {
    param([string]$Name, [string]$Value)
    
    if ($Value -and $Value -ne "your_${Name}_api_key_here") {
        [Environment]::SetEnvironmentVariable($Name, $Value, "User")
        Write-Host "✅ Set $Name" -ForegroundColor Green
        return $true
    } else {
        Write-Host "⚠️  $Name not set or using placeholder" -ForegroundColor Yellow
        return $false
    }
}

# Check current environment variables
Write-Host "`n🔍 Checking current environment variables..." -ForegroundColor Blue

$envVars = @(
    "OPENAI_API_KEY",
    "OPENROUTER_API_KEY", 
    "GEMINI_API_KEY",
    "GOOGLE_API_KEY",
    "ANTHROPIC_API_KEY",
    "XAI_API_KEY",
    "GROQ_API_KEY"
)

$setVars = 0
foreach ($var in $envVars) {
    $value = [Environment]::GetEnvironmentVariable($var, "User")
    if ($value) {
        Write-Host "✅ $var is set" -ForegroundColor Green
        $setVars++
    } else {
        Write-Host "❌ $var is not set" -ForegroundColor Red
    }
}

Write-Host "`n📊 Environment Status: $setVars/$($envVars.Count) variables set" -ForegroundColor Cyan

if ($setVars -eq $envVars.Count) {
    Write-Host "🎉 All environment variables are configured!" -ForegroundColor Green
    Write-Host "💡 You can now run the secure orchestrator" -ForegroundColor Cyan
} else {
    Write-Host "⚠️  Some environment variables are missing" -ForegroundColor Yellow
    Write-Host "`n📝 To set environment variables:" -ForegroundColor Blue
    Write-Host "1. Copy .env.template to .env" -ForegroundColor White
    Write-Host "2. Fill in your actual API keys" -ForegroundColor White
    Write-Host "3. Run this script again" -ForegroundColor White
    Write-Host "`n   Or set them manually:" -ForegroundColor Cyan
    Write-Host "   [Environment]::SetEnvironmentVariable('OPENAI_API_KEY', 'your_key', 'User')" -ForegroundColor White
}

# Create .gitignore to protect sensitive files
$gitignore = @"
# Environment variables
.env
.env.local
.env.production

# API keys and secrets
*.key
*.secret
*api_key*
*secret*

# Configuration backups
config-backups/
tool-optimization-backups/

# Logs
*.log
logs/

# Node modules
node_modules/

# Python
__pycache__/
*.pyc
.venv/
venv/

# IDE
.vscode/
.idea/
*.swp
*.swo

# OS
.DS_Store
Thumbs.db
"@

$gitignore | Out-File -FilePath ".gitignore" -Encoding UTF8
Write-Host "✅ Created .gitignore to protect sensitive files" -ForegroundColor Green

Write-Host "`n🎯 Next steps:" -ForegroundColor Yellow
Write-Host "1. Fill in your API keys in .env file" -ForegroundColor White
Write-Host "2. Run: .\deploy-secure-orchestrator.ps1" -ForegroundColor White
Write-Host "3. Test the orchestrator: node unified-ai-orchestrator-secure.js" -ForegroundColor White
