$word = New-Object -ComObject Word.Application
$word.Visible = $false
$doc = $word.Documents.Open("C:\xampp\htdocs\revive\Project_Report_Final.doc")
$doc.SaveAs([ref]"C:\xampp\htdocs\revive\Project_Report_Prince_Raj.docx", [ref]16)
$doc.Close()
$word.Quit()
