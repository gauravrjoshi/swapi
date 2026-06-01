<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToUnid
{
    /**
     * Boot the BelongsToUnid trait.
     */
    public static function bootBelongsToUnid(): void
    {
        // Apply global query scope to isolate data by UNID
        static::addGlobalScope('unid', function (Builder $builder) {
            $model = $builder->getModel();

            // If the model is a User, and no user is loaded in memory yet,
            // bypass to prevent infinite recursion during authentication.
            if ($model instanceof User && !Auth::hasUser()) {
                return;
            }

            if (Auth::check()) {
                $user = Auth::user();
                $table = $model->getTable();

                if ($model instanceof User) {
                    // For User model, filter by 'unid' directly on users table
                    $builder->where($table . '.unid', $user->unid);
                } else {
                    // For child models, filter by matching owner's unid in a subquery
                    $builder->whereIn($table . '.user_id', function ($query) use ($user) {
                        $query->select('id')
                            ->from('users')
                            ->where('unid', $user->unid);
                    });
                }
            }
        });
    }
}
