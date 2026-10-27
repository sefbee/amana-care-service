<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;


use App\Admin\Models\Role;
use App\Admin\Models\User;
use App\Admin\Models\RoleUser;
use App\Admin\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Yajra\DataTables\DataTables;

class UserController extends Controller
{
    public function __construct(protected FileUploadService $fileUploadService) {}

    public function index()
    {
        if (request()->wantsJson()) {
            $users = User::query()
                ->with('role')
                ->when(request()->role_id, function ($q) {
                    $q->where('primary_role_id', request()->role_id);
                })
                ->where('primary_role_id','!=', 1)
                ->staff();

            return DataTables::of($users)
                ->addIndexColumn()
                ->addColumn('image', function ($row) {
                    return '<img src="' . $row->photo_url . '" alt="customer Image" class="img-thumbnail rounded-circle" style="max-height: 70px; max-width: 70px; object-fit: cover;">';
                })
                ->editColumn('name', function ($row) {
                    return '<a href="' . route('users.show', $row->id) . '" class="text-primary text-hover-gray-700 fs-6 fw-bold">' . e($row->name) . '</a>
                        <br><small>User ID: ' . e($row->userid) . ' </small>';
                })
                ->editColumn('role', function ($row) {
                    return $row->role->title ?? '';
                })
                ->editColumn('status', function ($row) {
                    return $row->toggleButton(route('users.update-status', $row->id));
                })
                ->addColumn('action', function ($row) {
                    $btn = '';

                    $btn .= '<form action="' . e(route('users.password-reset', ['user' => $row->id])) . '" method="POST" class="d-inline me-2" data-confirm="Reset this staff member\'s password to the configured temporary password?">'
                        . '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">'
                        . '<button type="submit" title="Reset Password" aria-label="Reset password" class="btn btn-light-success btn-icon btn-sm"><i class="fas fa-key"></i></button>'
                        . '</form>';

                    $btn .= $row->editButton(route('users.edit', ['user' => $row->id]));
                    $btn .= $row->deleteButton(route('users.destroy', ['user' => $row->id]));

                    return $btn;
                })
                ->editColumn('last_login', function ($row) {
                    return $row->last_login ? $row->last_login->format('d M Y h:i a') : '-';
                })
                ->rawColumns(['status', 'action', 'name', 'image'])
                ->make(true);
        }

        $roles = Role::active()
            ->whereNotIn('slug', ['admin', 'platform-superuser'])
            ->get();

        return view('admin.identity.users.index', compact('roles'));
    }

