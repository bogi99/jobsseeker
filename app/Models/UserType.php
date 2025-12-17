<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserType extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'usertypes';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get the users for the user type.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'usertype_id');
    }
}
