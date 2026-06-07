<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Livewire\Account\Concerns\NormalizesAccountPayload;
use App\Services\AccountApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class InvoiceExportController extends Controller
{
    use NormalizesAccountPayload;

    public function __invoke(Request $request, AccountApiService $api): BinaryFileResponse|\Illuminate\Http\RedirectResponse
    {
        $subscriptionId = $request->integer('subscription');
        if ($subscriptionId <= 0) {
            $subscriptionId = null;
        }

        $result = $api->listInvoices($subscriptionId);
        if (! ($result['ok'] ?? false)) {
            return redirect()
                ->route('account.invoices.index', array_filter(['subscription' => $subscriptionId]))
                ->with('error', $result['message'] ?: __('account.export_failed'));
        }

        $invoices = $this->extractRows($result['data'] ?? null, ['invoices', 'items', 'rows']);
        if ($invoices === []) {
            return redirect()
                ->route('account.invoices.index', array_filter(['subscription' => $subscriptionId]))
                ->with('error', __('account.no_invoices_to_export'));
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'dw_invoices_');
        if ($zipPath === false) {
            return redirect()
                ->route('account.invoices.index', array_filter(['subscription' => $subscriptionId]))
                ->with('error', __('account.export_failed'));
        }

        $zipFile = $zipPath.'.zip';
        @unlink($zipPath);

        $zip = new ZipArchive;
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return redirect()
                ->route('account.invoices.index', array_filter(['subscription' => $subscriptionId]))
                ->with('error', __('account.export_failed'));
        }

        $added = 0;
        foreach ($invoices as $invoice) {
            if (! is_array($invoice)) {
                continue;
            }

            $url = $api->resolveInvoiceDownloadUrl($invoice);
            if ($url === null || $url === '') {
                continue;
            }

            $pdf = $api->fetchInvoicePdf($url);
            if ($pdf === null || $pdf === '') {
                continue;
            }

            $invId = (int) ($invoice['id'] ?? $invoice['invoice_id'] ?? 0);
            $invDate = (string) ($invoice['created_at'] ?? $invoice['date'] ?? $invoice['issued_at'] ?? '');
            $safeDate = preg_replace('/[^0-9\-]/', '', substr($invDate, 0, 10)) ?: date('Y-m-d');
            $filename = $invId > 0
                ? sprintf('invoice-%d-%s.pdf', $invId, $safeDate)
                : sprintf('invoice-%s-%d.pdf', $safeDate, $added + 1);

            $zip->addFromString($filename, $pdf);
            $added++;
        }

        $zip->close();

        if ($added === 0) {
            @unlink($zipFile);

            return redirect()
                ->route('account.invoices.index', array_filter(['subscription' => $subscriptionId]))
                ->with('error', __('account.no_invoices_to_export'));
        }

        $downloadName = $subscriptionId
            ? sprintf('subscription-%d-invoices-%s.zip', $subscriptionId, date('Y-m-d'))
            : sprintf('invoices-%s.zip', date('Y-m-d'));

        Log::info('InvoiceExportController: exported zip', [
            'count' => $added,
            'subscription_id' => $subscriptionId,
        ]);

        return response()->download($zipFile, $downloadName)->deleteFileAfterSend(true);
    }
}
