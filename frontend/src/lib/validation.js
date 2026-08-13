import { z } from 'zod'

/**
 * These schemas mirror the Laravel FormRequests one-for-one. The server stays
 * the source of truth — this exists so the user gets an answer before a round
 * trip, not so validation can be skipped on the API.
 * See backend/app/Http/Requests/*.php
 */

export const ACCOUNT_TYPES = ['event_planner', 'vendor', 'client']

const password = z
  .string()
  .min(8, 'Use at least 8 characters.')
  .regex(/[a-z]/, 'Include a lowercase letter.')
  .regex(/[A-Z]/, 'Include an uppercase letter.')
  .regex(/[0-9]/, 'Include a number.')
  .regex(/[^A-Za-z0-9]/, 'Include a symbol.')

export const registerSchema = z
  .object({
    first_name: z.string().trim().min(2, 'At least 2 characters.').max(50, 'At most 50 characters.'),
    last_name: z.string().trim().min(2, 'At least 2 characters.').max(50, 'At most 50 characters.'),
    email: z.email('Enter a valid email address.').max(255),
    phone: z
      .string()
      .trim()
      .min(7, 'Enter a valid phone number.')
      .max(20, 'Enter a valid phone number.')
      .regex(/^\+?[0-9\s\-()]+$/, 'Digits and + - ( ) only.'),
    password,
    password_confirmation: z.string(),
    account_type: z.enum(ACCOUNT_TYPES, { message: 'Choose an account type.' }),
    country: z.string().trim().min(1, 'Select your country.').max(100),
    terms: z.literal(true, { message: 'Accept the Terms and Privacy Policy to continue.' }),
  })
  .refine((values) => values.password === values.password_confirmation, {
    path: ['password_confirmation'],
    message: 'Passwords do not match.',
  })

export const loginSchema = z.object({
  email: z.email('Enter a valid email address.').max(255),
  password: z.string().min(1, 'Enter your password.'),
  remember: z.boolean().optional(),
})

export const forgotPasswordSchema = z.object({
  email: z.email('Enter a valid email address.').max(255),
})

export const resetPasswordSchema = z
  .object({
    password,
    password_confirmation: z.string(),
  })
  .refine((values) => values.password === values.password_confirmation, {
    path: ['password_confirmation'],
    message: 'Passwords do not match.',
  })

export const contactSchema = z.object({
  name: z.string().trim().min(2, 'Tell us your name.').max(100),
  email: z.email('Enter a valid email address.').max(255),
  subject: z.string().trim().min(3, 'Add a short subject.').max(150),
  message: z.string().trim().min(10, 'A little more detail helps us reply well.').max(2000),
})

/**
 * Scores a password 0–4 for the strength meter. Deliberately mirrors the five
 * rules above rather than inventing its own idea of "strong".
 */
export function passwordStrength(value = '') {
  if (!value) return 0

  const checks = [
    value.length >= 8,
    /[a-z]/.test(value) && /[A-Z]/.test(value),
    /[0-9]/.test(value),
    /[^A-Za-z0-9]/.test(value),
  ]

  return checks.filter(Boolean).length
}
