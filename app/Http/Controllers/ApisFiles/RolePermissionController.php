<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use Spatie\Permission\Exceptions\RoleAlreadyExists;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class RolePermissionController extends Controller
{

    /**
     * Constructor to apply permission-based middlewar
     *     
     */
    function __construct()
    {
        $this->middleware('permission:Role View', ['only' => ['getAllRoles','editRoleWithPermissions','getAllPermissions','addPermissions']]);
        $this->middleware('permission:Role Add', ['only' => ['createRoleWithPermissions']]);
        $this->middleware('permission:Role Edit', ['only' => ['updateRoleWithPermissions']]);
        $this->middleware('permission:Role Delete', ['only' => ['deleteRole','deletePermissionGroup']]);
    }
    
    public function getAllRoles()
    {
         // Retrieve all roles
         $roles = Role::where('id', '!=', 1)
                    ->get(['id', 'name']);
                    
        // Check if roles exist
        if ($roles->isEmpty()) {
             return response()->json([
                 'status' => false,
                 'message' => 'No roles found!'
             ], 404); // Return a 404 if no roles are found
         }
 
         // Return the roles with a custom structure
         return response()->json([
             'status' => true,
             'message' => 'Roles fetched successfully!',
             'data' => $roles
         ], 200);
    }
    
    public function getAllPermissions(Request $request)
    {
        // Retrieve all permissions grouped by their 'group' column
        $permissions = Permission::all()->groupBy('group');

        // Check if permissions exist
        if ($permissions->isEmpty()) {
            return response()->json([
                "status" => false,
                'message' => 'No permissions found.'
            ], 404); // Return 404 Not Found if no permissions exist
        }

        return response()->json([
            "status" => true,
            'message' => 'Permissions fetched successfully!',
            'data' => $permissions
        ], 200);
    }

    public function createRoleWithPermissions(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'role_name' => 'required|string|max:255',
            'permissions' => 'array', // should be an array of permission names
            'permissions.*' => 'exists:permissions,name', // ensure permissions exist
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 200);
        }

        try {
            // Create a new role
            $role = Role::create(['name' => $request['role_name'], 'guard_name' => 'sanctum']);

            // Check if `permissionAll` is set to 1 (assign all permissions)
            if ($request->has('permissionAll') && $request->permissionAll == 1) {
                // Get all permission names
                $allPermissions = Permission::pluck('name')->toArray();

                // Assign all permissions to the role
                $role->syncPermissions($allPermissions);
            } else {
                // Assign only the selected permissions
                if (!empty($request->permissions)) {
                    $role->syncPermissions($request->permissions);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Role created successfully',
                'data' => $role
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong!'
            ], 200);
        }
    }

    public function editRoleWithPermissions($roleId)
    {
        // Find the role by ID
        $role = Role::find($roleId);

        // Check if the role exists
        if (!$role) {
            return response()->json([
                "status" => false,
                'message' => 'Role not found!'
            ], 200);
        }

        // Get the role's permissions grouped by `group`
        $permissions = $role->permissions
            ->groupBy('group')
            ->where('admin','!=', 'Super Admin')
            ->map(function ($group) {
                return $group->pluck('name'); // Return only permission names
            });

        // Return the role and its permissions grouped by `group`
        return response()->json([
            "status" => true,
            "message" => 'Role fetched successfully!',
            "data" => [
                'role' => $role,
                'permissions' => $permissions
            ]
        ], 200);
    }

    public function updateRoleWithPermissions(Request $request, $roleId)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'role_name' => 'required|string',
            'permissions' => 'array', // should be an array of permission groups
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 200);
        }

        // Find the role by ID
        $role = Role::find($roleId);

        if (!$role) {
            return response()->json([
                "status" => false,
                'message' => 'Role not found!'
            ], 200);
        }

        // Check if the new role name already exists
        $existsRole = Role::where('name', $request->role_name)
            ->where('guard_name', 'sanctum')
            ->where('id', '!=', $roleId) // Exclude the current role from the check
            ->exists();

        if ($existsRole) {
            return response()->json([
                'status' => false,
                'message' => 'Role already exists'
            ], 200);
        }

        // Update the role's name
        $role->name = $request->role_name;
        $role->save();

        // Sync permissions (update the permissions for the role)
        if ($request->has('permissionAll') && $request->permissionAll == 1) {
            // Get all permission names
            $allPermissions = Permission::pluck('name')->toArray();

            // Assign all permissions to the role
            $role->syncPermissions($allPermissions);
        } else {
            // Assign only the selected permissions
            if (!empty($request->permissions)) {
                $role->syncPermissions($request->permissions);
            }
        }

        return response()->json([
            "status" => true,
            'message' => 'Role updated successfully',
            'data' => $role
        ], 200);
    }

    public function deleteRole($roleId)
    {
        // Find the role by ID
        $role = Role::find($roleId);

        // Check if the role exists
        if ($role) {
            if ($role->id == 1) {
                return response()->json([
                    "status" => false,
                    'message' => 'The Super Admin role cannot be deleted.'
                ], 403); // HTTP status 403 for forbidden action
            }
        }else{
            return response()->json([
                "status" => false,
                'message' => 'Role not found!'
            ], 200); // Return 404 Not Found if the role does not exist
        }

        if ($role->users()->exists()) { // This checks if any users are associated with the role
            return response()->json([
                "status" => false,
                'message' => 'Role cannot be deleted because it is assigned to one or more users.'
            ], 200); // Return 409 Conflict if the role is assigned
        }

        // Delete the role (this will also detach the associated permissions)
        $role->delete();

        return response()->json([
            "status" => true,
            'message' => 'Role deleted successfully!'
        ], 200);
    }
    
    public function addPermissions(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'permissions' => 'required|array',
            'permissions' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 200);
        }
        $permissionGroup = $request->permissions;
        $groupName = $permissionGroup['group'];
        foreach ($permissionGroup['items'] as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'sanctum',
                'group' => $groupName,
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Permissions added successfully',
        ]);
    }
    
    public function deletePermissionGroup(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'group' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 200);
        }

        $groupName = $request->input('group');

        // Find permissions by group name and delete them
        $permissions = Permission::where('group', $groupName)->get();

        if ($permissions->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Permission group not found.',
            ], 404);
        }

        // Delete permissions
        foreach ($permissions as $permission) {
            $permission->delete();
        }

        return response()->json([
            'status' => true,
            'message' => 'Permission group and its permissions deleted successfully.',
        ]);
    }
}
