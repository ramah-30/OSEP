import { lazy, Suspense, useEffect } from 'react'
import { Route, Routes, useLocation } from 'react-router-dom'
import Spinner from './components/ui/Spinner'

import PublicLayout from './components/layout/PublicLayout'
import { GuestRoute, ProtectedRoute, RoleRoute, VendorCategoryRoute, VerifiedRoute } from './routes/guards'

import Landing from './pages/Landing'
import NotFound from './pages/NotFound'
import Privacy from './pages/legal/Privacy'
import Terms from './pages/legal/Terms'

import ForgotPassword from './pages/auth/ForgotPassword'
import Login from './pages/auth/Login'
import Register from './pages/auth/Register'
import ResetPassword from './pages/auth/ResetPassword'
import VerifyEmail from './pages/auth/VerifyEmail'
import VerifyEmailCallback from './pages/auth/VerifyEmailCallback'
import PublicRsvp from './pages/rsvp/PublicRsvp'
import PlannerBookingPage from './pages/book/PlannerBookingPage'

import DashboardLayout from './pages/dashboard/DashboardLayout'
import ComingSoon from './pages/dashboard/ComingSoon'
import Settings from './pages/dashboard/Settings'
import Messages from './pages/dashboard/Messages'

import PlannerOverview from './pages/dashboard/planner/PlannerOverview'
import PlannerProfile from './pages/dashboard/planner/PlannerProfile'
import Events from './pages/dashboard/planner/Events'
import Clients from './pages/dashboard/planner/Clients'
import CalendarPage from './pages/dashboard/planner/CalendarPage'
import PlannerReviews from './pages/dashboard/planner/Reviews'
// Marketplace (planner hub)
import MarketplaceLayout from './pages/dashboard/planner/marketplace/MarketplaceLayout'
import Discover from './pages/dashboard/planner/marketplace/Discover'
import VendorsBrowse from './pages/dashboard/planner/marketplace/VendorsBrowse'
import MarketplaceVendorProfile from './pages/dashboard/planner/marketplace/VendorProfile'
import VenuesBrowse from './pages/dashboard/planner/marketplace/VenuesBrowse'
import MarketplaceVenueProfile from './pages/dashboard/planner/marketplace/VenueProfile'
import HotelsBrowse from './pages/dashboard/planner/marketplace/HotelsBrowse'
import HotelProfile from './pages/dashboard/planner/marketplace/HotelProfile'
import AccommodationBookings from './pages/dashboard/planner/marketplace/AccommodationBookings'
import Saved from './pages/dashboard/planner/marketplace/Saved'
import BookingRequests from './pages/dashboard/planner/marketplace/BookingRequests'
import MarketplaceContracts from './pages/dashboard/planner/marketplace/Contracts'
// Finance (planner hub)
import FinanceLayout from './pages/dashboard/planner/finance/FinanceLayout'
import FinanceDashboard from './pages/dashboard/planner/finance/FinanceDashboard'
import FinanceBudgets from './pages/dashboard/planner/finance/Budgets'
import FinanceExpenses from './pages/dashboard/planner/finance/Expenses'
import FinanceQuotations from './pages/dashboard/planner/finance/Quotations'
import FinanceInvoices from './pages/dashboard/planner/finance/Invoices'
import FinancePayments from './pages/dashboard/planner/finance/Payments'
import FinanceReports from './pages/dashboard/planner/finance/Reports'
import FinanceAudit from './pages/dashboard/planner/finance/Audit'
import FinancePrint from './pages/dashboard/planner/finance/FinancePrint'
import AiLayout from './pages/dashboard/planner/ai/AiLayout'
import AiDashboard from './pages/dashboard/planner/ai/AiDashboard'
import AiRecommendations from './pages/dashboard/planner/ai/AiRecommendations'
import AiTemplates from './pages/dashboard/planner/ai/AiTemplates'
import AiDocuments from './pages/dashboard/planner/ai/AiDocuments'
import AiDocument from './pages/dashboard/planner/ai/AiDocument'
import AiAutomation from './pages/dashboard/planner/ai/AiAutomation'
import AiPrompts from './pages/dashboard/planner/ai/AiPrompts'
import AiPrompt from './pages/dashboard/planner/ai/AiPrompt'
import EventWorkspace from './pages/dashboard/planner/EventWorkspace'
import WorkspaceOverview from './pages/dashboard/planner/workspace/WorkspaceOverview'
import Timeline from './pages/dashboard/planner/workspace/Timeline'
import Tasks from './pages/dashboard/planner/workspace/Tasks'
import Budget from './pages/dashboard/planner/workspace/Budget'
import Guests from './pages/dashboard/planner/workspace/Guests'
import EventVendors from './pages/dashboard/planner/workspace/EventVendors'
import VenueTab from './pages/dashboard/planner/workspace/VenueTab'
import Documents from './pages/dashboard/planner/workspace/Documents'
import ApprovalsTab from './pages/dashboard/planner/workspace/ApprovalsTab'
import ActivityTab from './pages/dashboard/planner/workspace/ActivityTab'
import EventSettings from './pages/dashboard/planner/workspace/EventSettings'

