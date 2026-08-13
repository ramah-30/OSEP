<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaxRuleResource;
use App\Models\Event;
use App\Models\TaxRule;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConfigController extends Controller
{
    use ApiResponse;

    /** The default budget / expense categories from the Phase 6 spec. */
    public const CATEGORIES = [
        'Venue', 'Catering', 'Decoration', 'Photography', 'Videography',
        'Entertainment', 'Transportation', 'Accommodation', 'Printing',
        'Marketing', 'Equipment Rental', 'Security', 'Staffing', 'Insurance',
        'Miscellaneous',
    ];

    /** Everything the finance forms need to populate their selects. */
    public function index(Request $request): JsonResponse
    {
        $planner = $request->user();

        $events = Event::where('planner_id', $planner->id)
            ->orderByDesc('event_date')
            ->get(['id', 'title', 'event_code', 'client_id']);

        $clientIds = $events->pluck('client_id')->filter()->unique();
        $clients = User::whereIn('id', $clientIds)->get()
            ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->full_name, 'email' => $u->email]);

        return $this->success([
            'events' => $events->map(fn (Event $e) => [
                'id' => $e->id,
                'title' => $e->title,
                'event_code' => $e->event_code,
                'client_id' => $e->client_id,
            ]),
            'clients' => $clients->values(),
            'categories' => self::CATEGORIES,
            'payment_methods' => collect(PaymentMethod::cases())
                ->map(fn (PaymentMethod $m) => ['value' => $m->value, 'label' => $m->label()]),
            'tax_rules' => TaxRuleResource::collection(
                TaxRule::where('planner_id', $planner->id)->where('is_active', true)->get()
            ),
        ]);
    }

    public function storeTaxRule(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'kind' => ['required', Rule::in(['tax', 'discount'])],
            'type' => ['required', Rule::in(['percentage', 'fixed'])],
            'rate' => ['required', 'numeric', 'min:0'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $rule = TaxRule::create([...$data, 'planner_id' => $request->user()->id]);

        return $this->created(['tax_rule' => new TaxRuleResource($rule)], 'Tax rule saved.');
    }

    public function destroyTaxRule(Request $request, TaxRule $taxRule): JsonResponse
    {
        abort_unless($taxRule->planner_id === $request->user()->id, 404);
        $taxRule->delete();

        return $this->success(null, 'Tax rule removed.');
    }
}
