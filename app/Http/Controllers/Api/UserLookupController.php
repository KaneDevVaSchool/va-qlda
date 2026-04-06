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

        if ($search = trim((string) $request->query('q', ''))) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $like = '%'.$escaped.'%';
            $q->where(function ($qq) use ($like) {
                $qq->where('name', 'like', $like)->orWhere('email', 'like', $like);
            });
        }

        return $q->limit(100)->get();
    }
}
