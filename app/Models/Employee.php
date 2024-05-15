<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;
    protected $table = 'employees';
    protected $fillable = [
        'first_name',
        'last_name',
        'second_last_name',        
        'other_names',
        'country',
        'identification_type',
        'identification_number',
        'email',
        'entry_date',
        'area' 
    ];
}