import ClientOverview from './pages/dashboard/client/ClientOverview'
import ClientProfile from './pages/dashboard/client/ClientProfile'
import MyEvents from './pages/dashboard/client/MyEvents'
import MyRequests from './pages/dashboard/client/MyRequests'
import FindPlanner from './pages/dashboard/client/FindPlanner'
import Progress from './pages/dashboard/client/Progress'
import ClientGuests from './pages/dashboard/client/Guests'
import BudgetOverview from './pages/dashboard/client/BudgetOverview'
import ClientPayments from './pages/dashboard/client/Payments'

import VendorOverview from './pages/dashboard/vendor/VendorOverview'
import VendorProfile from './pages/dashboard/vendor/VendorProfile'
// Vendor marketplace management
import VendorServices from './pages/dashboard/vendor/Services'
import VendorPortfolio from './pages/dashboard/vendor/Portfolio'
import VendorAvailability from './pages/dashboard/vendor/Availability'
import VendorVenues from './pages/dashboard/vendor/Venues'
import VendorRequests from './pages/dashboard/vendor/Requests'
import VendorQuotations from './pages/dashboard/vendor/Quotations'
import VendorContracts from './pages/dashboard/vendor/Contracts'
import VendorReviews from './pages/dashboard/vendor/Reviews'
import VendorAnalytics from './pages/dashboard/vendor/Analytics'
// Admin marketplace
import AdminOverview from './pages/dashboard/admin/AdminOverview'
import AdminVendors from './pages/dashboard/admin/AdminVendors'
import AdminVenues from './pages/dashboard/admin/AdminVenues'
import AdminCategories from './pages/dashboard/admin/AdminCategories'
import AdminReviews from './pages/dashboard/admin/AdminReviews'

import BookingRequestsInbox from './pages/dashboard/planner/BookingRequestsInbox'

// The Venue Designer pulls in the Konva canvas library — load it on demand so it
// stays out of the main bundle.
const VenueDesigner = lazy(() => import('./pages/dashboard/planner/workspace/VenueDesigner'))

/**
 * Restores scroll on navigation, and honours "/#features" style links whether
 * the user is already on the landing page or arriving from elsewhere.
 */
function ScrollManager() {
  const { pathname, hash } = useLocation()

  useEffect(() => {
    if (!hash) {
      window.scrollTo({ top: 0, left: 0, behavior: 'instant' })
      return
    }

    // Sections above the target contain images that have not been measured
    // yet, so the first scroll can land in the wrong place. Re-aim a couple of
    // times as the layout settles.
    const id = hash.slice(1)
    const jump = () =>
      document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' })

    const frame = requestAnimationFrame(jump)
    const timers = [setTimeout(jump, 180), setTimeout(jump, 600)]

    return () => {
      cancelAnimationFrame(frame)
      timers.forEach(clearTimeout)
    }
  }, [pathname, hash])

  return null
}

