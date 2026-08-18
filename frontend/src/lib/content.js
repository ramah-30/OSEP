/**
 * Landing page copy and imagery in one place, so wording changes never mean
 * hunting through JSX.
 *
 * Photography is served from the Unsplash CDN at an explicit width; every
 * placement sits on a navy gradient so the design still reads as intended if
 * an image fails to load.
 */

import authGardenTablescape from '../assets/auth-carousel/garden-tablescape.jpg'
import authGoldenHourPicnic from '../assets/auth-carousel/golden-hour-picnic.jpg'
import authBalloonArchNight from '../assets/auth-carousel/balloon-arch-night.jpg'
import authCateringBuffet from '../assets/auth-carousel/catering-buffet.jpg'
import authDrapedReceptionHall from '../assets/auth-carousel/draped-reception-hall.jpg'

const unsplash = (id, { w = 1600, h, q = 75 } = {}) =>
  `https://images.unsplash.com/${id}?auto=format&fit=crop&q=${q}&w=${w}${h ? `&h=${h}` : ''}`

export const IMAGES = {
  heroWedding: unsplash('photo-1519741497674-611481863552', { w: 1920 }),
  heroConference: unsplash('photo-1540575467063-178a50c2df87', { w: 1200 }),
  heroCelebration: unsplash('photo-1492684223066-81342ee5ff30', { w: 1200 }),
  aboutTeam: unsplash('photo-1511578314322-379afb476865', { w: 1200 }),
  aboutStage: unsplash('photo-1464366400600-7168b8af9bc3', { w: 900 }),
  authGala: unsplash('photo-1505236858219-8359eb29e329', { w: 1400 }),
  ctaBanquet: unsplash('photo-1478147427282-58a87a120781', { w: 1600 }),
}

/**
 * Auth screens (login/register) show a rotating carousel on their side panel
 * instead of a single static photo — five curated event-styling shots supplied
 * directly as local assets rather than hotlinked.
 */
export const AUTH_CAROUSEL = [
  {
    image: authDrapedReceptionHall,
    icon: 'Sparkles',
    title: 'A plan in minutes, not weeks',
    body: 'Describe the event and get a working timeline, budget and vendor shortlist you can edit.',
  },
  {
    image: authGardenTablescape,
    icon: 'HeartHandshake',
    title: 'Weddings, done right',
    body: 'From venue to vendors to the final toast, every detail lives in one shared plan.',
  },
  {
    image: authGoldenHourPicnic,
    icon: 'Users',
    title: 'One plan the whole team reads',
    body: 'Planners, vendors and clients see the same live event — no more conflicting spreadsheets.',
  },
  {
    image: authCateringBuffet,
    icon: 'ShieldCheck',
    title: 'Vendors you can actually trust',
    body: 'Every supplier is verified, and reviews come only from bookings that really happened.',
  },
  {
    image: authBalloonArchNight,
    icon: 'PartyPopper',
    title: 'Celebrations, elevated',
    body: 'Galas, launches and milestone nights — planned with the same care as the biggest conference.',
  },
]

export const STATS = [
  { value: 12500, suffix: '+', label: 'Events planned' },
  { value: 3200, suffix: '+', label: 'Verified vendors' },
  { value: 48, suffix: '', label: 'Countries served' },
  { value: 98, suffix: '%', label: 'Would recommend' },
]

export const TRUSTED_BY = [
  'Northwind Weddings',
  'Vertex Summit',
  'Lumen Hospitality',
  'Atlas Expo Group',
  'Saffron Catering',
  'Meridian Venues',
]

