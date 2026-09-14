<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Journal extends Model
{
    protected $fillable = ['name','sinta_level','issn','eissn','subject_area','website_url','source','source_url','synced_at'];
    protected $casts = ['sinta_level' => 'integer', 'synced_at' => 'datetime'];
    public function articles(): HasMany { return $this->hasMany(Article::class); }
}
