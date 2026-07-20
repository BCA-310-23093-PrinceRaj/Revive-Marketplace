param(
    [Parameter(Mandatory = $true)]
    [string]$PdfPath,
    [Parameter(Mandatory = $true)]
    [string]$OutputDocx,
    [Parameter(Mandatory = $true)]
    [string]$OutputText
)

$word = $null
$doc = $null
try {
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $word.DisplayAlerts = 0
    $doc = $word.Documents.Open($PdfPath, $false, $true, $false)
    $doc.SaveAs([ref]$OutputDocx, [ref]16)
    $doc.SaveAs([ref]$OutputText, [ref]2)
    Write-Output "OK"
} finally {
    if ($doc -ne $null) {
        $doc.Close($false)
    }
    if ($word -ne $null) {
        $word.Quit()
    }
}