export const FEATURES = [
  {
    icon: 'Sparkles',
    title: 'AI Event Planning',
    description:
      'Describe the event you have in mind and get a working plan back — timeline, task owners and risk flags — in seconds rather than weeks.',
    accent: 'purple',
  },
  {
    icon: 'Wallet',
    title: 'Smart Budgeting',
    description:
      'Live budgets that track quotes, deposits and overruns per category, with early warnings before a line item gets away from you.',
    accent: 'emerald',
  },
  {
    icon: 'Store',
    title: 'Vendor Marketplace',
    description:
      'Search verified caterers, venues, DJs, decorators and photographers by availability, budget band and past performance.',
    accent: 'navy',
  },
  {
    icon: 'Users',
    title: 'Guest Management',
    description:
      'Invitations, RSVPs, dietary needs and seating in one list that everyone on the team reads from — no more conflicting spreadsheets.',
    accent: 'navy',
  },
  {
    icon: 'LayoutGrid',
    title: 'Venue Designer',
    description:
      'Lay out tables, stages and flow to scale, then share a floor plan your venue and suppliers can actually build from.',
    accent: 'emerald',
  },
  {
    icon: 'BarChart3',
    title: 'Analytics',
    description:
      'Attendance, spend and vendor performance measured event over event, so every brief you write is sharper than the last.',
    accent: 'purple',
  },
]

export const STEPS = [
  {
    icon: 'UserPlus',
    title: 'Create Account',
    description:
      'Sign up as a planner, vendor or client. Your workspace is set up around how you actually work.',
  },
  {
    icon: 'Wand2',
    title: 'Plan Event',
    description:
      'Set the brief and let OSEP draft the timeline, budget and shortlist. Adjust anything — it stays in sync.',
  },
  {
    icon: 'PartyPopper',
    title: 'Execute Successfully',
    description:
      'Run the day from one live plan, with every supplier, guest and payment tracked in real time.',
  },
]

export const USER_CATEGORIES = [
  {
    key: 'event_planner',
    icon: 'ClipboardList',
    title: 'Event Planner',
    tagline: 'For professionals running events end to end',
    description:
      'Manage weddings from a single command centre.',
    benefits: [
      'Every event, timeline and deadline in one view',
      'AI-drafted plans you can edit, not fight',
      'Vendor shortlists matched to budget and date',
      'Client-ready updates without the status meeting',
    ],
    cta: 'Start planning',
    accent: 'navy',
  },
  {
    key: 'vendor',
    icon: 'Store',
    title: 'Vendor',
    tagline: 'For businesses that supply the event',
    description:
      'Caterers, venues, DJs, decorators, photographers, florists, security and transport — reach planners actively booking.',
    benefits: [
      'A profile in front of planners with live briefs',
      'Enquiries that arrive with budget and date attached',
      'Availability and quotes managed in one place',
      'Reviews that compound into more bookings',
    ],
    cta: 'List your service',
    accent: 'emerald',
  },
  {
    key: 'client',
    icon: 'HeartHandshake',
    title: 'Client',
    tagline: 'For individuals and companies hosting',
    description:
      'Organising a wedding, launch or company offsite? Stay across every decision without living in your inbox.',
    benefits: [
      'One clear view of budget, timeline and progress',
      'Approve choices in a click, from anywhere',
      'Trusted vendors, already vetted',
      'No surprises in the final invoice',
    ],
    cta: 'Plan my event',
    accent: 'purple',
  },
]

export const TESTIMONIALS = [
  {
    name: 'Maya Alvarez',
    role: 'Lead Planner, Northwind Weddings',
    quote:
      'We used to lose a full week to the first draft of a plan. OSEP gets us to a working timeline the same afternoon the brief lands, and the budget stays honest all the way to invoice.',
    image: unsplash('photo-1494790108377-be9c29b29330', { w: 200, h: 200, q: 70 }),
  },
  {
    name: 'Adaeze Nwosu',
    role: 'Founder, Saffron Catering',
    quote:
      'Enquiries arrive with the date, headcount and budget already attached. I stopped writing speculative quotes and my booking rate went up in the first month.',
    image: unsplash('photo-1531123897727-8f129e1688ce', { w: 200, h: 200, q: 70 }),
  },
  {
    name: 'Renée Castillo',
    role: 'Head of Events, Vertex Summit',
    quote:
      'Running a 2,000-delegate conference across three venues used to mean four spreadsheets and a prayer. Now the whole team is reading from one live plan.',
    image: unsplash('photo-1573497019940-1c28c88b4f3e', { w: 200, h: 200, q: 70 }),
  },
  {
    name: 'Daniel Reyes',
    role: 'Creative Director, Lumen Hospitality',
    quote:
      'The venue designer alone paid for the year. Sharing a floor plan that suppliers can build from removed an entire round of back-and-forth.',
    image: unsplash('photo-1507003211169-0a1dd7228f2d', { w: 200, h: 200, q: 70 }),
  },
  {
    name: 'Tom Whitaker',
    role: 'Operations, Atlas Expo Group',
    quote:
      'What sold me was the analytics. Being able to show a client exactly where last year’s spend went made this year’s brief a ten-minute conversation.',
    image: unsplash('photo-1568602471122-7832951cc4c5', { w: 200, h: 200, q: 70 }),
  },
  {
    name: 'Omar Haddad',
    role: 'Client — company offsite for 400',
    quote:
      'I am not an events person. I approved decisions on my phone between meetings and the whole thing came in under budget. That is all I wanted.',
    image: unsplash('photo-1600180758890-6b94519a8ba6', { w: 200, h: 200, q: 70 }),
  },
]

