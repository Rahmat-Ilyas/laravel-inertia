<?php

namespace App\Http\Controllers\Apps;

use Inertia\Inertia;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SaleController extends Controller
{
    public function index()
    {
        return Inertia::render('Apps/Sales/Index');
    }

    public function filter(Request $request)
    {
        $request->validate([
            'start_date'  => 'required',
            'end_date'    => 'required',
        ]);

        $sales = Transaction::with('cashier', 'customer')
            ->whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->get();

        $total = Transaction::whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->sum('grand_total');

        return Inertia::render('Apps/Sales/Index', [
            'sales'    => $sales,
            'total'    => (int) $total
        ]);
    }

    public function export(Request $request)
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setCreator('REPORT SALES')
            ->setLastModifiedBy('REPORT SALES')
            ->setTitle('REPORT SALES')
            ->setSubject('REPORT SALES')
            ->setDescription('REPORT SALES')
            ->setKeywords('pdf php');
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
        $sheet->getRowDimension(5)->setRowHeight(25);
        $sheet->getRowDimension(1)->setRowHeight(17);
        $sheet->getRowDimension(2)->setRowHeight(17);
        $sheet->getRowDimension(3)->setRowHeight(17);
        $spreadsheet->getDefaultStyle()->getFont()->setName('Times New Roman');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10);
        $spreadsheet->getActiveSheet()->getPageSetup()->setHorizontalCentered(true);
        $spreadsheet->getActiveSheet()->getPageSetup()->setVerticalCentered(false);

        //Margin PDF
        $spreadsheet->getActiveSheet()->getPageMargins()->setTop(0.3);
        $spreadsheet->getActiveSheet()->getPageMargins()->setRight(0.3);
        $spreadsheet->getActiveSheet()->getPageMargins()->setLeft(0.5);
        $spreadsheet->getActiveSheet()->getPageMargins()->setBottom(0.3);
        $sheet->getStyle('A:F')->getAlignment()->setWrapText(true);

        // Header Text
        $sheet->setCellValue('A1', 'REPORT SALES FARGROW STORE');
        $sheet->setCellValue('A2', 'Alamat : Dusun Karangpuang, Desa Barugae');
        $sheet->setCellValue('A3', 'Tep. : +6285333341194');
        $sheet->mergeCells('A1:F1');
        $sheet->mergeCells('A2:F2');
        $sheet->mergeCells('A3:F3');
        $sheet->getStyle('A1')->getFont()->setSize(12);

        $sheet->setCellValue('A4', 'NO');
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->setCellValue('B4', 'Date');
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->setCellValue('C4', 'Invoice');
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->setCellValue('D4', 'Cashier');
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->setCellValue('E4', 'Customer');
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->setCellValue('F4', 'Total');
        $sheet->getColumnDimension('F')->setWidth(20);

        $sheet->getStyle('A4:F4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('A2A9F8');

        $sales = Transaction::with('cashier', 'customer')->whereDate('created_at', '>=', $request->start_date)->whereDate('created_at', '<=', $request->end_date)->get();
        $total = Transaction::whereDate('created_at', '>=', $request->start_date)->whereDate('created_at', '<=', $request->end_date)->sum('grand_total');

        $cell = $cell_x = 5;
        $no = 1;

        foreach ($sales as $sale) {
            $sheet->setCellValue('A' . $cell, $no++);
            $sheet->setCellValue('B' . $cell, $sale->created_at);
            $sheet->setCellValue('C' . $cell, $sale->invoice);
            $sheet->setCellValue('D' . $cell, $sale->cashier->name ?? '');
            $sheet->setCellValue('E' . $cell, $sale->customer->name ?? 'Umum');
            $sheet->setCellValue('F' . $cell, formatPrice($sale->grand_total));
            $cell++;
        }

        $sheet->setCellValue('A' . $cell, 'TOTAL')->mergeCells('A' . $cell . ':E' . $cell);
        $sheet->setCellValue('F' . $cell, formatPrice($total));

        $sheet->getStyle('A' . $cell . ':F' . $cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('A2A9F8');

        $sheet->getStyle('A1:F' . $cell)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A1:F' . $cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '0000000'],
                ],
            ],
        ];

        $sheet->getStyle('A4:F' . $cell)->applyFromArray($border);
        $cell++;

        if ($request->type == 'excel') {
            $writer = new Xlsx($spreadsheet);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="REPORT_SALES.xlsx"');
        } else {

            $sheet->setCellValue('A' . ++$cell, 'Dicetak melalui ' . url()->current())->mergeCells('A' . $cell . ':F' . $cell);
            $spreadsheet->getActiveSheet()->getHeaderFooter()
                ->setOddHeader('&C&H' . url()->current());
            $spreadsheet->getActiveSheet()->getHeaderFooter()
                ->setOddFooter('&L&B &RPage &P of &N');
            $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, $cell_x - 1);
            $class = \PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf::class;
            \PhpOffice\PhpSpreadsheet\IOFactory::registerWriter('Pdf', $class);
            header('Content-Type: application/pdf');
            header('Cache-Control: max-age=0');
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Pdf');
        }
        $writer->save('php://output');
        exit;
    }
}
