<?php

namespace App\Models\Region;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A kecamatan (district / sub-district) - the level KiriminAja prices delivery by. */
class District extends Model
{
    protected $table = 'id_districts';

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['code', 'regency_code', 'name', 'kiriminaja_subdistrict_id'];

    protected $casts = [
        'kiriminaja_subdistrict_id' => 'integer',
    ];

    public function regency(): BelongsTo
    {
        return $this->belongsTo(Regency::class, 'regency_code', 'code');
    }

    public function villages(): HasMany
    {
        return $this->hasMany(Village::class, 'district_code', 'code');
    }
}
