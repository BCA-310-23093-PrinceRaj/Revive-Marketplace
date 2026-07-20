param(
    [string]$ProjectRoot = "C:\xampp\htdocs\revive"
)

$ErrorActionPreference = "Stop"

$outDir = Join-Path $ProjectRoot "screenshots\code"
if (!(Test-Path $outDir)) {
    New-Item -ItemType Directory -Path $outDir | Out-Null
}

Add-Type -AssemblyName System.Drawing

function New-CodeSnapshot {
    param(
        [string]$RelativePath,
        [string]$Title,
        [int]$StartLine,
        [int]$EndLine,
        [string]$OutputName
    )

    $path = Join-Path $ProjectRoot $RelativePath
    $allLines = Get-Content -LiteralPath $path
    $selected = for ($i = $StartLine; $i -le $EndLine -and $i -le $allLines.Count; $i++) {
        "{0,4}  {1}" -f $i, $allLines[$i - 1]
    }

    $width = 1500
    $lineHeight = 24
    $padding = 34
    $headerHeight = 74
    $height = [Math]::Max(520, $headerHeight + ($selected.Count * $lineHeight) + ($padding * 2))

    $bmp = New-Object System.Drawing.Bitmap($width, $height)
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
    $g.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::ClearTypeGridFit

    $bg = [System.Drawing.Color]::FromArgb(15, 16, 20)
    $panel = [System.Drawing.Color]::FromArgb(25, 27, 34)
    $accent = [System.Drawing.Color]::FromArgb(188, 255, 0)
    $text = [System.Drawing.Color]::FromArgb(236, 238, 242)
    $muted = [System.Drawing.Color]::FromArgb(150, 156, 170)

    $g.Clear($bg)
    $g.FillRectangle((New-Object System.Drawing.SolidBrush($panel)), 20, 20, $width - 40, $height - 40)
    $g.FillRectangle((New-Object System.Drawing.SolidBrush($accent)), 20, 20, 9, $height - 40)

    $titleFont = New-Object System.Drawing.Font("Segoe UI", 24, [System.Drawing.FontStyle]::Bold)
    $metaFont = New-Object System.Drawing.Font("Segoe UI", 13, [System.Drawing.FontStyle]::Regular)
    $codeFont = New-Object System.Drawing.Font("Consolas", 15, [System.Drawing.FontStyle]::Regular)

    $g.DrawString($Title, $titleFont, (New-Object System.Drawing.SolidBrush($text)), 50, 36)
    $g.DrawString($RelativePath + "  |  lines " + $StartLine + "-" + $EndLine, $metaFont, (New-Object System.Drawing.SolidBrush($muted)), 52, 68)

    $y = 112
    foreach ($line in $selected) {
        $trimmed = if ($line.Length -gt 150) { $line.Substring(0, 150) } else { $line }
        $g.DrawString($trimmed, $codeFont, (New-Object System.Drawing.SolidBrush($text)), 52, $y)
        $y += $lineHeight
    }

    $outPath = Join-Path $outDir $OutputName
    $bmp.Save($outPath, [System.Drawing.Imaging.ImageFormat]::Png)

    $g.Dispose()
    $bmp.Dispose()

    Write-Output $outPath
}

New-CodeSnapshot -RelativePath "chat.php" -Title "Chat UI and AJAX Polling Code" -StartLine 95 -EndLine 205 -OutputName "code_chat_php.png"
New-CodeSnapshot -RelativePath "actions\chat_action.php" -Title "Chat Message Upload and Storage Code" -StartLine 14 -EndLine 84 -OutputName "code_chat_action.png"
New-CodeSnapshot -RelativePath "database.sql" -Title "Chats Table Database Structure" -StartLine 89 -EndLine 103 -OutputName "code_chats_table.png"
