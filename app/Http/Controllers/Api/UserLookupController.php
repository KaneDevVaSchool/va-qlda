<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserLookupController extends Controller
{
    public function index(Request $request)
    {
        $idsParam = $request->query('ids');
        if ($idsParam !== null && $idsParam !== '') {
            $idList = is_array($idsParam) ? $idsParam : [$idsParam];
            $idList = array_values(array_unique(array_filter(array_map('intval', $idList))));
            if (count($idList) === 0) {
                return collect();
            }
            $idList = array_slice($idList, 0, 100);

            return User::query()
                ->select('id', 'name', 'email', 'role')
                ->whereIn('id', $idList)
                ->orderBy('name')
                ->get();
        }

        $search = trim((string) $request->query('q', ''));
        if ($search === '') {
            return collect();
        }

        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
        $like = '%'.$escaped.'%';

        return User::query()
            ->select('id', 'name', 'email', 'role')
            ->orderBy('name')
            ->where(function ($qq) use ($like) {
                $qq->where('name', 'like', $like)->orWhere('email', 'like', $like);
            })
            ->limit(100)
            ->get();
    }
}
