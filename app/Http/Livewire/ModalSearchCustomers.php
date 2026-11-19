<?php

namespace App\Http\Livewire;

use App\Models\Customer;
use CristhCasco\SgDnit\Concerns\CustomerFormTrait;
use Livewire\Component;

class ModalSearchCustomers extends Component
{
    use CustomerFormTrait;

    public $search = '', $customers = [];

    protected $paginationTheme = 'bootstrap';
    public  $customerName, $customerId;

    // Alta rápida de cliente desde el POS
    public $creating = false;

    protected $listeners = ['cancelNewCustomer'];

    //Para que se ejecute en tiempo real el metodo buscar
    public function liveSearch()
    {

        if (strlen($this->search) > 0) {
            $this->customers = Customer::select('customers.*')
                ->where('customers.id', 'like', "%{$this->search}%")
                ->orwhere('customers.name', 'like', "%{$this->search}%")
                ->orWhere('customers.last_name', 'like', "%{$this->search}%")
                ->orWhere('customers.ci', 'like', "%{$this->search}%")
                ->orWhere('customers.document_number', 'like', "%{$this->search}%")
                ->orWhere('customers.company', 'like', "%{$this->search}%")
                ->orderBy('customers.name', 'asc')
                ->get()->take(10);
        } else {
            return $this->customers = [];
        }
    }

    //Para reenderizar la vista
    public function render()
    {
        $this->liveSearch();

        return view('livewire.modalsearch.customers.component');
    }

    public function setCustomer($customerId)
    {
        $customer = Customer::find($customerId);
        if (!$customer) {
            return;
        }

        // Empresas no tienen nombre: se muestra la razón social
        $name = $customer->isDefaultOccasional() ? $customer->name : $customer->display_name;

        $this->customerName = $name;
        $this->customerId = $customer->id;
        $this->emit('setCustomer', $customer->id, $name);
        $this->dispatchBrowserEvent('closeModal');
    }

    /**
     * Abre el formulario de alta. Si lo buscado parece un CI / RUC, lo precarga
     * y consulta la DNIT; si no, lo usa como nombre.
     */
    public function newCustomer()
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

    public function cancelNewCustomer()
    {
        $this->resetCustomerForm();
        $this->creating = false;
    }

    /**
     * Guarda el cliente nuevo y lo asigna directamente a la venta.
     */
    public function saveCustomer()
    {
        $this->normalizeInputs();
        $this->validate($this->rules(), $this->messages());

        $customer = Customer::create($this->payload());

        $this->cancelNewCustomer();
        $this->search = '';
        $this->emit('customer-assigned', 'Cliente registrado y asignado a la venta');
        $this->setCustomer($customer->id);
    }
}
