<?php

declare(strict_types=1);

namespace App\Livewire\Account\Concerns;

trait NormalizesAccountPayload
{
    /**
     * @param  mixed  $data
     * @param  array<int, string>  $keys
     * @return array<int, array<string, mixed>>
     */
    protected function extractRows(mixed $data, array $keys = []): array
    {
        if (! is_array($data)) {
            return [];
        }

        if (array_is_list($data)) {
            return array_values(array_filter($data, 'is_array'));
        }

        $candidateKeys = array_merge(['data', 'response'], $keys);
        foreach ($candidateKeys as $key) {
            $v = $data[$key] ?? null;
            if (! is_array($v)) {
                continue;
            }

            if (array_is_list($v)) {
                return array_values(array_filter($v, 'is_array'));
            }

            if (isset($v['data']) && is_array($v['data']) && array_is_list($v['data'])) {
                return array_values(array_filter($v['data'], 'is_array'));
            }
        }

        return [];
    }

    /**
     * @param  mixed  $data
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    protected function extractOne(mixed $data, array $keys = []): array
    {
        if (! is_array($data)) {
            return [];
        }

        if (array_is_list($data)) {
            $first = $data[0] ?? null;

            return is_array($first) ? $first : [];
        }

        $candidateKeys = array_merge(['data', 'response'], $keys);
        foreach ($candidateKeys as $key) {
            $v = $data[$key] ?? null;
            if (! is_array($v)) {
                continue;
            }

            if (array_is_list($v)) {
                $first = $v[0] ?? null;

                return is_array($first) ? $first : [];
            }

            return $v;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function extractAmount(array $data): ?float
    {
        $amount = $data['balance']
            ?? $data['wallet_balance']
            ?? ($data['wallet']['balance'] ?? null)
            ?? $data['total']
            ?? $data['amount']
            ?? null;

        return is_numeric($amount) ? (float) $amount : null;
    }
}
