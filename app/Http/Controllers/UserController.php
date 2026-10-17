<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $auth = $request->user();
        $query = User::query();

        // Guru hanya boleh melihat datanya sendiri.
        if ($auth->isGuru()) {
            $query->whereKey($auth->id);
        }

        $users = $query
            ->when($request->q, function ($q) use ($request) {
                $q->where(function ($q2) use ($request) {
                    $q2->where('name', 'like', "%{$request->q}%")
                        ->orWhere('email', 'like', "%{$request->q}%")
                        ->orWhere('nomor_hp', 'like', "%{$request->q}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('users.index', compact('users'));
    }

    public function store(Request $request)
    {
        // Guru tidak boleh menambah user.
        abort_unless($request->user()->isAdmin(), 403, 'Hanya admin yang boleh menambah user.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'nomor_hp' => ['required', 'string', 'max:30'],
            'role' => ['required', 'in:admin,guru'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah dipakai.',
            'nomor_hp.required' => 'Nomor HP wajib diisi.',
            'role.required' => 'Role wajib dipilih.',
            'role.in' => 'Role tidak valid.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        User::create([
            'name' => trim($data['name']),
            'email' => trim($data['email']),
            'nomor_hp' => trim($data['nomor_hp']),
            'role' => $data['role'],
            'password' => $data['password'],
        ]);

        return back()->with('ok', 'User ditambah.');
    }

    public function update(Request $request, User $user)
    {
        $auth = $request->user();

        // Guru hanya boleh mengubah datanya sendiri, dan tidak boleh mengubah role.
        if ($auth->isGuru()) {
            abort_unless($auth->id === $user->id, 403, 'Guru hanya boleh mengubah datanya sendiri.');
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'nomor_hp' => ['required', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ];
        if ($auth->isAdmin()) {
            $rules['role'] = ['required', 'in:admin,guru'];
        }

        $data = $request->validate($rules, [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah dipakai.',
            'nomor_hp.required' => 'Nomor HP wajib diisi.',
            'role.required' => 'Role wajib dipilih.',
            'role.in' => 'Role tidak valid.',
            'password.min' => 'Password minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $user->name = trim($data['name']);
        $user->email = trim($data['email']);
        $user->nomor_hp = trim($data['nomor_hp']);
        if ($auth->isAdmin()) {
            $user->role = $data['role'];
        }
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        return back()->with('ok', 'User diupdate.');
    }

    public function destroy(Request $request, User $user)
    {
        // Hanya admin yang boleh menghapus user.
        abort_unless($request->user()->isAdmin(), 403, 'Hanya admin yang boleh menghapus user.');

        if ($request->user()->id === $user->id) {
            return back()->withErrors(['user' => 'Tidak bisa menghapus akun yang sedang dipakai.']);
        }

        $user->delete();

        return back()->with('ok', 'User dihapus.');
    }
}