export default function App() {
  return (
    <>
      <ScrollManager />

      <Routes>
        {/* Public marketing site */}
        <Route element={<PublicLayout />}>
          <Route path="/" element={<Landing />} />
          <Route path="/privacy" element={<Privacy />} />
          <Route path="/terms" element={<Terms />} />
          <Route path="*" element={<NotFound />} />
        </Route>

        {/* Only for signed-out visitors */}
        <Route element={<GuestRoute />}>
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />
          <Route path="/forgot-password" element={<ForgotPassword />} />
          <Route path="/reset-password" element={<ResetPassword />} />
        </Route>

        {/* Public guest RSVP — the URL token is the credential, no login needed */}
        <Route path="/rsvp/:token" element={<PublicRsvp />} />

        {/* Public planner booking page — no auth required */}
        <Route path="/book/:slug" element={<PlannerBookingPage />} />

        {/* Reachable either way — the API has already done the work */}
        <Route path="/verify-email/callback" element={<VerifyEmailCallback />} />

        {/* Signed in, email not yet confirmed */}
        <Route element={<ProtectedRoute />}>
          <Route path="/verify-email" element={<VerifyEmail />} />
        </Route>

        {/* Signed in and confirmed — the role workspaces */}
        <Route element={<VerifiedRoute />}>
          {/* Planner */}
          <Route element={<RoleRoute accountType="event_planner" />}>
            <Route path="/dashboard/planner" element={<DashboardLayout />}>
              <Route index element={<PlannerOverview />} />
              <Route path="events" element={<Events />} />
              <Route path="events/:eventId" element={<EventWorkspace />}>
                <Route index element={<WorkspaceOverview />} />
                <Route path="timeline" element={<Timeline />} />
                <Route path="tasks" element={<Tasks />} />
                <Route path="budget" element={<Budget />} />
                <Route path="guests" element={<Guests />} />
                <Route path="vendors" element={<EventVendors />} />
                <Route path="venue" element={<VenueTab />} />
                <Route
                  path="venue-designer"
                  element={
                    <Suspense fallback={<div className="grid min-h-64 place-items-center"><Spinner className="size-7" /></div>}>
                      <VenueDesigner />
                    </Suspense>
                  }
                />
                <Route path="documents" element={<Documents />} />
                <Route path="approvals" element={<ApprovalsTab />} />
                <Route path="activity" element={<ActivityTab />} />
                <Route path="settings" element={<EventSettings />} />
              </Route>
              <Route path="marketplace" element={<MarketplaceLayout />}>
                <Route index element={<Discover />} />
                <Route path="vendors" element={<VendorsBrowse />} />
                <Route path="vendors/:vendorId" element={<MarketplaceVendorProfile />} />
                <Route path="venues" element={<VenuesBrowse />} />
                <Route path="venues/:venueId" element={<MarketplaceVenueProfile />} />
                <Route path="hotels" element={<HotelsBrowse />} />
                <Route path="hotels/bookings" element={<AccommodationBookings />} />
                <Route path="hotels/:slug" element={<HotelProfile />} />
                <Route path="saved" element={<Saved />} />
                <Route path="booking-requests" element={<BookingRequests />} />
                <Route path="contracts" element={<MarketplaceContracts />} />
              </Route>
              <Route path="finance" element={<FinanceLayout />}>
                <Route index element={<FinanceDashboard />} />
                <Route path="budgets" element={<FinanceBudgets />} />
                <Route path="expenses" element={<FinanceExpenses />} />
                <Route path="quotations" element={<FinanceQuotations />} />
                <Route path="invoices" element={<FinanceInvoices />} />
                <Route path="payments" element={<FinancePayments />} />
                <Route path="reports" element={<FinanceReports />} />
                <Route path="audit" element={<FinanceAudit />} />
              </Route>
              <Route path="ai-assistant" element={<AiLayout />}>
                <Route index element={<AiDashboard />} />
                <Route path="recommendations" element={<AiRecommendations />} />
                <Route path="templates" element={<AiTemplates />} />
                <Route path="documents" element={<AiDocuments />} />
                <Route path="documents/:id" element={<AiDocument />} />
                <Route path="prompts" element={<AiPrompts />} />
                <Route path="prompts/:id" element={<AiPrompt />} />
                <Route path="automation" element={<AiAutomation />} />
              </Route>
              <Route path="clients" element={<Clients />} />
              <Route path="booking-requests" element={<BookingRequestsInbox />} />
              <Route path="calendar" element={<CalendarPage />} />
              <Route path="reviews" element={<PlannerReviews />} />
              <Route path="messages" element={<Messages />} />
              <Route path="profile" element={<PlannerProfile />} />
              <Route path="settings" element={<Settings />} />
              <Route path=":section" element={<ComingSoon />} />
            </Route>
            {/* Standalone printable finance documents (no dashboard chrome) */}
            <Route path="/dashboard/planner/finance/print/:kind/:id" element={<FinancePrint />} />
          </Route>

          {/* Client */}
          <Route element={<RoleRoute accountType="client" />}>
            <Route path="/dashboard/client" element={<DashboardLayout />}>
              <Route index element={<ClientOverview />} />
              <Route path="my-events" element={<MyEvents />} />
              <Route path="find-planner" element={<FindPlanner />} />
              <Route path="booking-requests" element={<MyRequests />} />
              <Route path="progress" element={<Progress />} />
              <Route path="guests" element={<ClientGuests />} />
              <Route path="budget" element={<BudgetOverview />} />
              <Route path="payments" element={<ClientPayments />} />
              <Route path="messages" element={<Messages />} />
              <Route path="profile" element={<ClientProfile />} />
              <Route path="settings" element={<Settings />} />
              <Route path=":section" element={<ComingSoon />} />
            </Route>
          </Route>

          {/* Vendor */}
          <Route element={<RoleRoute accountType="vendor" />}>
            <Route path="/dashboard/vendor" element={<DashboardLayout />}>
              <Route index element={<VendorOverview />} />
              <Route path="business-profile" element={<VendorProfile />} />
              <Route path="services" element={<VendorServices />} />
              <Route path="portfolio" element={<VendorPortfolio />} />
              <Route path="availability" element={<VendorAvailability />} />
              <Route element={<VendorCategoryRoute slug="venues" />}>
                <Route path="venues" element={<VendorVenues />} />
              </Route>
              <Route path="requests" element={<VendorRequests />} />
              <Route path="quotations" element={<VendorQuotations />} />
              <Route path="contracts" element={<VendorContracts />} />
              <Route path="reviews" element={<VendorReviews />} />
              <Route path="messages" element={<Messages />} />
              <Route path="analytics" element={<VendorAnalytics />} />
              <Route path="settings" element={<Settings />} />
              <Route path=":section" element={<ComingSoon />} />
            </Route>
          </Route>

          {/* Admin */}
          <Route element={<RoleRoute accountType="admin" />}>
            <Route path="/dashboard/admin" element={<DashboardLayout />}>
              <Route index element={<AdminOverview />} />
              <Route path="vendors" element={<AdminVendors />} />
              <Route path="venues" element={<AdminVenues />} />
              <Route path="categories" element={<AdminCategories />} />
              <Route path="reviews" element={<AdminReviews />} />
              <Route path="settings" element={<Settings />} />
              <Route path=":section" element={<ComingSoon />} />
            </Route>
          </Route>
        </Route>
      </Routes>
    </>
  )
}
