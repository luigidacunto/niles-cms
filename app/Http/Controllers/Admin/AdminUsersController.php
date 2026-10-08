<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminUsersController extends Controller
{
    public function index()
    {
        return view('admin.users.index', [
            'admins' => Admin::with('categories')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.users.form', [
            'admin' => new Admin(['role' => 'editor', 'permissions' => []]),
            'categories' => Category::orderBy('name')->get(),
            'resources' => config('permissions'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $admin = Admin::create($data + ['permissions' => $this->permissionsFromRequest($request)]);

        $admin->categories()->sync($this->categoryIdsToSync($request, $data['role']));

        return redirect()->route('admin.users.index')->with('status', 'Utente creato.');
    }

    public function edit(Admin $admin)
    {
        return view('admin.users.form', [
            'admin' => $admin,
            'categories' => Category::orderBy('name')->get(),
            'resources' => config('permissions'),
        ]);
    }

    public function update(Request $request, Admin $admin)
    {
        $data = $this->validated($request, $admin);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        // Utente protetto (il primo admin, seminato da FirstInstallSeeder): email/nome/password restano
        // modificabili, ma ruolo/permessi/categorie no, nemmeno da un altro admin — ignorati qui lato
        // server (non solo disabilitati in UI), così resta sempre garantito almeno un amministratore
        // con accesso pieno. Vedi App\Models\Admin::$protected.
        if ($admin->protected) {
            $data['role'] = 'admin';
            $admin->update($data);

            return redirect()->route('admin.users.index')->with('status', 'Utente aggiornato.');
        }

        $admin->update($data + ['permissions' => $this->permissionsFromRequest($request)]);
        $admin->categories()->sync($this->categoryIdsToSync($request, $data['role']));

        return redirect()->route('admin.users.index')->with('status', 'Utente aggiornato.');
    }

    public function destroy(Request $request, Admin $admin)
    {
        abort_if($admin->is($request->user('admin')), 403, 'Non puoi eliminare il tuo stesso account.');
        abort_if($admin->protected, 403, 'Questo utente è protetto e non può essere eliminato.');

        abort_if(
            $admin->role === 'admin' && Admin::where('role', 'admin')->count() <= 1,
            403,
            'Deve rimanere almeno un amministratore.'
        );

        $admin->delete();

        return redirect()->route('admin.users.index')->with('status', 'Utente eliminato.');
    }

    private function validated(Request $request, ?Admin $admin = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('admins', 'email')->ignore($admin)],
            'role' => ['required', Rule::in(['admin', 'editor'])],
            'active' => ['boolean'],
            'password_login_enabled' => ['boolean'],
            'all_categories' => ['boolean'],
            'password' => [
                $request->boolean('password_login_enabled') && ! $admin?->password
                    ? 'required' : 'nullable',
                'nullable', 'string', 'min:8',
            ],
        ];

        // Utente protetto: ruolo e categorie sono bloccati (il modulo li mostra disabilitati, quindi non vengono
        // nemmeno inviati) — non vanno né validati né letti, altrimenti il salvataggio fallisce per "role obbligatorio".
        if ($admin?->protected) {
            unset($rules['role'], $rules['all_categories']);
        }

        $data = $request->validate($rules);

        $data['active'] = $request->boolean('active');
        $data['password_login_enabled'] = $request->boolean('password_login_enabled');
        if (! $admin?->protected) {
            $data['all_categories'] = $request->boolean('all_categories');
        }

        return $data;
    }

    /**
     * Ogni editor gestisce sempre almeno «Notizie», la categoria generica di ripiego, così ha sempre un posto
     * dove mettere un post che non rientra in nulla di più specifico. Imposto lato server (non solo con una
     * casella disabilitata e spuntata nel form) per non poter essere tolto modificando la richiesta.
     */
    private function categoryIdsToSync(Request $request, string $role): array
    {
        $ids = collect($request->input('categories', []))->map(fn ($id) => (int) $id);

        if ($role === 'editor') {
            $newsId = Category::where('slug', 'news')->value('id');
            if ($newsId) {
                $ids->push($newsId);
            }
        }

        return $ids->unique()->values()->all();
    }

    private function permissionsFromRequest(Request $request): array
    {
        $permissions = [];

        foreach (array_keys(config('permissions')) as $resource) {
            $actions = array_values(array_intersect(
                $request->input("permissions.$resource", []),
                ['read', 'write']
            ));

            if ($actions && in_array($resource, Admin::COMBINED_PERMISSIONS, true)) {
                $actions = ['read', 'write'];
            }

            if ($actions) {
                $permissions[$resource] = $actions;
            }
        }

        return $permissions;
    }
}
