<?php

namespace App\Imports;

use App\Models\Contact;
use App\Services\ActivityLogger;
use App\Services\PhoneNormalizerService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ContactsImport implements ToCollection, WithHeadingRow
{
    public int   $imported = 0;
    public int   $updated  = 0;
    public int   $skipped  = 0;
    public array $errors   = [];

    private PhoneNormalizerService $normalizer;

    public function __construct(private string $duplicateAction = 'skip')
    {
        $this->normalizer = new PhoneNormalizerService();
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNum   = $index + 2;
            $name     = trim($row['name'] ?? $row['full_name'] ?? '');
            $rawPhone = trim($row['phone'] ?? $row['phone_number'] ?? $row['mobile'] ?? '');
            $email    = trim($row['email'] ?? '');

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

            $lastVisit = null;
            if (!empty($row['last_visit'])) {
                try {
                    $lastVisit = \Carbon\Carbon::parse($row['last_visit'])->format('Y-m-d');
                } catch (\Exception) {}
            }

            // Duplicate detection by phone or email
            $existing = Contact::where('phone', $normalizedPhone)->first();
            if (!$existing && $email) {
                $existing = Contact::where('email', $email)->first();
            }

            if ($existing) {
                if ($this->duplicateAction === 'update') {
                    $existing->update([
                        'name'       => $name,
                        'email'      => $email ?: $existing->email,
                        'notes'      => $row['notes'] ?? $existing->notes,
                        'last_visit' => $lastVisit ?? $existing->getRawOriginal('last_visit'),
                    ]);
                    ActivityLogger::contactUpdated($existing);
                    $this->updated++;
                } else {
                    $this->errors[] = "Row {$rowNum}: Phone {$normalizedPhone} already exists (skipped)";
                    $this->skipped++;
                }
                continue;
            }

            $contact = Contact::create([
                'name'       => $name,
                'phone'      => $normalizedPhone,
                'email'      => $email ?: null,
                'opted_in'   => true,
                'last_visit' => $lastVisit,
                'notes'      => $row['notes'] ?? null,
            ]);

            ActivityLogger::contactImported($contact);
            $this->imported++;
        }
    }
}
