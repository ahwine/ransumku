<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $guarded = [];

    public function farm(): BelongsTo { return $this->belongsTo(Farm::class); }
    public function session(): BelongsTo { return $this->belongsTo(ChatSession::class, 'chat_session_id'); }
}
