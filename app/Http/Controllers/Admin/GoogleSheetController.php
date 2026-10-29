<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WordPressSite;
use App\Services\GoogleSheetImporter;
use Illuminate\Http\Request;

/** Integrations → a Google Sheet store: link the sheet, match its columns, import orders. */
class GoogleSheetController extends Controller
{
    private function sheet(WordPressSite $site): WordPressSite
    {
        abort_unless($site->type === 'google_sheet', 404);

        return $site;
    }

    public function edit(WordPressSite $site)
    {
        $site = $this->sheet($site);
        $settings = (array) ($site->settings ?? []);

        return view('admin.integrations.sheet', [
            'site'     => $site,
            'settings' => $settings,
            'fields'   => GoogleSheetImporter::FIELDS,
            'required' => GoogleSheetImporter::REQUIRED,
            'logs'     => \App\Models\SyncLog::where('site_id', $site->id)->latest()->take(10)->get(),
        ]);
    }

    /** Reads the sheet's header row so the owner can match columns (AJAX). */
    public function columns(Request $request, WordPressSite $site)
    {
        $this->sheet($site);
        $request->validate(['sheet_url' => 'required|string|max:500']);

        $data = GoogleSheetImporter::fetch($request->sheet_url);
        if (! $data['ok']) {
            return response()->json(['ok' => false, 'message' => $data['error']], 422);
        }

        return response()->json([
            'ok'      => true,
            'headers' => $data['headers'],
            'rows'    => count(array_filter($data['rows'])),
            'sample'  => array_slice(array_values(array_filter($data['rows'])), 0, 3),
            'guess'   => self::guess($data['headers']),
        ]);
    }

    public function update(Request $request, WordPressSite $site)
    {
        $site = $this->sheet($site);
        $data = $request->validate([
            'sheet_url'   => 'required|string|max:500',
            'start_row'   => 'nullable|integer|min:2|max:1000000',
            'auto_import' => 'nullable|boolean',
            'mapping'     => 'required|array',
            'mapping.*'   => 'nullable|string|max:255',
        ]);

        if (! GoogleSheetImporter::csvUrl($data['sheet_url'])) {
            return back()->withInput()->with('error', 'This does not look like a Google Sheets link.');
        }

        $mapping = array_filter(
            array_intersect_key($data['mapping'], GoogleSheetImporter::FIELDS),
            fn ($v) => $v !== null && $v !== ''
        );
        foreach (GoogleSheetImporter::REQUIRED as $f) {
            if (empty($mapping[$f])) {
                return back()->withInput()->with('error', 'Please choose a column for: ' . trim(GoogleSheetImporter::FIELDS[$f], ' *') . '.');
            }
        }

        $settings = (array) ($site->settings ?? []);
        $settings['sheet_url']   = $data['sheet_url'];
        $settings['gid']         = null;   // read from the link each time
        $settings['start_row']   = (int) ($data['start_row'] ?? 2);
        $settings['auto_import'] = $request->boolean('auto_import');
        $settings['mapping']     = $mapping;

        $site->forceFill(['settings' => $settings, 'site_url' => $site->site_url ?: $data['sheet_url']])->save();

        return redirect()->route('admin.integrations.sheet.edit', $site)->with('success', 'Sheet settings saved.');
    }

    public function import(WordPressSite $site)
    {
        $site = $this->sheet($site);
        $r = GoogleSheetImporter::run($site);

        if ($r['error']) {
            return back()->with('error', $r['error']);
        }

        return back()->with('success', "Imported: {$r['created']} new, {$r['updated']} updated, {$r['skipped']} skipped"
            . ($r['invalid'] ? ", {$r['invalid']} rows ignored (missing name / valid phone / product)" : '') . '.');
    }

    /** Best guess of which header is which field, so most sheets need no clicking. */
    public static function guess(array $headers): array
    {
        $words = [
            'order_id' => ['order id', 'order no', 'order number', 'invoice'],
            'name'     => ['customer name', 'name', 'নাম'],
            'phone'    => ['phone', 'mobile', 'number', 'contact', 'ফোন', 'মোবাইল'],
            'address'  => ['address', 'ঠিকানা'],
            'district' => ['district', 'city', 'zilla', 'জেলা'],
            'product'  => ['product', 'item', 'পণ্য', 'প্রোডাক্ট'],
            'variant'  => ['size', 'variant', 'color', 'colour', 'সাইজ'],
            'qty'      => ['qty', 'quantity', 'পরিমাণ'],
            'price'    => ['price', 'unit price', 'rate', 'দাম'],
            'total'    => ['total', 'amount', 'মোট'],
            'shipping' => ['delivery', 'shipping', 'charge'],
            'note'     => ['note', 'comment', 'remarks'],
            'date'     => ['date', 'time', 'timestamp', 'তারিখ'],
        ];

        $out = [];
        $used = [];
        foreach ($words as $field => $list) {
            foreach ($list as $w) {
                foreach ($headers as $h) {
                    if (! in_array($h, $used, true) && str_contains(mb_strtolower($h), $w)) {
                        $out[$field] = $h;
                        $used[] = $h;
                        continue 3;
                    }
                }
            }
        }

        return $out;
    }
}
