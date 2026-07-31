<?php

namespace App\Http\Controllers\Web;

use App\Domain\Impersonation\Actions\StopImpersonationAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StopImpersonationController extends Controller
{
    public function store(Request $request, StopImpersonationAction $stopImpersonation): RedirectResponse
    {
        $impersonatorId = $request->session()->get('impersonator_id');

        abort_unless($impersonatorId, 403);

        $stopImpersonation->execute($impersonatorId, $request->user()->id);

        return redirect()->route('admin.spas.index');
    }
}
