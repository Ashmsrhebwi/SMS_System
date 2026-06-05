<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Segment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SegmentService
{
    /**
     * Return all eligible contacts as a Collection.
     * WARNING: For large datasets use eachEligibleContact() instead.
     */
    public function getEligibleContacts(?Segment $segment): Collection
    {
        return $this->buildQuery($segment)->get();
    }

    /**
     * Return a query Builder for eligible contacts (supports paginate, etc.).
     */
    public function getEligibleContactsQuery(?Segment $segment): Builder
    {
        return $this->buildQuery($segment);
    }

    /**
     * Process eligible contacts in chunks to avoid loading all into memory.
     */
    public function eachEligibleContact(?Segment $segment, int $chunkSize, callable $callback): void
    {
        $this->buildQuery($segment)->chunk($chunkSize, $callback);
    }

    public function countEligible(?Segment $segment): int
    {
        return $this->buildQuery($segment)->count();
    }

    private function buildQuery(?Segment $segment): Builder
    {
        $query = Contact::where('opted_in', true)
            ->whereNotExists(function ($q) {
                $q->from('opt_outs')
                  ->whereColumn('opt_outs.phone', 'contacts.phone');
            })
            ->whereNotExists(function ($q) {
                $q->from('global_blacklist')
                  ->whereColumn('global_blacklist.phone', 'contacts.phone');
            });

        if ($segment) {
            $this->applyConditions($query, $segment->conditions ?? []);
        }

        return $query;
    }

    private function applyConditions(Builder $query, array $conditions): void
    {
        foreach ($conditions as $condition) {
            $field    = $condition['field']    ?? null;
            $operator = $condition['operator'] ?? 'is';
            $value    = $condition['value']    ?? null;

            if (!$field || $value === null || $value === '') {
                continue;
            }

            if ($field === 'tag') {
                if ($operator === 'not_has') {
                    $query->whereDoesntHave('tags', fn($q) => $q->where('tags.id', (int) $value));
                } else {
                    $query->whereHas('tags', fn($q) => $q->where('tags.id', (int) $value));
                }
            } elseif ($field === 'opted_in') {
                $query->where('opted_in', (bool) $value);
            } elseif (in_array($field, ['country', 'phone_country'], true)) {
                // Match by phone number prefix (e.g. +966 for Saudi Arabia)
                if ($operator === 'is_not') {
                    $query->where('phone', 'not like', $value . '%');
                } else {
                    $query->where('phone', 'like', $value . '%');
                }
            } elseif ($field === 'created_at') {
                if ($operator === 'before') {
                    $query->whereDate('created_at', '<=', $value);
                } elseif ($operator === 'after') {
                    $query->whereDate('created_at', '>=', $value);
                } elseif ($operator === 'within_days') {
                    $query->where('created_at', '>=', now()->subDays((int) $value));
                }
            }
            // Legacy flat field names (backward compat with older segments)
            elseif ($field === 'created_before') {
                $query->whereDate('created_at', '<=', $value);
            } elseif ($field === 'created_after') {
                $query->whereDate('created_at', '>=', $value);
            }
        }
    }
}
