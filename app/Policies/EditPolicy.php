<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EditPolicy
{
    use HandlesAuthorization;

    // Default
    // public function update(User $user, $model)
    // {
    //     return $user->id === $model->user_id || (bool) $user->edit_all;
    // }
    
    // ADD
    public function viewAny(User $user)
    {
        // Admin Gudang & Bea Cukai boleh lihat
        return $user->hasAnyRole(['Super Admin', 'Admin Gudang', 'Bea Cukai']);
    }

    public function view(User $user, $model)
    {
        return $user->hasAnyRole(['Super Admin', 'Admin Gudang', 'Bea Cukai']);
    }

    public function update(User $user, $model)
    {
        return $user->id === $model->user_id || (bool) $user->edit_all;
    }

}
