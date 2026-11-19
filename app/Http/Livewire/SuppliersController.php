<?php

namespace App\Http\Livewire;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Traits\SupplierFormTrait;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class SuppliersController extends Component
{
    use WithPagination;
    use WithFileUploads;
    use SupplierFormTrait;

    // PUBLIC PROPERTIES (los campos del proveedor vienen de SupplierFormTrait)
    public $search, $pageTitle, $componentName, $image;

    private $pagination = 5;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Proveedores';
        $this->resetUI();
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if (strlen($this->search) > 0) {
            $suppliers = Supplier::where('name', 'like', '%' . $this->search . '%')
                ->orWhere('last_name', 'like', '%' . $this->search . '%')
                ->orWhere('ci', 'like', '%' . $this->search . '%')
                ->orWhere('document_number', 'like', '%' . $this->search . '%')
                ->orWhere('address', 'like', '%' . $this->search . '%')
                ->orWhere('email', 'like', '%' . $this->search . '%')
                ->orWhere('company', 'like', '%' . $this->search . '%')
                ->orWhere('ruc', 'like', '%' . $this->search . '%')
                ->orderBy('id', 'desc')
                ->paginate($this->pagination);
        } else {
            $suppliers = Supplier::orderBy('id', 'desc')->paginate($this->pagination);
        }

        return view('livewire.suppliers.component', ['data' => $suppliers])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    // STORE ---------------------------------------------------------------
    public function Store()
    {
        $this->normalizeInputs();
        $this->validate($this->rules(), $this->messages());

        $supplier = Supplier::create($this->payload());

        if ($this->image) {
            $customFileName = uniqid() . '_.' . $this->image->extension();
            $this->image->storeAs('public/suppliers', $customFileName);
            $supplier->image = $customFileName;
            $supplier->save();
        }

        $this->resetUI();
        $this->emit('supplier-added', 'Proveedor Registrado');
    }

    // EDIT ---------------------------------------------------------------
    public function Edit(Supplier $supplier)
    {
        $this->fillFromSupplier($supplier);
        $this->image = '';

        $this->emit('modal-show', 'show modal!');
    }

    // UPDATE ---------------------------------------------------------------
    public function Update()
    {
        $this->normalizeInputs();
        $this->validate($this->rules(), $this->messages());

        $supplier = Supplier::find($this->selected_id);
        $supplier->update($this->payload());

        if ($this->image) {
            $customFileName = uniqid() . '_.' . $this->image->extension();
            $this->image->storeAs('public/suppliers', $customFileName);

            $oldImage = $supplier->image;
            $supplier->image = $customFileName;
            $supplier->save();

            if ($oldImage && file_exists('storage/suppliers/' . $oldImage)) {
                unlink('storage/suppliers/' . $oldImage);
            }
        }

        $this->resetUI();
        $this->emit('supplier-updated', 'Proveedor Actualizado');
    }

    // DESTROY ---------------------------------------------------------------
    protected $listeners = ['deleteRow' => 'Destroy'];

    public function Destroy($id)
    {
        $supplier = Supplier::find($id);

        if (!$supplier) {
            $this->emit('supplier-error', 'Proveedor no encontrado');
            return;
        }

        // Las compras y sus créditos se borran en cascada con el proveedor: nunca borrar uno con compras
        if (Purchase::where('supplier_id', $supplier->id)->exists()) {
            $this->emit('supplier-error', 'No se puede eliminar el proveedor porque tiene compras registradas');
            return;
        }

        $imageTemp = $supplier->image;
        $supplier->delete();

        if ($imageTemp && file_exists('storage/suppliers/' . $imageTemp)) {
            unlink('storage/suppliers/' . $imageTemp);
        }

        $this->resetUI();
        $this->emit('supplier-deleted', 'Proveedor Eliminado');
    }

    // RESET ---------------------------------------------------------------
    public function resetUI()
    {
        $this->resetCustomerForm();
        $this->search = '';
        $this->image = '';
    }
}
