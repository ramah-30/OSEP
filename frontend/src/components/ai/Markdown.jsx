import { Fragment } from 'react'

/**
 * A tiny, dependency-free Markdown renderer scoped to what the AI produces:
 * headings (#/##/###), bold (**…**), inline code (`…`), bullet/numbered lists,
 * task checkboxes (- [ ] / - [x]), block quotes (> …), horizontal rules (---)
 * and simple pipe tables. It builds React elements directly — no raw HTML
 * injection — so AI output is always safe to render.
 */

function renderInline(text, keyPrefix) {
  // Split on **bold** and `code`, keeping the delimiters.
  const parts = String(text).split(/(\*\*[^*]+\*\*|`[^`]+`)/g).filter(Boolean)
  return parts.map((part, i) => {
    const key = `${keyPrefix}-${i}`
    if (part.startsWith('**') && part.endsWith('**')) {
      return <strong key={key} className="font-semibold text-ink">{part.slice(2, -2)}</strong>
    }
    if (part.startsWith('`') && part.endsWith('`')) {
      return <code key={key} className="rounded bg-canvas px-1 py-0.5 text-[0.85em] text-navy-800">{part.slice(1, -1)}</code>
    }
    return <Fragment key={key}>{part}</Fragment>
  })
}

const isTableRow = (line) => /^\s*\|.*\|\s*$/.test(line)
const isTableDivider = (line) => /^\s*\|?[\s:|-]+\|[\s:|-]*$/.test(line) && line.includes('-')

const splitRow = (line) =>
  line.trim().replace(/^\|/, '').replace(/\|$/, '').split('|').map((c) => c.trim())

export default function Markdown({ content, className }) {
  const lines = String(content ?? '').split('\n')
  const blocks = []
  let list = null // { ordered, items: [] }

  const flushList = () => {
    if (list) { blocks.push(list); list = null }
  }

  for (let i = 0; i < lines.length; i++) {
    const raw = lines[i]
    const line = raw.trimEnd()

    // Table: a header row followed by a --- divider row.
    if (isTableRow(line) && i + 1 < lines.length && isTableDivider(lines[i + 1])) {
      flushList()
      const header = splitRow(line)
      const rows = []
      i += 2
      while (i < lines.length && isTableRow(lines[i])) {
        rows.push(splitRow(lines[i]))
        i++
      }
      i-- // step back; the for-loop will advance
      blocks.push({ table: { header, rows } })
      continue
    }

    const bullet = line.match(/^\s*[-*]\s+(.*)$/)
    const ordered = line.match(/^\s*\d+\.\s+(.*)$/)
    const heading = line.match(/^(#{1,3})\s+(.*)$/)
    const quote = line.match(/^\s*>\s?(.*)$/)
    const hr = /^\s*(-{3,}|\*{3,}|_{3,})\s*$/.test(line)

    if (hr) {
      flushList()
      blocks.push({ hr: true })
    } else if (bullet) {
      if (!list || list.ordered) { flushList(); list = { ordered: false, items: [] } }
      list.items.push(bullet[1])
    } else if (ordered) {
      if (!list || !list.ordered) { flushList(); list = { ordered: true, items: [] } }
      list.items.push(ordered[1])
    } else if (heading) {
      flushList()
      blocks.push({ heading: heading[1].length, text: heading[2] })
    } else if (quote) {
      flushList()
      blocks.push({ quote: quote[1] })
    } else if (line.trim() === '') {
      flushList()
      blocks.push({ spacer: true })
    } else {
      flushList()
      blocks.push({ paragraph: line })
    }
  }
  flushList()

  return (
    <div className={className}>
      {blocks.map((block, i) => {
        if (block.heading) {
          const size = block.heading === 1 ? 'text-base' : 'text-sm'
          return <p key={i} className={`mt-3 first:mt-0 font-bold text-ink ${size}`}>{renderInline(block.text, `h${i}`)}</p>
        }
        if (block.paragraph) {
          return <p key={i} className="mt-2 first:mt-0 leading-relaxed">{renderInline(block.paragraph, `p${i}`)}</p>
        }
        if (block.quote !== undefined) {
          return (
            <blockquote key={i} className="mt-2 border-l-2 border-line pl-3 text-muted italic">
              {renderInline(block.quote, `q${i}`)}
            </blockquote>
          )
        }
        if (block.hr) {
          return <hr key={i} className="my-3 border-line" />
        }
        if (block.table) {
          return (
            <div key={i} className="mt-3 overflow-x-auto">
              <table className="w-full border-collapse text-left text-[0.9em]">
                <thead>
                  <tr className="border-b border-line">
                    {block.table.header.map((cell, j) => (
                      <th key={j} className="px-2 py-1.5 font-semibold text-ink">{renderInline(cell, `th${i}-${j}`)}</th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {block.table.rows.map((row, r) => (
                    <tr key={r} className="border-b border-line/60">
                      {row.map((cell, c) => (
                        <td key={c} className="px-2 py-1.5 text-muted">{renderInline(cell, `td${i}-${r}-${c}`)}</td>
                      ))}
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )
        }
        if (block.items) {
          const Tag = block.ordered ? 'ol' : 'ul'
          const hasChecks = !block.ordered && block.items.every((it) => /^\[[ xX]\]/.test(it))
          if (hasChecks) {
            return (
              <ul key={i} className="mt-2 space-y-1">
                {block.items.map((item, j) => {
                  const checked = /^\[[xX]\]/.test(item)
                  const label = item.replace(/^\[[ xX]\]\s*/, '')
                  return (
                    <li key={j} className="flex items-start gap-2 leading-relaxed">
                      <span className={`mt-0.5 grid size-4 shrink-0 place-items-center rounded border ${checked ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-line'}`}>
                        {checked ? '✓' : ''}
                      </span>
                      <span className={checked ? 'text-muted line-through' : ''}>{renderInline(label, `chk${i}-${j}`)}</span>
                    </li>
                  )
                })}
              </ul>
            )
          }
          return (
            <Tag key={i} className={`mt-2 space-y-1 ${block.ordered ? 'list-decimal' : 'list-disc'} pl-5`}>
              {block.items.map((item, j) => (
                <li key={j} className="leading-relaxed marker:text-muted">{renderInline(item, `li${i}-${j}`)}</li>
              ))}
            </Tag>
          )
        }
        return null // spacer — margins already handle spacing
      })}
    </div>
  )
}
