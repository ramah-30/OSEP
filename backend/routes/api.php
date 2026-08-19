<?php

use App\Http\Controllers\Api\V1\ActivityController;
use App\Http\Controllers\Api\V1\ApprovalController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BudgetItemController;
use App\Http\Controllers\Api\V1\CalendarController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CheckinController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\ClientGuestController;
use App\Http\Controllers\Api\V1\CommunicationLogController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\ConversationController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\EmailVerificationController;
use App\Http\Controllers\Api\V1\EventApprovalController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\GuestCategoryController;
use App\Http\Controllers\Api\V1\GuestController;
use App\Http\Controllers\Api\V1\GuestDashboardController;
use App\Http\Controllers\Api\V1\InvitationController;
use App\Http\Controllers\Api\V1\InvitationTemplateController;
use App\Http\Controllers\Api\V1\MealOptionController;
use App\Http\Controllers\Api\V1\MilestoneController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PasswordResetController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ReminderController;
use App\Http\Controllers\Api\V1\RsvpController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SettingsController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\VendorAssignmentController;
use App\Http\Controllers\Api\V1\VendorDirectoryController;
use App\Http\Controllers\Api\V1\VenueController;
use App\Http\Controllers\Api\V1\VenueLayoutController;
// Phase 7 — AI Planning Engine
use App\Http\Controllers\Api\V1\Ai\ChatController as AiChatController;
use App\Http\Controllers\Api\V1\Ai\DashboardController as AiDashboardController;
use App\Http\Controllers\Api\V1\Ai\RecommendationController as AiRecommendationController;
use App\Http\Controllers\Api\V1\Ai\AnalyticsController as AiAnalyticsController;
use App\Http\Controllers\Api\V1\Ai\MemoryController as AiMemoryController;
use App\Http\Controllers\Api\V1\Ai\TemplateController as AiTemplateController;
use App\Http\Controllers\Api\V1\Ai\DocumentController as AiDocumentController;
use App\Http\Controllers\Api\V1\Ai\FeedbackController as AiFeedbackController;
use App\Http\Controllers\Api\V1\Ai\KnowledgeController as AiKnowledgeController;
use App\Http\Controllers\Api\V1\Ai\AutomationController as AiAutomationController;
use App\Http\Controllers\Api\V1\Ai\ActionController as AiActionController;
use App\Http\Controllers\Api\V1\Ai\SettingsController as AiSettingsController;
use App\Http\Controllers\Api\V1\Ai\PromptController as AiPromptController;
use App\Http\Controllers\Api\V1\Ai\MeetingController as AiMeetingController;
// Client-planner booking
use App\Http\Controllers\Api\V1\BookingRequestController;
use App\Http\Controllers\Api\V1\ClientBookingController;
use App\Http\Controllers\Api\V1\ClientInvoiceController;
use App\Http\Controllers\Api\V1\ClientPlannerReviewController;
use App\Http\Controllers\Api\V1\PlannerReviewController;
use App\Http\Controllers\Api\V1\PublicPlannerController;
// Phase 5 — Vendor & Venue Marketplace
use App\Http\Controllers\Api\V1\Marketplace\AccommodationBookingController as MpAccommodationBookingController;
use App\Http\Controllers\Api\V1\Marketplace\AccommodationController as MpAccommodationController;
use App\Http\Controllers\Api\V1\Marketplace\DiscoveryController as MpDiscoveryController;
use App\Http\Controllers\Api\V1\Marketplace\VendorController as MpVendorController;
use App\Http\Controllers\Api\V1\Marketplace\VenueController as MpVenueController;
use App\Http\Controllers\Api\V1\Marketplace\CategoryController as MpCategoryController;
use App\Http\Controllers\Api\V1\Marketplace\SavedController as MpSavedController;
use App\Http\Controllers\Api\V1\Marketplace\BookingRequestController as MpBookingRequestController;
use App\Http\Controllers\Api\V1\Marketplace\QuotationController as MpQuotationController;
use App\Http\Controllers\Api\V1\Marketplace\ContractController as MpContractController;
use App\Http\Controllers\Api\V1\Marketplace\ReviewController as MpReviewController;
use App\Http\Controllers\Api\V1\Marketplace\MessageController as MpMessageController;
use App\Http\Controllers\Api\V1\Vendor\StorefrontController as VendorStorefrontController;
use App\Http\Controllers\Api\V1\Vendor\ServiceController as VendorServiceController;
use App\Http\Controllers\Api\V1\Vendor\PackageController as VendorPackageController;
use App\Http\Controllers\Api\V1\Vendor\PortfolioController as VendorPortfolioController;
use App\Http\Controllers\Api\V1\Vendor\AvailabilityController as VendorAvailabilityController;
use App\Http\Controllers\Api\V1\Vendor\VenueListingController as VendorVenueListingController;
use App\Http\Controllers\Api\V1\Vendor\RequestController as VendorRequestController;
use App\Http\Controllers\Api\V1\Vendor\QuotationController as VendorQuotationController;
use App\Http\Controllers\Api\V1\Vendor\ContractController as VendorContractController;
use App\Http\Controllers\Api\V1\Vendor\ReviewController as VendorReviewController;
use App\Http\Controllers\Api\V1\Vendor\DashboardController as VendorDashboardController;
use App\Http\Controllers\Api\V1\Vendor\Ai\ChatController as VendorAiChatController;
use App\Http\Controllers\Api\V1\Vendor\Ai\DashboardController as VendorAiDashboardController;
use App\Http\Controllers\Api\V1\Vendor\Ai\ActionController as VendorAiActionController;
use App\Http\Controllers\Api\V1\Client\Ai\ChatController as ClientAiChatController;
use App\Http\Controllers\Api\V1\Client\Ai\DashboardController as ClientAiDashboardController;
use App\Http\Controllers\Api\V1\Client\Ai\ActionController as ClientAiActionController;
use App\Http\Controllers\Api\V1\Vendor\AnalyticsController as VendorAnalyticsController;
use App\Http\Controllers\Api\V1\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\VendorController as AdminVendorController;
use App\Http\Controllers\Api\V1\Admin\VenueController as AdminVenueController;
use App\Http\Controllers\Api\V1\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\ReviewController as AdminReviewController;
// Phase 6 — Financial Management
use App\Http\Controllers\Api\V1\Finance\DashboardController as FinanceDashboardController;
use App\Http\Controllers\Api\V1\Finance\BudgetController as FinanceBudgetController;
use App\Http\Controllers\Api\V1\Finance\ExpenseController as FinanceExpenseController;
use App\Http\Controllers\Api\V1\Finance\QuotationController as FinanceQuotationController;
use App\Http\Controllers\Api\V1\Finance\InvoiceController as FinanceInvoiceController;
use App\Http\Controllers\Api\V1\Finance\PaymentController as FinancePaymentController;
use App\Http\Controllers\Api\V1\Finance\ScheduleController as FinanceScheduleController;
use App\Http\Controllers\Api\V1\Finance\RefundController as FinanceRefundController;
use App\Http\Controllers\Api\V1\Finance\ReceiptController as FinanceReceiptController;
use App\Http\Controllers\Api\V1\Finance\ReportController as FinanceReportController;
use App\Http\Controllers\Api\V1\Finance\ConfigController as FinanceConfigController;
use App\Http\Controllers\Api\V1\Finance\AuditController as FinanceAuditController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes — mounted under /api/v1 (see bootstrap/app.php)
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])
        ->middleware('throttle:register');

    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:login');

    Route::post('forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:password-reset');

    Route::post('reset-password', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:password-reset');

    Route::get('verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('signed')
        ->name('verification.verify');

    Route::post('resend-verification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:verification-resend');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

Route::post('contact', [ContactController::class, 'store'])
    ->middleware('throttle:contact');

// Public RSVP — the URL token is the credential, so these sit outside auth.
Route::get('rsvp/{token}', [RsvpController::class, 'show'])->middleware('throttle:60,1');
Route::post('rsvp/{token}', [RsvpController::class, 'respond'])->middleware('throttle:20,1');

// Public planner booking page — no auth required.
Route::get('planners/{slug}', [PublicPlannerController::class, 'show'])->middleware('throttle:60,1');

// Public vendor category list — needed by the registration form, before the
// vendor has an account to authenticate with.
Route::get('vendor-categories', [MpCategoryController::class, 'index'])->middleware('throttle:60,1');

/*
|--------------------------------------------------------------------------
| Phase 2 — the authenticated workspace
|--------------------------------------------------------------------------
| Every route below requires a Sanctum token. Role-specific areas are gated
| further with the `role:` middleware; the SPA mirrors the same rules but the
| server is the authority.
*/
Route::middleware('auth:sanctum')->group(function () {
    // Dashboard
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);

    // Profile
    Route::get('profile', [ProfileController::class, 'show']);
    Route::put('profile', [ProfileController::class, 'update']);
    Route::post('profile/image', [ProfileController::class, 'uploadImage']);

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::put('notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy']);

    // Direct messaging — shared by every role (planner-as-hub rules enforced in the controller)
    Route::get('conversations', [ConversationController::class, 'index']);
    Route::get('conversations/contacts', [ConversationController::class, 'contacts']);
    Route::post('conversations', [ConversationController::class, 'start']);
    Route::get('conversations/{conversation}', [ConversationController::class, 'show']);
    Route::post('conversations/{conversation}', [ConversationController::class, 'send']);

    // Settings
    Route::put('settings/account', [SettingsController::class, 'updateAccount']);
    Route::put('settings/email', [SettingsController::class, 'updateEmail']);
    Route::put('settings/password', [SettingsController::class, 'updatePassword']);
    Route::put('settings/preferences', [SettingsController::class, 'updatePreferences']);

    // Client workspace
    Route::middleware('role:client')->group(function () {
        Route::get('my-event', [EventController::class, 'myEvent']);
        Route::get('my-events', [EventController::class, 'myEvents']);

        // The client's own guest list for their event (planner is notified of
        // every change).
        Route::get('my-events/{event}/guests', [ClientGuestController::class, 'index']);
        Route::post('my-events/{event}/guests', [ClientGuestController::class, 'store']);
        Route::put('my-events/{event}/guests/{guest}', [ClientGuestController::class, 'update']);
        Route::delete('my-events/{event}/guests/{guest}', [ClientGuestController::class, 'destroy']);

        // Approvals stay available on the API even though the client nav no
        // longer surfaces them, so a planner's approval flow keeps working.
        Route::get('approvals', [ApprovalController::class, 'index']);
        Route::post('approvals/{approval}/respond', [ApprovalController::class, 'respond']);

        // Invoices sent by the planner, plus the simulated mobile-money pay action.
        Route::get('invoices', [ClientInvoiceController::class, 'index']);
        Route::get('invoices/{invoice}', [ClientInvoiceController::class, 'show']);
        Route::post('invoices/{invoice}/pay', [ClientInvoiceController::class, 'pay']);

        // Browse planners to book
        Route::get('planners', [PublicPlannerController::class, 'index']);

        // Booking requests (client sends, views, withdraws)
        Route::get('booking-requests', [ClientBookingController::class, 'index']);
        Route::post('booking-requests', [ClientBookingController::class, 'store']);
        Route::post('booking-requests/{bookingRequest}/withdraw', [ClientBookingController::class, 'withdraw']);

        // Reviews the client leaves about their planner
        Route::get('planner-reviews', [ClientPlannerReviewController::class, 'index']);
        Route::post('planner-reviews', [ClientPlannerReviewController::class, 'store']);

        // Client AI concierge (grounded in their own event; distinct from the
        // planner and vendor copilots). Prefixed to avoid colliding with the
        // planner's `ai/*` routes.
        Route::prefix('client/ai')->group(function () {
            Route::get('dashboard', [ClientAiDashboardController::class, 'index']);
            Route::get('meta', [ClientAiChatController::class, 'meta']);

            // Mode (offline engine ↔ live model) — shares the global driver switch
            Route::get('settings', [AiSettingsController::class, 'show']);
            Route::put('settings', [AiSettingsController::class, 'update']);
            Route::post('chat', [ClientAiChatController::class, 'chat']);
            Route::get('conversations', [ClientAiChatController::class, 'index']);
            Route::get('conversations/{conversation}', [ClientAiChatController::class, 'show']);
            Route::delete('conversations/{conversation}', [ClientAiChatController::class, 'destroy']);

            // Approval-gated actions (concierge proposes, client approves → it runs)
            Route::get('actions', [ClientAiActionController::class, 'index']);
            Route::post('actions/{action}/approve', [ClientAiActionController::class, 'approve']);
            Route::post('actions/{action}/reject', [ClientAiActionController::class, 'reject']);
        });
    });

    /*
    |----------------------------------------------------------------------
    | Phase 3 — the planner's event engine
    |----------------------------------------------------------------------
    | Everything below is planner-only. Nested records are additionally
    | scoped to the event in the URL inside each controller.
    */
    Route::middleware('role:event_planner')->group(function () {
        // Cross-event tools
        Route::get('calendar', [CalendarController::class, 'index']);
        Route::get('search', [SearchController::class, 'index']);

        // Reviews clients have left about this planner (badge + rating + list)
        Route::get('planner/reviews', [PlannerReviewController::class, 'index']);
        Route::get('clients', [ClientController::class, 'index']);
        Route::post('clients', [ClientController::class, 'store']);
        Route::get('clients/lookup', [ClientController::class, 'lookup']);
        Route::put('clients/{client}', [ClientController::class, 'update']);
        Route::delete('clients/{client}', [ClientController::class, 'destroy']);

        // Booking requests (planner receives, views, responds)
        Route::get('planner-booking-requests', [BookingRequestController::class, 'index']);
        Route::get('planner-booking-requests/{bookingRequest}', [BookingRequestController::class, 'show']);
        Route::post('planner-booking-requests/{bookingRequest}/respond', [BookingRequestController::class, 'respond']);
        Route::get('vendors', [VendorDirectoryController::class, 'index']);
        Route::get('categories', [CategoryController::class, 'index']);
        Route::post('categories/{type}', [CategoryController::class, 'store']);

        // Rich guest categories (colour / priority / seating)
        Route::get('guest-categories', [GuestCategoryController::class, 'index']);
        Route::post('guest-categories', [GuestCategoryController::class, 'store']);
        Route::put('guest-categories/{category}', [GuestCategoryController::class, 'update']);
        Route::delete('guest-categories/{category}', [GuestCategoryController::class, 'destroy']);

        /*
        |------------------------------------------------------------------
        | Phase 7 — AI Planning Engine (planner copilot)
        |------------------------------------------------------------------
        */
        Route::prefix('ai')->group(function () {
            // Dashboard widgets
            Route::get('dashboard', [AiDashboardController::class, 'index']);

            // Mode (offline engine ↔ live model)
            Route::get('settings', [AiSettingsController::class, 'show']);
            Route::put('settings', [AiSettingsController::class, 'update']);

            // Action queue (copilot proposes, planner approves → it runs)
            Route::get('actions', [AiActionController::class, 'index']);
            Route::post('actions/{action}/approve', [AiActionController::class, 'approve']);
            Route::post('actions/{action}/reject', [AiActionController::class, 'reject']);

            // Conversations & chat
            Route::get('meta', [AiChatController::class, 'meta']);
            Route::post('chat', [AiChatController::class, 'chat']);
            Route::get('conversations', [AiChatController::class, 'index']);
            Route::get('conversations/{conversation}', [AiChatController::class, 'show']);
            Route::put('conversations/{conversation}', [AiChatController::class, 'update']);
            Route::delete('conversations/{conversation}', [AiChatController::class, 'destroy']);

            // Recommendations
            Route::get('recommendations', [AiRecommendationController::class, 'index']);
            Route::put('recommendations/{recommendation}/dismiss', [AiRecommendationController::class, 'dismiss']);
            Route::post('recommendations/{recommendation}/apply', [AiRecommendationController::class, 'apply']);

            // Analytics & insights
            Route::get('analytics', [AiAnalyticsController::class, 'analytics']);
            Route::get('insights', [AiAnalyticsController::class, 'insights']);
            Route::get('scenario', [AiAnalyticsController::class, 'scenario']);
            Route::get('benchmarks', [AiAnalyticsController::class, 'benchmarks']);

            // Memory
            Route::get('memory', [AiMemoryController::class, 'index']);
            Route::post('memory', [AiMemoryController::class, 'store']);
            Route::put('memory/{memory}', [AiMemoryController::class, 'update']);
            Route::delete('memory/{memory}', [AiMemoryController::class, 'destroy']);

            // Content generation — templates & generated documents
            Route::get('templates', [AiTemplateController::class, 'index']);
            Route::get('documents', [AiDocumentController::class, 'index']);
            Route::post('documents', [AiDocumentController::class, 'store']);
            Route::get('documents/{document}', [AiDocumentController::class, 'show']);
            Route::put('documents/{document}', [AiDocumentController::class, 'update']);
            Route::delete('documents/{document}', [AiDocumentController::class, 'destroy']);

            // Feedback on AI output (messages & documents)
            Route::post('feedback', [AiFeedbackController::class, 'store']);
            Route::get('feedback/summary', [AiFeedbackController::class, 'summary']);
            Route::delete('feedback/{subjectType}/{subjectId}', [AiFeedbackController::class, 'destroy']);

            // Knowledge base (planner notes the copilot retrieves & cites)
            Route::get('knowledge', [AiKnowledgeController::class, 'index']);
            Route::post('knowledge', [AiKnowledgeController::class, 'store']);
            Route::put('knowledge/{knowledge}', [AiKnowledgeController::class, 'update']);
            Route::delete('knowledge/{knowledge}', [AiKnowledgeController::class, 'destroy']);

            // Automation rules (trigger → AI action)
            Route::get('automation', [AiAutomationController::class, 'index']);
            Route::post('automation', [AiAutomationController::class, 'store']);
            Route::post('automation/run', [AiAutomationController::class, 'run']);
            Route::put('automation/{automation}', [AiAutomationController::class, 'update']);
            Route::delete('automation/{automation}', [AiAutomationController::class, 'destroy']);

            // Prompt library (versioned, reusable, grounded prompts)
            Route::get('prompts', [AiPromptController::class, 'index']);
            Route::post('prompts', [AiPromptController::class, 'store']);
            Route::get('prompts/{prompt}', [AiPromptController::class, 'show']);
            Route::put('prompts/{prompt}', [AiPromptController::class, 'update']);
            Route::delete('prompts/{prompt}', [AiPromptController::class, 'destroy']);
            Route::post('prompts/{prompt}/run', [AiPromptController::class, 'run']);
            Route::post('prompts/{prompt}/rollback', [AiPromptController::class, 'rollback']);

            // Meeting assistant (notes → grounded summary + action items → tasks)
            Route::get('meetings', [AiMeetingController::class, 'index']);
            Route::post('meetings', [AiMeetingController::class, 'store']);
            Route::get('meetings/{meeting}', [AiMeetingController::class, 'show']);
            Route::put('meetings/{meeting}', [AiMeetingController::class, 'update']);
            Route::delete('meetings/{meeting}', [AiMeetingController::class, 'destroy']);
            Route::post('meetings/{meeting}/process', [AiMeetingController::class, 'process']);
            Route::put('meetings/{meeting}/items/{item}', [AiMeetingController::class, 'updateItem']);
            Route::post('meetings/{meeting}/items/{item}/task', [AiMeetingController::class, 'convertItem']);
        });

        // Invitation template library
        Route::get('invitation-templates', [InvitationTemplateController::class, 'index']);
        Route::post('invitation-templates', [InvitationTemplateController::class, 'store']);
        Route::post('invitation-templates/{template}/duplicate', [InvitationTemplateController::class, 'duplicate']);
        Route::put('invitation-templates/{template}', [InvitationTemplateController::class, 'update']);
        Route::delete('invitation-templates/{template}', [InvitationTemplateController::class, 'destroy']);

        // Events
        Route::get('events', [EventController::class, 'index']);
        Route::post('events', [EventController::class, 'store']);
        Route::get('events/{event}', [EventController::class, 'show']);
        Route::put('events/{event}', [EventController::class, 'update']);
        Route::delete('events/{event}', [EventController::class, 'destroy']);
        Route::put('events/{event}/status', [EventController::class, 'updateStatus']);

        // Tasks (Kanban)
        Route::get('events/{event}/tasks', [TaskController::class, 'index']);
        Route::post('events/{event}/tasks', [TaskController::class, 'store']);
        Route::put('events/{event}/tasks/reorder', [TaskController::class, 'reorder']);
        Route::put('events/{event}/tasks/{task}', [TaskController::class, 'update']);
        Route::delete('events/{event}/tasks/{task}', [TaskController::class, 'destroy']);
        Route::post('events/{event}/tasks/{task}/comments', [TaskController::class, 'addComment']);

        // Timeline milestones
        Route::get('events/{event}/milestones', [MilestoneController::class, 'index']);
        Route::post('events/{event}/milestones', [MilestoneController::class, 'store']);
        Route::put('events/{event}/milestones/{milestone}', [MilestoneController::class, 'update']);
        Route::delete('events/{event}/milestones/{milestone}', [MilestoneController::class, 'destroy']);

        // Guest management, invitations & RSVP (Phase 4)
        Route::get('events/{event}/guests/dashboard', [GuestDashboardController::class, 'index']);
        Route::get('events/{event}/guests/export', [GuestController::class, 'export']);
        Route::post('events/{event}/guests/import', [GuestController::class, 'import']);
        Route::post('events/{event}/guests/bulk', [GuestController::class, 'bulkStore']);
        Route::post('events/{event}/guests/bulk-action', [GuestController::class, 'bulkAction']);
        Route::get('events/{event}/guests', [GuestController::class, 'index']);
        Route::post('events/{event}/guests', [GuestController::class, 'store']);
        Route::get('events/{event}/guests/{guest}/ticket', [GuestController::class, 'ticket']);
        Route::get('events/{event}/guests/{guest}/history', [GuestController::class, 'history']);
        Route::post('events/{event}/guests/{guest}/notes', [GuestController::class, 'note']);
        Route::post('events/{event}/guests/{guest}/archive', [GuestController::class, 'archive']);
        Route::post('events/{event}/guests/{guest}/duplicate', [GuestController::class, 'duplicate']);
        Route::put('events/{event}/guests/{guest}', [GuestController::class, 'update']);
        Route::delete('events/{event}/guests/{guest}', [GuestController::class, 'destroy']);

        // Invitations
        Route::get('events/{event}/invitations', [InvitationController::class, 'index']);
        Route::post('events/{event}/invitations/send', [InvitationController::class, 'send']);
        Route::get('events/{event}/invitations/{invitation}', [InvitationController::class, 'show']);
        Route::post('events/{event}/invitations/{invitation}/resend', [InvitationController::class, 'resend']);

        // Reminders
        Route::get('events/{event}/reminders', [ReminderController::class, 'index']);
        Route::post('events/{event}/reminders/send', [ReminderController::class, 'send']);

        // RSVP responses (planner view)
        Route::get('events/{event}/rsvp', [RsvpController::class, 'index']);

        // Meal options (RSVP menu)
        Route::get('events/{event}/meal-options', [MealOptionController::class, 'index']);
        Route::post('events/{event}/meal-options', [MealOptionController::class, 'store']);
        Route::put('events/{event}/meal-options/{mealOption}', [MealOptionController::class, 'update']);
        Route::delete('events/{event}/meal-options/{mealOption}', [MealOptionController::class, 'destroy']);

        // Check-in
        Route::get('events/{event}/checkins', [CheckinController::class, 'index']);
        Route::get('events/{event}/checkins/statistics', [CheckinController::class, 'statistics']);
        Route::post('events/{event}/checkins', [CheckinController::class, 'store']);
        Route::delete('events/{event}/checkins/{guest}', [CheckinController::class, 'destroy']);

        // Communication log
        Route::get('events/{event}/communications', [CommunicationLogController::class, 'index']);

        // Venue
        Route::get('events/{event}/venue', [VenueController::class, 'show']);
        Route::put('events/{event}/venue', [VenueController::class, 'upsert']);

        // Venue Designer — floor-plan layouts
        Route::get('events/{event}/venue-layouts', [VenueLayoutController::class, 'index']);
        Route::post('events/{event}/venue-layouts', [VenueLayoutController::class, 'store']);
        Route::get('events/{event}/venue-layouts/{layout}', [VenueLayoutController::class, 'show']);
        Route::put('events/{event}/venue-layouts/{layout}', [VenueLayoutController::class, 'update']);
        Route::delete('events/{event}/venue-layouts/{layout}', [VenueLayoutController::class, 'destroy']);
        Route::post('events/{event}/venue-layouts/{layout}/duplicate', [VenueLayoutController::class, 'duplicate']);
        Route::put('events/{event}/venue-layouts/{layout}/objects/{object}/seating', [VenueLayoutController::class, 'updateSeating']);

        // Vendor assignments
        Route::get('events/{event}/vendor-assignments', [VendorAssignmentController::class, 'index']);
        Route::post('events/{event}/vendor-assignments', [VendorAssignmentController::class, 'store']);
        Route::put('events/{event}/vendor-assignments/{assignment}', [VendorAssignmentController::class, 'update']);
        Route::delete('events/{event}/vendor-assignments/{assignment}', [VendorAssignmentController::class, 'destroy']);

        // Budget
        Route::get('events/{event}/budget-items', [BudgetItemController::class, 'index']);
        Route::post('events/{event}/budget-items', [BudgetItemController::class, 'store']);
        Route::put('events/{event}/budget-items/{item}', [BudgetItemController::class, 'update']);
        Route::delete('events/{event}/budget-items/{item}', [BudgetItemController::class, 'destroy']);

        // Approvals (planner side)
        Route::get('events/{event}/approvals', [EventApprovalController::class, 'index']);
        Route::post('events/{event}/approvals', [EventApprovalController::class, 'store']);

        // Documents
        Route::get('events/{event}/documents', [DocumentController::class, 'index']);
        Route::post('events/{event}/documents', [DocumentController::class, 'store']);
        Route::delete('events/{event}/documents/{document}', [DocumentController::class, 'destroy']);

        // Activity feed
        Route::get('events/{event}/activity', [ActivityController::class, 'index']);

        /*
        |------------------------------------------------------------------
        | Phase 5 — Marketplace (planner side)
        |------------------------------------------------------------------
        */
        Route::prefix('marketplace')->group(function () {
            Route::get('dashboard', [MpDiscoveryController::class, 'dashboard']);

            // Saved collections & shortlists
            Route::get('collections', [MpSavedController::class, 'index']);
            Route::post('collections', [MpSavedController::class, 'store']);
            Route::put('collections/{collection}', [MpSavedController::class, 'update']);
            Route::delete('collections/{collection}', [MpSavedController::class, 'destroy']);
            Route::post('collections/{collection}/items', [MpSavedController::class, 'addItem']);
            Route::delete('collections/{collection}/items/{item}', [MpSavedController::class, 'removeItem']);

            // Booking requests
            Route::get('booking-requests', [MpBookingRequestController::class, 'index']);
            Route::post('booking-requests', [MpBookingRequestController::class, 'store']);
            Route::get('booking-requests/{bookingRequest}', [MpBookingRequestController::class, 'show']);
            Route::post('booking-requests/{bookingRequest}/withdraw', [MpBookingRequestController::class, 'withdraw']);

            // Hotel room bookings (honeymoon stays booked for a client)
            Route::get('accommodation-bookings', [MpAccommodationBookingController::class, 'index']);
            Route::post('accommodation-bookings', [MpAccommodationBookingController::class, 'store']);
            Route::get('accommodation-bookings/{booking}', [MpAccommodationBookingController::class, 'show']);
            Route::post('accommodation-bookings/{booking}/cancel', [MpAccommodationBookingController::class, 'cancel']);

            // Quotations (planner reviews vendor quotes)
            Route::get('quotations', [MpQuotationController::class, 'index']);
            Route::get('quotations/{quotation}', [MpQuotationController::class, 'show']);
            Route::post('quotations/{quotation}/respond', [MpQuotationController::class, 'respond']);

            // Contracts
            Route::get('contracts', [MpContractController::class, 'index']);
            Route::get('contracts/{contract}', [MpContractController::class, 'show']);
            Route::post('contracts/{contract}/sign', [MpContractController::class, 'sign']);
            Route::post('contracts/{contract}/pay', [MpContractController::class, 'pay']);

            // Reviews
            Route::get('reviews', [MpReviewController::class, 'index']);
            Route::get('my-reviews', [MpReviewController::class, 'mine']);
            Route::post('reviews', [MpReviewController::class, 'store']);
        });

        /*
        |------------------------------------------------------------------
        | Phase 6 — Financial Management
        |------------------------------------------------------------------
        */
        Route::prefix('finance')->group(function () {
            Route::get('dashboard', [FinanceDashboardController::class, 'index']);
            Route::get('config', [FinanceConfigController::class, 'index']);
            Route::post('tax-rules', [FinanceConfigController::class, 'storeTaxRule']);
            Route::delete('tax-rules/{taxRule}', [FinanceConfigController::class, 'destroyTaxRule']);
            Route::get('audit', [FinanceAuditController::class, 'index']);

            // Budgets — keyed by the owning event
            Route::get('budgets', [FinanceBudgetController::class, 'index']);
            Route::get('budgets/{event}', [FinanceBudgetController::class, 'show']);
            Route::put('budgets/{event}', [FinanceBudgetController::class, 'upsert']);
            Route::post('budgets/{event}/transition', [FinanceBudgetController::class, 'transition']);
            Route::post('budgets/{event}/items', [FinanceBudgetController::class, 'storeItem']);
            Route::put('budgets/{event}/items/{item}', [FinanceBudgetController::class, 'updateItem']);
            Route::delete('budgets/{event}/items/{item}', [FinanceBudgetController::class, 'destroyItem']);

            // Expenses
            Route::get('expenses', [FinanceExpenseController::class, 'index']);
            Route::post('expenses', [FinanceExpenseController::class, 'store']);
            Route::get('expenses/{expense}', [FinanceExpenseController::class, 'show']);
            Route::put('expenses/{expense}', [FinanceExpenseController::class, 'update']);
            Route::delete('expenses/{expense}', [FinanceExpenseController::class, 'destroy']);
            Route::post('expenses/{expense}/transition', [FinanceExpenseController::class, 'transition']);

            // Client quotations
            Route::get('quotations', [FinanceQuotationController::class, 'index']);
            Route::post('quotations', [FinanceQuotationController::class, 'store']);
            Route::get('quotations/{quotation}', [FinanceQuotationController::class, 'show']);
            Route::put('quotations/{quotation}', [FinanceQuotationController::class, 'update']);
            Route::delete('quotations/{quotation}', [FinanceQuotationController::class, 'destroy']);
            Route::post('quotations/{quotation}/send', [FinanceQuotationController::class, 'send']);
            Route::post('quotations/{quotation}/decide', [FinanceQuotationController::class, 'decide']);
            Route::post('quotations/{quotation}/convert', [FinanceQuotationController::class, 'convertToInvoice']);

            // Invoices
            Route::get('invoices', [FinanceInvoiceController::class, 'index']);
            Route::post('invoices', [FinanceInvoiceController::class, 'store']);
            Route::get('invoices/{invoice}', [FinanceInvoiceController::class, 'show']);
            Route::put('invoices/{invoice}', [FinanceInvoiceController::class, 'update']);
            Route::delete('invoices/{invoice}', [FinanceInvoiceController::class, 'destroy']);
            Route::post('invoices/{invoice}/send', [FinanceInvoiceController::class, 'send']);
            Route::post('invoices/{invoice}/cancel', [FinanceInvoiceController::class, 'cancel']);

            // Payments & receipts
            Route::get('payments', [FinancePaymentController::class, 'index']);
            Route::post('payments', [FinancePaymentController::class, 'store']);
            Route::delete('payments/{payment}', [FinancePaymentController::class, 'destroy']);
            Route::get('receipts', [FinanceReceiptController::class, 'index']);
            Route::get('receipts/{receipt}', [FinanceReceiptController::class, 'show']);

            // Payment schedules (installment plans)
            Route::get('schedules', [FinanceScheduleController::class, 'index']);
            Route::post('schedules', [FinanceScheduleController::class, 'store']);
            Route::put('schedules/{schedule}', [FinanceScheduleController::class, 'update']);
            Route::delete('schedules/{schedule}', [FinanceScheduleController::class, 'destroy']);

            // Refunds
            Route::get('refunds', [FinanceRefundController::class, 'index']);
            Route::post('refunds', [FinanceRefundController::class, 'store']);
            Route::post('refunds/{refund}/transition', [FinanceRefundController::class, 'transition']);

            // Reports
            Route::get('reports/{type}', [FinanceReportController::class, 'show']);
        });
    });

    /*
    |----------------------------------------------------------------------
    | Phase 5 — Marketplace (vendor storefront management)
    |----------------------------------------------------------------------
    */
    Route::middleware('role:vendor')->prefix('marketplace/vendor')->group(function () {
        Route::get('storefront', [VendorStorefrontController::class, 'show']);
        Route::put('storefront', [VendorStorefrontController::class, 'update']);
        Route::post('storefront/verify', [VendorStorefrontController::class, 'requestVerification']);

        Route::get('dashboard', [VendorDashboardController::class, 'index']);
        Route::get('analytics', [VendorAnalyticsController::class, 'index']);

        // Services / packages / portfolio
        Route::apiResource('services', VendorServiceController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::apiResource('packages', VendorPackageController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::apiResource('portfolio', VendorPortfolioController::class)->only(['index', 'store', 'update', 'destroy']);

        // Availability calendar
        Route::get('availability', [VendorAvailabilityController::class, 'index']);
        Route::post('availability', [VendorAvailabilityController::class, 'upsert']);
        Route::delete('availability/{date}', [VendorAvailabilityController::class, 'destroy']);

        // Own venue listings
        Route::get('venues', [VendorVenueListingController::class, 'index']);
        Route::post('venues', [VendorVenueListingController::class, 'store']);
        Route::get('venues/{venue}', [VendorVenueListingController::class, 'show']);
        Route::put('venues/{venue}', [VendorVenueListingController::class, 'update']);
        Route::delete('venues/{venue}', [VendorVenueListingController::class, 'destroy']);
        Route::put('venues/{venue}/images', [VendorVenueListingController::class, 'syncImages']);
        Route::post('venues/{venue}/availability', [VendorVenueListingController::class, 'upsertAvailability']);

        // Incoming booking requests
        Route::get('requests', [VendorRequestController::class, 'index']);
        Route::post('requests/{bookingRequest}/respond', [VendorRequestController::class, 'respond']);

        // Quotations (vendor authors)
        Route::get('quotations', [VendorQuotationController::class, 'index']);
        Route::post('quotations', [VendorQuotationController::class, 'store']);
        Route::get('quotations/{quotation}', [VendorQuotationController::class, 'show']);
        Route::put('quotations/{quotation}', [VendorQuotationController::class, 'update']);
        Route::post('quotations/{quotation}/send', [VendorQuotationController::class, 'send']);
        Route::delete('quotations/{quotation}', [VendorQuotationController::class, 'destroy']);

        // Contracts
        Route::get('contracts', [VendorContractController::class, 'index']);
        Route::get('contracts/{contract}', [VendorContractController::class, 'show']);
        Route::put('contracts/{contract}', [VendorContractController::class, 'update']);
        Route::post('contracts/{contract}/transition', [VendorContractController::class, 'transition']);

        // Reviews
        Route::get('reviews', [VendorReviewController::class, 'index']);
        Route::post('reviews/{review}/reply', [VendorReviewController::class, 'reply']);

        // Vendor AI copilot (business-focused; distinct from the planner copilot)
        Route::prefix('ai')->group(function () {
            Route::get('dashboard', [VendorAiDashboardController::class, 'index']);
            Route::get('meta', [VendorAiChatController::class, 'meta']);

            // Mode (offline engine ↔ live model) — shares the global driver switch
            Route::get('settings', [AiSettingsController::class, 'show']);
            Route::put('settings', [AiSettingsController::class, 'update']);
            Route::post('chat', [VendorAiChatController::class, 'chat']);
            Route::get('conversations', [VendorAiChatController::class, 'index']);
            Route::get('conversations/{conversation}', [VendorAiChatController::class, 'show']);
            Route::delete('conversations/{conversation}', [VendorAiChatController::class, 'destroy']);

            // Approval-gated actions (copilot proposes, vendor approves → it runs)
            Route::get('actions', [VendorAiActionController::class, 'index']);
            Route::post('actions/{action}/approve', [VendorAiActionController::class, 'approve']);
            Route::post('actions/{action}/reject', [VendorAiActionController::class, 'reject']);
        });
    });

    /*
    |----------------------------------------------------------------------
    | Phase 5 — Marketplace (admin moderation)
    |----------------------------------------------------------------------
    */
    Route::middleware('role:admin')->prefix('admin/marketplace')->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index']);

        Route::get('vendors', [AdminVendorController::class, 'index']);
        Route::put('vendors/{vendor}/verify', [AdminVendorController::class, 'verify']);
        Route::put('vendors/{vendor}/suspend', [AdminVendorController::class, 'suspend']);
        Route::put('vendors/{vendor}/feature', [AdminVendorController::class, 'feature']);

        Route::get('venues', [AdminVenueController::class, 'index']);
        Route::put('venues/{venue}/verify', [AdminVenueController::class, 'verify']);
        Route::put('venues/{venue}/suspend', [AdminVenueController::class, 'suspend']);
        Route::put('venues/{venue}/feature', [AdminVenueController::class, 'feature']);

        Route::apiResource('categories', AdminCategoryController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::get('reviews', [AdminReviewController::class, 'index']);
        Route::put('reviews/{review}/moderate', [AdminReviewController::class, 'moderate']);
    });

    /*
    |----------------------------------------------------------------------
    | Phase 5 — Marketplace (shared read + messaging)
    |----------------------------------------------------------------------
    | Browsing and secure messaging are open to any authenticated user; the
    | write actions above are role-gated. Ownership is enforced per-record.
    */
    Route::prefix('marketplace')->group(function () {
        Route::get('discover', [MpDiscoveryController::class, 'discover']);
        Route::get('categories', [MpCategoryController::class, 'index']);
        Route::get('vendors', [MpVendorController::class, 'index']);
        Route::get('vendors/{vendor}', [MpVendorController::class, 'show']);
        Route::get('venues', [MpVenueController::class, 'index']);
        Route::get('venues/{venue}', [MpVenueController::class, 'show']);

        // Hotels / accommodation (browse; booking is planner-gated above)
        Route::get('accommodations', [MpAccommodationController::class, 'index']);
        Route::get('accommodations/{accommodation}', [MpAccommodationController::class, 'show']);

        // Messaging (planner ↔ vendor/venue; participants only)
        Route::get('messages', [MpMessageController::class, 'index']);
        Route::post('messages', [MpMessageController::class, 'start']);
        Route::get('messages/{thread}', [MpMessageController::class, 'show']);
        Route::post('messages/{thread}', [MpMessageController::class, 'send']);
    });
});
