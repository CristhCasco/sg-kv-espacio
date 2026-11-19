<?php

namespace App\Http\Livewire;

use App\Models\Company;
use App\Models\Sale;
use App\Services\ScaleBarcodeService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class SettingsController extends Component
{
    use WithFileUploads;

    public $componentName, $pageTitle, $tab = 'empresa';

    // Datos de la empresa
    public $name, $trade_name, $ruc, $dv, $address, $city, $phone, $email, $economic_activity;

    // Facturación (timbrado y numeración)
    public $timbrado_number, $timbrado_valid_from, $timbrado_valid_until;
    public $establishment_code, $expedition_point, $invoice_start, $invoice_end, $invoice_current;

    // Impresión
    public $logoUpload, $ticket_width = 80, $ticket_show_logo = true, $ticket_footer, $print_after_sale = 'ask';
    public $print_method = 'browser';

    // Balanza (etiquetas con código de barras)
    public $scale_enabled, $scale_barcode_length, $scale_weight_prefix, $scale_unit_prefix;
    public $scale_code_start, $scale_code_length, $scale_value_start, $scale_value_length;
    public $scale_weight_decimals, $scale_unit_decimals, $scaleTestBarcode = '';

    public function mount()
    {
        $this->componentName = 'Configuración';
        $this->pageTitle = 'Empresa, facturación, impresión y balanza';

        $this->fill(ScaleBarcodeService::DEFAULTS);

        $company = Company::first();
        if ($company) {
            $this->fill(array_filter($company->only(ScaleBarcodeService::FIELDS), function ($v) {
                return $v !== null;
            }));
            $this->fill($company->only([
                'name', 'trade_name', 'ruc', 'dv', 'address', 'city', 'phone', 'email', 'economic_activity',
                'timbrado_number', 'establishment_code', 'expedition_point',
                'invoice_start', 'invoice_end', 'invoice_current',
                'ticket_width', 'ticket_show_logo', 'ticket_footer', 'print_after_sale',
            ]));
            $this->print_method = Company::printMethod();
            $this->timbrado_valid_from  = optional($company->timbrado_valid_from ? Carbon::parse($company->timbrado_valid_from) : null)->format('Y-m-d');
            $this->timbrado_valid_until = optional($company->timbrado_valid_until ? Carbon::parse($company->timbrado_valid_until) : null)->format('Y-m-d');
        } else {
            $this->establishment_code = '001';
            $this->expedition_point = '001';
            $this->invoice_start = 1;
            $this->invoice_end = 9999999;
            $this->invoice_current = 0;
        }
    }

    public function render()
    {
        return view('livewire.settings.component', [
            'nextInvoice'     => $this->nextInvoicePreview(),
            'lastIssued'      => $this->lastIssuedInvoice(),
            'timbradoStatus'  => $this->timbradoStatus(),
            'currentLogo'     => optional(Company::first())->logoDataUri(),
            'scaleTest'       => $this->scaleTestResult(),
        ])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    public function setTab($tab)
    {
        $this->tab = $tab;
    }

    public function saveCompany()
    {
        $this->validate([
            'name'              => 'required|max:255',
            'trade_name'        => 'nullable|max:255',
            'ruc'               => 'nullable|regex:/^\d+$/|max:20',
            'dv'                => 'nullable|required_with:ruc|digits:1',
            'address'           => 'required|max:255',
            'city'              => 'nullable|max:100',
            'phone'             => 'required|max:50',
            'email'             => 'nullable|email|max:150',
            'economic_activity' => 'nullable|max:255',
        ], [
            'name.required'    => 'La razón social es requerida',
            'ruc.regex'        => 'El RUC solo debe contener números (sin el DV)',
            'dv.required_with' => 'El dígito verificador es requerido si carga el RUC',
            'dv.digits'        => 'El DV debe ser un solo dígito',
            'address.required' => 'La dirección es requerida',
            'phone.required'   => 'El teléfono es requerido',
            'email.email'      => 'El correo no es válido',
        ]);

        $company = Company::first() ?? new Company(['invoice_current' => 0]);
        $company->fill([
            'name'              => trim($this->name),
            'trade_name'        => $this->trade_name ?: null,
            'ruc'               => trim((string) $this->ruc), // vacío si aún no tiene RUC (columna NOT NULL)
            'dv'                => trim((string) $this->ruc) !== '' ? $this->dv : null,
            'address'           => trim($this->address),
            'city'              => $this->city ?: null,
            'phone'             => trim($this->phone),
            'email'             => $this->email ?: null,
            'economic_activity' => $this->economic_activity ?: null,
        ])->save();

        $this->emit('global-msg', 'Datos de la empresa guardados');
    }

    public function saveInvoicing()
    {
        $this->validate([
            'timbrado_number'      => 'required|regex:/^\d+$/|max:20',
            'timbrado_valid_from'  => 'nullable|date',
            'timbrado_valid_until' => 'required|date' . ($this->timbrado_valid_from ? '|after_or_equal:timbrado_valid_from' : ''),
            'establishment_code'   => 'required|digits:3',
            'expedition_point'     => 'required|digits:3',
            'invoice_start'        => 'required|integer|min:1',
            'invoice_end'          => 'required|integer|gte:invoice_start|max:9999999',
            'invoice_current'      => 'required|integer|min:0|lte:invoice_end',
        ], [
            'timbrado_number.required'            => 'El número de timbrado es requerido',
            'timbrado_number.regex'               => 'El timbrado solo debe contener números',
            'timbrado_valid_until.required'       => 'La fecha de vencimiento es requerida',
            'timbrado_valid_until.after_or_equal' => 'El vencimiento debe ser posterior al inicio de vigencia',
            'establishment_code.digits'           => 'Debe tener 3 dígitos (ej: 001)',
            'expedition_point.digits'             => 'Debe tener 3 dígitos (ej: 001)',
            'invoice_end.gte'                     => 'El número final debe ser mayor o igual al inicial',
            'invoice_end.max'                     => 'El número final no puede superar 9999999',
            'invoice_current.lte'                 => 'La última factura emitida no puede superar el número final',
        ]);

        $company = Company::first();
        if (! $company) {
            $this->emit('global-msg', 'Primero guarda los datos de la empresa');
            $this->tab = 'empresa';
            return;
        }

        $company->update([
            'timbrado_number'      => $this->timbrado_number,
            'timbrado_valid_from'  => $this->timbrado_valid_from ?: null,
            'timbrado_valid_until' => $this->timbrado_valid_until,
            'establishment_code'   => $this->establishment_code,
            'expedition_point'     => $this->expedition_point,
            'invoice_start'        => (int) $this->invoice_start,
            'invoice_end'          => (int) $this->invoice_end,
            'invoice_current'      => (int) $this->invoice_current,
        ]);

        $this->emit('global-msg', 'Datos de facturación guardados');
    }

    public function savePrinting()
    {
        $this->validate([
            'logoUpload'       => 'nullable|image|mimes:png,jpg,jpeg|max:1024',
            'ticket_width'     => 'required|in:' . implode(',', Company::TICKET_WIDTHS),
            'ticket_show_logo' => 'boolean',
            'ticket_footer'    => 'nullable|max:300',
            'print_after_sale' => 'required|in:ask,ticket,none',
            'print_method'     => 'required|in:' . implode(',', array_keys(Company::PRINT_METHODS)),
        ], [
            'logoUpload.image' => 'El logo debe ser una imagen',
            'logoUpload.mimes' => 'El logo debe ser PNG o JPG',
            'logoUpload.max'   => 'El logo no puede pesar más de 1 MB',
            'ticket_width.in'  => 'Ancho de papel no válido',
            'print_method.in'  => 'Método de impresión no válido',
            'ticket_footer.max' => 'El pie del ticket no puede superar 300 caracteres',
        ]);

        $company = Company::first();
        if (! $company) {
            $this->emit('global-msg', 'Primero guarda los datos de la empresa');
            $this->tab = 'empresa';
            return;
        }

        if ($this->logoUpload) {
            $fileName = 'logo_' . uniqid() . '.' . $this->logoUpload->extension();
            $this->logoUpload->storeAs('public/' . Company::LOGO_DIR, $fileName);
            $this->deleteLogoFile($company->logo);
            $company->logo = $fileName;
        }

        $company->fill([
            'ticket_width'     => (int) $this->ticket_width,
            'ticket_show_logo' => (bool) $this->ticket_show_logo,
            'ticket_footer'    => trim((string) $this->ticket_footer) ?: null,
            'print_after_sale' => $this->print_after_sale,
            'print_method'     => $this->print_method,
        ])->save();

        Company::forgetCurrent();
        $this->logoUpload = null;
        $this->emit('global-msg', 'Configuración de impresión guardada');
    }

    public function removeLogo()
    {
        $company = Company::first();
        if ($company && $company->logo) {
            $this->deleteLogoFile($company->logo);
            $company->update(['logo' => null]);
            Company::forgetCurrent();
        }

        $this->logoUpload = null;
        $this->emit('global-msg', 'Logo eliminado');
    }

    private function scaleConfig()
    {
        return [
            'scale_enabled'         => (bool) $this->scale_enabled,
            'scale_barcode_length'  => (int) $this->scale_barcode_length,
            'scale_weight_prefix'   => trim((string) $this->scale_weight_prefix) ?: null,
            'scale_unit_prefix'     => trim((string) $this->scale_unit_prefix) ?: null,
            'scale_code_start'      => (int) $this->scale_code_start,
            'scale_code_length'     => (int) $this->scale_code_length,
            'scale_value_start'     => (int) $this->scale_value_start,
            'scale_value_length'    => (int) $this->scale_value_length,
            'scale_weight_decimals' => (int) $this->scale_weight_decimals,
            'scale_unit_decimals'   => (int) $this->scale_unit_decimals,
        ];
    }

    public function saveScale()
    {
        $this->validate([
            'scale_enabled'         => 'boolean',
            'scale_barcode_length'  => 'required|integer|min:8|max:20',
            'scale_weight_prefix'   => 'nullable|regex:/^\d{1,5}$/|different:scale_unit_prefix',
            'scale_unit_prefix'     => 'nullable|regex:/^\d{1,5}$/',
            'scale_code_start'      => 'required|integer|min:1|lte:scale_barcode_length',
            'scale_code_length'     => 'required|integer|min:1|max:10',
            'scale_value_start'     => 'required|integer|min:1|lte:scale_barcode_length',
            'scale_value_length'    => 'required|integer|min:1|max:10',
            'scale_weight_decimals' => 'required|integer|min:0|max:6',
            'scale_unit_decimals'   => 'required|integer|min:0|max:6',
        ], [
            'scale_weight_prefix.regex'     => 'Solo números (ej: 2)',
            'scale_unit_prefix.regex'       => 'Solo números (ej: 1)',
            'scale_weight_prefix.different' => 'El prefijo de peso y el de unidad deben ser distintos',
            'scale_code_start.lte'          => 'La posición no puede superar el largo de la etiqueta',
            'scale_value_start.lte'         => 'La posición no puede superar el largo de la etiqueta',
        ]);

        $c = $this->scaleConfig();
        if ($c['scale_code_start'] + $c['scale_code_length'] - 1 > $c['scale_barcode_length']
            || $c['scale_value_start'] + $c['scale_value_length'] - 1 > $c['scale_barcode_length']) {
            $this->addError('scale_barcode_length', 'El código o el valor se salen del largo de la etiqueta');
            return;
        }

        $company = Company::first();
        if (! $company) {
            $this->emit('global-msg', 'Primero guarda los datos de la empresa');
            $this->tab = 'empresa';
            return;
        }

        $company->update($c);
        Company::forgetCurrent();
        $this->emit('global-msg', 'Configuración de balanza guardada');
    }

    public function resetScaleDefaults()
    {
        $this->fill(ScaleBarcodeService::DEFAULTS);
        $this->resetErrorBag();
    }

    /**
     * Interpreta la etiqueta de prueba con los valores que están en pantalla (aunque no estén guardados).
     */
    private function scaleTestResult()
    {
        $barcode = trim((string) $this->scaleTestBarcode);
        if ($barcode === '' || $this->tab !== 'balanza') {
            return null;
        }

        if (\App\Models\Product::where('barcode', $barcode)->exists()) {
            return ['ok' => true, 'exact' => true, 'product' => \App\Models\Product::where('barcode', $barcode)->first()];
        }

        $label = (new ScaleBarcodeService($this->scaleConfig()))->parse($barcode);

        if (! $label) {
            return ['ok' => false, 'message' => 'No es una etiqueta de balanza con esta configuración (revisa prefijos y largo) y tampoco existe un producto con ese código.'];
        }

        if ($label['error']) {
            return ['ok' => false, 'label' => $label, 'message' => $label['error']];
        }

        return ['ok' => true, 'exact' => false, 'label' => $label];
    }

    private function deleteLogoFile($fileName)
    {
        if ($fileName) {
            Storage::disk('public')->delete(Company::LOGO_DIR . '/' . $fileName);
        }
    }

    /**
     * Número que se asignará a la próxima factura (aprox., misma lógica base que InvoiceNumberService).
     */
    private function nextInvoicePreview()
    {
        if (! $this->establishment_code || ! $this->expedition_point) {
            return null;
        }

        $current = max((int) $this->invoice_current, (int) $this->invoice_start - 1);
        $next = str_pad($current + 1, 7, '0', STR_PAD_LEFT);

        return "{$this->establishment_code}-{$this->expedition_point}-{$next}";
    }

    private function lastIssuedInvoice()
    {
        return Sale::whereNotNull('invoice_number')->orderByDesc('id')->value('invoice_number');
    }

    private function timbradoStatus()
    {
        if (! $this->timbrado_valid_until) {
            return ['class' => 'secondary', 'text' => 'Sin fecha de vencimiento'];
        }

        $days = now()->startOfDay()->diffInDays(Carbon::parse($this->timbrado_valid_until), false);

        if ($days < 0) {
            return ['class' => 'danger', 'text' => 'Timbrado vencido: no se podrán emitir facturas'];
        }
        if ($days <= 30) {
            return ['class' => 'warning', 'text' => "El timbrado vence en {$days} día(s)"];
        }

        return ['class' => 'success', 'text' => "Timbrado vigente ({$days} días restantes)"];
    }
}
