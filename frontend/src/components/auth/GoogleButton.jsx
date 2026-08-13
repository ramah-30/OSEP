const API_URL = import.meta.env.VITE_API_URL ?? '/api/v1'

/**
 * A full page redirect rather than an XHR — the OAuth handshake has to happen
 * in the browser's address bar, not in the background.
 */
export default function GoogleButton({ label = 'Continue with Google' }) {
  return (
    <a
      href={`${API_URL}/auth/google/redirect`}
      className="flex h-12 w-full items-center justify-center gap-3 rounded-btn border border-line bg-surface text-[0.95rem] font-semibold text-ink transition-[transform,border-color,background-color] duration-200 hover:-translate-y-0.5 hover:border-navy-200 hover:bg-canvas"
    >
      <svg viewBox="0 0 24 24" className="size-5" aria-hidden="true">
        <path
          fill="#4285F4"
          d="M23.52 12.27c0-.85-.08-1.67-.22-2.45H12v4.64h6.46a5.53 5.53 0 0 1-2.4 3.62v3h3.88c2.27-2.09 3.58-5.17 3.58-8.81Z"
        />
        <path
          fill="#34A853"
          d="M12 24c3.24 0 5.96-1.08 7.94-2.91l-3.88-3.01c-1.08.72-2.45 1.15-4.06 1.15-3.13 0-5.78-2.11-6.73-4.95H1.26v3.1A12 12 0 0 0 12 24Z"
        />
        <path
          fill="#FBBC05"
          d="M5.27 14.28a7.2 7.2 0 0 1 0-4.56v-3.1H1.26a12 12 0 0 0 0 10.76l4.01-3.1Z"
        />
        <path
          fill="#EA4335"
          d="M12 4.77c1.76 0 3.35.61 4.6 1.8l3.44-3.44C17.95 1.18 15.24 0 12 0A12 12 0 0 0 1.26 6.62l4.01 3.1C6.22 6.88 8.87 4.77 12 4.77Z"
        />
      </svg>
      {label}
    </a>
  )
}
