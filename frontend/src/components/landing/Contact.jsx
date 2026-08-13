import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { api, applyServerErrors, parseApiError } from '../../lib/api'
import { CONTACT_DETAILS } from '../../lib/content'
import { contactSchema } from '../../lib/validation'
import Alert from '../ui/Alert'
import Button from '../ui/Button'
import Card from '../ui/Card'
import Icon from '../ui/Icon'
import Input from '../ui/Input'
import Reveal from '../ui/Reveal'
import Section, { Eyebrow } from '../ui/Section'

export default function Contact() {
  const [status, setStatus] = useState(null)

  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors, isSubmitting },
  } = useForm({ resolver: zodResolver(contactSchema), mode: 'onBlur' })

  const onSubmit = async (values) => {
    setStatus(null)

    try {
      const { data } = await api.post('/contact', values)
      setStatus({ tone: 'success', message: data.message })
      reset()
    } catch (error) {
      const parsed = parseApiError(error)
      applyServerErrors(parsed.errors, setError)
      setStatus({ tone: 'error', message: parsed.message })
    }
  }

  return (
    <Section id="contact" tone="canvas">
      <div className="grid gap-12 lg:grid-cols-[1fr_1.35fr] lg:gap-16">
        <Reveal>
          <Eyebrow>Contact</Eyebrow>

          <h2 className="mt-5 text-h2 font-extrabold text-ink text-balance">
            Let's talk about your next event
          </h2>
          <p className="mt-5 text-lead text-muted text-pretty">
            Questions about the platform, a partnership, or bringing your vendor business on
            board — we read everything that arrives here.
          </p>

          <div className="mt-10 space-y-4">
            {CONTACT_DETAILS.map((detail) => {
              const body = (
                <>
                  <span className="grid size-11 shrink-0 place-items-center rounded-xl bg-surface text-navy-800 ring-1 ring-line">
                    <Icon name={detail.icon} className="size-5" />
                  </span>
                  <span>
                    <span className="block text-sm font-medium text-muted">{detail.label}</span>
                    <span className="block font-semibold text-ink">{detail.value}</span>
                  </span>
                </>
              )

              return detail.href ? (
                <a
                  key={detail.label}
                  href={detail.href}
                  className="flex items-center gap-4 rounded-btn p-2 transition-colors duration-200 hover:bg-surface"
                >
                  {body}
                </a>
              ) : (
                <div key={detail.label} className="flex items-center gap-4 p-2">
                  {body}
                </div>
              )
            })}
          </div>
        </Reveal>

        <Reveal delay={0.08}>
          <Card className="p-7 md:p-9">
            <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-5">
              {status && (
                <Alert tone={status.tone}>{status.message}</Alert>
              )}

              <div className="grid gap-5 sm:grid-cols-2">
                <Input
                  label="Full name"
                  icon="User"
                  autoComplete="name"
                  error={errors.name?.message}
                  {...register('name')}
                />
                <Input
                  label="Email address"
                  icon="Mail"
                  type="email"
                  autoComplete="email"
                  error={errors.email?.message}
                  {...register('email')}
                />
              </div>

              <Input
                label="Subject"
                icon="Info"
                error={errors.subject?.message}
                {...register('subject')}
              />

              <div>
                <textarea
                  rows={5}
                  placeholder="Tell us about the event you have in mind…"
                  aria-label="Message"
                  aria-invalid={errors.message ? 'true' : undefined}
                  className={`w-full resize-y rounded-btn border bg-surface p-4 text-[0.95rem] text-ink outline-none transition-[border-color,box-shadow] duration-200 placeholder:text-muted/70 ${
                    errors.message
                      ? 'border-danger focus:border-danger focus:shadow-[0_0_0_3px_rgba(239,68,68,0.14)]'
                      : 'border-line hover:border-navy-200 focus:border-navy-600 focus:shadow-[0_0_0_3px_rgba(41,71,200,0.12)]'
                  }`}
                  {...register('message')}
                />
                {errors.message && (
                  <p className="mt-1.5 flex items-center gap-1.5 text-sm text-danger">
                    <Icon name="TriangleAlert" className="size-3.5 shrink-0" />
                    {errors.message.message}
                  </p>
                )}
              </div>

              <Button type="submit" size="lg" fullWidth loading={isSubmitting}>
                {isSubmitting ? 'Sending…' : 'Send message'}
                {!isSubmitting && <Icon name="Send" className="size-[18px]" />}
              </Button>

              <p className="text-center text-sm text-muted">
                We reply within one business day. No marketing lists, ever.
              </p>
            </form>
          </Card>
        </Reveal>
      </div>
    </Section>
  )
}
