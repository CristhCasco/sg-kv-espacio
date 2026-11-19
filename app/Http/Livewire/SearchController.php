<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Traits\CartTrait;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SearchController extends Component
{
    use CartTrait;

    public $search;
    public $currentPath;

    public function mount()
    {
        $this->currentPath = url()->current();
    }

    protected $listeners = ['scan-code' => 'ScanCode'];


    public function ScanCode($barcode)
    {
        //Log::info('SearchController -> ScanCode');

        $routeName = Str::afterLast($this->currentPath, '/');
        $isPreviewSales = strpos($this->currentPath, 'preview-sales') !== false;
        $isEditingPreviewSale = strpos($this->currentPath, 'preview-sales') !== false && strpos($this->currentPath, '/edit') !== false;
        $isCreatingPreviewSale = strpos($this->currentPath, 'preview-sales/create') !== false;
        $isPurchases = $routeName == 'purchases';

        if ($isPurchases) {
            $this->emit('addProductFromPurchase', $barcode);
        } elseif ($isEditingPreviewSale) {
            // Para editar preventas - buscar producto y emitir a EditPreviewSale
            $this->searchAndEmitProductToEdit($barcode);
        } elseif ($isCreatingPreviewSale) {
            // Para crear preventas - buscar producto y emitir a CreatePreviewSale
            $this->searchAndEmitProduct($barcode);
        } elseif ($routeName != 'pos' && !$isPurchases && !$isPreviewSales) {
            $this->ScanearCode($barcode);
            redirect()->to('/pos');
        }
    }

    public function searchAndEmitProduct($barcode)
    {
        $product = \App\Models\Product::where('barcode', 'like', "%{$barcode}%")
            ->orWhere('name', 'like', "%{$barcode}%")
            ->orWhere('model', 'like', "%{$barcode}%")
            ->first();

        if ($product) {
            // Emitir a CreatePreviewSale component
            $this->emitTo('create-preview-sale', 'addProduct', $product->id);
        }
    }

    public function searchAndEmitProductToEdit($barcode)
    {
        $product = \App\Models\Product::where('barcode', 'like', "%{$barcode}%")
            ->orWhere('name', 'like', "%{$barcode}%")
            ->orWhere('model', 'like', "%{$barcode}%")
            ->first();

        if ($product) {
            // Emitir a EditPreviewSale component
            $this->emitTo('edit-preview-sale', 'addProduct', $product->id);
        }
    }


    public function render()
    {
        return view('livewire.search');
    }
}
