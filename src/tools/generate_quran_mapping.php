<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Quran Mapping Generator
|--------------------------------------------------------------------------
|
| Generates:
|
| database/data/quran_ayah_pages.json
|
| Based on:
| Mushaf-Learning/quran-text
|
| The source provides the starting ayah of each page
| of the 604-page Madinah Mushaf.
|
|--------------------------------------------------------------------------
*/

$pagesUrl =
    'https://raw.githubusercontent.com/Mushaf-Learning/quran-text/main/metadata/pages.json';

$surahsUrl =
    'https://raw.githubusercontent.com/Mushaf-Learning/quran-text/main/metadata/surahs.json';

$outputPath =
    __DIR__ . '/../database/data/quran_ayah_pages.json';


/*
|--------------------------------------------------------------------------
| Helper: Download JSON
|--------------------------------------------------------------------------
*/

function downloadJson(string $url): array
{
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'Ehkam Quran Mapping Generator',
        ],
    ]);

    $content = file_get_contents($url, false, $context);

    if ($content === false) {
        throw new RuntimeException(
            "Failed to download: {$url}"
        );
    }

    try {

        return json_decode(
            $content,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

    } catch (JsonException $e) {

        throw new RuntimeException(
            "Invalid JSON from {$url}: "
            . $e->getMessage()
        );
    }
}


/*
|--------------------------------------------------------------------------
| 1. Download page metadata
|--------------------------------------------------------------------------
*/

echo "Downloading page metadata...\n";

$pages = downloadJson($pagesUrl);


/*
|--------------------------------------------------------------------------
| 2. Download surah metadata
|--------------------------------------------------------------------------
*/

echo "Downloading surah metadata...\n";

$surahs = downloadJson($surahsUrl);


/*
|--------------------------------------------------------------------------
| 3. Validate pages
|--------------------------------------------------------------------------
*/

if (count($pages) !== 604) {

    throw new RuntimeException(
        "Expected 604 pages, got "
        . count($pages)
    );
}


/*
|--------------------------------------------------------------------------
| 4. Build ayah count map
|--------------------------------------------------------------------------
*/

$ayahCounts = [];

$totalAyahs = 0;

foreach ($surahs as $surah) {

    $number = (int) $surah['number'];

    $ayahCount = (int) $surah['ayah_count'];

    $ayahCounts[$number] = $ayahCount;

    $totalAyahs += $ayahCount;
}


/*
|--------------------------------------------------------------------------
| 5. Validate Quran ayah count
|--------------------------------------------------------------------------
*/

if ($totalAyahs !== 6236) {

    throw new RuntimeException(
        "Expected 6236 ayahs, got "
        . $totalAyahs
    );
}


/*
|--------------------------------------------------------------------------
| 6. Generate mapping
|--------------------------------------------------------------------------
*/

$mapping = [];

$pageCount = count($pages);

for ($i = 0; $i < $pageCount; $i++) {

    $current = $pages[$i];

    $pageNumber = (int) $current['page'];

    $startSurah = (int) $current['sura'];

    $startAyah = (int) $current['aya'];


    /*
    |--------------------------------------------------------------------------
    | Next page boundary
    |--------------------------------------------------------------------------
    */

    if ($i + 1 < $pageCount) {

        $next = $pages[$i + 1];

        $nextSurah = (int) $next['sura'];

        $nextAyah = (int) $next['aya'];

    } else {

        /*
        |--------------------------------------------------------------------------
        | Last page
        |--------------------------------------------------------------------------
        */

        $nextSurah = 114;

        $nextAyah = $ayahCounts[114] + 1;
    }


    /*
    |--------------------------------------------------------------------------
    | Walk through ayahs
    |--------------------------------------------------------------------------
    */

    $surahNumber = $startSurah;

    $ayahNumber = $startAyah;

    while (
        $surahNumber < $nextSurah ||
        (
            $surahNumber === $nextSurah &&
            $ayahNumber < $nextAyah
        )
    ) {

        $mapping[] = [
            'surah_number' => $surahNumber,
            'ayah_number' => $ayahNumber,
            'page_number' => $pageNumber,
        ];


        /*
        |--------------------------------------------------------------------------
        | Next ayah
        |--------------------------------------------------------------------------
        */

        $ayahNumber++;


        /*
        |--------------------------------------------------------------------------
        | Move to next surah
        |--------------------------------------------------------------------------
        */

        if (
            isset($ayahCounts[$surahNumber]) &&
            $ayahNumber > $ayahCounts[$surahNumber]
        ) {

            $surahNumber++;

            $ayahNumber = 1;
        }
    }
}


/*
|--------------------------------------------------------------------------
| 7. Validate generated mapping
|--------------------------------------------------------------------------
*/

echo "Generated ayahs: "
    . count($mapping)
    . PHP_EOL;

if (count($mapping) !== 6236) {

    throw new RuntimeException(
        "ERROR: Expected 6236 ayahs but generated "
        . count($mapping)
    );
}


/*
|--------------------------------------------------------------------------
| 8. Validate first ayah
|--------------------------------------------------------------------------
*/

$first = $mapping[0];

if (
    $first['surah_number'] !== 1 ||
    $first['ayah_number'] !== 1 ||
    $first['page_number'] !== 1
) {

    throw new RuntimeException(
        "Invalid first mapping."
    );
}


/*
|--------------------------------------------------------------------------
| 9. Validate last ayah
|--------------------------------------------------------------------------
*/

$last = $mapping[count($mapping) - 1];

if (
    $last['surah_number'] !== 114 ||
    $last['ayah_number'] !== 6 ||
    $last['page_number'] !== 604
) {

    throw new RuntimeException(
        "Invalid last mapping."
    );
}


/*
|--------------------------------------------------------------------------
| 10. Validate page count
|--------------------------------------------------------------------------
*/

$usedPages = [];

foreach ($mapping as $row) {

    $usedPages[$row['page_number']] = true;
}

if (count($usedPages) !== 604) {

    throw new RuntimeException(
        "Expected 604 pages but found "
        . count($usedPages)
    );
}


/*
|--------------------------------------------------------------------------
| 11. Ensure output directory
|--------------------------------------------------------------------------
*/

$outputDirectory = dirname($outputPath);

if (!is_dir($outputDirectory)) {

    mkdir(
        $outputDirectory,
        0777,
        true
    );
}


/*
|--------------------------------------------------------------------------
| 12. Convert to JSON
|--------------------------------------------------------------------------
*/

$json = json_encode(
    $mapping,
    JSON_PRETTY_PRINT |
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES |
    JSON_THROW_ON_ERROR
);


/*
|--------------------------------------------------------------------------
| 13. Write file
|--------------------------------------------------------------------------
*/

$result = file_put_contents(
    $outputPath,
    $json
);

if ($result === false) {

    throw new RuntimeException(
        "Unable to write: {$outputPath}"
    );
}


/*
|--------------------------------------------------------------------------
| 14. Done
|--------------------------------------------------------------------------
*/

echo PHP_EOL;

echo "============================================"
    . PHP_EOL;

echo "Quran mapping generated successfully!"
    . PHP_EOL;

echo "============================================"
    . PHP_EOL;

echo "Surahs : 114"
    . PHP_EOL;

echo "Ayahs  : " . count($mapping)
    . PHP_EOL;

echo "Pages  : " . count($usedPages)
    . PHP_EOL;

echo "File   : {$outputPath}"
    . PHP_EOL;

echo "Size   : {$result} bytes"
    . PHP_EOL;

echo "============================================"
    . PHP_EOL;