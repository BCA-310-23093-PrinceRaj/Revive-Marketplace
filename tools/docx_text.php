<?php
$xmlPath = $argv[1] ?? '';
if ($xmlPath === '' || !is_file($xmlPath)) {
    fwrite(STDERR, "Usage: php docx_text.php path/to/document.xml\n");
    exit(1);
}

$xml = file_get_contents($xmlPath);
$paragraphs = preg_split('/<\/w:p>/', $xml);
$i = 0;
foreach ($paragraphs as $paragraph) {
    preg_match_all('/<w:t[^>]*>(.*?)<\/w:t>/s', $paragraph, $matches);
    if (!$matches[1]) {
        continue;
    }
    $text = html_entity_decode(implode('', $matches[1]), ENT_QUOTES | ENT_XML1, 'UTF-8');
    $text = trim(preg_replace('/\s+/', ' ', $text));
    if ($text === '') {
        continue;
    }
    $i++;
    echo str_pad((string)$i, 4, ' ', STR_PAD_LEFT) . ': ' . $text . PHP_EOL;
}
