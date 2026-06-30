@php($level = (int) ($data['level'] ?? 2))
@php($level = max(1, min(6, $level)))
<h{{ $level }}>{{ $data['text'] ?? '' }}</h{{ $level }}>
