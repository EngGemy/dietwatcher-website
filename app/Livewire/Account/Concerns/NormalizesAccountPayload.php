<?php

declare(strict_types=1);

namespace App\Livewire\Account\Concerns;

trait NormalizesAccountPayload
{
    /**
     * Default list keys tried after `data` / `response`.
     *
     * @var array<int, string>
     */
    protected array $defaultListKeys = ['items', 'rows', 'list', 'records', 'result'];

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

        $candidateKeys = array_values(array_unique(array_merge(
            ['data', 'response'],
            $keys,
            $this->defaultListKeys,
        )));

        foreach ($candidateKeys as $key) {
            $rows = $this->extractListFromContainer($data[$key] ?? null, $keys);
            if ($rows !== []) {
                return $rows;
            }
        }

        if (isset($data['id']) && (is_numeric($data['id']) || is_string($data['id']))) {
            return [$data];
        }

        return [];
    }

    /**
     * Extract rows from a decoded API result, with optional single-entity fallback.
     *
     * @param  array<string, mixed>  $result
     * @param  array<int, string>  $keys
     * @param  array<int, string>  $singleKeys
     * @return array<int, array<string, mixed>>
     */
    protected function extractRowsFromApiResult(array $result, array $keys, array $singleKeys = []): array
    {
        if (! ($result['ok'] ?? false)) {
            return [];
        }

        $rows = $this->extractRows($result['data'] ?? null, $keys);
        if ($rows === [] && is_array($result['raw'] ?? null)) {
            $rows = $this->extractRows($result['raw'], $keys);
        }

        if ($rows !== [] || $singleKeys === []) {
            return $rows;
        }

        $single = $this->extractOne($result['data'] ?? null, $singleKeys);
        if ($single === [] && is_array($result['raw'] ?? null)) {
            $single = $this->extractOne($result['raw'], $singleKeys);
        }

        return $single !== [] ? [$single] : [];
    }

    /**
     * @param  mixed  $container
     * @param  array<int, string>  $keys
     * @return array<int, array<string, mixed>>
     */
    protected function extractListFromContainer(mixed $container, array $keys = []): array
    {
        if (! is_array($container)) {
            return [];
        }

        if (array_is_list($container)) {
            return array_values(array_filter($container, 'is_array'));
        }

        $nestedKeys = array_values(array_unique(array_merge(
            ['data', 'items', 'rows', 'list', 'records', 'result'],
            $keys,
        )));

        foreach ($nestedKeys as $nestedKey) {
            $nested = $container[$nestedKey] ?? null;
            if (! is_array($nested)) {
                continue;
            }

            if (array_is_list($nested)) {
                return array_values(array_filter($nested, 'is_array'));
            }

            if (isset($nested['data']) && is_array($nested['data']) && array_is_list($nested['data'])) {
                return array_values(array_filter($nested['data'], 'is_array'));
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

        $candidateKeys = array_values(array_unique(array_merge(['data', 'response'], $keys)));
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
