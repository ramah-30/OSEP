import { Link } from 'react-router-dom'
import { FOOTER_LINKS } from '../../lib/content'
import Icon from '../ui/Icon'
import Logo from '../ui/Logo'

const SOCIALS = [
  { label: 'LinkedIn', href: 'https://www.linkedin.com', path: 'M4.98 3.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5ZM3 9h4v12H3V9Zm7 0h3.8v1.71h.05a4.17 4.17 0 0 1 3.75-2c4 0 4.75 2.5 4.75 5.76V21h-4v-5.7c0-1.36-.03-3.1-1.9-3.1s-2.2 1.47-2.2 3v5.8h-4V9Z' },
  { label: 'X', href: 'https://x.com', path: 'M17.53 3h3.2l-7 8 8.23 10h-6.44l-5.05-6.12L4.7 21H1.5l7.49-8.56L1.1 3h6.6l4.57 5.6L17.53 3Zm-1.12 16.1h1.77L7.68 4.8H5.78l10.63 14.3Z' },
  { label: 'Instagram', href: 'https://www.instagram.com', path: 'M12 2.2c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41a3.8 3.8 0 0 1-1.38-.9 3.8 3.8 0 0 1-.9-1.38c-.16-.42-.36-1.06-.41-2.23C2.21 15.58 2.2 15.2 2.2 12s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41C8.42 2.21 8.8 2.2 12 2.2Zm0 3.8a6 6 0 1 0 0 12 6 6 0 0 0 0-12Zm0 9.9a3.9 3.9 0 1 1 0-7.8 3.9 3.9 0 0 1 0 7.8Zm7.65-10.15a1.4 1.4 0 1 1-2.8 0 1.4 1.4 0 0 1 2.8 0Z' },
  { label: 'Facebook', href: 'https://www.facebook.com', path: 'M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.77-3.89 1.1 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.45 2.89h-2.33v6.99A10 10 0 0 0 22 12Z' },
]

export default function Footer() {
  return (
    <footer className="bg-navy-950 text-white">
      <div className="container-page py-16 md:py-20">
        <div className="grid gap-12 lg:grid-cols-[1.4fr_2fr]">
          <div className="max-w-sm">
            <Logo variant="light" showTagline />
            <p className="mt-5 leading-relaxed text-white/65">
              OSEP brings planning, budgeting, vendors and guests into one place — so the work
              behind an unforgettable event stops feeling like a second job.
            </p>

            <div className="mt-6 flex gap-2.5">
              {SOCIALS.map((social) => (
                <a
                  key={social.label}
                  href={social.href}
                  target="_blank"
                  rel="noreferrer noopener"
                  aria-label={social.label}
                  className="grid size-10 place-items-center rounded-xl bg-white/8 text-white/75 ring-1 ring-white/12 transition-all duration-200 hover:-translate-y-0.5 hover:bg-white/15 hover:text-white"
                >
                  <svg viewBox="0 0 24 24" className="size-[18px]" fill="currentColor" aria-hidden="true">
                    <path d={social.path} />
                  </svg>
                </a>
              ))}
            </div>
          </div>

          <div className="grid gap-10 sm:grid-cols-3">
            {Object.entries(FOOTER_LINKS).map(([group, links]) => (
              <div key={group}>
                <h3 className="text-sm font-bold uppercase tracking-wider text-white/50">{group}</h3>
                <ul className="mt-4 space-y-3">
                  {links.map((link) => (
                    <li key={link.label}>
                      <Link
                        to={link.to}
                        className="text-white/75 transition-colors duration-200 hover:text-white"
                      >
                        {link.label}
                      </Link>
                    </li>
                  ))}
                </ul>
              </div>
            ))}
          </div>
        </div>

        <div className="mt-14 flex flex-col gap-4 border-t border-white/10 pt-8 sm:flex-row sm:items-center sm:justify-between">
          <p className="text-sm text-white/55">
            © {new Date().getFullYear()} OSEP. All rights reserved.
          </p>
          <p className="flex items-center gap-2 text-sm text-white/55">
            <Icon name="ShieldCheck" className="size-4 text-emerald-400" />
            Encrypted in transit · Role-based access · Audit logged
          </p>
        </div>
      </div>
    </footer>
  )
}
