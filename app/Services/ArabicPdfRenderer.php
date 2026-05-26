<?php

namespace App\Services;

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\Response;

/**
 * Arabic-aware PDF renderer (mPDF).
 *
 * Why mPDF and not DomPDF: DomPDF does NOT apply OpenType GSUB substitutions
 * or BiDi reordering, which causes Arabic letters to render disconnected and
 * in reversed (logical) order. mPDF handles both natively when useOTL is set
 * and directionality is rtl.
 *
 * Cairo TTFs live at public/fonts/cairo/ (Regular + Bold). SemiBold is not
 * mapped because mPDF's per-family slot model only supports R/B/I/BI.
 */
class ArabicPdfRenderer
{
    public function build(string $view, array $data, array $opts = []): Mpdf
    {
        $defaults = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaults['fontDir'];

        $fontData = (new FontVariables())->getDefaults()['fontdata'];

        // mPDF concatenates fontDir entries with the TTF filename directly,
        // so the directory MUST end with a separator AND use forward slashes
        // (mixed separators on Windows can defeat mPDF's path resolution).
        $cairoDir = str_replace('\\', '/', public_path('fonts/cairo')) . '/';

        $mpdf = new Mpdf([
            'mode'             => 'utf-8',
            'format'           => $opts['paper'] ?? 'A4',
            'orientation'      => $opts['orientation'] ?? 'P',
            'margin_left'      => 12,
            'margin_right'     => 12,
            'margin_top'       => 15,
            'margin_bottom'    => 15,
            'margin_header'    => 6,
            'margin_footer'    => 6,
            'tempDir'          => storage_path('app/mpdf-tmp'),
            'fontDir'          => array_merge($fontDirs, [$cairoDir]),
            'fontdata'         => $fontData + [
                'cairo' => [
                    'R'          => 'Cairo-Regular.ttf',
                    'B'          => 'Cairo-Bold.ttf',
                    'useOTL'     => 0xFF,
                    'useKashida' => 75,
                ],
            ],
            'default_font'      => 'cairo',
            'default_font_size' => 11,
        ]);

        $mpdf->SetDirectionality('rtl');
        $mpdf->autoScriptToLang   = true;
        $mpdf->autoLangToFont     = true;
        $mpdf->SetTitle($opts['title'] ?? '');

        $mpdf->WriteHTML(view($view, $data)->render());

        return $mpdf;
    }

    public function stream(string $view, array $data, string $filename, array $opts = []): Response
    {
        return $this->respond($this->build($view, $data, $opts), $filename, 'I');
    }

    public function download(string $view, array $data, string $filename, array $opts = []): Response
    {
        return $this->respond($this->build($view, $data, $opts), $filename, 'D');
    }

    private function respond(Mpdf $mpdf, string $filename, string $dest): Response
    {
        $bytes = $mpdf->Output($filename, 'S');

        $headers = [
            'Content-Type' => 'application/pdf',
        ];

        if ($dest === 'D') {
            $headers['Content-Disposition'] = 'attachment; filename="' . $filename . '"';
        } else {
            $headers['Content-Disposition'] = 'inline; filename="' . $filename . '"';
        }

        return new Response($bytes, 200, $headers);
    }
}
