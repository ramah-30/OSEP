<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\AuthorizesEventAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMealOptionRequest;
use App\Http\Resources\MealOptionResource;
use App\Models\Event;
use App\Models\MealOption;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MealOptionController extends Controller
{
    use ApiResponse, AuthorizesEventAccess;

    public function index(Request $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        return $this->success([
            'meal_options' => MealOptionResource::collection($event->mealOptions()->get()),
        ]);
    }

    public function store(StoreMealOptionRequest $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $option = $event->mealOptions()->create($request->validated());

        return $this->created(['meal_option' => new MealOptionResource($option)], 'Meal option added.');
    }

    public function update(StoreMealOptionRequest $request, Event $event, MealOption $mealOption): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);
        $this->ensureBelongsToEvent($event, $mealOption);

        $mealOption->fill($request->validated())->save();

        return $this->success(['meal_option' => new MealOptionResource($mealOption)], 'Meal option updated.');
    }

    public function destroy(Request $request, Event $event, MealOption $mealOption): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);
        $this->ensureBelongsToEvent($event, $mealOption);

        $mealOption->delete();

        return $this->success(null, 'Meal option removed.');
    }
}
