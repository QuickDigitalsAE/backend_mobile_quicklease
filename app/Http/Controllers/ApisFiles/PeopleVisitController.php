<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use App\Models\PeopleVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class PeopleVisitController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:PeopleVisits View', ['only' => ['index', 'show']]);
        $this->middleware('permission:PeopleVisits Add', ['only' => ['store']]);
        $this->middleware('permission:PeopleVisits Edit', ['only' => ['update']]);
        $this->middleware('permission:PeopleVisits Delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        try {
            $query = PeopleVisit::query()
                ->select(
                    'slug',
                    DB::raw('COUNT(*) as people_visited')
                );

            if ($request->filled('search')) {
                $search = strtolower(trim($request->search));

                $query->where(function ($builder) use ($search) {

                    // Search in original slug
                    $builder->orWhere('slug', 'like', "%{$search}%");

                    // Search in generated title
                    $builder->orWhereRaw("
                        LOWER(
                            REPLACE(
                                SUBSTRING_INDEX(slug, '/', -1),
                                '-',
                                ' '
                            )
                        ) LIKE ?
                    ", ["%{$search}%"]);
                });
            }

            // Single selected date
            if ($request->filled('selected_date')) {
                $query->whereDate('visit_datetime', $request->selected_date);
            }

            // Date range
            if ($request->filled('from_date') && $request->filled('to_date')) {
                $query->whereDate('visit_datetime', '>=', $request->from_date)
                    ->whereDate('visit_datetime', '<=', $request->to_date);
            } elseif ($request->filled('from_date')) {
                $query->whereDate('visit_datetime', '>=', $request->from_date);
            } elseif ($request->filled('to_date')) {
                $query->whereDate('visit_datetime', '<=', $request->to_date);
            }

            $query->groupBy('slug')
                ->orderBy('people_visited', 'DESC');

            $perPage = (int) $request->input('per_page', 10);

            if ($perPage === 0) {
                $PeopleVisits = $query->get();

                $pagination = [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $PeopleVisits->count(),
                    'total' => $PeopleVisits->count(),
                ];
            } else {
                $PeopleVisits = $query->paginate($perPage);

                $pagination = [
                    'current_page' => $PeopleVisits->currentPage(),
                    'last_page' => $PeopleVisits->lastPage(),
                    'per_page' => $PeopleVisits->perPage(),
                    'total' => $PeopleVisits->total(),
                ];
            }

            if ($PeopleVisits->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'People visites not found',
                    'data' => [],
                ], 200);
            }

            return response()->json([
                'status' => true,
                'message' => 'People visites fetched successfully',
                'data' => $PeopleVisits->map(function ($item) {

                    $slug = $item->slug;

                    // If slug contains '/', take the last part
                    if (str_contains($slug, '/')) {
                        $title = last(explode('/', $slug));
                    } else {
                        $title = $slug;
                    }

                    // Convert slug to Title Case
                    $title = ucwords(str_replace('-', ' ', $title));

                    return [
                        'slug' => $slug,
                        'title' => $title,
                        'people_visited' => (int) $item->people_visited,
                    ];
                }),
                'pagination' => $pagination,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        return $this->persistPeopleVisit($request, 'People visite created successfully');
    }

    public function trackVisit(Request $request)
    {
        return $this->persistTrackedPeopleVisit($request);
    }

    private function persistPeopleVisit(Request $request, string $successMessage)
    {
        try {
            $validator = Validator::make($request->all(), [
                'slug' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => implode("\n", $validator->errors()->all()),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            DB::beginTransaction();

            $PeopleVisit = PeopleVisit::create([
                'slug' => $request->slug,
                'visit_datetime' => now(),
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => $successMessage,
                'data' => $this->formatPeopleVisit($PeopleVisit),
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function persistTrackedPeopleVisit(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'slug' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => implode("\n", $validator->errors()->all()),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            DB::beginTransaction();
            
            $slug = $request->slug;

            $peopleVisit = PeopleVisit::create([
                'slug' => $slug,
                'visit_datetime' => now(),
            ]);

            DB::commit();

            $PeopleVisitdCount = PeopleVisit::where('slug', $slug)
                ->where('visit_datetime', '>=', now()->subMinutes(30)) // Count visits in the last 30 minutes
                ->count();
            
            return response()->json([
                'status' => true,
                'message' => 'People visit tracked successfully',
                'data' => [
                    'people_visited' => $PeopleVisitdCount
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        try {
            $PeopleVisit = PeopleVisit::find($id);

            if (!$PeopleVisit) {
                return response()->json([
                    'status' => false,
                    'message' => 'People visite not found',
                    'data' => null,
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'status' => true,
                'message' => 'People visite fetched successfully',
                'data' => $this->formatPeopleVisit($PeopleVisit),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'slug' => 'required|string|max:255',
                'visit_datetime' => 'required|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => implode("\n", $validator->errors()->all()),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $PeopleVisit = PeopleVisit::find($id);

            if (!$PeopleVisit) {
                return response()->json([
                    'status' => false,
                    'message' => 'People visite not found',
                    'data' => null,
                ], Response::HTTP_NOT_FOUND);
            }

            DB::beginTransaction();

            if ($request->has('slug')) {
                $PeopleVisit->slug = $request->slug;
            }
            $PeopleVisit->visit_datetime = now();
            $PeopleVisit->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'People visite updated successfully',
                'data' => $this->formatPeopleVisit($PeopleVisit),
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            $PeopleVisit = PeopleVisit::find($id);

            if (!$PeopleVisit) {
                return response()->json([
                    'status' => false,
                    'message' => 'People visite not found',
                    'data' => null,
                ], Response::HTTP_NOT_FOUND);
            }

            $PeopleVisit->delete();

            return response()->json([
                'status' => true,
                'message' => 'People visite deleted successfully',
                'data' => null,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function formatPeopleVisit(PeopleVisit $PeopleVisit): array
    {
        return [
            'id' => $PeopleVisit->id,
            'slug' => $PeopleVisit->slug,
            'visit_datetime' => optional($PeopleVisit->visit_datetime)->format('Y-m-d H:i:s'),
            'created_at' => $PeopleVisit->created_at,
            'updated_at' => $PeopleVisit->updated_at,
        ];
    }
}
