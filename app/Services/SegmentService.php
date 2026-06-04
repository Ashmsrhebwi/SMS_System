<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\GlobalBlacklist;
use App\Models\OptOut;
use App\Models\Segment;
use Illuminate\Database\Eloquent\Collection;

class SegmentService
{
    public function getEligibleContacts(?Segment $segment): Collection
    {
        $optOutPhones = OptOut::pluck('phone')->toArray();
        $blacklistPhones = GlobalBlacklist::pluck('phone')->toArray();
        $excludedPhones = array_unique(array_merge($optOutPhones, $blacklistPhones));

        $query = Contact::where('opted_in', true)
            ->whereNotIn('phone', $excludedPhones);

        if ($segment) {
            $this->applyConditions($query, $segment->conditions ?? []);
        }

        return $query->get();
    }

    public function countEligible(?Segment $segment): int
    {
        $optOutPhones = OptOut::pluck('phone')->toArray();
        $blacklistPhones = GlobalBlacklist::pluck('phone')->toArray();
        $excludedPhones = array_unique(array_merge($optOutPhones, $blacklistPhones));

        $query = Contact::where('opted_in', true)
            ->whereNotIn('phone', $excludedPhones);

        if ($segment) {
            $this->applyConditions($query, $segment->conditions ?? []);
        }

        return $query->count();
    }

    private function applyConditions($query, array $conditions): void
    {
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? null;
            $value = $condition['value'] ?? null;

            if (!$field || $value === null || $value === '') {
                continue;
            }

            match ($field) {
                'tag' => $query->whereHas('tags', fn($q) => $q->where('name', $value)),
                'opted_in' => $query->where('opted_in', (bool) $value),
                'phone_country' => $query->where('phone', 'like', $value . '%'),
                'created_after' => $query->whereDate('created_at', '>=', $value),
                'created_before' => $query->whereDate('created_at', '<=', $value),
                default => null,
            };
        }
    }
}
