<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Article extends Model
{
    protected $fillable = ['journal_id','authors','title','year','doi','url','abstract','keywords','source','external_id','fetched_at'];
    protected $casts = ['year' => 'integer', 'fetched_at' => 'datetime'];
    public function journal(): BelongsTo { return $this->belongsTo(Journal::class); }
}
