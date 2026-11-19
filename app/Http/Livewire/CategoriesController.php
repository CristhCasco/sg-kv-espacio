<?php

namespace App\Http\Livewire;


use App\Models\Category;
use App\Models\VatType;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;


class CategoriesController extends Component
{
    use WithFileUploads;
    use WithPagination;


    public $name, $vat_type_id, $selected_id, $image, $search, $componentName, $pageTitle;
    private $pagination = 5;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Categorias';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }


    public function render()
    {
        if(strlen($this->search) > 0)
        {
            $data = Category::with('vatType')
                ->where('name', 'like', '%' . $this->search . '%')
                ->paginate($this->pagination);
        }
        else
        {
            $data = Category::with('vatType')->orderBy('id', 'desc')->paginate($this->pagination);
        }

        $vatTypes = VatType::orderBy('rate', 'asc')->get();
        
        return view('livewire.category.categories', ['categories' => $data, 'vatTypes' => $vatTypes])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    public function Edit($id)
    {
        $record = Category::find($id, ['id', 'name', 'vat_type_id', 'image']);
        $this->name = $record->name;
        $this->vat_type_id = $record->vat_type_id;
        $this->selected_id = $record->id;
        $this->image = null;

        $this->emit('show-modal', 'show modal!');
    }

    public function Store()
    {
        $rules = [
            'name' => 'required|unique:categories|min:3',
            'vat_type_id' => 'required|exists:vat_types,id'
        ];

        $messages = [
            'name.required' => 'Nombre de la categoria es requerido',
            'name.unique' => 'Ya existe una categoria con ese nombre',
            'name.min' => 'El nombre de la categoria debe tener al menos 3 caracteres',
            'vat_type_id.required' => 'El tipo de IVA es requerido',
            'vat_type_id.exists' => 'Seleccione un tipo de IVA valido'
        ];

        $this->validate($rules, $messages);

        $category = Category::create([
            'name' => $this->name,
            'vat_type_id' => $this->vat_type_id
        ]);

        $customFileName = null;
        if($this->image)
        {   //para crear un nombre unico para la imagen
            $customFileName = uniqid() . '_.' . $this->image->extension();
            //para guardar la imagen en el storage
            $this->image->storeAs('public/categories', $customFileName);
            //para actualizar el campo image de la tabla categories
            $category->image = $customFileName;
            //para guardar el registro
            $category->save();
        }

        $this->resetUI();
        $this->emit('category-added', 'Categoria Registrada');
    }

    public function Update()
    {
        $rules = [
            'name' => "required|min:3|unique:categories,name,{$this->selected_id}",
            'vat_type_id' => 'required|exists:vat_types,id'
        ];

        $messages = [
            'name.required' => 'Nombre de la categoria es requerido',
            'name.unique' => 'Ya existe una categoria con ese nombre',
            'name.min' => 'El nombre de la categoria debe tener al menos 3 caracteres',
            'vat_type_id.required' => 'El tipo de IVA es requerido',
            'vat_type_id.exists' => 'Seleccione un tipo de IVA valido'
        ];

        $this->validate($rules, $messages);

        //para actualizar el registro
        $category = Category::find($this->selected_id);
        //para actualizar el campo name
        $category->update([
            'name' => $this->name,
            'vat_type_id' => $this->vat_type_id
        ]);

        //validar si el usuario subio una imagen
        if($this->image)
		{
			$customFileName = uniqid() . '_.' . $this->image->extension();
			$this->image->storeAs('public/categories', $customFileName);
			$imageName = $category->image;

			$category->image = $customFileName;
			$category->save();

			if($imageName !=null)
			{
				if(file_exists('storage/categories' . $imageName))
				{
					unlink('storage/categories' . $imageName);
				}
			} 

		}

		$this->resetUI();
		$this->emit('category-updated', 'Categoría Actualizada');
    }

    public function resetUI()
    {
        $this->name = '';
        $this->vat_type_id = null;
        $this->image = null;
        $this->search = '';
        $this->selected_id = 0;
    } 

    //para escuchar el evento deleteRow
    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(Category $category)
	{   	

		$imageName = $category->image; 
		$category->delete();

		if($imageName !=null) {
			unlink('storage/categories/' . $imageName);
		}

		$this->resetUI();
		$this->emit('category-deleted', 'Categoría Eliminada');

	}
        
}

