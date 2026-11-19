<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Customer;
use CristhCasco\SgDnit\Concerns\CustomerFormTrait;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Validation\ValidationException;

class CustomersController extends Component
{
    use WithPagination;
    use WithFileUploads;
    use CustomerFormTrait;

    // PUBLIC PROPERTIES (los campos del cliente vienen de CustomerFormTrait)
    public $search, $pageTitle, $componentName, $image;

    private $pagination = 5;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Clientes';
        $this->resetUI();
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if (strlen($this->search) > 0) {

            $customers = Customer::where('name', 'like', '%' . $this->search . '%')
                ->orWhere('last_name', 'like', '%' . $this->search . '%')
                ->orWhere('ci', 'like', '%' . $this->search . '%')
                ->orWhere('address', 'like', '%' . $this->search . '%')
                ->orWhere('email', 'like', '%' . $this->search . '%')
                ->orWhere('company', 'like', '%' . $this->search . '%')
                ->orWhere('document_number', 'like', '%' . $this->search . '%')
                ->orderBy('id', 'desc')
                ->paginate($this->pagination);

        } else {

            $customers = Customer::orderBy('id', 'desc')->paginate($this->pagination);
        }

        return view('livewire.customers.component', ['data' => $customers])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    // STORE ---------------------------------------------------------------
    public function Store()
    {
        $this->normalizeInputs();
        $this->validate($this->rules(), $this->messages());

        $customer = Customer::create($this->payload());

        if ($this->image) {
            $customFileName = uniqid() . '_.' . $this->image->extension();
            $this->image->storeAs('public/customers', $customFileName);
            $customer->image = $customFileName;
            $customer->save();
        }

        $this->resetUI();
        $this->emit('customer-added', 'Cliente Registrado');
    }


    // EDIT ---------------------------------------------------------------
    public function Edit(Customer $customer)
    {
        $this->resetValidation();
        $this->autoDetected = false;
        $this->dnitStatus = null;
        $this->dnitMessage = null;
        $this->image = '';

        $this->selected_id     = $customer->id;
        $this->person          = $customer->person;
        $this->name            = $customer->name;
        $this->last_name       = $customer->last_name;
        $this->email           = $customer->email;
        $this->phone           = $customer->phone;

        // Clientes antiguos pueden tener solo el CI cargado
        $this->document_number = $customer->document_number ?: $customer->ci;
        $this->dv              = $customer->dv;
        $this->company         = $customer->company;

        $this->address         = $customer->address;
        $this->birthday        = $customer->birthday;
        $this->credit_limit    = \App\Helpers\Num::input($customer->credit_limit); // decimal(15,2): sin ".00" en el campo

        $this->document_type_id = $customer->document_type_id;
        $this->tax_condition_id = $customer->tax_condition_id;

        $this->emit('modal-show', 'show modal!');
    }

    // UPDATE ---------------------------------------------------------------
    public function Update()
    {
        $this->normalizeInputs();
        $this->validate($this->rules(), $this->messages());

        $customer = Customer::find($this->selected_id);

        $customer->update($this->payload());

        if ($this->image) {
            $customFileName = uniqid() . '_.' . $this->image->extension();
            $this->image->storeAs('public/customers', $customFileName);

            $oldImage = $customer->image;
            $customer->image = $customFileName;
            $customer->save();

            if ($oldImage && file_exists('storage/customers/' . $oldImage)) {
                unlink('storage/customers/' . $oldImage);
            }
        }

        $this->resetUI();
        $this->emit('customer-updated', 'Cliente Actualizado');
    }

    // DESTROY ---------------------------------------------------------------
    protected $listeners = ['deleteRow' => 'Destroy'];

    public function Destroy($id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            $this->emit('customer-error', 'Cliente no encontrado');
            return;
        }

        $imageTemp = $customer->image;

        try {
            $customer->delete();
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first()
                ?: 'No se pudo eliminar el cliente.';

            $this->emit('customer-error', $message);
            return;
        }

        if ($imageTemp && file_exists('storage/customers/' . $imageTemp)) {
            unlink('storage/customers/' . $imageTemp);
        }

        $this->resetUI();
        $this->emit('customer-deleted', 'Cliente Eliminado');
    }

    // RESET ---------------------------------------------------------------
    public function resetUI()
    {
        $this->resetCustomerForm();
        $this->search = '';
        $this->image = '';
    }
}
