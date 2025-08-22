<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Issue extends Model
{
    use HasFactory; // <- Add this trait

    // Mass assignable fields
    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
    ];
}
