<?php

namespace App\Livewire\Shop;

use App\Enums\Permission;
use App\Models\Promotion;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Promotions')]
class PromotionList extends Component
{
    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can(Permission::PromotionsManage->value), 403);
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDelete = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = null;
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->can(Permission::PromotionsManage->value), 403);

        Promotion::findOrFail($id)->delete(); // coupons cascade
        $this->confirmingDelete = null;
        session()->flash('success', 'Promotion deleted.');
    }

    public function render()
    {
        return view('livewire.shop.promotion-list', [
            'promotions' => Promotion::withCount('coupons')->latest()->get(),
        ]);
    }
}
