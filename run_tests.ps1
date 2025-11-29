Set-Location c:\Users\hyper\workspace\fwber\fwber-frontend
Write-Host "Starting server..."
$serverProcess = Start-Process -FilePath "npm.cmd" -ArgumentList "run dev -- -p 3005" -PassThru -NoNewWindow
Start-Sleep -Seconds 40
Write-Host "Running tests..."
npx cypress run --spec "cypress/e2e/groups.cy.js,cypress/e2e/boosts.cy.js,cypress/e2e/premium-features.cy.js,cypress/e2e/events.cy.js"
Write-Host "Stopping server..."
Stop-Process -Id $serverProcess.Id -Force
