<?php

namespace Database\Seeders;

use App\Models\QuranAyahPage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class QuranAyahPageSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/quran_ayah_pages.json');

        /*
        |--------------------------------------------------------------------------
        | 1. Check JSON file
        |--------------------------------------------------------------------------
        */

        if (!file_exists($path)) {
            throw new RuntimeException(
                "Quran mapping file not found: {$path}"
            );
        }

        if (filesize($path) === 0) {
            throw new RuntimeException(
                "Quran mapping file is empty: {$path}"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Read JSON
        |--------------------------------------------------------------------------
        */

        $json = file_get_contents($path);

        if ($json === false) {
            throw new RuntimeException(
                "Unable to read Quran mapping file."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Decode JSON
        |--------------------------------------------------------------------------
        */

        try {

            $data = json_decode(
                $json,
                true,
                512,
                JSON_THROW_ON_ERROR
            );

        } catch (\JsonException $e) {

            throw new RuntimeException(
                "Invalid Quran mapping JSON: " . $e->getMessage()
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Check JSON structure
        |--------------------------------------------------------------------------
        */

        if (!is_array($data)) {

            throw new RuntimeException(
                "Quran mapping must be a JSON array."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Expected Quran size
        |--------------------------------------------------------------------------
        */

        $expectedAyahs = 6236;

        if (count($data) !== $expectedAyahs) {

            throw new RuntimeException(
                "Invalid Quran mapping count. " .
                "Expected {$expectedAyahs} ayahs, " .
                "got " . count($data)
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Validate every record
        |--------------------------------------------------------------------------
        */

        $seen = [];

        foreach ($data as $index => $row) {

            if (!is_array($row)) {

                throw new RuntimeException(
                    "Invalid row at index {$index}."
                );
            }

            if (
                !array_key_exists('surah_number', $row) ||
                !array_key_exists('ayah_number', $row) ||
                !array_key_exists('page_number', $row)
            ) {

                throw new RuntimeException(
                    "Missing required fields at index {$index}."
                );
            }

            $surah = (int) $row['surah_number'];
            $ayah = (int) $row['ayah_number'];
            $page = (int) $row['page_number'];

            /*
            |--------------------------------------------------------------------------
            | Surah validation
            |--------------------------------------------------------------------------
            */

            if ($surah < 1 || $surah > 114) {

                throw new RuntimeException(
                    "Invalid surah number {$surah} at index {$index}."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Ayah validation
            |--------------------------------------------------------------------------
            */

            if ($ayah < 1) {

                throw new RuntimeException(
                    "Invalid ayah number {$ayah} at index {$index}."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Page validation
            |--------------------------------------------------------------------------
            */

            if ($page < 1 || $page > 604) {

                throw new RuntimeException(
                    "Invalid page number {$page} at index {$index}."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Duplicate validation
            |--------------------------------------------------------------------------
            */

            $key = "{$surah}:{$ayah}";

            if (isset($seen[$key])) {

                throw new RuntimeException(
                    "Duplicate ayah found: {$key}"
                );
            }

            $seen[$key] = true;
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Validate Quran starts
        |--------------------------------------------------------------------------
        */

        $firstAyah = $data[0];

        if (
            (int) $firstAyah['surah_number'] !== 1 ||
            (int) $firstAyah['ayah_number'] !== 1 ||
            (int) $firstAyah['page_number'] !== 1
        ) {

            throw new RuntimeException(
                "Invalid Quran beginning. Expected Surah 1, Ayah 1, Page 1."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Validate Quran ending
        |--------------------------------------------------------------------------
        */

        $lastAyah = $data[count($data) - 1];

        if (
            (int) $lastAyah['surah_number'] !== 114 ||
            (int) $lastAyah['ayah_number'] !== 6 ||
            (int) $lastAyah['page_number'] !== 604
        ) {

            throw new RuntimeException(
                "Invalid Quran ending. Expected Surah 114, Ayah 6, Page 604."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 9. Validate page range
        |--------------------------------------------------------------------------
        */

        $pages = collect($data)
            ->pluck('page_number')
            ->map(fn ($page) => (int) $page)
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        if (count($pages) !== 604) {

            throw new RuntimeException(
                "Invalid page count. Expected 604 pages, got "
                . count($pages)
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 10. Import into database
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use ($data) {

            /*
            |--------------------------------------------------------------------------
            | Clear old mapping
            |--------------------------------------------------------------------------
            */

            QuranAyahPage::query()->delete();

            /*
            |--------------------------------------------------------------------------
            | Insert in chunks
            |--------------------------------------------------------------------------
            */

            foreach (array_chunk($data, 500) as $chunk) {

                $rows = [];

                foreach ($chunk as $row) {

                    $rows[] = [
                        'surah_number' => (int) $row['surah_number'],
                        'ayah_number' => (int) $row['ayah_number'],
                        'page_number' => (int) $row['page_number'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                QuranAyahPage::insert($rows);
            }
        });

        /*
        |--------------------------------------------------------------------------
        | 11. Success
        |--------------------------------------------------------------------------
        */

        $this->command?->info(
            'Quran page mapping imported successfully.'
        );

        $this->command?->info(
            'Total ayahs: ' . count($data)
        );

        $this->command?->info(
            'Total pages: ' . count($pages)
        );
    }
}