<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UserAdminController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');

        $users = User::query()
            ->with(['roles:id,name,color', 'bannedBy:id,name'])
            ->withCount([
                'collections',
                'forumPosts as forum_posts_count' => fn ($query) => $query->withTrashed(),
                'forumThreads as forum_threads_count' => fn ($query) => $query->withTrashed(),
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status === 'banned', fn ($query) => $query->whereNotNull('banned_at'))
            ->when($status === 'active', fn ($query) => $query->whereNull('banned_at'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function ban(Request $request, User $user): RedirectResponse
    {
        $this->ensureTargetCanBeModerated($request, $user);

        $validated = $request->validate([
            'banned_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $user->forceFill([
            'banned_at' => now(),
            'banned_reason' => $validated['banned_reason'] ?? null,
            'banned_by' => $request->user()->id,
        ])->save();

        $this->deleteUserSessions($user);

        return back()->with('success', "Le compte de {$user->name} a été suspendu.");
    }

    public function unban(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            abort(422, 'Vous ne pouvez pas modifier votre propre suspension.');
        }

        $user->forceFill([
            'banned_at' => null,
            'banned_reason' => null,
            'banned_by' => null,
        ])->save();

        return back()->with('success', "Le compte de {$user->name} a été réactivé.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->ensureTargetCanBeModerated($request, $user);

        $validated = $request->validate([
            'delete_forum_messages' => ['sometimes', 'boolean'],
        ]);

        $deleteForumMessages = (bool) ($validated['delete_forum_messages'] ?? false);
        $name = $user->name;

        DB::transaction(function () use ($user, $deleteForumMessages): void {
            $this->deleteUserSessions($user);

            if ($deleteForumMessages) {
                $this->deleteForumContent($user);
            } else {
                $anonymousUser = $this->deletedUserPlaceholder();
                $this->transferForumContent($user, $anonymousUser);
            }

            $user->delete();
        });

        $message = $deleteForumMessages
            ? "Le compte de {$name} et ses messages forum ont été supprimés."
            : "Le compte de {$name} a été supprimé. Ses messages forum ont été conservés sous utilisateur supprimé.";

        return redirect()->route('admin.users.index')->with('success', $message);
    }

    private function ensureTargetCanBeModerated(Request $request, User $user): void
    {
        if ($user->id === $request->user()->id) {
            abort(422, 'Vous ne pouvez pas effectuer cette action sur votre propre compte.');
        }
    }

    private function deleteUserSessions(User $user): void
    {
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
    }

    private function deleteForumContent(User $user): void
    {
        $user->forumThreads()
            ->withTrashed()
            ->get()
            ->each(function ($thread): void {
                $thread->posts()->withTrashed()->forceDelete();
                $thread->forceDelete();
            });

        $user->forumPosts()->withTrashed()->forceDelete();
    }

    private function transferForumContent(User $from, User $to): void
    {
        $from->forumThreads()->withTrashed()->update(['author_id' => $to->id]);
        $from->forumPosts()->withTrashed()->update(['author_id' => $to->id]);
    }

    private function deletedUserPlaceholder(): User
    {
        $user = User::where('email', 'deleted-user@vinyls-collection.local')->first();

        if ($user) {
            return $user;
        }

        $user = new User([
            'name' => 'Utilisateur supprimé',
            'email' => 'deleted-user@vinyls-collection.local',
            'password' => Str::password(64),
            'profile_public' => false,
        ]);
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }
}
