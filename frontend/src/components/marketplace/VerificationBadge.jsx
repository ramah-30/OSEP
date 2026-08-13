import Badge from '../ui/Badge'
import Icon from '../ui/Icon'
import { verificationMeta } from '../../lib/marketplace'

/**
 * The trust badge shown throughout the marketplace. Hidden entirely for
 * unverified listings unless `always` is set (e.g. the vendor's own storefront).
 */
export default function VerificationBadge({ level, always = false, className }) {
  const meta = verificationMeta(level)

  if (level === 'unverified' && !always) return null

  return (
    <Badge tone={meta.tone} className={className}>
      <Icon name={meta.icon} className="size-3.5" />
      {meta.label}
    </Badge>
  )
}
