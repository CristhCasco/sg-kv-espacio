<?php

namespace App\Services;

use App\Models\Company;

class TicketPdfLayoutService
{
    public function calculateHeight(iterable $items, array $options = []): float
    {
        $minHeight = (float) ($options['minHeight'] ?? 600);
        $baseHeight = (float) ($options['baseHeight'] ?? 220);
        $lineHeight = (float) ($options['lineHeight'] ?? 12);
        $charsPerLine = max(1, (int) ($options['charsPerLine'] ?? Company::ticketCharsPerLine()));
        $nameKey = $options['itemNameKey'] ?? 'name';
        $discountKey = $options['discountKey'] ?? 'unit_discount';

        $extraLines = (int) ($options['headerLines'] ?? 10)
            + (int) ($options['footerLines'] ?? 8)
            + (($options['includeQr'] ?? false) ? 8 : 0)
            + (optional(Company::current())->ticketLogo() ? 7 : 0);

        // Tickets con el detalle en columnas (CANT | DESCRIPCIÓN | P.U. | SUBT): una fila por producto,
        // más las líneas que ocupe la descripción si no entra en su columna. Medido con DomPDF:
        // ~12 pt por línea; se dejan 4 líneas de margen (crédito, descuentos, pie de varias líneas)
        // para no cortar el ticket en dos páginas.
        if (!empty($options['columns'])) {
            $lineHeight = (float) ($options['lineHeight'] ?? 12);
            $descChars = max(1, (int) ($options['descChars'] ?? round($charsPerLine * 0.5)));
            $detailLines = 1; // encabezado de columnas

            foreach ($items as $item) {
                $name = trim((string) data_get($item, $nameKey, ''));
                $detailLines += max(1, (int) ceil(mb_strlen($name) / $descChars));
                $detailLines += (float) data_get($item, $discountKey, 0) > 0 ? 2 : 0;
            }

            $height = (float) ($options['baseHeight'] ?? 0) + ($detailLines + $extraLines + 4) * $lineHeight;

            return max((float) ($options['minHeight'] ?? 150), $height);
        }

        $detailLines = 0;

        foreach ($items as $item) {
            $name = trim((string) data_get($item, $nameKey, ''));
            $nameLines = max(1, (int) ceil(mb_strlen($name) / $charsPerLine));
            $discountValue = (float) data_get($item, $discountKey, 0);
            $discountLines = $discountValue > 0 ? 2 : 0;

            $detailLines += $nameLines + 2 + $discountLines;
        }

        $calculatedHeight = $baseHeight + (($detailLines + $extraLines) * $lineHeight);

        return max($minHeight, $calculatedHeight);
    }
}
