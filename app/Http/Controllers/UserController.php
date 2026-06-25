<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->orderBy('id')->paginate(20)->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'username' => 'required|string|max:100|unique:users,username',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,user',
            'department' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['email'] = $validated['username'] . '@zcgl.local';

        User::create($validated);

        return redirect()->route('users.index')->with('success', '用户添加成功');
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'username' => ['required', 'string', 'max:100', Rule::unique('users')->ignore($user->id)],
            'role' => 'required|in:admin,user',
            'department' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'password' => 'nullable|string|min:6',
        ]);

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('users.index')->with('success', '用户更新成功');
    }

    public function destroy(User $user)
    {
        // 不能删除自己
        if ($user->id === auth()->id()) {
            return back()->with('error', '不能删除自己的账号');
        }

        // 确保至少保留一个管理员
        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', '必须保留至少一个管理员账号');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', '用户删除成功');
    }
}