    public function create()
    {
        $roles = Role::active()
            ->whereNotIn('slug', ['admin', 'platform-superuser'])
            ->get();
        return view('admin.identity.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'designation' => ['required', 'string', 'max:200'],
            'userid' => ['required', 'string','max:40','unique:users,userid',],
            'phone' => ['required','regex:/^01[0-9]{9}$/','min:11','max:11',],
            'primary_role_id' => ['required', 'integer', Rule::exists('roles', 'id')->where(fn ($query) => $query->whereNotIn('slug', ['platform-superuser']))],
            'dob' => ['required', 'date', 'before:today'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'gender' => ['required'],
        ], [
            'primary_role_id.required' => 'The `User type` field is required.',
        ]);

        try {
            $validated['password'] = Hash::make($this->defaultUserPassword());
            DB::transaction(function () use ($validated) {

                $user = User::create($validated);

                DB::table('role_user')->insert([
                    'role_id' => $validated['primary_role_id'],
                    'user_id' => $user->id,
                    'is_primary' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

            flash()->success('Successfully created');

            return redirect()->route('users.index');
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }

        return back()->withInput($request->all());
    }

    public function show(User $user)
    {
        $this->assertCanManageUser($user);

        return view('admin.identity.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $this->assertCanManageUser($user);

        $roles = Role::active()
            ->where('slug', 'user')
            ->get();
        return view('admin.identity.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $this->assertCanManageUser($user);

        $validated = $request->validate([
            'name' => 'required|max:150',
            'designation' => 'required|max:200',
            'phone' => ['nullable', 'regex:/^01[0-9]{9}$/'],
            'email' => ['required', 'email', 'max:255'],
            'dob' => ['required', 'date', 'before:today'],

        ]);

        try {

            if ($request->hasFile('photo')) {
                $validated['photo'] = $this->fileUploadService->upload('photo', 'user');
            }

            $validated['updated_by'] = auth()->id();

            $user->update($validated);

            flash()->success('Successfully updated');

            return redirect()->route('users.index');
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }

        return back()->withInput($request->all());
    }

    public function destroy(User $user)
    {
        $this->assertCanManageUser($user);

        try {
            $user->userid = $user->userid . '_deleted_' . time();
            $user->save();

            $user->delete();
            activity('authorization')->causedBy(request()->user())->performedOn($user)
                ->withProperties(['target_user_id' => $user->id])
                ->log('Staff account deleted');
            flash()->success('User deleted successfully');
        } catch (\Exception $e) {
            flash()->error('User delete failed.');
        }

        return back();
    }

    public function updateStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $this->assertCanManageUser($user);

        try {
            $user->is_active = $request->boolean('toggle_input');
            $user->save();
            activity('authorization')->causedBy($request->user())->performedOn($user)
                ->withProperties(['target_user_id' => $user->id, 'is_active' => (bool) $user->is_active])
                ->log($user->is_active ? 'Staff account activated' : 'Staff account deactivated');

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function passwordReset(User $user)
    {
        $this->assertCanManageUser($user);

        try {
            DB::transaction(function () use ($user): void {
                $user->password = Hash::make($this->defaultUserPassword());
                $user->save();

                // A reset password must no longer authorize previously issued API tokens.
                $user->tokens()->delete();

                activity('authorization')->causedBy(request()->user())->performedOn($user)
                    ->withProperties(['target_user_id' => $user->id])
                    ->log('Staff password reset');
            });
            flash()->success('Password reset successfully');
        } catch (\Exception $e) {
            report($e);
            flash()->error('Password could not be reset.');
        }

        return back();
    }

    public function bulkUpload(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'user_bulk_excel' => 'required|file|mimes:xlsx,xls'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $defaultPassword = $this->defaultUserPassword();
            $rows = IOFactory::load($request->file('user_bulk_excel')->getPathname())
                ->getActiveSheet()
                ->toArray();

            // Expected columns: name, userid, gender, designation, phone, email, address
            if (count($rows[0]) < 7) {
                return back()->withError('Invalid format. Required columns: name, userid, gender, designation, phone, email, address');
            }

            $success = 0;
            $importErrors = [];

            foreach (array_slice($rows, 1) as $index => $row) {
                $line = $index + 2; 

                $name = trim($row[0] ?? '');
                $userid = trim($row[1] ?? '');
                $gender = strtolower(trim($row[2] ?? ''));
                $designation = trim($row[3] ?? '');
                $phone = trim($row[4] ?? '');
                $email = trim($row[5] ?? '');
                $address = trim($row[6] ?? '');


                if (empty($name) || empty($userid) || empty($designation) || empty($phone)) {
                    $importErrors[] = "Row $line: Missing required fields.";
                    continue;
                }

                if (User::where('userid', $userid)->exists()) {
                    $importErrors[] = "Row $line: User ID '$userid' already exists.";
                    continue;
                }

                try {
                    DB::beginTransaction();

                    $user = new User();

                    $user->name = $name;
                    $user->userid = $userid;
                    $user->gender = $gender === 'male' ? 1 : ($gender === 'female' ? 2 : null);
                    $user->designation = $designation;
                    $user->phone = $phone;
                    $user->email = $email;
                    $user->address = $address;
                    $user->password = Hash::make($defaultPassword);
                    $user->is_active = 1;
                    $user->primary_role_id = Role::where('slug', 'user')->value('id');
                    $user->can_access_admin_panel = 0;
                    $user->is_password_changed = 0;
                    $user->created_at = now();
                    $user->updated_at = now();

                    $user->save();

                    $roleUser = new RoleUser();

                    $roleUser->role_id = Role::where('slug', 'user')->value('id');
                    $roleUser->user_id = $user->id;
                    $roleUser->is_primary = 1;
                    $roleUser->is_active = 1;
                    $roleUser->created_at = now();
                    $roleUser->updated_at = now();
                    $roleUser->save();

                    DB::commit();

                    $success++;
                } catch (\Throwable $e) {
                    DB::rollBack();
                    $importErrors[] = "Row $line: " . $e->getMessage();
                }
            }

            if ($success === 0) {
                return back()->withError("Import failed. All rows have errors.")
                            ->with('import_errors', $importErrors);
            }

            $message = "$success user(s) imported successfully.";
            if ($importErrors) $message .= " " . count($importErrors) . " row(s) skipped.";

            return back()->withSuccess($message)
                        ->with('import_errors', $importErrors);

        } catch (\Throwable $e) {
            return back()->withError("Import error: " . $e->getMessage());
        }
    }

    public function role_wise_users(string $role_slug, ?string $supervisor_user_code = null)
    {
        $role = Role::query()->where('slug', $role_slug)->where('is_active', true)->firstOrFail();

        $users = User::query()
            ->where('is_active', true)
            ->staff()
            ->where(function ($query) use ($role): void {
                $query->where('primary_role_id', $role->id)
                    ->orWhereHas('roles', fn ($roles) => $roles->where('roles.id', $role->id)->where('role_user.is_active', true));
            })
            ->when($supervisor_user_code, fn ($query) => $query->where('supervisor_user_code', $supervisor_user_code))
            ->orderBy('name')
            ->get(['id', 'name', 'user_code']);

        return response()->json($users);
    }

    private function defaultUserPassword(): string
    {
        $password = config('starter.default_user_password');

        if (blank($password)) {
            throw new \RuntimeException('Set USER_DEFAULT_PASSWORD in the environment before creating or resetting users.');
        }

        return $password;
    }

    private function assertCanManageUser(User $user): void
    {
        abort_unless(auth()->user()->isPlatformSuperuser() || ! $user->isPlatformSuperuser(), 403);
    }

    // This method sync user primary role in the `role_user` table
    public function sync_user_roles()
    {
        if (env('APP_ENV') != 'local') {
            return 'This route is for data migration purpose only. this will work only in local environment';
        }

        // read all users by chunk, check if user primary role exists on `role_user` table and insert if not exists
        User::query()
            ->with('roles')
            ->whereNotNull('primary_role_id')
            ->chunk(100, function ($users) {
                foreach ($users as $user) {
                    // $user_roles = $user->roles->pluck('id')->toArray();

                    // $user_primary_role = $user->roles->where('is_primary', 1)->first();

                    $role_exists = $user->roles->where('id', $user->primary_role_id)->first();

                    if (! $role_exists) {
                        DB::table('role_user')->insert([
                            'role_id' => $user->primary_role_id,
                            'user_id' => $user->id,
                            'is_primary' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });

        return 'User role sync completed';
    }
}
