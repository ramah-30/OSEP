import { Eyebrow } from '../../components/ui/Section'

/**
 * Shared shell for Privacy and Terms so both read as one document family.
 * Sections carry ids, which is what makes the footer's deep links work.
 */
export default function LegalPage({ eyebrow, title, updated, intro, sections }) {
  return (
    <div className="bg-surface">
      <div className="container-page py-16 md:py-20">
        <div className="mx-auto max-w-3xl">
          <Eyebrow>{eyebrow}</Eyebrow>
          <h1 className="mt-5 text-h2 font-extrabold text-ink text-balance">{title}</h1>
          <p className="mt-3 text-sm font-medium text-muted">Last updated {updated}</p>
          <p className="mt-6 text-lead text-muted text-pretty">{intro}</p>

          <nav aria-label="On this page" className="mt-10 rounded-card border border-line bg-canvas p-6">
            <p className="text-sm font-bold uppercase tracking-wider text-muted">On this page</p>
            <ul className="mt-3 space-y-2">
              {sections.map((section) => (
                <li key={section.id}>
                  <a
                    href={`#${section.id}`}
                    className="text-navy-800 underline-offset-4 transition-colors duration-200 hover:underline"
                  >
                    {section.heading}
                  </a>
                </li>
              ))}
            </ul>
          </nav>

          <div className="mt-12 space-y-12">
            {sections.map((section) => (
              <section key={section.id} id={section.id} className="scroll-mt-28">
                <h2 className="text-h3 font-extrabold text-ink">{section.heading}</h2>
                <div className="mt-4 space-y-4 leading-relaxed text-muted">
                  {section.body.map((paragraph, index) => (
                    <p key={index}>{paragraph}</p>
                  ))}
                </div>
              </section>
            ))}
          </div>

          <p className="mt-16 rounded-card border border-line bg-canvas p-6 text-muted">
            Questions about this document? Email{' '}
            <a href="mailto:legal@osep.app" className="font-semibold text-navy-800 underline underline-offset-4">
              legal@osep.app
            </a>{' '}
            and we will come back to you within one business day.
          </p>
        </div>
      </div>
    </div>
  )
}
