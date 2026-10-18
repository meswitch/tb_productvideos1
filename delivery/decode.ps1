# decode.ps1 - ThirtyBeeS product_video.zip decoder (PowerShell, Windows-native)
# Usage: powershell -ExecutionPolicy Bypass -File decode.ps1 pv.txt product_video.zip
param([string]$InFile = 'pv.txt', [string]$OutFile = 'product_video.zip')
$raw = Get-Content -Raw $InFile
$b64 = ($raw -replace '[^A-Za-z0-9+/=]', '') -replace '=+$',''
switch ($b64.Length % 4) { 2 {$b64 += '=='} 3 {$b64 += '='} }
try { $bytes = [Convert]::FromBase64String($b64) } catch { Write-Host "RESULT: FAILED - invalid Base64"; exit 1 }
[IO.File]::WriteAllBytes($OutFile, $bytes)
$md5 = (Get-FileHash -Algorithm MD5 $OutFile).Hash.ToLower()
Write-Host ("Size:   {0} bytes" -f $bytes.Length)
Write-Host "MD5:    $md5"
if ($bytes.Length -eq 9765 -and $md5 -eq '11ee794437bd930008b956877c37155a') {
    Write-Host "RESULT: SUCCESS - zip matches the v1.2.0 build"; exit 0
} else { Write-Host "RESULT: MISMATCH - paste truncated/corrupted, re-copy chunks 1-10"; exit 1 }
