import { Children, isValidElement } from 'react'

/** Flatten a React node into plain text for an option label. */
function nodeToText(node) {
  if (node == null || node === false) return ''
  if (typeof node === 'string' || typeof node === 'number') return String(node)
  if (Array.isArray(node)) return node.map(nodeToText).join('')
  if (isValidElement(node)) return nodeToText(node.props.children)
  return ''
}

/**
 * Normalise the two ways our selects receive their choices — an `options` array
 * (strings or {value,label,disabled}) or `<option>`/`<optgroup>` children — into
 * a single flat list ListboxSelect can render. The disabled empty-value option
 * used as an inline placeholder is dropped.
 */
export function normaliseOptions(options, children) {
  if (options?.length) {
    return options.map((o) =>
      typeof o === 'string' ? { value: o, label: o } : o,
    )
  }

  const out = []
  const walk = (nodes) => {
    Children.forEach(nodes, (child) => {
      if (!isValidElement(child)) return
      if (child.type === 'option') {
        const value = child.props.value ?? ''
        // Skip the classic disabled placeholder (<option value="" disabled>).
        if (value === '' && child.props.disabled) return
        out.push({
          value,
          label: nodeToText(child.props.children),
          disabled: !!child.props.disabled,
        })
      } else if (child.type === 'optgroup') {
        out.push({ group: child.props.label })
        walk(child.props.children)
      } else if (child.props?.children) {
        walk(child.props.children)
      }
    })
  }
  walk(children)
  return out
}
