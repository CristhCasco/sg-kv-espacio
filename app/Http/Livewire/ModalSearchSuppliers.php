<?php

namespace App\Http\Livewire;

use App\Models\Supplier;
use App\Traits\SupplierFormTrait;
use Livewire\Component;

class ModalSearchSuppliers extends Component
{
    use SupplierFormTrait;

    public $search = '', $suppliers = [];

    protected $paginationTheme = 'bootstrap';
    public $supplierName, $supplierId;

    // Alta rápida de proveedor desde Compras (igual que clientes en Ventas)
    public $creating = false;

    protected $listeners = ['cancelNewSupplier'];

    //Para que se ejecute en tiempo real el metodo buscar
    public function liveSearch()
    {
        if (strlen($this->search) > 0) {
            $this->suppliers = Supplier::where('id', 'like', "%{$this->search}%")
                ->orWhere('name', 'like', "%{$this->search}%")
                ->orWhere('last_name', 'like', "%{$this->search}%")
                ->orWhere('ci', 'like', "%{$this->search}%")
                ->orWhere('document_number', 'like', "%{$this->search}%")
                ->orWhere('company', 'like', "%{$this->search}%")
                ->orWhere('ruc', 'like', "%{$this->search}%")
                ->orderBy('name', 'asc')
                ->take(10)
                ->get();
        } else {
            return $this->suppliers = [];
        }
    }

    //Para reenderizar la vista
    public function render()
    {
        $this->liveSearch();

        return view('livewire.modalsearch.suppliers.component');
    }

    public function setSupplier($supplierId)
    {
        $supplier = Supplier::find($supplierId);
        if (!$supplier) {
            return;
        }

        // Empresas no tienen nombre: se muestra la razón social
        $name = $supplier->display_name;

        $this->supplierName = $name;
        $this->supplierId = $supplier->id;
        $this->emit('setSupplier', $supplier->id, $name);
        $this->dispatchBrowserEvent('closeModal');
    }

    /**
     * Abre el formulario de alta. Si lo buscado parece un CI / RUC, lo precarga
     * y consulta la DNIT; si no, lo usa como nombre.
     */
    public function newSupplier()
    {
        $this->resetCustomerForm();
        $this->creating = true;

        $term = trim((string) $this->search);
        if (preg_match('/^[\d.\s-]+$/', $term)) {
            $this->document_number = $term;
            $this->updatedDocumentNumber();
        } elseif ($term !== '') {
            $this->person = 'FISICA';
            $this->name = $term;
        }
    }

    public function cancelNewSupplier()
    {
        $this->resetCustomerForm();
        $this->creating = false;
    }

    /**
     * Guarda el proveedor nuevo y lo asigna directamente a la compra.
     */
    public function saveSupplier()
    {
        $this->normalizeInputs();
        $this->validate($this->rules(), $this->messages());

        $supplier = Supplier::create($this->payload());

        $this->cancelNewSupplier();
        $this->search = '';
        $this->emit('supplier-assigned', 'Proveedor registrado y asignado a la compra');
        $this->setSupplier($supplier->id);
    }
}
