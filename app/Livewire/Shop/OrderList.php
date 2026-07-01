<?php

namespace App\Livewire\Shop;

use App\Enums\Permission;
use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Orders')]
class OrderList extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->can(Permission::OrdersManage->value), 403);
    }

    public function render()
    {
        return view('livewire.shop.order-list', [
            'orders' => Order::withCount('items')->latest('placed_at')->get(),
        ]);
    }
}
