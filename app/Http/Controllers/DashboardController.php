<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function dashboard()
    {
        $tokens = request()->user()->tokens;

        return view('dashboard', compact('tokens'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function showTokenForm()
    {
        return view('token-create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function createToken(Request $request)
    {
        $request->validate([
            'name' => 'required'
        ]);

        $tokenName = request()->post('name');
        $user = request()->user();

        $token = $user->createToken($tokenName)->plainTextToken;

        return view('token-show', compact(['tokenName', 'token']));

    }


    /**
     * Remove the specified resource from storage.
     */
    public function deleteToken(PersonalAccessToken $token)
    {
        $token->delete();

        return redirect('dashboard');
    }
}
