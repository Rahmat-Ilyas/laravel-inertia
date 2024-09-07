<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::when(request()->q, function ($categories) {
            $categories = $categories->where('name', 'like', '%' . request()->q . '%');
        })->latest()->paginate(5);
        
        return Inertia::render('Apps/Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function create()
    {
        return Inertia::render('Apps/Categories/Create');
    }

    public function edit(Category $category)
    {
        return Inertia::render('Apps/Categories/Edit', [
            'category' => $category,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'image'         => 'required|image|mimes:jpeg,jpg,png|max:2000',
            'name'          => 'required|unique:categories',
            'description'   => 'required'
        ]);
        
        $image = $request->file('image');
        $image->storeAs('public/categories', $image->hashName());
        
        Category::create([
            'image'         => $image->hashName(),
            'name'          => $request->name,
            'description'   => $request->description
        ]);
        
        return redirect()->route('apps.categories.index');
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name'          => 'required|unique:categories,name,' . $category->id,
            'description'   => 'required'
        ]);
        
        if ($request->file('image')) {
            Storage::disk('local')->delete('public/categories/' . basename($category->image));
            
            $image = $request->file('image');
            $image->storeAs('public/categories', $image->hashName());
            
            $category->update([
                'image' => $image->hashName(),
                'name' => $request->name,
                'description'   => $request->description
            ]);
        }
        
        $category->update([
            'name'          => $request->name,
            'description'   => $request->description
        ]);
        
        return redirect()->route('apps.categories.index');
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        Storage::disk('local')->delete('public/categories/' . basename($category->image));
        $category->delete();
        
        return redirect()->route('apps.categories.index');
    }
}
