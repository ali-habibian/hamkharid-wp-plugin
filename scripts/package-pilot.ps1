[CmdletBinding()]
param(
	[Parameter(Mandatory = $true)]
	[ValidateNotNullOrEmpty()]
	[string] $OutputDirectory,

	[string] $Composer = 'composer',

	[string] $QaReportPath,

	[switch] $AllowDirty,

	[switch] $Force
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$projectRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path.TrimEnd([char[]]@('\', '/'))
$projectManifest = Join-Path $projectRoot 'hamkharid.php'
$composerManifest = Join-Path $projectRoot 'composer.json'
$composerLock = Join-Path $projectRoot 'composer.lock'

if (-not (Test-Path -LiteralPath $projectManifest -PathType Leaf)) {
	throw "Plugin entrypoint is missing: $projectManifest"
}

if (-not (Test-Path -LiteralPath $composerManifest -PathType Leaf) -or -not (Test-Path -LiteralPath $composerLock -PathType Leaf)) {
	throw 'composer.json and composer.lock are required to create a reproducible runtime autoloader.'
}

$manifestText = Get-Content -LiteralPath $projectManifest -Raw
$versionMatch = [regex]::Match($manifestText, '(?m)^\s*\*\s*Version:\s*([^\s]+)\s*$')
if (-not $versionMatch.Success) {
	throw 'Could not read the plugin version from the hamkharid.php header.'
}
$version = $versionMatch.Groups[1].Value

$resolvedOutputDirectory = [System.IO.Path]::GetFullPath($OutputDirectory).TrimEnd([char[]]@('\', '/'))
$rootPrefix = $projectRoot + [System.IO.Path]::DirectorySeparatorChar
if ($resolvedOutputDirectory.Equals($projectRoot, [System.StringComparison]::OrdinalIgnoreCase) -or $resolvedOutputDirectory.StartsWith($rootPrefix, [System.StringComparison]::OrdinalIgnoreCase)) {
	throw 'OutputDirectory must be outside the repository.'
}
if (-not (Test-Path -LiteralPath $resolvedOutputDirectory -PathType Container)) {
	New-Item -ItemType Directory -Path $resolvedOutputDirectory -Force | Out-Null
}
$resolvedOutputDirectory = (Resolve-Path -LiteralPath $resolvedOutputDirectory).Path.TrimEnd([char[]]@('\', '/'))

$zipPath = Join-Path $resolvedOutputDirectory "hamkharid-$version-pilot.zip"
$manifestPath = Join-Path $resolvedOutputDirectory "hamkharid-$version-pilot.manifest.json"
$outputExists = (Test-Path -LiteralPath $zipPath) -or (Test-Path -LiteralPath $manifestPath)
if ($outputExists) {
	if (-not $Force) {
		throw "The package output already exists. Pass -Force only when replacing a reviewed local artifact: $zipPath"
	}
}

$gitStatus = (& git -C $projectRoot status --porcelain)
if ($LASTEXITCODE -ne 0) {
	throw 'Could not read Git status for the package manifest.'
}
$isDirty = @($gitStatus).Count -gt 0
if ($isDirty -and -not $AllowDirty) {
	throw 'The repository has uncommitted changes. Commit the reviewed release state or pass -AllowDirty to record the dirty build explicitly.'
}
$revision = (& git -C $projectRoot rev-parse HEAD).Trim()
if ($LASTEXITCODE -ne 0) {
	throw 'Could not read the source Git revision.'
}
$qaReport = $null
if ($QaReportPath) {
	$resolvedQaReport = (Resolve-Path -LiteralPath $QaReportPath).Path
	$qaReport = Get-Content -LiteralPath $resolvedQaReport -Raw
}

$requiredPaths = @(
	'hamkharid.php',
	'uninstall.php',
	'src',
	'assets/css/frontend.css',
	'assets/js/complements-block.js',
	'languages',
	'readme.txt',
	'CHANGELOG.md',
	'LICENSE'
)
foreach ($relativePath in $requiredPaths) {
	if (-not (Test-Path -LiteralPath (Join-Path $projectRoot $relativePath))) {
		throw "Required runtime or pilot documentation file is missing: $relativePath"
	}
}

$tempRoot = [System.IO.Path]::GetFullPath([System.IO.Path]::GetTempPath()).TrimEnd([char[]]@('\', '/'))
$stagePath = Join-Path $tempRoot ("hamkharid-pilot-stage-" + [guid]::NewGuid().ToString('N'))
$temporaryZipPath = Join-Path $tempRoot ("hamkharid-pilot-" + [guid]::NewGuid().ToString('N') + '.zip')
$temporaryManifestPath = Join-Path $tempRoot ("hamkharid-pilot-" + [guid]::NewGuid().ToString('N') + '.manifest.json')
$packagePath = Join-Path $stagePath 'hamkharid'
New-Item -ItemType Directory -Path $packagePath -Force | Out-Null

try {
	Copy-Item -LiteralPath $projectManifest -Destination $packagePath
	Copy-Item -LiteralPath (Join-Path $projectRoot 'uninstall.php') -Destination $packagePath
	Copy-Item -LiteralPath (Join-Path $projectRoot 'src') -Destination $packagePath -Recurse
	Copy-Item -LiteralPath (Join-Path $projectRoot 'assets') -Destination $packagePath -Recurse
	Copy-Item -LiteralPath (Join-Path $projectRoot 'languages') -Destination $packagePath -Recurse
	foreach ($fileName in @('readme.txt', 'CHANGELOG.md', 'LICENSE', 'composer.json', 'composer.lock')) {
		Copy-Item -LiteralPath (Join-Path $projectRoot $fileName) -Destination $packagePath
	}

	& $Composer --working-dir=$packagePath install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-progress
	if ($LASTEXITCODE -ne 0) {
		throw "Composer could not build the runtime autoloader (exit $LASTEXITCODE)."
	}

	if (-not (Test-Path -LiteralPath (Join-Path $packagePath 'vendor/autoload.php') -PathType Leaf)) {
		throw 'Composer completed without producing vendor/autoload.php.'
	}
	Remove-Item -LiteralPath (Join-Path $packagePath 'composer.json'), (Join-Path $packagePath 'composer.lock') -Force

	$forbiddenFiles = Get-ChildItem -LiteralPath $packagePath -Recurse -File | Where-Object {
		$_.Name -match '(^\.env|\.log$|\.sql$|\.sqlite$|\.cache$|\.phpunit\.result\.cache$)' -or $_.FullName -match '[\\/](tests|node_modules|\.git|\.idea|\.claude|wordpress-plugin-ai-index)[\\/]'
	}
	if (@($forbiddenFiles).Count -gt 0) {
		throw "Staging contains excluded development or sensitive files: $($forbiddenFiles.FullName -join ', ')"
	}

	Add-Type -AssemblyName System.IO.Compression.FileSystem
	[System.IO.Compression.ZipFile]::CreateFromDirectory($stagePath, $temporaryZipPath, [System.IO.Compression.CompressionLevel]::Optimal, $false)
	$zipHash = (Get-FileHash -LiteralPath $temporaryZipPath -Algorithm SHA256).Hash.ToLowerInvariant()
	$files = @(
		Get-ChildItem -LiteralPath $packagePath -Recurse -File | Sort-Object FullName | ForEach-Object {
			$relative = $_.FullName.Substring($stagePath.Length).TrimStart([char[]]@('\', '/')).Replace('\', '/')
			[pscustomobject]@{
				path   = $relative
				size   = $_.Length
				sha256 = (Get-FileHash -LiteralPath $_.FullName -Algorithm SHA256).Hash.ToLowerInvariant()
			}
		}
	)
	$buildManifest = [ordered]@{
		product             = 'Smart Category Complement – Hamkharid'
		version             = $version
		built_at_utc        = [DateTime]::UtcNow.ToString('yyyy-MM-ddTHH:mm:ssZ')
		source_revision     = $revision
		source_dirty        = $isDirty
		composer_mode       = 'no-dev --optimize-autoloader'
		zip_file            = [System.IO.Path]::GetFileName($zipPath)
		zip_sha256          = $zipHash
		files               = $files
		qa_report           = $qaReport
		qa_status           = if ($qaReport) { 'Recorded in manifest from the supplied report.' } else { 'Not recorded; this artifact is not ready for pilot acceptance.' }
		rollback            = 'Back up the QA database, deactivate the plugin, then restore the prior plugin ZIP and database backup.'
		limitations         = @('This command builds a local artifact only. It does not upload, publish, deploy, or send the ZIP.')
	}
	$buildManifest | ConvertTo-Json -Depth 8 | Set-Content -LiteralPath $temporaryManifestPath -Encoding utf8

	$backupZipPath = Join-Path $stagePath 'previous.zip'
	$backupManifestPath = Join-Path $stagePath 'previous.manifest.json'
	if (Test-Path -LiteralPath $zipPath) {
		Move-Item -LiteralPath $zipPath -Destination $backupZipPath
	}
	if (Test-Path -LiteralPath $manifestPath) {
		Move-Item -LiteralPath $manifestPath -Destination $backupManifestPath
	}
	try {
		Move-Item -LiteralPath $temporaryZipPath -Destination $zipPath
		Move-Item -LiteralPath $temporaryManifestPath -Destination $manifestPath
	} catch {
		Remove-Item -LiteralPath $zipPath, $manifestPath -Force -ErrorAction SilentlyContinue
		if (Test-Path -LiteralPath $backupZipPath) {
			Move-Item -LiteralPath $backupZipPath -Destination $zipPath
		}
		if (Test-Path -LiteralPath $backupManifestPath) {
			Move-Item -LiteralPath $backupManifestPath -Destination $manifestPath
		}
		throw
	}

	[pscustomobject]@{
		Zip      = $zipPath
		SHA256   = $zipHash
		Manifest = $manifestPath
		Version  = $version
		Dirty    = $isDirty
	}
}
finally {
	$resolvedStage = [System.IO.Path]::GetFullPath($stagePath)
	$expectedStagePrefix = $tempRoot + [System.IO.Path]::DirectorySeparatorChar
	if ($resolvedStage.StartsWith($expectedStagePrefix, [System.StringComparison]::OrdinalIgnoreCase) -and (Test-Path -LiteralPath $resolvedStage)) {
		Remove-Item -LiteralPath $resolvedStage -Recurse -Force
	}
	foreach ($temporaryArtifact in @($temporaryZipPath, $temporaryManifestPath)) {
		if (Test-Path -LiteralPath $temporaryArtifact) {
			Remove-Item -LiteralPath $temporaryArtifact -Force
		}
	}
}
