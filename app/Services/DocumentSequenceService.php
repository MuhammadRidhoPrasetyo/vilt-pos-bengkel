<?php

namespace App\Services;

use App\Models\DocumentSequence;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentSequenceService
{
    public function generate(string $type, ?string $storeId = null, ?Carbon $date = null): string
    {
        $date = $date ?? now();

        return DB::transaction(function () use ($type, $storeId, $date) {
            $year = (int) $date->format('Y');
            $month = (int) $date->format('m');
            $day = (int) $date->format('d');

            /** @var DocumentSequence|null $docSeq */
            $docSeq = DocumentSequence::query()
                ->where('type', $type)
                ->where('store_id', $storeId)
                ->lockForUpdate()
                ->first();

            if (! $docSeq) {
                $docSeqGlobal = $storeId !== null
                    ? DocumentSequence::query()->where('type', $type)->whereNull('store_id')->first()
                    : null;

                $defaultPrefix = match ($type) {
                    'transaction' => 'TRX',
                    'service_order' => 'WO',
                    'purchase' => 'PO',
                    'stock_adjustment' => 'SA',
                    'stock_transfer' => 'ST',
                    default => strtoupper(substr($type, 0, 3)),
                };

                $formatPattern = $docSeqGlobal?->format_pattern ?? '{STORE_CODE}/{PREFIX}/{YYYY}{MM}/{SEQ:4}';
                $resetFreq = $docSeqGlobal?->reset_frequency ?? 'monthly';
                $prefix = $docSeqGlobal?->prefix ?? $defaultPrefix;
                $padding = $docSeqGlobal?->padding ?? 4;

                $created = DocumentSequence::create([
                    'type' => $type,
                    'store_id' => $storeId,
                    'prefix' => $prefix,
                    'format_pattern' => $formatPattern,
                    'reset_frequency' => $resetFreq,
                    'sequence' => 0,
                    'day' => $day,
                    'month' => $month,
                    'year' => $year,
                    'padding' => $padding,
                ]);

                $docSeq = DocumentSequence::where('id', $created->id)->lockForUpdate()->first();
            }

            $shouldReset = match ($docSeq->reset_frequency) {
                'daily' => ($docSeq->year !== $year || $docSeq->month !== $month || $docSeq->day !== $day),
                'monthly' => ($docSeq->year !== $year || $docSeq->month !== $month),
                'yearly' => ($docSeq->year !== $year),
                default => false,
            };

            $nextSequence = $shouldReset ? 1 : ($docSeq->sequence + 1);

            $docSeq->update([
                'sequence' => $nextSequence,
                'day' => $day,
                'month' => $month,
                'year' => $year,
            ]);

            $store = $storeId ? Store::find($storeId) : null;
            $storeCode = $store?->code ?? 'MAIN';
            $storeName = $store ? strtoupper(Str::slug($store->name, '-')) : 'POS';

            return $this->formatNumber(
                pattern: $docSeq->format_pattern,
                prefix: $docSeq->prefix ?? 'DOC',
                storeCode: $storeCode,
                storeName: $storeName,
                date: $date,
                sequence: $nextSequence,
                defaultPadding: $docSeq->padding ?? 4
            );
        });
    }

    private function formatNumber(
        string $pattern,
        string $prefix,
        string $storeCode,
        string $storeName,
        Carbon $date,
        int $sequence,
        int $defaultPadding
    ): string {
        $result = $pattern;

        $result = str_replace('{STORE_CODE}', $storeCode, $result);
        $result = str_replace('{STORE_NAME}', $storeName, $result);
        $result = str_replace('{PREFIX}', $prefix, $result);
        $result = str_replace('{YYYY}', $date->format('Y'), $result);
        $result = str_replace('{YY}', $date->format('y'), $result);
        $result = str_replace('{MM}', $date->format('m'), $result);
        $result = str_replace('{DD}', $date->format('d'), $result);

        // Match {SEQ:n} or {NUMBER} or {SEQ}
        if (preg_match('/\{SEQ:(\d+)\}/', $result, $matches)) {
            $padLength = (int) $matches[1];
            $paddedSeq = str_pad((string) $sequence, $padLength, '0', STR_PAD_LEFT);
            $result = str_replace($matches[0], $paddedSeq, $result);
        } else {
            $paddedSeq = str_pad((string) $sequence, $defaultPadding, '0', STR_PAD_LEFT);
            $result = str_replace('{NUMBER}', $paddedSeq, $result);
            $result = str_replace('{SEQ}', $paddedSeq, $result);
        }

        return $result;
    }
}
