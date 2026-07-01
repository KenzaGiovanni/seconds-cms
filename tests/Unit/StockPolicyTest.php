<?php

use App\Enums\StockPolicy;

it('knows which policies track stock', function () {
    expect(StockPolicy::None->tracksStock())->toBeFalse()
        ->and(StockPolicy::Deny->tracksStock())->toBeTrue()
        ->and(StockPolicy::Backorder->tracksStock())->toBeTrue();
});
