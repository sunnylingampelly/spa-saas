<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Customers\Models\Customer;

class UpdateCustomerAction
{
    public function execute(Customer $customer, array $data): Customer
    {
        $customer->update($data);

        return $customer;
    }
}
