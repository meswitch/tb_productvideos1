<?php
// decode_chunks.php - ThirtyBeeS product_video.zip decoder (PHP, no Python needed)
// Usage:  php decode_chunks.php pv.txt product_video.zip
if ($argc < 2) { fwrite(STDERR, "Usage: php {$argv[0]} <base64file> [output.zip]\n"); exit(1); }
$in = file_get_contents($argv[1]);
if ($in === false) { fwrite(STDERR, "Cannot read {$argv[1]}\n"); exit(1); }

// Strip chunk headers like "--- CHUNK 1/10 ---", whitespace, newlines, markers
$in = preg_replace('/-+ ?(BEGIN|END) BASE64 ?-+/i', '', $in);
$in = preg_replace('/[^\w+\/=]/', '', str_replace(["\r","\n"," "], '', $in)); // keep only b64 chars
$in = rtrim($in, '='); // normalize padding, re-add correct amount
$pad = (4 - (strlen($in) % 4)) % 4;
$in .= str_repeat('=', $pad);

$data = base64_decode($in, true); // strict mode
if ($data === false) { echo "RESULT: FAILED - invalid Base64 (a chunk was corrupted/dropped)\n"; exit(1); }

$out = $argv[2] ?? 'product_video.zip';
file_put_contents($out, $data);

$md5   = md5_file($out);
$sha256= hash('sha256', $out);
echo "Size: " . filesize($out) . " bytes\n";
echo "MD5:    $md5\n";
echo "SHA256: $sha256\n";

// List zip contents without ZipArchive dependency
if (class_exists('ZipArchive')) {
    $z = new ZipArchive(); if ($z->open($out) === true) {
        echo "Files in zip:\n"; for ($i=0;$i<$z->numFiles;$i++) echo "  - ".$z->getNameIndex($i)."\n"; $z->close();
    }
}
$ok = ($md5 === '11ee794437bd930008b956877c37155a' && filesize($out) === 9765);
echo $ok ? "RESULT: SUCCESS - zip matches the v1.2.0 build\n" : "RESULT: MISMATCH - paste got truncated/corrupted, re-copy chunks 1-10\n";
exit($ok ? 0 : 1);
