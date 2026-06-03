<?php

namespace App\Imports;

use App\Models\Contact;
use App\Services\PhoneNormalizerService;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Collection;

class ContactsImport implements ToCollection, WithHeadingRow
{
    public int $imported = 0;
    public int $skipped = 0;
    public array $errors = [];

    private PhoneNormalizerService $normalizer;

    public function __construct()
    {
        $this->normalizer = new PhoneNormalizerService();
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // +2 because of heading row and 0-index

            $name = trim($row['name'] ?? $row['full_name'] ?? '');
            $rawPhone = trim($row['phone'] ?? $row['phone_number'] ?? $row['mobile'] ?? '');

            if (empty($name) || empty($rawPhone)) {
                $this->errors[] = "Row {$rowNum}: Missing name or phone";
                $this->skipped++;
                continue;
            }

            $normalizedPhone = $this->normalizer->normalize($rawPhone);

            if (!$normalizedPhone) {
                $this->errors[] = "Row {$rowNum}: Invalid phone number '{$rawPhone}'";
                $this->skipped++;
                continue;
            }

            // Check for duplicate
            if (Contact::where('phone', $normalizedPhone)->exists()) {
                $this->errors[] = "Row {$rowNum}: Phone {$normalizedPhone} already exists (skipped)";
                $this->skipped++;
                continue;
            }

            $lastVisit = null;
            if (!empty($row['last_visit'])) {
                try {
                    $lastVisit = \Carbon\Carbon::parse($row['last_visit'])->format('Y-m-d');
                } catch (\Exception $e) {
                    // Ignore invalid date
                }
            }

            Contact::create([
                'name' => $name,
                'phone' => $normalizedPhone,
                'opted_in' => true,
                'last_visit' => $lastVisit,
                'notes' => $row['notes'] ?? null,
            ]);

            $this->imported++;
        }
    }
}
