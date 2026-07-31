<?php

namespace App\Http\Controllers\Web\SpaOwner;

use App\Domain\Customers\Actions\AdjustWalletAction;
use App\Domain\Customers\Models\Customer;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CustomerWalletController extends Controller
{
    public function store(Request $request, Customer $customer, AdjustWalletAction $action): RedirectResponse
    {
        $this->authorize('update', $customer);

        $data = $request->validate([
            'type' => ['required', 'in:credit,debit'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $action->execute($customer, $data['type'], (float) $data['amount'], $data['reason'] ?? null, $request->user()->id);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['amount' => $e->getMessage()]);
        }

        return back()->with('success', 'Wallet updated.');
    }
}
