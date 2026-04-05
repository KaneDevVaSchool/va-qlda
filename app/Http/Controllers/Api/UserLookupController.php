<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserLookupController extends Controller
{
    public function index(Request $request)
    {
        $q = User::query()->select('id', 'name', 'email', 'role')->orderBy('name');

        if ($search = $request->query('q')) {
            $q->where(function ($qq) use ($search) {
                $qq->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        return $q->limit(100)->get();
    }
}
