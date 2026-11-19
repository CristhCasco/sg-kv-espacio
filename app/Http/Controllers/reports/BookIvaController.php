<?php

namespace App\Http\Controllers\reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\BookIvaSalesExport;
use Maatwebsite\Excel\Facades\Excel;

class BookIvaController extends Controller
{
    public function exportLibroIvaVentas(Request $request)
    {
        $start = $request->input('date_from');
        $end = $request->input('date_to');

        $fileName = 'LibroIVA_Ventas_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(
            new BookIvaSalesExport($start, $end),
            $fileName
        );
    }
}
