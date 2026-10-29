<?php

use Illuminate\Database\Eloquent\Model;

test('eloquent fails on lazy loading and unknown attributes outside production', function () {
    expect(Model::preventsLazyLoading())->toBeTrue()
        ->and(Model::preventsSilentlyDiscardingAttributes())->toBeTrue()
        ->and(Model::preventsAccessingMissingAttributes())->toBeTrue();
});
