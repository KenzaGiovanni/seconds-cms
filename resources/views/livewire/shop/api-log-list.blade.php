<div>
    <div class="mb-6 flex items-center justify-between gap-4">
        <h1 class="font-display text-2xl font-semibold tracking-tight text-ink">API Logs</h1>
        @if ($totalFailed > 0)
            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-800">
                {{ $totalFailed }} failed
            </span>
        @endif
    </div>

    <div class="mb-4 flex flex-wrap gap-3">
        <select wire:model.live="provider" class="rounded-[var(--radius-btn)] border border-line bg-bg px-3 py-2 text-sm text-ink">
            <option value="all">All providers</option>
            <option value="xendit">Xendit</option>
            <option value="kiriminaja">KiriminAja</option>
        </select>
        <select wire:model.live="direction" class="rounded-[var(--radius-btn)] border border-line bg-bg px-3 py-2 text-sm text-ink">
            <option value="all">All directions</option>
            <option value="outbound">Outbound (we called them)</option>
            <option value="inbound">Inbound (webhook)</option>
        </select>
        <select wire:model.live="outcome" class="rounded-[var(--radius-btn)] border border-line bg-bg px-3 py-2 text-sm text-ink">
            <option value="all">All outcomes</option>
            <option value="success">Successful</option>
            <option value="failed">Failed</option>
        </select>
    </div>

    <div class="overflow-hidden rounded-[var(--radius-btn)] border border-line bg-bg">
        <table class="w-full text-sm">
            <thead class="border-b border-line bg-soft">
                <tr>
                    <th class="px-4 py-3 text-left font-display font-medium text-ink">When</th>
                    <th class="px-4 py-3 text-left font-display font-medium text-ink">Provider</th>
                    <th class="px-4 py-3 text-left font-display font-medium text-ink">Direction</th>
                    <th class="px-4 py-3 text-left font-display font-medium text-ink">Endpoint</th>
                    <th class="px-4 py-3 text-left font-display font-medium text-ink">Status</th>
                    <th class="px-4 py-3 text-left font-display font-medium text-ink">Duration</th>
                    <th class="px-4 py-3 text-left font-display font-medium text-ink">Linked to</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($logs as $log)
                    <tr wire:key="log-{{ $log->id }}" wire:click="toggle({{ $log->id }})" class="cursor-pointer hover:bg-soft">
                        <td class="px-4 py-3 text-ink">{{ $log->created_at?->format('d M Y, H:i:s') }}</td>
                        <td class="px-4 py-3 text-ink">{{ ucfirst($log->provider) }}</td>
                        <td class="px-4 py-3 text-muted">{{ $log->direction === 'inbound' ? 'Inbound' : 'Outbound' }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-ink">{{ $log->method }} {{ \Illuminate\Support\Str::limit($log->endpoint, 50) }}</td>
                        <td class="px-4 py-3">
                            @if ($log->successful)
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">
                                    {{ $log->status_code ?? 'OK' }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">
                                    {{ $log->status_code ?? 'Failed' }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $log->duration_ms !== null ? $log->duration_ms.' ms' : '-' }}</td>
                        <td class="px-4 py-3 text-muted">
                            @if ($log->loggable)
                                {{ class_basename($log->loggable_type) }} #{{ $log->loggable_id }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @if ($expandedId === $log->id)
                        <tr wire:key="log-detail-{{ $log->id }}">
                            <td colspan="7" class="bg-soft px-4 py-4">
                                @if ($log->error_message)
                                    <p class="mb-3 text-sm text-red-700"><strong>Error:</strong> {{ $log->error_message }}</p>
                                @endif
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <h3 class="mb-1 font-display text-xs font-semibold uppercase text-muted">Request</h3>
                                        <pre class="max-h-80 overflow-auto rounded-[var(--radius-btn)] bg-ink p-3 text-xs text-white">{{ json_encode($log->request_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </div>
                                    <div>
                                        <h3 class="mb-1 font-display text-xs font-semibold uppercase text-muted">Response</h3>
                                        <pre class="max-h-80 overflow-auto rounded-[var(--radius-btn)] bg-ink p-3 text-xs text-white">{{ json_encode($log->response_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-muted">No API calls logged yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $logs->links() }}
    </div>
</div>
