<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Models\BudgetCategory;
use App\Models\EventCategory;
use App\Models\GuestCategory;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Configurable option catalogues for events, guests and budgets. Global defaults
 * (null owner) plus the current planner's own custom entries.
 */
class CategoryController extends Controller
{
    use ApiResponse;

    /** @var array<string, class-string<Model>> */
    private const TYPES = [
        'event' => EventCategory::class,
        'guest' => GuestCategory::class,
        'budget' => BudgetCategory::class,
    ];

    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        return $this->success([
            'event' => $this->names(EventCategory::class, $userId),
            'guest' => $this->names(GuestCategory::class, $userId),
            'budget' => $this->names(BudgetCategory::class, $userId),
        ]);
    }

    public function store(StoreCategoryRequest $request, string $type): JsonResponse
    {
        $model = self::TYPES[$type] ?? null;
        abort_if($model === null, 404);

        $name = $request->validated()['name'];

        /** @var Model $model */
        $model::firstOrCreate([
            'created_by' => $request->user()->id,
            'name' => $name,
        ]);

        return $this->created([
            'type' => $type,
            'categories' => $this->names($model, $request->user()->id),
        ], 'Category added.');
    }

    /**
     * Global defaults plus this planner's own, de-duplicated and sorted.
     *
     * @param  class-string<Model>  $model
     * @return array<int, string>
     */
    private function names(string $model, int $userId): array
    {
        return $model::query()
            ->where(fn ($q) => $q->whereNull('created_by')->orWhere('created_by', $userId))
            ->pluck('name')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
