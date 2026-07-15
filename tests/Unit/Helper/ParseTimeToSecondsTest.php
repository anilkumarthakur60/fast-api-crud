<?php

describe('parseTimeToSeconds', function () {
    it('parses H:i:s into positive total seconds', function () {
        expect(parseTimeToSeconds('01:30:00'))->toBe(5400);
        expect(parseTimeToSeconds('00:00:45'))->toBe(45);
        expect(parseTimeToSeconds('02:00:00'))->toBe(7200);
    });

    it('parses MM:SS into total seconds', function () {
        expect(parseTimeToSeconds('30:45'))->toBe(1845);
    });

    it('parses a plain seconds string', function () {
        expect(parseTimeToSeconds('90'))->toBe(90);
    });

    it('never returns a negative value (regression: Carbon 3 signed diff)', function () {
        expect(parseTimeToSeconds('01:30:00'))->toBeGreaterThan(0);
    });
});
