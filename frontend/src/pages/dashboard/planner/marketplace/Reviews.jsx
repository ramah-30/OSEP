import { useTranslation } from 'react-i18next'
import LoadState from '../../../../components/dashboard/LoadState'
import ReviewList from '../../../../components/marketplace/ReviewList'
import { useResource } from '../../../../lib/useResource'

export default function Reviews() {
  const { t } = useTranslation()
  const { data, loading, error, reload } = useResource('/marketplace/my-reviews')

  return (
    <div className="space-y-4">
      <p className="text-sm text-muted">{t('vendors.myReviewsDescription')}</p>
      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && <ReviewList reviews={data.reviews} />}
      </LoadState>
    </div>
  )
}
