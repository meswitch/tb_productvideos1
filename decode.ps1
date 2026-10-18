$bytes = [System.Convert]::FromBase64String((Get-Content .\pv_block.txt -Raw))
[System.IO.File]::WriteAllBytes(".\product_video.zip", $bytes)
Get-FileHash .\product_video.zip -Algorithm SHA256
