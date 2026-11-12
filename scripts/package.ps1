param(
    [string]$CoreArchive = '',
    [string]$OutputDirectory = ''
)
$ErrorActionPreference = 'Stop'
$projectRoot = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
if (!$OutputDirectory) { $OutputDirectory = Join-Path $projectRoot 'dist' }
$outputRoot = [IO.Path]::GetFullPath($OutputDirectory)
New-Item -ItemType Directory -Path $outputRoot -Force | Out-Null
$version = '7.1.4'
$expectedHash = 'a147146c159362778ecbbbfb07bb20ddf4e85b130f971c824e656a8f938c5f87'
if (!$CoreArchive) {
    $CoreArchive = Join-Path $outputRoot "wordpress-$version.zip"
    if (!(Test-Path -LiteralPath $CoreArchive)) {
        Invoke-WebRequest -Uri "https://wordpress.org/wordpress-$version.zip" -OutFile $CoreArchive -UseBasicParsing
    }
}
if ((Get-FileHash -LiteralPath $CoreArchive -Algorithm SHA256).Hash.ToLowerInvariant() -ne $expectedHash) {
    throw 'WordPress archive checksum mismatch. Use the pinned official archive. Do not bypass this check.'
}
# A unique staging directory avoids overwriting any existing local installation.
$stageRoot = Join-Path $outputRoot ('stage-' + [guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Path $stageRoot | Out-Null
Expand-Archive -LiteralPath $CoreArchive -DestinationPath $stageRoot
$siteRoot = Join-Path $stageRoot 'aurevia-demo'
Rename-Item -LiteralPath (Join-Path $stageRoot 'wordpress') -NewName 'aurevia-demo'
Copy-Item -LiteralPath (Join-Path $projectRoot 'wp-content/themes/aurevia') -Destination (Join-Path $siteRoot 'wp-content/themes') -Recurse
New-Item -ItemType Directory -Path (Join-Path $siteRoot 'wp-content/mu-plugins') -Force | Out-Null
Copy-Item -LiteralPath (Join-Path $projectRoot 'wp-content/mu-plugins/aurevia-demo.php') -Destination (Join-Path $siteRoot 'wp-content/mu-plugins')
Copy-Item -LiteralPath (Join-Path $projectRoot 'docs') -Destination $siteRoot -Recurse
foreach ($name in @('setup.php','README.md','README.en.md','LICENSE','THIRD-PARTY.md','VERIFICATION.md','.gitignore')) {
    Copy-Item -LiteralPath (Join-Path $projectRoot $name) -Destination $siteRoot
}
$archivePath = Join-Path $outputRoot 'aurevia-xampp.zip'
if (Test-Path -LiteralPath $archivePath) { throw 'aurevia-xampp.zip already exists. Choose a new output directory.' }
Compress-Archive -LiteralPath $siteRoot -DestinationPath $archivePath -CompressionLevel Optimal
# Keep the staging tree for inspection; no recursive deletion is performed.
Write-Output "Packaged: $archivePath"
Write-Output "Staging: $siteRoot"
Write-Output "SHA256: $((Get-FileHash -LiteralPath $archivePath -Algorithm SHA256).Hash)"
