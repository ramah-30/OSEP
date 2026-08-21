<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Guest;
use Illuminate\Support\Str;

/**
 * CSV import/export for the guest list. Import validates each row, skips
 * duplicates (by email, else full name - both within the file and against the
 * event) and returns a structured report so the UI can show exactly what
 * happened. Export produces a spreadsheet-friendly CSV string.
 */
class GuestCsvService
{
    /** Canonical field => accepted header aliases (lower-cased, spaces/underscores ignored). */
    private const ALIASES = [
        'first_name' => ['firstname', 'first', 'givenname'],
        'last_name' => ['lastname', 'last', 'surname', 'familyname'],
        'full_name' => ['fullname', 'name', 'guestname'],
        'email' => ['email', 'emailaddress', 'mail'],
        'phone' => ['phone', 'phonenumber', 'mobile', 'contact', 'tel'],
        'category' => ['category', 'group', 'type'],
        'gender' => ['gender', 'sex'],
        'meal_preference' => ['mealpreference', 'meal', 'mealchoice'],
        'dietary_restrictions' => ['dietaryrestrictions', 'dietary', 'diet'],
        'notes' => ['notes', 'note', 'comment', 'comments'],
    ];

    /**
     * @return array{imported:int, duplicates:array<int,string>, errors:array<int,array{row:int,message:string}>, guests:array<int,Guest>}
     */
    public function import(Event $event, string $contents): array
    {
        $rows = $this->parse($contents);
        $report = ['imported' => 0, 'duplicates' => [], 'errors' => [], 'guests' => []];

        if (empty($rows)) {
            $report['errors'][] = ['row' => 0, 'message' => 'The file is empty.'];

            return $report;
        }

        $header = $this->mapHeader(array_shift($rows));

        if (! in_array('full_name', $header, true) && ! in_array('first_name', $header, true)) {
            $report['errors'][] = ['row' => 1, 'message' => 'No name column found. Include a "name" or "first_name" header.'];

            return $report;
        }

        // Existing keys in the event, so we don't re-add the same person.
        $existingEmails = $event->guests()->whereNotNull('email')->pluck('email')
            ->map(fn ($e) => Str::lower($e))->flip();
        $existingNames = $event->guests()->pluck('full_name')
            ->map(fn ($n) => Str::lower($n))->flip();

        $seenEmails = [];
        $seenNames = [];

        foreach ($rows as $i => $raw) {
            $line = $i + 2; // 1-based, plus the header row
            $data = $this->rowToData($header, $raw);

            $fullName = $data['full_name']
                ?? trim(implode(' ', array_filter([$data['first_name'] ?? null, $data['last_name'] ?? null])));

            if ($fullName === '' || $fullName === null) {
                // Skip genuinely blank lines silently; flag partial ones.
                if (array_filter($raw, fn ($v) => trim((string) $v) !== '')) {
                    $report['errors'][] = ['row' => $line, 'message' => 'Missing guest name.'];
                }

                continue;
            }

            $email = isset($data['email']) ? Str::lower(trim($data['email'])) : null;

            if ($email && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $report['errors'][] = ['row' => $line, 'message' => "Invalid email \"{$data['email']}\"."];

                continue;
            }

            $nameKey = Str::lower($fullName);
            $isDuplicate = ($email && (isset($existingEmails[$email]) || isset($seenEmails[$email])))
                || (! $email && (isset($existingNames[$nameKey]) || isset($seenNames[$nameKey])));

            if ($isDuplicate) {
                $report['duplicates'][] = $fullName;

                continue;
            }

            $guest = $event->guests()->create([
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'full_name' => $fullName,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'gender' => $data['gender'] ?? null,
                'category' => $data['category'] ?? null,
                'meal_preference' => $data['meal_preference'] ?? null,
                'dietary_restrictions' => $data['dietary_restrictions'] ?? null,
                'notes' => $data['notes'] ?? null,
                'rsvp_status' => 'pending',
            ]);

            $report['imported']++;
            $report['guests'][] = $guest;

            if ($email) {
                $seenEmails[$email] = true;
            } else {
                $seenNames[$nameKey] = true;
            }
        }

        return $report;
    }

    /**
     * @param  iterable<Guest>  $guests
     */
    public function export(iterable $guests): string
    {
        $columns = ['Full Name', 'First Name', 'Last Name', 'Email', 'Phone', 'Category',
            'RSVP Status', 'Invitation Status', 'Check-in Status', 'Meal Preference',
            'Dietary Restrictions', 'Seat', 'Notes'];

        $out = fopen('php://temp', 'r+');
        fputcsv($out, $columns);

        foreach ($guests as $g) {
            fputcsv($out, [
                $g->full_name,
                $g->first_name,
                $g->last_name,
                $g->email,
                $g->phone,
                $g->category,
                $g->rsvp_status?->label(),
                $g->invitation_status?->label(),
                $g->checkin_status?->label(),
                $g->meal_preference,
                $g->dietary_restrictions,
                $g->seat_number,
                $g->notes,
            ]);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $csv;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function parse(string $contents): array
    {
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents); // strip BOM
        $rows = [];

        foreach (preg_split('/\r\n|\r|\n/', trim($contents)) as $line) {
            if ($line === '') {
                continue;
            }
            $rows[] = str_getcsv($line);
        }

        return $rows;
    }

    /**
     * @param  array<int, string>  $headerRow
     * @return array<int, string|null>  canonical field per column index
     */
    private function mapHeader(array $headerRow): array
    {
        return array_map(function ($cell) {
            $key = preg_replace('/[^a-z]/', '', Str::lower((string) $cell));

            foreach (self::ALIASES as $field => $aliases) {
                if ($key === preg_replace('/[^a-z]/', '', $field) || in_array($key, $aliases, true)) {
                    return $field;
                }
            }

            return null;
        }, $headerRow);
    }

    /**
     * @param  array<int, string|null>  $header
     * @param  array<int, string>  $row
     * @return array<string, string>
     */
    private function rowToData(array $header, array $row): array
    {
        $data = [];

        foreach ($header as $index => $field) {
            if ($field === null) {
                continue;
            }
            $value = trim((string) ($row[$index] ?? ''));
            if ($value !== '') {
                $data[$field] = $value;
            }
        }

        return $data;
    }
}
