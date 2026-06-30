@php($level = max(1, min(6, (int) ($data['level'] ?? 2))))
<h{{ $level }}>{{ $data['text'] ?? '' }}</h{{ $level }}>
