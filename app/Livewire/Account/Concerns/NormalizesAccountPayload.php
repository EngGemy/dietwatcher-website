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
        return $this->extractWalletBalance($data);
    }

    /**
     * Resolve wallet balance from every known API payload shape.
     *
     * @param  mixed  $payload
     */
    protected function extractWalletBalance(mixed $payload): ?float
    {
        if (! is_array($payload)) {
            return null;
        }

        $roots = [$payload];
        foreach (['data', 'wallet', 'response', 'customer', 'profile'] as $key) {
            if (is_array($payload[$key] ?? null)) {
                $roots[] = $payload[$key];
            }
        }

        $flatKeys = [
            'balance',
            'wallet_balance',
            'wallet',
            'wallet_amount',
            'walletAmount',
            'available_balance',
            'availableBalance',
            'total_balance',
            'totalBalance',
            'current_balance',
            'currentBalance',
            'remaining_balance',
            'remainingBalance',
            'credit_balance',
            'creditBalance',
        ];

        $nestedKeys = [
            'wallet.balance',
            'wallet.available_balance',
            'wallet.availableBalance',
            'wallet.total',
            'customer.wallet_balance',
            'customer.balance',
            'customer.wallet',
            'user.wallet_balance',
            'user.wallet',
            'profile.wallet_balance',
            'profile.balance',
            'profile.wallet',
            'meta.balance',
            'summary.balance',
        ];

        foreach ($roots as $root) {
            foreach ($flatKeys as $key) {
                $amount = $this->numericMoneyValue($root[$key] ?? null);
                if ($amount !== null) {
                    return $amount;
                }
            }

            foreach ($nestedKeys as $path) {
                $amount = $this->numericMoneyValue(data_get($root, $path));
                if ($amount !== null) {
                    return $amount;
                }
            }
        }

        if (array_is_list($payload)) {
            return $this->computeWalletBalanceFromTransactions($payload);
        }

        $transactions = $this->extractRows($payload, ['transactions', 'wallet_transactions', 'items', 'rows']);
        foreach ($transactions as $transaction) {
            if (! is_array($transaction)) {
                continue;
            }

            foreach (['balance_after', 'balanceAfter', 'remaining_balance', 'remainingBalance', 'wallet_balance', 'balance'] as $key) {
                $amount = $this->numericMoneyValue($transaction[$key] ?? null);
                if ($amount !== null) {
                    return $amount;
                }
            }
        }

        if ($transactions !== []) {
            $computed = $this->computeWalletBalanceFromTransactions($transactions);

            return $computed;
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $transactions
     */
    protected function computeWalletBalanceFromTransactions(array $transactions): ?float
    {
        if ($transactions === []) {
            return null;
        }

        $sorted = $transactions;
        usort($sorted, static function (array $a, array $b): int {
            $aTime = strtotime((string) ($a['created_at'] ?? $a['date'] ?? $a['createdAt'] ?? '')) ?: 0;
            $bTime = strtotime((string) ($b['created_at'] ?? $b['date'] ?? $b['createdAt'] ?? '')) ?: 0;

            return $bTime <=> $aTime;
        });

        foreach ($sorted as $transaction) {
            foreach (['balance_after', 'balanceAfter', 'remaining_balance', 'remainingBalance', 'wallet_balance', 'balance'] as $key) {
                $amount = $this->numericMoneyValue($transaction[$key] ?? null);
                if ($amount !== null) {
                    return $amount;
                }
            }
        }

        $net = 0.0;
        $hasAmount = false;
        foreach ($transactions as $transaction) {
            if (! is_array($transaction)) {
                continue;
            }

            $amount = $this->extractTransactionAmount($transaction);
            if ($amount === null) {
                continue;
            }

            $hasAmount = true;
            $isCredit = $this->walletTransactionIsCredit($transaction, $amount);
            $net += $isCredit ? abs($amount) : -abs($amount);
        }

        return $hasAmount ? round($net, 2) : null;
    }

    /**
     * @param  array<string, mixed>  $transaction
     */
    protected function extractTransactionAmount(array $transaction): ?float
    {
        foreach ([
            'amount',
            'value',
            'total',
            'money',
            'price',
            'sum',
            'transaction_amount',
            'wallet_amount',
            'paid_amount',
            'amount_in_sar',
            'amountInSar',
            'credit',
            'debit',
            'charge_amount',
            'withdraw_amount',
        ] as $key) {
            $amount = $this->numericMoneyValue($transaction[$key] ?? null);
            if ($amount !== null && abs($amount) > 0) {
                return $amount;
            }
        }

        foreach (['payment.amount', 'payment.total', 'transaction.amount', 'details.amount'] as $path) {
            $amount = $this->numericMoneyValue(data_get($transaction, $path));
            if ($amount !== null && abs($amount) > 0) {
                return $amount;
            }
        }

        $halala = (int) ($transaction['amount_halala'] ?? $transaction['amountHalala'] ?? 0);
        if ($halala !== 0) {
            return round($halala / 100, 2);
        }

        return null;
    }

    protected function numericMoneyValue(mixed $value): ?float
    {
        if (is_array($value)) {
            $value = $value['amount'] ?? $value['value'] ?? $value['balance'] ?? null;
        }

        if (! is_numeric($value)) {
            if (! is_string($value)) {
                return null;
            }

            $normalized = preg_replace('/[^\d.\-+]/', '', str_replace(',', '', $value)) ?? '';
            if ($normalized === '' || ! is_numeric($normalized)) {
                return null;
            }

            $value = $normalized;
        }

        return round((float) $value, 2);
    }

    /**
     * @param  array<string, mixed>  $transaction
     */
    protected function normalizeWalletTransactionType(array $transaction): string
    {
        $type = strtolower(trim((string) (
            $transaction['type']
            ?? $transaction['kind']
            ?? $transaction['transaction_type']
            ?? $transaction['transactionType']
            ?? $transaction['description']
            ?? $transaction['note']
            ?? $transaction['purpose']
            ?? ''
        )));

        $type = preg_replace('/[\s\-]+/', '_', $type) ?? $type;
        $type = ltrim($type, '+_');

        return $type;
    }

    /**
     * @param  array<string, mixed>  $transaction
     */
    protected function walletTransactionLabel(array $transaction): string
    {
        $type = $this->normalizeWalletTransactionType($transaction);
        $translatedType = $type !== '' ? __('account.tx_types.'.$type) : '';
        $hasTypeTranslation = $translatedType !== '' && $translatedType !== 'account.tx_types.'.$type;

        $raw = $transaction['description']
            ?? $transaction['note']
            ?? $transaction['purpose']
            ?? $transaction['title']
            ?? $transaction['label']
            ?? '';

        if (is_array($raw)) {
            $raw = $raw[app()->getLocale()] ?? $raw['ar'] ?? $raw['en'] ?? '';
        }

        $raw = trim(ltrim(trim((string) $raw), '+−-'));
        $rawKey = strtolower(preg_replace('/[\s\-]+/', '_', $raw) ?? $raw);
        $translatedDesc = $rawKey !== '' ? __('account.tx_desc.'.$rawKey) : '';
        if ($translatedDesc !== '' && $translatedDesc !== 'account.tx_desc.'.$rawKey) {
            return $translatedDesc;
        }

        $translatedRawType = $rawKey !== '' ? __('account.tx_types.'.$rawKey) : '';
        if ($translatedRawType !== '' && $translatedRawType !== 'account.tx_types.'.$rawKey) {
            return $translatedRawType;
        }

        if ($hasTypeTranslation && ($raw === '' || $rawKey === $type || $rawKey === ltrim($type, '_'))) {
            return $translatedType;
        }

        if ($hasTypeTranslation && in_array($rawKey, ['charge', 'sale', 'purchase', 'deduct', 'credit', 'debit', 'in', 'out'], true)) {
            return $translatedType;
        }

        if ($raw !== '') {
            return $raw;
        }

        return $hasTypeTranslation ? $translatedType : __('account.transaction');
    }

    /**
     * @param  array<string, mixed>  $transaction
     */
    protected function walletTransactionIsCredit(array $transaction, float $amount): bool
    {
        $type = $this->normalizeWalletTransactionType($transaction);
        $direction = strtolower(trim((string) ($transaction['direction'] ?? $transaction['flow'] ?? '')));

        if (in_array($direction, ['in', 'credit', 'charge', 'deposit', 'refund'], true)) {
            return true;
        }

        if (in_array($direction, ['out', 'debit', 'sale', 'purchase', 'deduct', 'withdraw'], true)) {
            return false;
        }

        if (in_array($type, ['charge', 'credit', 'in', 'deposit', 'refund', 'top_up', 'exchange_points'], true)) {
            return true;
        }

        if (in_array($type, ['sale', 'purchase', 'deduct', 'debit', 'out', 'withdraw', 'cancel_subscription', 'cancel_purchase', 'purchase_cancel'], true)) {
            return false;
        }

        return $amount >= 0;
    }
}
