<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class StaffController extends Controller
{
	public function index(): View
	{
		$users = User::query()
			->where('role', '!=', 2)
			->orderBy('id')
			->get();

		return view('admin.staff.list', compact('users'));
	}
}
