<?php

namespace App\Http\Controllers;

use App\Models\GameScore;
use Illuminate\Http\Request;

class GameScoreController extends Controller
{
    public function index()
    {
        $scores = GameScore::where('game_key', 'orbital_dodge')
            ->orderByDesc('score')
            ->orderBy('created_at')
            ->limit(10)
            ->get(['id', 'name', 'score', 'created_at']);

        return response()->json($scores);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:50',
            'score' => 'required|integer|min:1',
        ]);

        $score = GameScore::create([
            'game_key' => 'orbital_dodge',
            'name'     => $data['name'],
            'score'    => $data['score'],
        ]);

        return response()->json($score, 201);
    }
}
