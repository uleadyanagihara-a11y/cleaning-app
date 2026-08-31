<?php

namespace App\Http\Controllers;

use App\Http\Requests\PdfOutputRequest;
use App\Models\CleaningAssignment;
use App\Services\CleaningAssignmentPdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PdfController extends Controller
{
    private const JAPANESE_FONT_PATH = '/usr/share/fonts/opentype/ipaexfont-gothic/ipaexg.ttf';

    public function index(Request $request): Response
    {
        $validated = Validator::make(
            ['date' => $request->query('date', now()->toDateString())],
            ['date' => ['required', 'date_format:Y-m-d']],
            [
                'date.required' => '対象日を入力してください。',
                'date.date_format' => '対象日は年-月-日の形式で入力してください。',
            ],
        )->validate();

        return Inertia::render('Pdf/Index', [
            'selectedDate' => $validated['date'],
            'assignmentCount' => CleaningAssignment::query()
                ->where('assignment_date', $validated['date'])
                ->count(),
        ]);
    }

    public function preview(
        PdfOutputRequest $request,
        CleaningAssignmentPdfService $pdfService,
    ): HttpResponse {
        return $this->renderPdf($request, $pdfService, false);
    }

    public function download(
        PdfOutputRequest $request,
        CleaningAssignmentPdfService $pdfService,
    ): HttpResponse {
        return $this->renderPdf($request, $pdfService, true);
    }

    private function renderPdf(
        PdfOutputRequest $request,
        CleaningAssignmentPdfService $pdfService,
        bool $download,
    ): HttpResponse {
        $date = (string) $request->validated('date');
        $data = $pdfService->build($date);

        if ($data['assigned_member_count'] === 0) {
            throw ValidationException::withMessages([
                'date' => 'この対象日の確定済み掃除当番はありません。',
            ]);
        }

        $fontPath = is_file(self::JAPANESE_FONT_PATH)
            ? self::JAPANESE_FONT_PATH
            : null;
        $chroot = [base_path()];

        if ($fontPath !== null) {
            $chroot[] = dirname($fontPath);
        }

        $pdf = Pdf::setOption('chroot', $chroot)
            ->setOption('enable_font_subsetting', true)
            ->loadView('pdf.cleaning-assignments', [
                ...$data,
                'fontPath' => $fontPath,
            ])
            ->setPaper('a4', 'portrait');
        $filename = "cleaning-assignments-{$date}.pdf";

        return $download
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }
}
