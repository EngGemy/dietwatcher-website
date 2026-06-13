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

        if ($single !== [] && $this->isValidDataRow($single, $singleKeys)) {
            return [$single];
        }

        return [];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  callable(array<string, mixed>): bool  $validator
     * @return array<int, array<string, mixed>>
     */
    protected function filterValidRows(array $rows, callable $validator): array
    {
        return array_values(array_filter(
            $rows,
            static fn ($row): bool => is_array($row) && $validator($row),
        ));
    }

    protected function isValidInvoiceRow(array $row): bool
    {
        if ((int) ($row['id'] ?? $row['invoice_id'] ?? 0) > 0) {
            return true;
        }

        foreach (['number', 'invoice_number'] as $key) {
            if (trim((string) ($row[$key] ?? '')) !== '') {
                return true;
            }
        }

        $amount = $row['total'] ?? $row['amount'] ?? $row['grand_total'] ?? null;
        $date = trim((string) ($row['created_at'] ?? $row['date'] ?? $row['issued_at'] ?? ''));

        return is_numeric($amount) && $date !== '';
    }

    protected function isValidOrderRow(array $row): bool
    {
        if ((int) ($row['id'] ?? $row['order_id'] ?? 0) > 0) {
            return true;
        }

        foreach (['order_number', 'number', 'external_order_number'] as $key) {
            if (trim((string) ($row[$key] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $singleKeys
     */
    protected function isValidDataRow(array $row, array $singleKeys): bool
    {
        if ($this->isValidInvoiceRow($row) || $this->isValidOrderRow($row)) {
            return true;
        }

        foreach ($singleKeys as $key) {
            if (in_array($key, ['invoice', 'subscription', 'order'], true)) {
                return $this->isValidInvoiceRow($row) || $this->isValidOrderRow($row);
            }
        }

        return isset($row['id']) && (is_numeric($row['id']) || is_string($row['id']));
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
