<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeacherRequest;
use App\Http\Requests\UpdateTeacherRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class TeacherController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', User::class);

        $teachers = User::where('tenant_id', Auth::user()->tenant_id)
            ->latest()
            ->get();

        return Inertia::render('Teachers/Index', [
            'teachers' => $teachers,
        ]);
    }

    public function store(StoreTeacherRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        User::create([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            // Admin-created accounts skip self-registration entirely, so there's
            // no Registered event and no verification email to click — the admin
            // vouches for the email address by typing it in here.
            'email_verified_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Pengajar berhasil ditambahkan.');
    }

    public function update(UpdateTeacherRequest $request, User $teacher): RedirectResponse
    {
        $validated = $request->validated();

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $teacher->update($data);

        return redirect()->back()->with('success', 'Data pengajar berhasil diperbarui.');
    }

    public function destroy(User $teacher): RedirectResponse
    {
        Gate::authorize('delete', $teacher);

        $teacher->delete();

        return redirect()->back()->with('success', 'Pengajar berhasil dihapus.');
    }
}
