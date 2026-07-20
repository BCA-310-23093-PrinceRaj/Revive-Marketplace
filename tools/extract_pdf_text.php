<?php
$pdfPath = $argv[1] ?? '';
if ($pdfPath === '' || !is_file($pdfPath)) {
    fwrite(STDERR, "Usage: php extract_pdf_text.php file.pdf\n");
    exit(1);
}

$data = file_get_contents($pdfPath);
$pageCount = preg_match_all('/\/Type\s*\/Page\b/', $data);
if (preg_match('/\/Count\s+(\d+)/', $data, $m)) {
    $pageCount = max($pageCount, (int) $m[1]);
}

function decode_pdf_string(string $s): string
{
    $s = preg_replace_callback('/\\\\([0-7]{1,3})/', fn($m) => chr(octdec($m[1])), $s);
    $s = strtr($s, [
        '\\n' => "\n",
        '\\r' => "\r",
        '\\t' => "\t",
        '\\b' => "\b",
        '\\f' => "\f",
        '\\(' => '(',
        '\\)' => ')',
        '\\\\' => '\\',
    ]);
    return $s;
}

function decode_pdf_hex(string $hex): string
{
    $hex = preg_replace('/[^0-9a-fA-F]/', '', $hex);
    if (strlen($hex) % 2 === 1) {
        $hex .= '0';
    }
    $bytes = hex2bin($hex) ?: '';
    if (str_starts_with($bytes, "\xFE\xFF")) {
        $out = '';
        for ($i = 2; $i + 1 < strlen($bytes); $i += 2) {
            $code = (ord($bytes[$i]) << 8) | ord($bytes[$i + 1]);
            $out .= mb_convert_encoding(pack('n', $code), 'UTF-8', 'UTF-16BE');
        }
        return $out;
    }
    return $bytes;
}

function clean_text(string $text): string
{
    $text = preg_replace('/[^\P{C}\n\r\t]+/u', ' ', $text);
    $text = preg_replace('/[ \t]+/', ' ', $text);
    $text = preg_replace('/\s*\n\s*/', "\n", $text);
    return trim($text);
}

$chunks = [];
preg_match_all('/<<.*?\/Filter\s*\/FlateDecode.*?>>\s*stream\r?\n(.*?)\r?\nendstream/s', $data, $streams);
foreach ($streams[1] as $stream) {
    $decoded = @gzuncompress($stream);
    if ($decoded === false) {
        $decoded = @zlib_decode($stream);
    }
    if ($decoded === false) {
        continue;
    }

    preg_match_all('/\((?:\\\\.|[^\\\\)])*\)\s*(?:Tj|TJ|\'|")/s', $decoded, $literalMatches);
    foreach ($literalMatches[0] as $match) {
        if (preg_match_all('/\((?:\\\\.|[^\\\\)])*\)/s', $match, $strings)) {
            foreach ($strings[0] as $str) {
                $chunks[] = decode_pdf_string(substr($str, 1, -1));
            }
        }
    }

    preg_match_all('/<([0-9a-fA-F\s]+)>\s*Tj/s', $decoded, $hexMatches);
    foreach ($hexMatches[1] as $hex) {
        $chunks[] = decode_pdf_hex($hex);
    }

    preg_match_all('/\[(.*?)\]\s*TJ/s', $decoded, $arrayMatches);
    foreach ($arrayMatches[1] as $array) {
        preg_match_all('/\((?:\\\\.|[^\\\\)])*\)|<([0-9a-fA-F\s]+)>/s', $array, $parts);
        foreach ($parts[0] as $part) {
            if ($part[0] === '(') {
                $chunks[] = decode_pdf_string(substr($part, 1, -1));
            } elseif ($part[0] === '<') {
                $chunks[] = decode_pdf_hex(substr($part, 1, -1));
            }
        }
        $chunks[] = "\n";
    }
}

$text = clean_text(implode(' ', $chunks));
echo "PAGES: {$pageCount}\n";
echo "TEXT_LENGTH: " . strlen($text) . "\n\n";
echo $text . "\n";
