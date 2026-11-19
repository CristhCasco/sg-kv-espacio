<?php

namespace Tests\Unit;

use App\Services\TicketPdfLayoutService;
use PHPUnit\Framework\TestCase;

class TicketPdfLayoutServiceTest extends TestCase
{
    public function test_it_increases_ticket_height_for_large_tickets(): void
    {
        $service = new TicketPdfLayoutService();

        $items = collect(range(1, 30))->map(function ($index) {
            return [
                'name' => 'Producto con descripción extensa para probar el cálculo dinámico del alto del ticket número ' . $index,
                'unit_discount' => $index % 2 === 0 ? 1000 : 0,
            ];
        });

        $height = $service->calculateHeight($items, [
            'headerLines' => 12,
            'footerLines' => 10,
            'includeQr' => true,
        ]);

        $this->assertGreaterThan(600, $height);
    }

    public function test_it_keeps_minimum_height_for_small_tickets(): void
    {
        $service = new TicketPdfLayoutService();

        $items = collect([
            ['name' => 'Producto corto', 'unit_discount' => 0],
        ]);

        $height = $service->calculateHeight($items);

        $this->assertSame(600.0, $height);
    }
}
