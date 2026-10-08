<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        return view('admin.categories.index', [
            'categories' => Category::orderBy('order')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.categories.form', ['category' => new Category]);
    }

    public function store(Request $request)
    {
        Category::create($this->validated($request));

        return redirect()->route('admin.categories.index')->with('status', 'Categoria creata.');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.form', ['category' => $category]);
    }

    public function update(Request $request, Category $category)
    {
        $category->update($this->validated($request, $category));

        return redirect()->route('admin.categories.index')->with('status', 'Categoria aggiornata.');
    }

    public function destroy(Category $category)
    {
        $postCount = $category->posts()->count();
        abort_if($postCount > 0, 422, "Non puoi eliminare questa categoria: ha ancora {$postCount} post assegnati. Spostali prima in un'altra categoria.");

        $category->delete();

        return redirect()->route('admin.categories.index')->with('status', 'Categoria eliminata.');
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('categories', 'slug')->ignore($category)],
            'description' => ['nullable', 'string', 'max:1000'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
