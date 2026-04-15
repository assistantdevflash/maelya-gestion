<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageContact;
use Illuminate\Http\Request;

class AdminMessageController extends Controller
{
    public function index(Request $request)
    {
        $messages = MessageContact::latest()->paginate(30);
        $nonLus = MessageContact::where('lu', false)->count();
        return view('admin.messages.index', compact('messages', 'nonLus'));
    }

    public function marquerLu(MessageContact $message)
    {
        $message->update(['lu' => true]);
        return back()->with('success', 'Message marqué comme lu.');
    }

    public function destroy(MessageContact $message)
    {
        $message->delete();
        return back()->with('success', 'Message supprimé.');
    }
}
