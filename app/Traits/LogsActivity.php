<?php

namespace App\Traits;

use App\Models\UserActivityLog;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    protected static function resolveActivityLanguage(): string
    {
        return request()->route('lang')
            ?? request()->input('language')
            ?? 'en';
    }

    public static function bootLogsActivity()
    {
        static::updating(function ($model) {
            $changed = $model->getDirty();
            $original = $model->getOriginal();
            $table_name = $model->getTable();
            
            $changes = [];
            foreach ($original as $key => $value) {
                if (array_key_exists($key, $changed)) {
                    $changes['id'] = $original['id'];
                    
                    if($table_name == 'users'){
                        $changes['name'] = $original['name'];
                        $changes['email'] = $original['email'];
                    }
                    
                    $changes[$key] = [
                        'old' => $value,
                        'new' => $changed[$key],
                    ];
                }
            }

            if (!empty($changes)) {
                UserActivityLog::create([
                    'user_id' => Auth::id(),
                    'user_name' => Auth::user()->name ?? 'Client',
                    'model_type' => get_class($model),
                    'table_name'=> $table_name,
                    'action' => 'updated',
                    'changes' => $changes,
                    'language' => static::resolveActivityLanguage(),
                ]);
            }
        });

        static::created(function ($model) {
            UserActivityLog::create([
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name ?? 'Client',
                'model_type' => get_class($model),
                'table_name'=> $model->getTable(),
                'action' => 'created',
                'changes' => $model->getAttributes(),
                'language' => static::resolveActivityLanguage(),
            ]);
        });

        static::deleted(function ($model) {
            UserActivityLog::create([
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name ?? 'System',
                'model_type' => get_class($model),
                'table_name'=> $model->getTable(),
                'action' => 'deleted',
                'changes' => $model->getOriginal(),
                'language' => static::resolveActivityLanguage(),
            ]);
        });
    }
}
