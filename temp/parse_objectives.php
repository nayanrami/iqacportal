<?php
// Parse objectives.html looking for all content between key headings
$html = file_get_contents('c:/xampp/htdocs/feedback/objectives.html');
$doc = new DOMDocument();
@$doc->loadHTML($html);
$xpath = new DOMXPath($doc);

// Get all paragraphs, divs with text
echo "=== Content blocks ===" . PHP_EOL;
$nodes = $xpath->query('//p|//li|//div[@class]|//h3|//h2|//h4');
foreach ($nodes as $node) {
    $text = trim(preg_replace('/\s+/', ' ', $node->textContent));
    if (strlen($text) > 20 && strlen($text) < 2000) {
        echo '[' . $node->tagName . '] ' . $text . PHP_EOL . PHP_EOL;
    }
}
