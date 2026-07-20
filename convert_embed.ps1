$word = New-Object -ComObject Word.Application
$word.Visible = $false
try {
    $doc = $word.Documents.Open("C:\xampp\htdocs\revive\Project_Report_Final.doc")
    
    # Force Word to embed linked pictures
    foreach ($shape in $doc.InlineShapes) {
        if ($shape.LinkFormat -ne $null) {
            $shape.LinkFormat.SavePictureWithDocument = $true
            $shape.LinkFormat.BreakLink()
        }
    }
    
    $doc.SaveAs([ref]"C:\xampp\htdocs\revive\Project_Report_Prince_Raj.docx", [ref]16)
    $doc.Close()
} finally {
    $word.Quit()
}
