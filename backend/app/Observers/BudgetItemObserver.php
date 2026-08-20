<?php

namespace App\Observers;

use App\Models\AiEventHealthScore;
use App\Models\BudgetItem;

/**
 * When budget items change, invalidate the cached AI health score so the next
 * AI query sees fresh budget data rather than stale cached metrics.
 */
class BudgetItemObserver
{
    public function created(BudgetItem $item): void
    {
        $this->invalidateHealthScore($item);
    }

    public function updated(BudgetItem $item): void
    {
        $this->invalidateHealthScore($item);
    }

    public function deleted(BudgetItem $item): void
    {
        $this->invalidateHealthScore($item);
    }

    private function invalidateHealthScore(BudgetItem $item): void
    {
        if ($item->event_id) {
            AiEventHealthScore::where('event_id', $item->event_id)->delete();
        }
    }
}
