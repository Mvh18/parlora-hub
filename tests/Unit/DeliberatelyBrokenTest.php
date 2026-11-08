<?php

// Temporary: proves that a failing test makes ci-ok fail. Reverted in the next commit.
test('deliberately broken', function () {
    expect(1)->toBe(2);
});
