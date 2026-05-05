$word = New-Object -ComObject Word.Application
try {
    $doc = $word.Documents.Open('C:\wamp64\www\OmniShop-PHP\OmniShop-LocalSetup-Eric.docx')
    $doc.Content.Text | Out-File -FilePath 'C:\wamp64\www\OmniShop-PHP\OmniShop-LocalSetup-Eric.txt' -Encoding utf8
    $doc.Close()
} finally {
    $word.Quit()
}
