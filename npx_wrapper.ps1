# PowerShell wrapper to set environment variables before running npx
# This bypasses Codex's environment variable whitelist

param(
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]]$Arguments
)

# Set essential Node.js environment variables
$env:PATH = "C:\Program Files\nodejs\;C:\Users\mrgen\.local\bin;C:\Program Files\PowerShell\7;C:\Users\mrgen\AppData\Roaming\npm;C:\WINDOWS\system32;C:\WINDOWS"
$env:NODE_PATH = "C:\Program Files\nodejs\node_modules"
$env:NODE = "C:\Program Files\nodejs\node.exe"
$env:NPM_CONFIG_PREFIX = "C:\Users\mrgen\AppData\Roaming\npm"
$env:NODE_ENV = "production"

# Run the npx command with the provided arguments
& "C:\Program Files\nodejs\npx.cmd" $Arguments
