<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use TeamTeaTime\Forum\Models\Category;
use TeamTeaTime\Forum\Models\Thread;

class ForumCategoryController extends Controller
{
    public function show($category_id)
    {
        $category = Category::findOrFail($category_id);
        
        $threads = Thread::where('category_id', $category->id)
            ->with(['author', 'lastPost.author'])
            ->withCount('posts')
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        // Map threads data to ensure all needed properties are available
        $threads->getCollection()->transform(function ($thread) {
            return [
                'id' => $thread->id,
                'title' => $thread->title,
                'reply_count' => $thread->reply_count,
                'locked' => $thread->locked,
                'pinned' => $thread->pinned,
                'created_at' => $thread->created_at,
                'updated_at' => $thread->updated_at,
                'author' => [
                    'id' => $thread->author->id,
                    'name' => $thread->author->name,
                ],
                'lastPost' => $thread->lastPost ? [
                    'id' => $thread->lastPost->id,
                    'author' => [
                        'id' => $thread->lastPost->author->id,
                        'name' => $thread->lastPost->author->name,
                    ],
                    'created_at' => $thread->lastPost->created_at,
                ] : null,
                'posts_count' => $thread->posts_count,
            ];
        });

        return Inertia::render('Forum/Category/Show', [
            'category' => [
                'id' => $category->id,
                'title' => $category->title,
                'description' => $category->description,
                'color_light_mode' => $category->color_light_mode,
                'color_dark_mode' => $category->color_dark_mode,
            ],
            'threads' => $threads
        ]);
    }

    public function manage()
    {
        // Check permissions
        $user = auth()->user();
        if (!$user || !$this->canManageCategories($user)) {
            abort(403, 'Unauthorized');
        }

        $categories = Category::with('children')
            ->whereNull('parent_id')
            ->orderBy('weight')
            ->get();

        return Inertia::render('Forum/Category/Manage', [
            'categories' => $categories
        ]);
    }

    public function store(Request $request)
    {
        // Check permissions
        $user = auth()->user();
        if (!$user || !$this->canManageCategories($user)) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'parent_id' => 'nullable|integer|exists:forum_categories,id',
            'accepts_threads' => 'boolean',
            'is_private' => 'boolean',
            'color_light_mode' => 'nullable|string|max:7',
            'color_dark_mode' => 'nullable|string|max:7'
        ]);

        $category = Category::create([
            'title' => $request->title,
            'description' => $request->description,
            'parent_id' => $request->parent_id,
            'accepts_threads' => $request->boolean('accepts_threads'),
            'is_private' => $request->boolean('is_private'),
            'color_light_mode' => $request->color_light_mode,
            'color_dark_mode' => $request->color_dark_mode ?? $request->color_light_mode,
            'weight' => Category::max('weight') + 1
        ]);

        return redirect()->back()->with('success', 'Catégorie créée avec succès.');
    }

    public function update(Request $request, $category_id)
    {
        $category = Category::findOrFail($category_id);
        
        // Check permissions
        $user = auth()->user();
        if (!$user || !$this->canManageCategories($user)) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'parent_id' => 'nullable|integer|exists:forum_categories,id',
            'accepts_threads' => 'boolean',
            'is_private' => 'boolean',
            'color_light_mode' => 'nullable|string|max:7',
            'color_dark_mode' => 'nullable|string|max:7'
        ]);

        $category->update([
            'title' => $request->title,
            'description' => $request->description,
            'parent_id' => $request->parent_id,
            'accepts_threads' => $request->boolean('accepts_threads'),
            'is_private' => $request->boolean('is_private'),
            'color_light_mode' => $request->color_light_mode,
            'color_dark_mode' => $request->color_dark_mode ?? $request->color_light_mode,
        ]);

        return redirect()->back()->with('success', 'Catégorie modifiée avec succès.');
    }

    public function destroy($category_id)
    {
        $category = Category::findOrFail($category_id);
        
        // Check permissions
        $user = auth()->user();
        if (!$user || !$this->canManageCategories($user)) {
            abort(403, 'Unauthorized');
        }

        // Check if category has threads
        if ($category->threads()->count() > 0) {
            return redirect()->back()->withErrors('Impossible de supprimer une catégorie qui contient des threads.');
        }

        // Check if category has children
        if ($category->children()->count() > 0) {
            return redirect()->back()->withErrors('Impossible de supprimer une catégorie qui contient des sous-catégories.');
        }

        $category->delete();

        return redirect()->back()->with('success', 'Catégorie supprimée avec succès.');
    }

    public function bulkManage(Request $request)
    {
        // Check permissions
        $user = auth()->user();
        if (!$user || !$this->canManageCategories($user)) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|integer|exists:forum_categories,id',
            'categories.*.weight' => 'required|integer'
        ]);

        foreach ($request->categories as $categoryData) {
            Category::where('id', $categoryData['id'])
                ->update(['weight' => $categoryData['weight']]);
        }

        return response()->json(['success' => true]);
    }

    private function canManageCategories($user)
    {
        // Only admins can manage categories
        return $user->roles()->where('name', 'admin')->exists();
    }
}