import LegalPage from './LegalPage'

const SECTIONS = [
  {
    id: 'collect',
    heading: 'What we collect',
    body: [
      'When you register we collect your first and last name, email address, phone number, country and the account type you select. We store a hashed version of your password — never the password itself.',
      'We also record technical information tied to authentication: the IP address and browser used when you sign in, register, reset a password or verify an email. This is what lets us show you unusual activity and investigate account takeover attempts.',
    ],
  },
  {
    id: 'use',
    heading: 'How we use it',
    body: [
      'Your details are used to operate your account: signing you in, routing you to the right workspace, sending verification and password reset emails, and providing support when you ask for it.',
      'We do not sell your personal information, and we do not share it with advertisers.',
    ],
  },
  {
    id: 'cookies',
    heading: 'Cookies and local storage',
    body: [
      'OSEP keeps your session token in your browser’s local storage so you stay signed in between visits. Clearing your browser data signs you out.',
      'We do not use third-party advertising or cross-site tracking cookies.',
    ],
  },
  {
    id: 'security',
    heading: 'Security',
    body: [
      'Passwords are hashed with bcrypt. Session tokens expire — twelve hours for a normal sign-in, thirty days when you choose "remember me" — and every token is revoked immediately when you reset your password.',
      'Authentication endpoints are rate limited per account and per network address, and every authentication event is written to an audit log. Access to data is enforced by role on the server, not merely hidden in the interface.',
    ],
  },
  {
    id: 'rights',
    heading: 'Your rights',
    body: [
      'You can request a copy of the personal data we hold about you, ask us to correct it, or ask us to delete your account and its data. Email privacy@osep.app and we will action the request within thirty days.',
      'Where processing is based on consent, you may withdraw that consent at any time.',
    ],
  },
  {
    id: 'retention',
    heading: 'Retention',
    body: [
      'Account data is kept for as long as your account is open. If you close your account we delete personal data within ninety days, other than records we are legally required to retain.',
      'Authentication audit records are retained for twelve months for security purposes.',
    ],
  },
]

export default function Privacy() {
  return (
    <LegalPage
      eyebrow="Legal"
      title="Privacy Policy"
      updated="25 July 2026"
      intro="This policy explains what OSEP collects, why we collect it, and the choices you have. It is written to be read, not to be survived."
      sections={SECTIONS}
    />
  )
}
