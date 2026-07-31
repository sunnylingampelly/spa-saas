<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Domain\Announcements\Models\Announcement;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('SuperAdmin/Announcements', [
            'announcements' => Announcement::with('creator:id,name')->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($data, $request) {
            // Only one announcement is ever shown at a time — publishing a new one
            // retires whatever was active before it.
            Announcement::where('is_active', true)->update(['is_active' => false]);

            Announcement::create([
                'message' => $data['message'],
                'is_active' => true,
                'created_by_user_id' => $request->user()->id,
            ]);
        });

        return back()->with('success', 'Announcement published.');
    }

    public function deactivate(Announcement $announcement): RedirectResponse
    {
        $announcement->update(['is_active' => false]);

        return back()->with('success', 'Announcement withdrawn.');
    }
}
