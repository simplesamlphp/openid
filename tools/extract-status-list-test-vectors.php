<?php

declare(strict_types=1);

/**
 * Extracts the Appendix C test vectors from the Token Status List draft into a JSON fixture.
 *
 * The 8-bit vector alone asserts 256 indices, so the vectors are generated rather than transcribed.
 * Every extracted index is decoded and compared against the value the spec states for it, so a
 * mis-parse fails here instead of turning into a fixture that agrees with a broken implementation.
 *
 * Usage:
 *   php tools/extract-status-list-test-vectors.php \
 *       specifications/draft-ietf-oauth-status-list.md \
 *       tests/data/status-list-test-vectors-appendix-c.json
 */

$specPath = $argv[1] ?? throw new RuntimeException('Missing spec path.');
$outPath = $argv[2] ?? throw new RuntimeException('Missing output path.');

$lines = file($specPath, FILE_IGNORE_NEW_LINES);
if ($lines === false) {
    throw new RuntimeException('Unable to read spec.');
}

// Locate the Appendix C section headings.
$sections = [];
$current = null;
foreach ($lines as $number => $line) {
    if (preg_match('/^#{2,3} /', $line) !== 1) {
        continue;
    }

    // Any heading closes the section that was open.
    if ($current !== null) {
        $sections[$current]['end'] = $number;
        $current = null;
    }

    if (preg_match('/^### \[(C\.\d)\.\]/', $line, $matches) === 1) {
        $current = $matches[1];
        $sections[$current] = ['start' => $number, 'end' => count($lines)];
    }
}

if ($sections === []) {
    throw new RuntimeException('No Appendix C sections found.');
}

$vectors = [];

foreach ($sections as $name => $bounds) {
    $body = array_slice($lines, $bounds['start'], $bounds['end'] - $bounds['start']);

    $statuses = [];
    $bits = null;
    $lst = null;
    $collectingLst = false;
    $lstParts = [];

    foreach ($body as $line) {
        if (preg_match('/^\s*status\[(\d+)\]\s*=\s*0b([01]+)\s*$/', $line, $matches) === 1) {
            $idx = (int)$matches[1];
            if (array_key_exists($idx, $statuses)) {
                throw new RuntimeException(sprintf('%s: duplicate index %d in the spec text.', $name, $idx));
            }
            $statuses[$idx] = bindec($matches[2]);
            continue;
        }

        if ($bits === null && preg_match('/^\s*"bits":\s*(\d+)\s*,?\s*$/', $line, $matches) === 1) {
            $bits = (int)$matches[1];
            continue;
        }

        // The "lst" value is wrapped across several indented lines; join until the closing quote.
        if ($lst === null && !$collectingLst && preg_match('/^\s*"lst":\s*"(.*)$/', $line, $matches) === 1) {
            $rest = $matches[1];
            if (str_ends_with($rest, '"')) {
                $lst = substr($rest, 0, -1);
                continue;
            }
            $collectingLst = true;
            $lstParts[] = $rest;
            continue;
        }

        if ($collectingLst) {
            $rest = trim($line);
            if (str_ends_with($rest, '"')) {
                $lstParts[] = substr($rest, 0, -1);
                $lst = implode('', $lstParts);
                $collectingLst = false;
                continue;
            }
            $lstParts[] = $rest;
        }
    }

    if ($bits === null || $lst === null || $statuses === []) {
        throw new RuntimeException(sprintf('%s: incomplete vector (bits/lst/statuses).', $name));
    }

    // Sanity-check the extraction by decoding the list ourselves and reading back every asserted index.
    if (preg_match('/^[A-Za-z0-9_-]+$/', $lst) !== 1) {
        throw new RuntimeException(sprintf('%s: lst is not unpadded base64url.', $name));
    }

    $padded = strtr($lst, '-_', '+/');
    $remainder = strlen($padded) % 4;
    if ($remainder !== 0) {
        $padded .= str_repeat('=', 4 - $remainder);
    }
    $compressed = base64_decode($padded, true);
    if ($compressed === false) {
        throw new RuntimeException(sprintf('%s: lst did not base64-decode.', $name));
    }

    $bytes = @gzuncompress($compressed);
    if ($bytes === false) {
        throw new RuntimeException(sprintf('%s: lst did not zlib-decompress.', $name));
    }

    $perByte = intdiv(8, $bits);
    $mask = (1 << $bits) - 1;

    foreach ($statuses as $idx => $expected) {
        $byteIndex = intdiv($idx, $perByte);
        if ($byteIndex >= strlen($bytes)) {
            throw new RuntimeException(sprintf('%s: index %d out of bounds.', $name, $idx));
        }
        $shift = ($idx % $perByte) * $bits;
        $actual = (ord($bytes[$byteIndex]) >> $shift) & $mask;
        if ($actual !== $expected) {
            throw new RuntimeException(
                sprintf('%s: index %d decoded as %d, spec says %d.', $name, $idx, $actual, $expected),
            );
        }
    }

    ksort($statuses);

    $vectors[$name] = [
        'bits' => $bits,
        'capacity' => 1 << 20, // Appendix C: "All examples are initialized with a size of 2^20 entries."
        'byteLength' => strlen($bytes),
        'statuses' => $statuses,
        'lst' => $lst,
    ];

    printf(
        "%s: bits=%d, %d asserted indices, %d decompressed bytes, lst %d chars — all indices verified.%s",
        $name,
        $bits,
        count($statuses),
        strlen($bytes),
        strlen($lst),
        PHP_EOL,
    );
}

$json = json_encode($vectors, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
file_put_contents($outPath, $json . PHP_EOL);

printf('Wrote %s (%d bytes).%s', $outPath, strlen($json), PHP_EOL);
