<?php

namespace App\Services;

use App\Models\Printer;
use Illuminate\Support\Facades\DB;

class PrinterService
{
    public function create(array $data): Printer
    {
        return DB::transaction(function () use ($data) {
            if (! empty($data['is_default'])) {
                Printer::where('store_id', $data['store_id'])->update(['is_default' => false]);
            }

            return Printer::create($data);
        });
    }

    public function update(Printer $printer, array $data): Printer
    {
        return DB::transaction(function () use ($printer, $data) {
            if (! empty($data['is_default'])) {
                Printer::where('store_id', $data['store_id'])
                    ->where('id', '!=', $printer->id)
                    ->update(['is_default' => false]);
            }

            $printer->update($data);

            return $printer->refresh();
        });
    }

    public function delete(Printer $printer): void
    {
        $printer->delete();
    }
}
