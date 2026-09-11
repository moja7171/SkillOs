<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['skill_id', 'prerequisite_skill_id'])]
class SkillDependency extends Model
{
    //
}
