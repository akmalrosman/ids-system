<?php

namespace App\Enums;

/**
 * Processing state of a flight's FDR file.
 *
 * none -> pending (uploaded, job queued) -> processing -> ready | failed
 */
enum FdrStatus: string
{
    case None = 'none';
    case Pending = 'pending';
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::None => 'No FDR',
            self::Pending => 'Queued',
            self::Processing => 'Processing',
            self::Ready => 'Ready',
            self::Failed => 'Failed',
        };
    }

    /** Badge variant used by <x-ui.badge>. */
    public function variant(): string
    {
        return match ($this) {
            self::Ready => 'success',
            self::Failed => 'danger',
            self::Pending, self::Processing => 'warning',
            self::None => 'muted',
        };
    }

    public function isBusy(): bool
    {
        return in_array($this, [self::Pending, self::Processing], true);
    }
}
