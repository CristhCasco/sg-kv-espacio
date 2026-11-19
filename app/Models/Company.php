<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $table = 'companies';

    protected  $fillable = [
        'name', 'trade_name', 'ruc', 'dv', 'address', 'city', 'phone', 'email', 'economic_activity',
        'timbrado_number', 'timbrado_valid_from', 'timbrado_valid_until',
        'establishment_code', 'expedition_point', 'invoice_start', 'invoice_end', 'invoice_current',
        'logo', 'ticket_width', 'ticket_show_logo', 'ticket_footer', 'print_after_sale', 'print_method',
        'scale_enabled', 'scale_barcode_length', 'scale_weight_prefix', 'scale_unit_prefix',
        'scale_code_start', 'scale_code_length', 'scale_value_start', 'scale_value_length',
        'scale_weight_decimals', 'scale_unit_decimals',
    ];

    protected $casts = [
        'ticket_show_logo' => 'boolean',
        'ticket_width'     => 'integer',
        'scale_enabled'    => 'boolean',
    ];

    /** Carpeta (disco public) donde se guarda el logo. */
    const LOGO_DIR = 'company';

    /** Anchos de papel soportados (mm). */
    const TICKET_WIDTHS = [58, 80];

    /** Métodos de impresión soportados (clave => nombre en Configuración). */
    const PRINT_METHODS = [
        'browser' => 'Navegador (impresión web)',
        'ninja'   => 'Programa PrinterNinja (print://)',
    ];

    private static $current;

    /**
     * Empresa configurada (una sola), cacheada durante la petición.
     */
    public static function current()
    {
        if (! self::$current) {
            self::$current = self::first();
        }

        return self::$current;
    }

    public static function forgetCurrent()
    {
        self::$current = null;
    }

    /** Ancho del ticket en mm (58 u 80). */
    public static function ticketWidth()
    {
        $width = (int) optional(self::current())->ticket_width;

        return in_array($width, self::TICKET_WIDTHS) ? $width : 80;
    }

    /** Método de impresión configurado (browser por defecto). */
    public static function printMethod()
    {
        $method = optional(self::current())->print_method;

        return array_key_exists($method, self::PRINT_METHODS) ? $method : 'browser';
    }

    /** Ancho del ticket en puntos, para setPaper() de DomPDF. */
    public static function ticketWidthPt()
    {
        return round(self::ticketWidth() * 72 / 25.4, 2);
    }

    /** Caracteres aproximados por línea según el ancho del papel. */
    public static function ticketCharsPerLine()
    {
        return self::ticketWidth() === 58 ? 20 : 28;
    }

    /** Ruta absoluta del logo en disco, o null si no hay. */
    public function logoPath()
    {
        if (! $this->logo) {
            return null;
        }

        $path = storage_path('app/public/' . self::LOGO_DIR . '/' . $this->logo);

        return is_file($path) ? $path : null;
    }

    /**
     * Logo incrustado en base64: funciona en vistas HTML y en DomPDF sin depender
     * del enlace "storage" ni de la URL/subcarpeta donde esté instalado el sistema.
     */
    public function logoDataUri()
    {
        $path = $this->logoPath();
        if (! $path) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }

    /** Logo a imprimir en tickets (respeta la opción "mostrar logo"). */
    public function ticketLogo()
    {
        return $this->ticket_show_logo ? $this->logoDataUri() : null;
    }

    /** Líneas del pie de ticket configurado (o el texto por defecto). */
    public function ticketFooterLines($default = 'Gracias por su compra')
    {
        $text = trim((string) $this->ticket_footer);

        return preg_split('/\r\n|\r|\n/', $text !== '' ? $text : $default);
    }

    public static function getNextInvoiceNumber($companyId)
{
    $company = self::find($companyId);

    // Buscar el siguiente número disponible
    $next = $company->invoice_current + 1;

    // Verificar si ese número ya fue usado por una venta ACTIVA
    $exists = \App\Models\Sale::where('company_id', $companyId)
        ->where('invoice_number', $next)
        ->whereHas('documentStatus', function ($q) {
            $q->where('name', 'ACTIVA');
        })
        ->exists();

    if ($exists) {
        // Si existe, seguir incrementando hasta encontrar libre
        while ($exists) {
            $next++;
            $exists = \App\Models\Sale::where('company_id', $companyId)
                ->where('invoice_number', $next)
                ->whereHas('documentStatus', function ($q) {
                    $q->where('name', 'ACTIVA');
                })
                ->exists();
        }
    }

    // Actualizar contador
    $company->invoice_current = $next;
    $company->save();

    return $next;
}



   
    
}
