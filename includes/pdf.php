<?php

function buildSimplePdf(array $lines): string
{
    $escaped = [];
    foreach ($lines as $line) {
        $line = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], (string) $line);
        $escaped[] = $line;
    }

    $stream = "BT\n/F1 12 Tf\n50 790 Td\n";
    foreach ($escaped as $idx => $line) {
        if ($idx > 0) {
            $stream .= "0 -18 Td\n";
        }
        $stream .= '(' . $line . ") Tj\n";
    }
    $stream .= "ET";

    $objects = [];
    $objects[] = "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n";
    $objects[] = "2 0 obj << /Type /Pages /Count 1 /Kids [3 0 R] >> endobj\n";
    $objects[] = "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj\n";
    $objects[] = "4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n";
    $objects[] = "5 0 obj << /Length " . strlen($stream) . " >> stream\n" . $stream . "\nendstream endobj\n";

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $obj) {
        $offsets[] = strlen($pdf);
        $pdf .= $obj;
    }

    $xrefPos = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= sprintf('%010d 00000 n ', $offsets[$i]) . "\n";
    }
    $pdf .= "trailer << /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefPos . "\n%%EOF";

    return $pdf;
}
