<?php

describe('formatDuration', function () {
    it('formats durations with default separator and placeholders', function () {
        expect(formatDuration(10000, '%y %mo %d %h %m %s'))->toBe('2h 46m 40s');
        expect(formatDuration(1000, '%y %mo %d %h %m %s'))->toBe('16m 40s');
        expect(formatDuration(100, '%y %mo %d %h %m %s'))->toBe('1m 40s');
        expect(formatDuration(10, '%y %mo %d %h %m %s'))->toBe('10s');
    });

    it('formats durations with dash separator', function () {
        expect(formatDuration(10000, '%y %mo %d %h %m %s', '-'))->toBe('2h-46m-40s');
        expect(formatDuration(1000, '%y %mo %d %h %m %s', '-'))->toBe('16m-40s');
        expect(formatDuration(100, '%y %mo %d %h %m %s', '-'))->toBe('1m-40s');
        expect(formatDuration(10, '%y %mo %d %h %m %s', '-'))->toBe('10s');
    });

    it('formats durations with comma separator', function () {
        expect(formatDuration(10000, '%y %mo %d %h %m %s', ','))->toBe('2h,46m,40s');
        expect(formatDuration(1000, '%y %mo %d %h %m %s', ','))->toBe('16m,40s');
        expect(formatDuration(100, '%y %mo %d %h %m %s', ','))->toBe('1m,40s');
        expect(formatDuration(10, '%y %mo %d %h %m %s', ','))->toBe('10s');
    });

    it('formats durations with colon separator', function () {
        expect(formatDuration(10000, '%y %mo %d %h %m %s', ':'))->toBe('2h:46m:40s');
        expect(formatDuration(1000, '%y %mo %d %h %m %s', ':'))->toBe('16m:40s');
        expect(formatDuration(100, '%y %mo %d %h %m %s', ':'))->toBe('1m:40s');
        expect(formatDuration(10, '%y %mo %d %h %m %s', ':'))->toBe('10s');
    });

    it('formats durations with dot separator', function () {
        expect(formatDuration(10000, '%y %mo %d %h %m %s', '.'))->toBe('2h.46m.40s');
        expect(formatDuration(1000, '%y %mo %d %h %m %s', '.'))->toBe('16m.40s');
        expect(formatDuration(100, '%y %mo %d %h %m %s', '.'))->toBe('1m.40s');
        expect(formatDuration(10, '%y %mo %d %h %m %s', '.'))->toBe('10s');
    });

    it('formats long durations with all units', function () {
        // 1yr + 1mo + 2d + 3h + 4m + 5s
        $duration =
            (365 * 86400)   // 1 year
            + (30 * 86400) // 1 month
            + (2 * 86400) // 2 days
            + (3 * 3600)  // 3 hours
            + (4 * 60)    // 4 minutes
            + 5;            // 5 seconds

        expect(formatDuration($duration, '%y %mo %d %h %m %s'))
            ->toBe('1yr 1mo 2d 3h 4m 5s');
    });

    it('returns "0s" for zero duration', function () {
        expect(formatDuration(0))->toBe('0s');
        expect(formatDuration(0, '%y %mo %d %h %m %s'))->toBe('0s');
    });

    it('ignores unknown %‐tokens and preserves literals', function () {
        expect(formatDuration(10000, '%x %h %m %s'))->toBe('2h 46m 40s');
        expect(formatDuration(100, 'Duration: %h %m %s'))
            ->toBe('Duration: 1m 40s');
    });

    it('formats partial units correctly (boundary cases)', function () {
        expect(formatDuration(59))->toBe('59s');
        expect(formatDuration(3600))->toBe('1h');
        expect(formatDuration(86400))->toBe('1d');
        expect(formatDuration(2592000))->toBe('1mo');
        expect(formatDuration(31536000))->toBe('1yr');
    });

    it('handles float durations by flooring', function () {
        expect(formatDuration(59.999, '%h %m %s'))->toBe('59s');
        expect(formatDuration(3661.5, '%h %m %s'))->toBe('1h 1m 1s');
    });

    it('handles large durations correctly', function () {
        $duration = 100 * 365 * 86400; // exactly 100 years
        expect(formatDuration($duration))->toContain('100yr');
    });

    it('handles negative durations as absolute', function () {
        expect(formatDuration(-3600))->toBe('1h');
    });

    it('handles missing format and separator with defaults', function () {
        expect(formatDuration(90))->toBe('1m 30s');
        expect(formatDuration(3601))->toBe('1h 1s');
    });

    it('returns only valid parts from custom format', function () {
        expect(formatDuration(60, '%h %m %s'))->toBe('1m');
        expect(formatDuration(0, '%y %mo %d'))->toBe('0s');
    });
});
