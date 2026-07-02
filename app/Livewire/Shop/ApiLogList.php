<?php

namespace App\Livewire\Shop;

use App\Enums\Permission;
use App\Models\ApiLog;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Read-only viewer over every outbound Xendit/KiriminAja API call and inbound
 * webhook (App\Support\ApiLogger) - built for debugging (Kenza, 07-02: "I
 * need to see every log on that API").
 */
#[Layout('layouts.admin')]
#[Title('API Logs')]
class ApiLogList extends Component
{
    use WithPagination;

    /** @var 'all'|'xendit'|'kiriminaja' */
    public string $provider = 'all';

    /** @var 'all'|'outbound'|'inbound' */
    public string $direction = 'all';

    /** @var 'all'|'success'|'failed' */
    public string $outcome = 'all';

    public ?int $expandedId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can(Permission::OrdersManage->value), 403);
    }

    public function updating(): void
    {
        $this->resetPage();
    }

    public function toggle(int $id): void
    {
        $this->expandedId = $this->expandedId === $id ? null : $id;
    }

    public function render()
    {
        $logs = ApiLog::query()
            ->when($this->provider !== 'all', fn ($q) => $q->where('provider', $this->provider))
            ->when($this->direction !== 'all', fn ($q) => $q->where('direction', $this->direction))
            ->when($this->outcome === 'success', fn ($q) => $q->where('successful', true))
            ->when($this->outcome === 'failed', fn ($q) => $q->where('successful', false))
            ->with('loggable')
            ->latest('created_at')
            ->paginate(30);

        return view('livewire.shop.api-log-list', [
            'logs' => $logs,
            'totalFailed' => ApiLog::where('successful', false)->count(),
        ]);
    }
}
