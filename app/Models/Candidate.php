<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    //

    public function skills()
    {
        return $this->hasMany(CandidateSkill::class, 'candidate_id');
          
    }
}

