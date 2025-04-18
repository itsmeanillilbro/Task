<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateSkill extends Model
{
   public function candidate()
   {
       return $this->belongsTo(Candidate::class);
   }
}
