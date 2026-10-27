param(
    [string]$OutputDir = ".\wordpress-plugin-ai-index",
    [int]$ChunkSizeMB = 5,
    [switch]$NoZip,
    [switch]$Force
)

$ErrorActionPreference = "Stop"

$ExcludedDirs = @(
    "vendor","node_modules",".git",".idea",".vscode",".vs",
    "coverage",".phpunit.cache",".cache",".sass-cache",".parcel-cache",
    ".turbo",".next","dist","build","tmp","temp","logs"
)

$ExcludedFiles = @(
    ".env",".env.*",".npmrc","auth.json","wp-config.php",
    "composer.lock","package-lock.json","yarn.lock","pnpm-lock.yaml",
    "*.pem","*.key","*.p12","*.pfx","*.crt","*.cer"
)

$AllowedNames = @(
    "composer.json","package.json","readme.txt","readme.md",
    "phpunit.xml","phpunit.xml.dist","phpcs.xml","phpcs.xml.dist",
    ".editorconfig"
)

$AllowedExtensions = @(
    ".php",".inc",".js",".jsx",".mjs",".cjs",".ts",".tsx",".vue",
    ".css",".scss",".sass",".less",".json",".xml",".yml",".yaml",".md",".txt"
)

$ProjectRoot = (Get-Location).Path.TrimEnd('\','/')
$OutputFullPath = [System.IO.Path]::GetFullPath((Join-Path $ProjectRoot $OutputDir))
$ManifestPath = Join-Path $OutputFullPath "index-manifest.json"
$UpdateReportPath = Join-Path $OutputFullPath "02-last-update.txt"
$ZipPath = Join-Path $ProjectRoot "wordpress-plugin-ai-index.zip"

function Rel([string]$FullPath) {
    return $FullPath.Substring($ProjectRoot.Length).TrimStart('\','/')
}

function IsExcludedDir([string]$FullPath) {
    if ($FullPath.StartsWith($OutputFullPath,[System.StringComparison]::OrdinalIgnoreCase)) { return $true }
    $r = Rel $FullPath
    foreach ($d in $ExcludedDirs) {
        if ($r -eq $d -or
            $r.StartsWith("$d\",[System.StringComparison]::OrdinalIgnoreCase) -or
            $r.StartsWith("$d/",[System.StringComparison]::OrdinalIgnoreCase)) { return $true }
    }
    return $false
}

function IsExcludedFile([System.IO.FileInfo]$File) {
    if ($File.FullName.StartsWith($OutputFullPath,[System.StringComparison]::OrdinalIgnoreCase)) { return $true }
    foreach ($p in $ExcludedFiles) { if ($File.Name -like $p) { return $true } }
    return $false
}

function IsAllowedFile([System.IO.FileInfo]$File) {
    if ($AllowedNames -contains $File.Name.ToLowerInvariant()) { return $true }
    return $AllowedExtensions -contains $File.Extension.ToLowerInvariant()
}

function GetFiles {
    $result = [System.Collections.Generic.List[System.IO.FileInfo]]::new()
    $stack = [System.Collections.Generic.Stack[string]]::new()
    $stack.Push($ProjectRoot)

    while ($stack.Count -gt 0) {
        $dir = $stack.Pop()
        try {
            foreach ($sub in [System.IO.Directory]::EnumerateDirectories($dir)) {
                try {
                    $attr = [System.IO.File]::GetAttributes($sub)
                    if (($attr -band [System.IO.FileAttributes]::ReparsePoint) -ne 0) { continue }
                    if (-not (IsExcludedDir $sub)) { $stack.Push($sub) }
                } catch { Write-Warning "Could not inspect directory: $sub" }
            }

            foreach ($fp in [System.IO.Directory]::EnumerateFiles($dir)) {
                try {
                    $f = [System.IO.FileInfo]$fp
                    if (-not (IsExcludedFile $f) -and (IsAllowedFile $f)) { $result.Add($f) }
                } catch { Write-Warning "Could not inspect file: $fp" }
            }
        } catch { Write-Warning "Could not scan directory: $dir" }
    }

    return @($result | Sort-Object FullName)
}

function Sha([string]$Path) {
    return (Get-FileHash -LiteralPath $Path -Algorithm SHA256).Hash.ToLowerInvariant()
}

function NewChunk([int]$Number) {
    $name = "source-{0:D3}.txt" -f $Number
    $path = Join-Path $OutputFullPath $name
    $w = New-Object System.IO.StreamWriter($path,$false,(New-Object System.Text.UTF8Encoding($false)))
    $w.WriteLine("WORDPRESS / WOOCOMMERCE PLUGIN SOURCE INDEX - CHUNK {0:D3}" -f $Number)
    $w.WriteLine("Generated: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')")
    $w.WriteLine("")
    return $w
}

function WriteTree($Files) {
    $out = Join-Path $OutputFullPath "00-project-tree.txt"
    @"
WORDPRESS / WOOCOMMERCE PLUGIN PROJECT INDEX
Generated: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")
Project root: $ProjectRoot
Indexed files: $($Files.Count)

============================================================
PROJECT FILE TREE
============================================================

"@ | Set-Content -LiteralPath $out -Encoding UTF8
    foreach ($f in $Files) { Add-Content -LiteralPath $out -Value (Rel $f.FullName) -Encoding UTF8 }
}

function WriteSummary($Files,[int]$Added,[int]$Modified,[int]$Deleted) {
    $php = @($Files | Where-Object { $_.Extension.ToLowerInvariant() -in @(".php",".inc") }).Count
    $js = @($Files | Where-Object { $_.Extension.ToLowerInvariant() -in @(".js",".jsx",".mjs",".cjs",".ts",".tsx",".vue") }).Count
    $css = @($Files | Where-Object { $_.Extension.ToLowerInvariant() -in @(".css",".scss",".sass",".less") }).Count
    @"
WORDPRESS / WOOCOMMERCE PLUGIN PROJECT SUMMARY
==============================================

Generated: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")
Total indexed files: $($Files.Count)
PHP / INC files:     $php
JS / TS / Vue files: $js
Style files:         $css

Last update:
Added:                $Added
Modified:             $Modified
Deleted:              $Deleted
"@ | Set-Content -LiteralPath (Join-Path $OutputFullPath "01-summary.txt") -Encoding UTF8
}

function WriteUpdateReport($Added,$Modified,$Deleted) {
    $lines = [System.Collections.Generic.List[string]]::new()
    $lines.Add("WORDPRESS / WOOCOMMERCE PLUGIN INDEX - LAST UPDATE")
    $lines.Add("==================================================")
    $lines.Add("")
    $lines.Add("Generated: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')")
    $lines.Add("Added:     $($Added.Count)")
    $lines.Add("Modified:  $($Modified.Count)")
    $lines.Add("Deleted:   $($Deleted.Count)")
    $lines.Add("")

    foreach ($section in @(
        @{Name="ADDED";Items=$Added},
        @{Name="MODIFIED";Items=$Modified},
        @{Name="DELETED";Items=$Deleted}
    )) {
        $lines.Add("=== $($section.Name) ===")
        if ($section.Items.Count -eq 0) {
            $lines.Add("(none)")
        } else {
            foreach ($x in @($section.Items | Sort-Object)) { $lines.Add([string]$x) }
        }
        $lines.Add("")
    }

    $lines | Set-Content -LiteralPath $UpdateReportPath -Encoding UTF8
}

function WriteChunks($Files) {
    Get-ChildItem -LiteralPath $OutputFullPath -Filter "source-*.txt" -File -ErrorAction SilentlyContinue | Remove-Item -Force
    if ($Files.Count -eq 0) { return 0 }

    $limit = $ChunkSizeMB * 1MB
    $num = 1
    $size = 0
    $writer = NewChunk $num

    try {
        foreach ($f in $Files) {
            $rp = Rel $f.FullName
            try {
                $content = [System.IO.File]::ReadAllText($f.FullName)
                $header = "`r`n======================================================================`r`nFILE: $rp`r`n======================================================================`r`n`r`n"
                $block = $header + $content + "`r`n"
                $bytes = [System.Text.Encoding]::UTF8.GetByteCount($block)

                if ($size -gt 0 -and ($size + $bytes) -gt $limit) {
                    $writer.Flush(); $writer.Close()
                    $num++; $size = 0; $writer = NewChunk $num
                }

                $writer.Write($block)
                $size += $bytes
            } catch { Write-Warning "Could not read: $rp" }
        }
    } finally {
        if ($null -ne $writer) { $writer.Flush(); $writer.Close() }
    }
    return $num
}

function WriteList([string]$Name,$Items) {
    @($Items | Sort-Object) | Set-Content -LiteralPath (Join-Path $OutputFullPath $Name) -Encoding UTF8
}

function WriteInventories($Files) {
    WriteList "10-php-files.txt" @(
        $Files | Where-Object { $_.Extension.ToLowerInvariant() -in @(".php",".inc") } |
        ForEach-Object { Rel $_.FullName }
    )
    WriteList "11-js-ts-files.txt" @(
        $Files | Where-Object { $_.Extension.ToLowerInvariant() -in @(".js",".jsx",".mjs",".cjs",".ts",".tsx",".vue") } |
        ForEach-Object { Rel $_.FullName }
    )
    WriteList "12-style-files.txt" @(
        $Files | Where-Object { $_.Extension.ToLowerInvariant() -in @(".css",".scss",".sass",".less") } |
        ForEach-Object { Rel $_.FullName }
    )
    WriteList "13-templates-views.txt" @(
        $Files | Where-Object {
            $r = (Rel $_.FullName).Replace('\','/').ToLowerInvariant()
            $r -match '(^|/)(templates?|views?)/'
        } | ForEach-Object { Rel $_.FullName }
    )
    WriteList "14-tests.txt" @(
        $Files | Where-Object {
            $r = (Rel $_.FullName).Replace('\','/').ToLowerInvariant()
            $r -match '(^|/)(tests?|specs?)/' -or $_.Name -match '(Test|Spec)\.'
        } | ForEach-Object { Rel $_.FullName }
    )
    WriteList "15-root-entrypoints.txt" @(
        $Files | Where-Object {
            $_.DirectoryName -eq $ProjectRoot -and $_.Extension.ToLowerInvariant() -in @(".php",".inc")
        } | ForEach-Object { Rel $_.FullName }
    )
}

function WriteZip {
    if ($NoZip) { return }
    if (Test-Path $ZipPath) { Remove-Item -LiteralPath $ZipPath -Force }
    Compress-Archive -Path (Join-Path $OutputFullPath "*") -DestinationPath $ZipPath -CompressionLevel Optimal -Force
}

if (-not (Test-Path $OutputFullPath)) {
    New-Item -ItemType Directory -Path $OutputFullPath -Force | Out-Null
}

Write-Host ""
Write-Host "Scanning WordPress / WooCommerce plugin..." -ForegroundColor Cyan
$Files = @(GetFiles)
Write-Host "Found $($Files.Count) relevant files." -ForegroundColor Green

$OldByPath = @{}
if (Test-Path $ManifestPath) {
    try {
        $oldManifest = Get-Content -LiteralPath $ManifestPath -Raw | ConvertFrom-Json
        foreach ($item in @($oldManifest.files)) { $OldByPath[[string]$item.path] = $item }
        Write-Host "Loaded existing manifest with $($OldByPath.Count) files." -ForegroundColor DarkGray
    } catch {
        Write-Warning "Manifest could not be read. Full refresh will be performed."
        $OldByPath = @{}
    }
} else {
    Write-Host "No manifest found. Full refresh will be performed." -ForegroundColor Yellow
}

$Added = [System.Collections.Generic.List[string]]::new()
$Modified = [System.Collections.Generic.List[string]]::new()
$Deleted = [System.Collections.Generic.List[string]]::new()
$CurrentPaths = @{}
$NewManifestFiles = [System.Collections.Generic.List[object]]::new()

foreach ($f in $Files) {
    $rp = Rel $f.FullName
    $CurrentPaths[$rp] = $true
    $old = $null
    if ($OldByPath.ContainsKey($rp)) { $old = $OldByPath[$rp] }

    $hash = $null

    if ($null -eq $old) {
        $Added.Add($rp)
        $hash = Sha $f.FullName
    } elseif ($Force) {
        $Modified.Add($rp)
        $hash = Sha $f.FullName
    } else {
        $sameLen = ([int64]$old.length -eq [int64]$f.Length)
        $sameTime = ([int64]$old.lastWriteTimeUtcTicks -eq [int64]$f.LastWriteTimeUtc.Ticks)

        if ($sameLen -and $sameTime -and $old.sha256) {
            $hash = [string]$old.sha256
        } else {
            $hash = Sha $f.FullName
            if (-not $old.sha256 -or $hash -ne [string]$old.sha256) { $Modified.Add($rp) }
        }
    }

    if ($null -eq $hash) { $hash = Sha $f.FullName }

    $NewManifestFiles.Add([PSCustomObject]@{
        path = $rp
        length = [int64]$f.Length
        lastWriteTimeUtcTicks = [int64]$f.LastWriteTimeUtc.Ticks
        sha256 = $hash
    })
}

foreach ($oldPath in $OldByPath.Keys) {
    if (-not $CurrentPaths.ContainsKey($oldPath)) { $Deleted.Add([string]$oldPath) }
}

$HasChanges = $Force.IsPresent -or $Added.Count -gt 0 -or $Modified.Count -gt 0 -or $Deleted.Count -gt 0

if ($HasChanges) {
    Write-Host ""
    Write-Host "Changes detected:" -ForegroundColor Yellow
    Write-Host "  Added:    $($Added.Count)"
    Write-Host "  Modified: $($Modified.Count)"
    Write-Host "  Deleted:  $($Deleted.Count)"

    WriteTree $Files
    WriteSummary $Files $Added.Count $Modified.Count $Deleted.Count
    WriteUpdateReport $Added $Modified $Deleted

    Write-Host "Refreshing source chunks..." -ForegroundColor Cyan
    $chunks = WriteChunks $Files
    WriteInventories $Files

    [PSCustomObject]@{
        version = 1
        generatedAtUtc = [DateTime]::UtcNow.ToString("o")
        projectRoot = $ProjectRoot
        chunkSizeMB = $ChunkSizeMB
        fileCount = $Files.Count
        files = $NewManifestFiles.ToArray()
    } | ConvertTo-Json -Depth 5 | Set-Content -LiteralPath $ManifestPath -Encoding UTF8

    WriteZip

    Write-Host ""
    Write-Host "Index updated successfully." -ForegroundColor Green
    Write-Host "Indexed files: $($Files.Count)"
    Write-Host "Source chunks: $chunks"
    if (-not $NoZip) { Write-Host "ZIP:           $ZipPath" }
} else {
    Write-Host ""
    Write-Host "No source changes detected. Existing index is current." -ForegroundColor Green
    if (-not $NoZip -and -not (Test-Path $ZipPath)) {
        WriteZip
        Write-Host "ZIP created: $ZipPath"
    }
}
