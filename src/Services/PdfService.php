<?php
declare(strict_types=1);

namespace Somen\InventoryManagementSystem\Services;

use Somen\InventoryManagementSystem\Core\Env;

if (!extension_loaded('curl')) {
    throw new \RuntimeException('PDF engine (TCPDF) needs the PHP curl extension. Enable extension=curl in php.ini and restart Apache.');
}
$__tcpdf = dirname(__DIR__, 2) . '/lib/tcpdf/tcpdf.php';
if (!class_exists('TCPDF', false)) {
    require_once $__tcpdf;
}
unset($__tcpdf);

/**
 * Branded TCPDF document (navy header band, gold rule, page numbers).
 * Used later for receipts, delivery slips, ledger and stock reports.
 */
final class BrandedPdf extends \TCPDF
{
    public string $docTitle = '';

    public function Header(): void
    {
        $this->SetFillColor(11, 31, 58);
        $this->Rect(0, 0, $this->getPageWidth(), 22, 'F');
        $this->SetFillColor(201, 162, 75);
        $this->Rect(0, 22, $this->getPageWidth(), 1.2, 'F');
        $this->SetTextColor(201, 162, 75);
        $this->SetFont('helvetica', 'B', 16);
        $this->SetXY(15, 6);
        $this->Cell(90, 10, (string)Env::get('APP_NAME', 'StockSense'), 0, 0, 'L');
        $this->SetTextColor(248, 245, 238);
        $this->SetFont('helvetica', '', 10);
        $this->SetXY(100, 7.5);
        $this->Cell($this->getPageWidth() - 115, 8, $this->docTitle, 0, 0, 'R');
    }

    public function Footer(): void
    {
        $this->SetY(-14);
        $this->SetDrawColor(201, 162, 75);
        $this->Line(15, $this->GetY(), $this->getPageWidth() - 15, $this->GetY());
        $this->SetTextColor(11, 31, 58);
        $this->SetFont('helvetica', '', 8);
        $this->Cell(0, 8, 'Generated ' . date('d M Y, H:i') . '   |   Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

final class PdfService
{
    public static function make(string $title, string $orientation = 'P'): BrandedPdf
    {
        $pdf = new BrandedPdf($orientation, 'mm', 'A4', true, 'UTF-8', false);
        $pdf->docTitle = $title;
        $pdf->SetCreator((string)Env::get('APP_NAME', 'StockSense'));
        $pdf->SetTitle($title);
        $pdf->SetMargins(15, 30, 15);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->setFontSubsetting(true);
        $pdf->SetFont('dejavusans', '', 10); // supports the rupee sign
        $pdf->SetTextColor(11, 31, 58);
        $pdf->AddPage();
        return $pdf;
    }

    /** Returns raw PDF bytes (controllers send them with Response::raw). */
    public static function render(BrandedPdf $pdf): string
    {
        return $pdf->Output('document.pdf', 'S');
    }
}
