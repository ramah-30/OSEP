import LegalPage from './LegalPage'

const SECTIONS = [
  {
    id: 'accounts',
    heading: 'Your account',
    body: [
      'You must provide accurate details when registering and keep them current. You are responsible for everything that happens under your account, so keep your password to yourself and tell us promptly if you believe it has been compromised.',
      'Accounts are for a single person. Planners, vendors and clients working together should each hold their own account rather than sharing one.',
    ],
  },
  {
    id: 'account-types',
    heading: 'Account types',
    body: [
      'You choose an account type — Event Planner, Vendor or Client — when you register. It determines the workspace and tools available to you.',
      'Vendors listing services in the marketplace must be able to substantiate the services and credentials they advertise. We may ask for evidence and may remove listings we cannot verify.',
    ],
  },
  {
    id: 'acceptable-use',
    heading: 'Acceptable use',
    body: [
      'Do not use OSEP to break the law, to infringe someone else’s rights, to send unsolicited marketing, or to attempt to access accounts or data that are not yours.',
      'Do not probe, scan or test the security of the platform without our written permission. If you believe you have found a vulnerability, email security@osep.app and we will work with you.',
    ],
  },
  {
    id: 'availability',
    heading: 'Service availability',
    body: [
      'We aim to keep OSEP available at all times, but we do not guarantee uninterrupted service. Planned maintenance is announced in advance wherever practical.',
      'Features described as forthcoming may change. We will not remove a feature you depend on without reasonable notice.',
    ],
  },
  {
    id: 'liability',
    heading: 'Liability',
    body: [
      'OSEP is a planning platform. Contracts for services at your event are between you and the vendor concerned; we are not a party to them and do not guarantee any vendor’s performance.',
      'To the fullest extent permitted by law, our liability arising out of your use of the platform is limited to the amount you paid us in the twelve months preceding the claim.',
    ],
  },
  {
    id: 'termination',
    heading: 'Ending the agreement',
    body: [
      'You may close your account at any time. We may suspend or close an account that breaches these terms, and will tell you why unless we are legally prevented from doing so.',
      'Sections that by their nature should survive termination — liability, for instance — will do so.',
    ],
  },
]

export default function Terms() {
  return (
    <LegalPage
      eyebrow="Legal"
      title="Terms of Service"
      updated="25 July 2026"
      intro="These terms govern your use of OSEP. By creating an account you agree to them, so they are kept as short and plain as we can make them."
      sections={SECTIONS}
    />
  )
}
