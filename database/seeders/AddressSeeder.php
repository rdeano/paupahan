<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\District;
use App\Models\Street;
use App\Support\AddressOptions;
use Illuminate\Database\Seeder;

/**
 * Davao City districts, barangays (PSA PSGC) and streets (OpenStreetMap).
 * See database/data/SOURCES.md for where the data came from. Safe to re-run.
 */
class AddressSeeder extends Seeder
{
    public function run(): void
    {
        $barangayIds = [];

        foreach ($this->readCsv('barangays.csv') as $row) {
            $district = $row['district'] !== '' ? District::firstOrCreate(['name' => $row['district']]) : null;

            $barangay = Barangay::updateOrCreate(
                ['psgc_code' => $row['psgc_code']],
                ['name' => $row['name'], 'district_id' => $district?->id],
            );

            $barangayIds[$row['psgc_code']] = $barangay->id;
        }

        $now = now();
        $streets = [];

        foreach ($this->readCsv('streets.csv') as $row) {
            if (! isset($barangayIds[$row['barangay_psgc_code']])) {
                continue;
            }

            $streets[] = [
                'barangay_id' => $barangayIds[$row['barangay_psgc_code']],
                'name' => $row['name'],
                'status' => Street::APPROVED,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($streets, 500) as $chunk) {
            Street::insertOrIgnore($chunk);
        }

        AddressOptions::flush();
    }

    /** @return \Generator<array<string, string>> */
    private function readCsv(string $file): \Generator
    {
        $handle = fopen(database_path("data/{$file}"), 'r');
        $header = fgetcsv($handle, escape: '');

        while (($row = fgetcsv($handle, escape: '')) !== false) {
            yield array_combine($header, array_map('trim', $row));
        }

        fclose($handle);
    }
}
