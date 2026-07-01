<?php

namespace App\Livewire\Shop;

use App\Enums\OrderStatus;
use App\Enums\Permission;
use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class OrderDetail extends Component
{
    public Order $order;

    public function mount(int $id): void
    {
        abort_unless(auth()->user()->can(Permission::OrdersManage->value), 403);

        $this->order = Order::with(['items.product', 'items.variant'])->findOrFail($id);
    }

    public function transitionTo(string $status): void
    {
        abort_unless(auth()->user()->can(Permission::OrdersManage->value), 403);

        $to = OrderStatus::from($status);

        if (! $this->order->canTransitionTo($to)) {
            session()->flash('error', 'That status change is not allowed from here.');

            return;
        }

        // Restock before the transition flips the status - shouldRestockOnCancel()
        // reflects the state we're leaving, not the one we're entering.
        if ($to === OrderStatus::Cancelled && $this->order->status->shouldRestockOnCancel()) {
            foreach ($this->order->items as $item) {
                $item->product?->incrementStock($item->quantity, $item->variant);
            }
        }

        $this->order->transitionTo($to);
        session()->flash('success', 'Order status updated.');
    }

    public function render()
    {
        return view('livewire.shop.order-detail', [
            'availableTransitions' => $this->order->status->transitions(),
        ]);
    }

    public function title(): string
    {
        return 'Order '.$this->order->number;
    }
}
