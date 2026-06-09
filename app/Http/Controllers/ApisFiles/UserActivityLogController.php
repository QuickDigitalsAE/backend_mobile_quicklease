<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Catalog;
use App\Models\CatalogTranslation;
use App\Models\UserActivityLog;
use App\Models\PropertyTranslation;
use App\Models\ProductTranslation;
use App\Models\ProductCoverageTranslation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UserActivityLogController extends Controller
{
    /**
     * Constructor to apply permission-based middlewar
     *     
     */
    function __construct()
    {
        $this->middleware('permission:Activities View', ['only' => ['userAuthHistory', 'adminSideAllLogs']]);
    }

    // Get all user auth activities logs
    public function userAuthHistory($per_page = 6)
    {
        try {
            $activityLogsQuery = UserActivityLog::whereIn('action', ['login', 'logout'])
                                                    ->orderBy('created_at', 'DESC');

            $perPage = request()->input('per_page', $per_page);
            
            // Check if full log list is requested (per_page = 0)
            if ($perPage == 0) {
                // Retrieve all blogs excluding the recent one
                $userActivityLogs = $activityLogsQuery->get();
                
                // No pagination meta for full blog list
                $pagination = [
                    'current_page' => 0,
                    'last_page' => 1,
                    'per_page' => $userActivityLogs->count(), // All items in one "page"
                    'total' => $userActivityLogs->count(),
                ];
            } else {
                // Paginate the remaining logs
                $userActivityLogs = $activityLogsQuery->paginate($perPage);
                
                // Add pagination meta for paginated list
                $pagination = [
                    'current_page' => $userActivityLogs->currentPage(),
                    'last_page' => $userActivityLogs->lastPage(),
                    'per_page' => $userActivityLogs->perPage(),
                    'total' => $userActivityLogs->total(),
                ];
            }
            
            if($userActivityLogs->isEmpty()){
                return response()->json(['status' => 'false', 'message' => 'User Auth logs not found'], 200);
            }
            
            $activityLog_Data = $userActivityLogs->map(function ($activityLog) {
                $activity_id = $activityLog->id;
                $user_id = $activityLog->user_id;
                $user_name = $activityLog->user_name;
                $action = $activityLog->action;
                $changes = $activityLog->changes;
                $created_at = $activityLog->created_at->toDateTimeString();
                
                $userQuery = User::where('id', '=', $user_id)->first();
                $user_email = "";
                if($userQuery){
                    $user_name =  $userQuery->name;
                    $user_email =  $userQuery->email;
                }
                return [
                    'id' => $activity_id,
                    'user_name' =>  $user_name,
                    'user_email' => $user_email,
                    'action' => $action,
                    'changes' => $changes,
                    'created_at' => $created_at,
                ];
            });

            
            return response()->json([
                'status' => 'true',
                'data' => $activityLog_Data,
                'pagination' => $pagination
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return $ex;
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    // Get all user activity logs on the admin side
    public function adminSideAllLogs($per_page = 6)
    {
        try {
            $activityLogsQuery = UserActivityLog::whereNotIn('action', ['login', 'logout'])
                                                    ->orderBy('id', 'DESC');

            $perPage = request()->input('per_page', $per_page);
            
            // Check if full log list is requested (per_page = 0)
            if ($perPage == 0) {
                // Retrieve all blogs excluding the recent one
                $userActivityLogs = $activityLogsQuery->get();
                
                // No pagination meta for full blog list
                $pagination = [
                    'current_page' => 0,
                    'last_page' => 1,
                    'per_page' => $userActivityLogs->count(), // All items in one "page"
                    'total' => $userActivityLogs->count(),
                ];
            } else {
                // Paginate the remaining logs
                $userActivityLogs = $activityLogsQuery->paginate($perPage);
                
                // Add pagination meta for paginated list
                $pagination = [
                    'current_page' => $userActivityLogs->currentPage(),
                    'last_page' => $userActivityLogs->lastPage(),
                    'per_page' => $userActivityLogs->perPage(),
                    'total' => $userActivityLogs->total(),
                ];
            }
            
            if($userActivityLogs->isEmpty()){
                return response()->json(['status' => 'false', 'message' => 'User activity Logs not found'], 200);
            }
            
            $userIds = $userActivityLogs->pluck('user_id')->unique();
            $users = User::whereIn('id', $userIds)->get()->keyBy('id');
            
            $activityLog_Data = $userActivityLogs->map(function ($activityLog) use ($users) {
                $activity_id = $activityLog->id;
                $user_id = $activityLog->user_id;
                $user_name = $activityLog->user_name;
                $model_type = $activityLog->model_type;
                $table_name = $activityLog->table_name;
                $action = $activityLog->action;
                $changes = $activityLog->changes;
                $created_at = $activityLog->created_at->toDateTimeString();
                
                $user = $users[$user_id] ?? null;
                $user_name = $user->name ?? '';
                $user_email = $user->email ?? '';
                
                
                $row_identity = null;
                $record_id = $changes['id'];
                
                // Define which fields are IDs referencing another model
                $relatedFields = [
                    'catalog_id' => ['model' => Catalog::class, 'column' => 'name'],
                ];
            
                // Define array-based reference fields
                $arrayFields = [
                    'additional_catalog_ids' => ['model' => Catalog::class, 'column' => 'name'],
                ];
                
                if (!Str::contains($table_name, '_translations')) {
                    $modelData = $model_type::find($record_id);
                    $row_identity = $modelData->slug ?? "";
                    
                    if(empty($row_identity) && $table_name == 'users'){
                        $name = is_array($changes['name'] ?? null)
                            ? ($changes['name']['new'] ?? $changes['name']['old'] ?? '')
                            : ($changes['name'] ?? '');

                        $email = is_array($changes['email'] ?? null)
                            ? ($changes['email']['new'] ?? $changes['email']['old'] ?? '')
                            : ($changes['email'] ?? '');

                        $row_identity = trim($name . ' (' . $email . ')');

                    }else if(empty($row_identity) && $table_name == 'bookings'){
                        $row_identity =  "#".$changes['order_number'].' ('.$changes['name'].")";
                    }else if(empty($row_identity) && $table_name == 'properties'){
                       $translationRecord = PropertyTranslation::where('property_id', $record_id)
                                ->where('language','en')
                                ->first();
                        
                        // Decode the JSON translation data
                        $translatedData = json_decode($translationRecord->field_values, true);
                        $row_identity = $translatedData['property_title'];
                    }else if(empty($row_identity) && $table_name == 'product_coverages'){
                       $translationRecord = ProductCoverageTranslation::where('coverage_id', $record_id)
                                ->where('language','en')
                                ->first();
                        
                        // Decode the JSON translation data
                        $translatedData = json_decode($translationRecord->field_values, true);
                        $row_identity = $translatedData['title'];
                    }
                    
                    
                    if($table_name == 'products' && isset($changes['catalog_id']) && is_array($changes['catalog_id'])){
                        $catalogOldId = (int) $changes['catalog_id']['old'];
                        $catalogNewId = (int) $changes['catalog_id']['new'];
                
                        $catalogOldTitle = $this->getCatalogTitles($catalogOldId);
                        $catalogNewTitle = $this->getCatalogTitles($catalogNewId);
                        
                        // Replace ID with title
                        $changes['catalog_id'] = [
                            'old' => $catalogOldTitle,
                            'new' => $catalogNewTitle,
                        ];
                    }
                    
                    if ($table_name == 'products' && isset($changes['additional_catalog_ids']) && is_array($changes['additional_catalog_ids'])) {
                        $oldTitles = $this->getCatalogTitlesFromJsonIds($changes['additional_catalog_ids']['old']);
                        $newTitles = $this->getCatalogTitlesFromJsonIds($changes['additional_catalog_ids']['new']);
                    
                        $changes['additional_catalog_ids'] = [
                            'old' => $oldTitles,
                            'new' => $newTitles,
                        ];
                    }
                    
                    if ($table_name == 'catalogs' && isset($changes['car_ids']) && is_array($changes['car_ids'])) {
                        $oldTitles = $this->getCarsTitlesFromJsonIds($changes['car_ids']['old']);
                        $newTitles = $this->getCarsTitlesFromJsonIds($changes['car_ids']['new']);
                    
                        $changes['car_ids'] = [
                            'old' => $oldTitles,
                            'new' => $newTitles,
                        ];
                    }
                    
                }else if (str_contains($table_name, '_translations')) {

                    // Convert table name to model name
                    $base_name = str_replace('_translations', '', $table_name);
                    $mainModel = Str::replaceLast('Translation', '', $model_type);
                    
                    // Check if classes exist
                    if (class_exists($model_type) && class_exists($mainModel)) {
                        
                        // Get the translation record with relationship
                        $translationRecord = $model_type::with($base_name)->find($record_id);
                        
                        // Get slug from the related main model
                        $row_identity = optional(optional($translationRecord)->{$base_name})->slug ?? "";
                        
                        if(empty($row_identity) && $table_name == 'property_translations'){
                            
                            // Decode the JSON translation data
                            $translatedData = json_decode($translationRecord->field_values, true);
                            $row_identity = $translatedData['property_title'];
                        }else if(empty($row_identity) && $table_name == 'product_coverage_translations'){
                            
                            // Decode the JSON translation data
                            $translatedData = json_decode($translationRecord->field_values, true);
                            $row_identity = $translatedData['title'];
                        }
                        
                    }
                }
                
                return [
                    'id' => $activity_id,
                    'user_name' =>  $user_name,
                    'user_email' => $user_email,
                    'model_type' => $model_type,
                    'table_name' => $table_name,
                    'action' => $action,
                    'row_identity' => $row_identity,
                    'changes' => $changes,
                    'created_at' => $created_at,
                ];
            });
 
            return response()->json([
                'status' => 'true',
                'data' => $activityLog_Data,
                'pagination' => $pagination
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return $ex;
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function getCatalogTitles($catalog_id) {
        if (empty($catalog_id) || $catalog_id == null) {
            return null;
        }
    
        // Get all relevant translations in a single query
        $translations = CatalogTranslation::where('catalog_id', $catalog_id)
                                            ->where('language', 'en')
                                            ->first();
    
        $translationsData = $translations ? json_decode($translations->field_values, true) : [];
        $title = $translationsData['catalog_title'] ?? null;
    
        return $title;
    }
    
    public function getCatalogTitlesFromJsonIds($jsonIds) {
        if (empty($jsonIds)) {
            return [];
        }
    
        // Decode JSON: "[\"50\",\"229\"]" => ['50', '229']
        $ids = json_decode($jsonIds, true);
    
        if (!is_array($ids)) {
            return [];
        }
    
        // Get all relevant translations in a single query
        $translations = CatalogTranslation::whereIn('catalog_id', $ids)
            ->where('language', 'en')
            ->get();
    
        // Extract titles from field_values
        $titles = [];
        foreach ($translations as $translation) {
            $fieldValues = json_decode($translation->field_values, true);
            $titles[] = $fieldValues['catalog_title'] ?? 'N/A';
        }
    
        return $titles;
    }
    
    public function getCarsTitlesFromJsonIds($jsonIds) {
        if (empty($jsonIds)) {
            return [];
        }
    
        // Decode JSON: "[\"50\",\"229\"]" => ['50', '229']
        $ids = json_decode($jsonIds, true);
    
        if (!is_array($ids)) {
            return [];
        }
    
        // Get all relevant translations in a single query
        $translations = ProductTranslation::whereIn('product_id', $ids)
            ->where('language', 'en')
            ->get();
    
        // Extract titles from field_values
        $titles = [];
        foreach ($translations as $translation) {
            $fieldValues = json_decode($translation->field_values, true);
            $titles[] = $fieldValues['product_title'] ?? 'N/A';
        }
    
        return $titles;
    }
}
