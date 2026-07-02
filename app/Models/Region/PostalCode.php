<?php

namespace App\Models\Region;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostalCode extends Model
{
    protected $table = 'id_postal_codes';

    public $timestamps = false;

    protected $fillable = ['urban', 'sub_district', 'city', 'province_code', 'postal_code', 'district_code'];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_code', 'code');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_code', 'code');
    }
}
