<?php

namespace App\Http\Controllers\Apps;

use Inertia\Inertia;
use App\Models\Profit;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProfitController extends Controller
{
    public function index()
    {
        return Inertia::render('Apps/Profits/Index');
    }

    public function filter(Request $request)
    {
        $request->validate([
            'start_date'  => 'required',
            'end_date'    => 'required',
        ]);
        
        $profits = Profit::with('transaction')->whereDate('created_at', '>=', $request->start_date)->whereDate('created_at', '<=', $request->end_date)->get();
         
        $total = Profit::whereDate('created_at', '>=', $request->start_date)->whereDate('created_at', '<=', $request->end_date)->sum('total');

        return Inertia::render('Apps/Profits/Index', [
            'profits'   => $profits,
            'total'     => (int) $total
        ]);
    }

    public function export(Request $request)
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setCreator('REPORT PROFITS')
        ->setLastModifiedBy('REPORT PROFITS')
        ->setTitle('REPORT PROFITS')
        ->setSubject('REPORT PROFITS')
        ->setDescription('REPORT PROFITS')
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
        $sheet->getStyle('A:D')->getAlignment()->setWrapText(true);

        // Header Text
        $sheet->setCellValue('A1', 'REPORT PROFITS FARGROW STORE');
        $sheet->setCellValue('A2', 'Alamat : Dusun Karangpuang, Desa Barugae');
        $sheet->setCellValue('A3', 'Tep. : +6285333341194');
        $sheet->mergeCells('A1:D1');
        $sheet->mergeCells('A2:D2');
        $sheet->mergeCells('A3:D3');
        $sheet->getStyle('A1')->getFont()->setSize(12);

        $sheet->setCellValue('A4', 'NO');
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->setCellValue('B4', 'Date');
        $sheet->getColumnDimension('B')->setWidth(45);
        $sheet->setCellValue('C4', 'Invoice');
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->setCellValue('D4', 'Total');
        $sheet->getColumnDimension('D')->setWidth(30);

        $sheet->getStyle('A4:D4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('A2A9F8');

        $profits = Profit::with('transaction')->whereDate('created_at', '>=', $request->start_date)->whereDate('created_at', '<=', $request->end_date)->get();
        $total = Profit::whereDate('created_at', '>=', $request->start_date)->whereDate('created_at', '<=', $request->end_date)->sum('total');

        $cell = $cell_x = 5;
        $no = 1;

        foreach ($profits as $profit) {
            $sheet->setCellValue('A' . $cell, $no++);
            $sheet->setCellValue('B' . $cell, $profit->created_at);
            $sheet->setCellValue('C' . $cell, $profit->transaction->invoice);
            $sheet->setCellValue('D' . $cell, formatPrice($profit->total));
            $cell++;
        }

        $sheet->setCellValue('A' . $cell, 'TOTAL')->mergeCells('A' . $cell . ':C' . $cell);
        $sheet->setCellValue('D' . $cell, formatPrice($total));

        $sheet->getStyle('A' . $cell . ':D' . $cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('A2A9F8');

        $sheet->getStyle('A1:D' . $cell)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A1:D' . $cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '0000000'],
                ],
            ],
        ];

        $sheet->getStyle('A4:D' . $cell)->applyFromArray($border);
        $cell++;

        if ($request->type == 'excel') {
            $writer = new Xlsx($spreadsheet);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="REPORT_PROFITS.xlsx"');
        } else {

            $sheet->setCellValue('A' . ++$cell, 'Dicetak melalui ' . url()->current())->mergeCells('A' . $cell . ':D' . $cell);
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
