<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['hash', 'phrase', 'context', 'meaning', 'note'])]
class SubtitleLookup extends Model
{
    /** Cache key for one phrase in one sentence (case and spacing insensitive). */
    public static function hashFor(string $phrase, string $context): string
    {
        $norm = fn (string $s) => mb_strtolower(trim(preg_replace('/\s+/u', ' ', $s)));

        return sha1($norm($phrase)."\n".$norm($context));
    }
}
