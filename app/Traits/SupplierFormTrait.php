<?php

namespace App\Traits;

use App\Models\Supplier;
use CristhCasco\SgDnit\Concerns\CustomerFormTrait;

/**
 * Formulario de proveedor con el mismo autocompletado DNIT que clientes:
 * reutiliza el formulario del paquete sg-dnit y cambia solo lo propio de proveedores.
 */
trait SupplierFormTrait
{
    use CustomerFormTrait {
        rules as protected dnitRules;
    }

    protected function dnitModel()
    {
        return Supplier::class;
    }

    protected function dnitEntityLabel()
    {
        return 'proveedor';
    }

    protected function rules()
    {
        $rules = $this->dnitRules();

        // Las empresas suelen tener teléfono fijo (021...), no solo celular
        $rules['phone'] = 'nullable|max:30';
        unset($rules['credit_limit']);

        return $rules;
    }

    protected function payload()
    {
        $isFisica = $this->person === 'FISICA';
        $hasRuc = $this->dv !== '';

        return [
            'person' => $this->person,
            'name' => $isFisica ? $this->name : null,
            'last_name' => $isFisica ? $this->last_name : null,
            'ci' => $isFisica ? (int) $this->document_number : null,
            'company' => $isFisica ? null : $this->company,

            'document_number' => $this->document_number,
            'dv' => $hasRuc ? $this->dv : null,
            // Columna anterior, la siguen leyendo búsquedas y reportes
            'ruc' => $hasRuc ? $this->document_number . '-' . $this->dv : null,

            'email' => $this->email ?: null,
            'phone' => $this->phone ?: null,
            'address' => $this->address ?: null,
            'birthday' => $this->birthday ?: null,
        ];
    }

    protected function fillFromSupplier(Supplier $supplier)
    {
        $this->resetValidation();
        $this->autoDetected = false;
        $this->dnitStatus = null;
        $this->dnitMessage = null;

        $this->selected_id = $supplier->id;
        $this->person = $supplier->person;
        $this->name = $supplier->name;
        $this->last_name = $supplier->last_name;
        $this->company = $supplier->company;
        // Proveedores antiguos pueden tener solo el CI cargado
        $this->document_number = $supplier->document_number ?: $supplier->ci;
        $this->dv = $supplier->dv;
        $this->email = $supplier->email;
        $this->phone = $supplier->phone;
        $this->address = $supplier->address;
        $this->birthday = $supplier->birthday;
    }
}
