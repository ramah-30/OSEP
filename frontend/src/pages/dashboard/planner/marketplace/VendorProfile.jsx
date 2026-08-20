import { useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import Card from '../../../../components/ui/Card'
import Icon from '../../../../components/ui/Icon'
import Avatar from '../../../../components/ui/Avatar'
import Badge from '../../../../components/ui/Badge'
import Button from '../../../../components/ui/Button'
import Tabs from '../../../../components/ui/Tabs'
import LoadState from '../../../../components/dashboard/LoadState'
import RatingStars from '../../../../components/marketplace/RatingStars'
import VerificationBadge from '../../../../components/marketplace/VerificationBadge'
import ReviewList from '../../../../components/marketplace/ReviewList'
import BookingRequestModal from '../../../../components/marketplace/BookingRequestModal'
import ReviewModal from '../../../../components/marketplace/ReviewModal'
import MessageComposeModal from '../../../../components/marketplace/MessageComposeModal'
import { useResource } from '../../../../lib/useResource'
import { formatCurrency, formatDate } from '../../../../lib/format'

export default function VendorProfile() {
  const { vendorId } = useParams()
  const navigate = useNavigate()
  const { data, loading, error, reload } = useResource(`/marketplace/vendors/${vendorId}`)
  const [tab, setTab] = useState('about')
  const [modal, setModal] = useState(null)

  const vendor = data?.vendor
  const provider = vendor ? { type: 'vendor', id: vendor.id, name: vendor.business_name } : null

  return (
    <div className="space-y-6">
      <button onClick={() => navigate(-1)} className="flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-ink">
        <Icon name="ArrowLeft" className="size-4" /> Back
      </button>

      <LoadState loading={loading} error={error} onRetry={reload}>
        {vendor && (
          <>
            <Card className="p-6">
              <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div className="flex items-start gap-4">
                  <Avatar name={vendor.business_name} src={vendor.logo_url} size="xl" className="shrink-0 ring-2 ring-line" />
                  <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                      <h2 className="text-2xl font-extrabold tracking-tight text-ink">{vendor.business_name}</h2>
                      <VerificationBadge level={vendor.verification_level} />
                    </div>
                    <p className="text-muted">{vendor.tagline ?? vendor.category}</p>
                  </div>
                </div>
                <div className="flex flex-wrap gap-2">
                  <Button onClick={() => setModal('book')}><Icon name="ClipboardList" className="size-4" /> Request booking</Button>
                  <Button variant="secondary" to={`/dashboard/planner/messages?to=${vendor.id}`}><Icon name="MessageSquare" className="size-4" /> Message</Button>
                  <Button variant="ghost" onClick={() => setModal('review')}><Icon name="Star" className="size-4" /> Review</Button>
                </div>
              </div>

              <div className="mt-5 flex flex-wrap items-center gap-x-6 gap-y-2 border-t border-line pt-4 text-sm text-muted">
                <RatingStars rating={vendor.rating} count={vendor.reviews_count} />
                <span className="flex items-center gap-1.5"><Icon name="MapPin" className="size-4" />{vendor.location ?? '—'}</span>
                {vendor.years_in_business != null && <span className="flex items-center gap-1.5"><Icon name="Building2" className="size-4" />{vendor.years_in_business} yrs in business</span>}
                {vendor.response_time_hours != null && <span className="flex items-center gap-1.5"><Icon name="Timer" className="size-4" />~{vendor.response_time_hours}h response</span>}
                <span className="flex items-center gap-1.5"><Icon name="CheckCircle2" className="size-4" />{vendor.completed_jobs} jobs completed</span>
              </div>
            </Card>

            <Tabs
              value={tab}
              onChange={setTab}
              tabs={[
                { value: 'about', label: 'About' },
                { value: 'packages', label: `Packages (${vendor.packages?.length ?? 0})` },
                { value: 'portfolio', label: `Portfolio (${vendor.portfolios?.length ?? 0})` },
                { value: 'reviews', label: `Reviews (${vendor.reviews_count})` },
              ]}
            />

            {tab === 'about' && (
              <div className="grid gap-6 lg:grid-cols-3">
                <Card className="p-6 lg:col-span-2">
                  <h3 className="font-bold text-ink">About</h3>
                  <p className="mt-2 whitespace-pre-line text-sm text-muted">{vendor.description ?? 'No description provided.'}</p>
                  {vendor.services?.length > 0 && (
                    <div className="mt-5">
                      <h4 className="text-sm font-bold text-ink">Services offered</h4>
                      <div className="mt-2 flex flex-wrap gap-2">
                        {vendor.services.map((s) => <Badge key={s.id} tone="navy">{s.name}</Badge>)}
                      </div>
                    </div>
                  )}
                </Card>
                <Card className="p-6">
                  <h3 className="font-bold text-ink">Contact</h3>
                  <ul className="mt-3 space-y-2.5 text-sm">
                    {vendor.phone && <li className="flex items-center gap-2 text-muted"><Icon name="Phone" className="size-4" />{vendor.phone}</li>}
                    {vendor.contact_email && <li className="flex items-center gap-2 text-muted"><Icon name="Mail" className="size-4" />{vendor.contact_email}</li>}
                    {vendor.website && <li className="flex items-center gap-2 text-muted"><Icon name="Globe2" className="size-4" /><a href={vendor.website} target="_blank" rel="noreferrer" className="text-navy-700 hover:underline">Website</a></li>}
                  </ul>
                </Card>
              </div>
            )}

            {tab === 'packages' && (
              <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                {(vendor.packages ?? []).map((p) => (
                  <Card key={p.id} className="flex flex-col p-6">
                    <p className="font-bold text-ink">{p.name}</p>
                    <p className="mt-1 text-2xl font-extrabold text-navy-800">{p.price != null ? formatCurrency(p.price) : 'On request'}</p>
                    <p className="text-xs text-muted">{p.price_unit}</p>
                    {p.description && <p className="mt-2 text-sm text-muted">{p.description}</p>}
                    <ul className="mt-3 flex-1 space-y-1.5 text-sm">
                      {(p.inclusions ?? []).map((inc, i) => (
                        <li key={i} className="flex items-start gap-2 text-ink"><Icon name="Check" className="mt-0.5 size-4 shrink-0 text-emerald-500" />{inc}</li>
                      ))}
                    </ul>
                    <Button className="mt-4" fullWidth variant="secondary" onClick={() => setModal('book')}>Request this package</Button>
                  </Card>
                ))}
                {!vendor.packages?.length && <p className="text-sm text-muted">No packages listed yet.</p>}
              </div>
            )}

            {tab === 'portfolio' && (
              <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                {(vendor.portfolios ?? []).map((item) => (
                  <Card key={item.id} className="overflow-hidden">
                    <div className="h-44 w-full bg-canvas">
                      {item.cover_url && <img src={item.cover_url} alt="" className="size-full object-cover" />}
                    </div>
                    <div className="p-4">
                      <div className="flex items-center justify-between">
                        <p className="font-bold text-ink">{item.title}</p>
                        {item.is_case_study && <Badge tone="purple">Case study</Badge>}
                      </div>
                      <p className="text-xs text-muted">{item.event_type} · {formatDate(item.event_date)}</p>
                      {item.description && <p className="mt-2 text-sm text-muted">{item.description}</p>}
                      {item.client_feedback && <p className="mt-2 border-l-2 border-navy-200 pl-3 text-sm italic text-muted">“{item.client_feedback}”</p>}
                    </div>
                  </Card>
                ))}
                {!vendor.portfolios?.length && <p className="text-sm text-muted">No portfolio items yet.</p>}
              </div>
            )}

            {tab === 'reviews' && <ReviewList reviews={vendor.reviews} />}
          </>
        )}
      </LoadState>

      {provider && (
        <>
          <BookingRequestModal open={modal === 'book'} onClose={() => setModal(null)} provider={provider} onSubmitted={reload} />
          <ReviewModal open={modal === 'review'} onClose={() => setModal(null)} provider={provider} onSubmitted={reload} />
          <MessageComposeModal open={modal === 'message'} onClose={() => setModal(null)} provider={provider} />
        </>
      )}
    </div>
  )
}