export const FAQS = [
  {
    question: 'Who is OSEP built for?',
    answer:
      'Three groups. Event planners running events professionally, vendors supplying services such as catering, venues, music or photography, and clients — individuals or companies — organising an event of their own. You pick your account type when you register, and your workspace is shaped around it.',
  },
  {
    question: 'What does the AI actually do?',
    answer:
      'It turns a brief into a starting point: a draft timeline, a budget broken down by category, a vendor shortlist and flags on the parts most likely to slip. Everything it produces is editable — it drafts, you decide.',
  },
  {
    question: 'Do I need a credit card to create an account?',
    answer:
      'No. Registration is free and takes about a minute. You will only be asked for payment details if and when you move onto a paid plan.',
  },
  {
    question: 'How do you verify vendors?',
    answer:
      'Vendors submit business details and proof of past work before they appear in the marketplace. Reviews are tied to real bookings made through OSEP, so ratings reflect events that actually happened.',
  },
  {
    question: 'Can my whole team use one workspace?',
    answer:
      'Yes. The platform is built on roles from day one, so team members and clients can be given exactly the access they need — nothing more.',
  },
  {
    question: 'Is my data secure?',
    answer:
      'Passwords are hashed, sessions are token-based with expiry, and every authentication event is written to an audit log. Access is enforced by role on the server, never only in the interface.',
  },
  {
    question: 'Can I move events I have already started?',
    answer:
      'Yes. Guest lists, budgets and supplier contacts can be brought across, and our team will help with the first migration.',
  },
  {
    question: 'What is coming next?',
    answer:
      'The event management dashboard is the next release: live event workspaces, vendor booking and the venue designer. Registering now means your account is ready the day it ships.',
  },
]

export const CONTACT_DETAILS = [
  { icon: 'Mail', label: 'Email us', value: 'osep@gmail.com', href: 'mailto:osep@gmail.com' },
  { icon: 'Phone', label: 'Call us', value: '0755967702', href: 'tel:0755967702' },
  { icon: 'MapPin', label: 'Visit us', value: 'Ferry Kigamboni, Dar es Salaam', href: null },
  { icon: 'Clock', label: 'Response time', value: 'Within one business day', href: null },
]

export const FOOTER_LINKS = {
  Company: [
    { label: 'About', to: '/#about' },
    { label: 'Features', to: '/#features' },
    { label: 'How it works', to: '/#how-it-works' },
    { label: 'Contact', to: '/#contact' },
  ],
  Resources: [
    { label: 'For planners', to: '/register?type=event_planner' },
    { label: 'For vendors', to: '/register?type=vendor' },
    { label: 'For clients', to: '/register?type=client' },
    { label: 'FAQ', to: '/#faq' },
  ],
  Legal: [
    { label: 'Privacy Policy', to: '/privacy' },
    { label: 'Terms of Service', to: '/terms' },
    { label: 'Cookie Policy', to: '/privacy#cookies' },
    { label: 'Security', to: '/privacy#security' },
  ],
}
