<?php
// Parse curriculum.html to extract all course data
$html = file_get_contents('c:/xampp/htdocs/feedback/curriculum.html');
$doc = new DOMDocument();
@$doc->loadHTML($html);
$xpath = new DOMXPath($doc);

// Find all tables
$tables = $xpath->query('//table');
echo "Tables found: " . $tables->length . PHP_EOL;

foreach ($tables as $i => $table) {
    $rows = $xpath->query('.//tr', $table);
    echo "\n=== Table $i ({$rows->length} rows) ===" . PHP_EOL;
    foreach ($rows as $r) {
        $cells = $xpath->query('.//td|.//th', $r);
        $line = [];
        foreach ($cells as $c) {
            $text = trim(preg_replace('/\s+/', ' ', $c->textContent));
            if ($text) $line[] = $text;
        }
        if (!empty($line)) {
            echo implode(' | ', $line) . PHP_EOL;
        }
    }
}

// Also look for syllabus links
echo "\n=== Syllabus Links ===" . PHP_EOL;
$links = $xpath->query('//a[contains(@href, "syllabus") or contains(@href, "IT")]');
foreach ($links as $link) {
    echo trim($link->textContent) . ' => ' . $link->getAttribute('href') . PHP_EOL;
}
