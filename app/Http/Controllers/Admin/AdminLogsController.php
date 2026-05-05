<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminLogsController extends Controller
{
    public function index(Request $request)
    {
        $logPath = storage_path('logs/laravel.log');
        $lignes  = 200;
        $contenu = '';

        if (file_exists($logPath)) {
            // Lire les N dernières lignes efficacement
            $file   = new \SplFileObject($logPath, 'r');
            $file->seek(PHP_INT_MAX);
            $total  = $file->key();
            $debut  = max(0, $total - $lignes);
            $result = [];
            $file->seek($debut);
            while (!$file->eof()) {
                $result[] = $file->current();
                $file->next();
            }
            $contenu = implode('', $result);
        }

        return view('admin.logs.index', compact('contenu'));
    }

    public function clear()
    {
        $logPath = storage_path('logs/laravel.log');
        if (file_exists($logPath)) {
            file_put_contents($logPath, '');
        }
        return back()->with('success', 'Log vidé avec succès.');
    }
}
