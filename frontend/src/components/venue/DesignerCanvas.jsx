import { forwardRef, useEffect, useImperativeHandle, useLayoutEffect, useRef, useState } from 'react'
import { Stage, Layer, Rect, Ellipse, Line, Text, Group, Transformer } from 'react-konva'
import { shapeForType } from '../../lib/venueCatalog'

const BASE_PPM = 22 // pixels per metre at zoom 1

const snap = (v, on) => (on ? Math.round(v / 0.5) * 0.5 : Math.round(v * 100) / 100)

/**
 * The interactive floor-plan canvas. Coordinates are in metres; the stage scales
 * them to pixels (BASE_PPM × zoom) and can be panned by dragging empty space.
 * Exposes `toDataURL()` to the parent for PNG export.
 */
const DesignerCanvas = forwardRef(function DesignerCanvas(
  { layout, objects, layers, view, snapOn, selectedUid, onSelect, onChange, warningsByUid = {} },
  ref,
) {
  const wrapRef = useRef(null)
  const stageRef = useRef(null)
  const trRef = useRef(null)
  const nodeRefs = useRef({})
  const [size, setSize] = useState({ width: 800, height: 600 })

  const ppm = BASE_PPM * view.zoom
  const layerState = Object.fromEntries((layers ?? []).map((l) => [l.id, l]))

  useImperativeHandle(ref, () => ({
    toDataURL: (opts) => stageRef.current?.toDataURL(opts),
  }))

  useLayoutEffect(() => {
    if (!wrapRef.current) return
    const el = wrapRef.current
    const update = () => setSize({ width: el.clientWidth, height: 600 })
    update()
    const ro = new ResizeObserver(update)
    ro.observe(el)
    return () => ro.disconnect()
  }, [])

  // Attach the transformer to the selected node (unless its layer is locked).
  useEffect(() => {
    const tr = trRef.current
    if (!tr) return
    const obj = objects.find((o) => o.uid === selectedUid)
    const node = selectedUid ? nodeRefs.current[selectedUid] : null
    const locked = obj && layerState[obj.layer]?.locked
    if (node && !locked) {
      tr.nodes([node])
    } else {
      tr.nodes([])
    }
    tr.getLayer()?.batchDraw()
  }, [selectedUid, objects, layers]) // eslint-disable-line react-hooks/exhaustive-deps

  function handleStageClick(e) {
    // Clicking empty space (the stage or the venue floor) clears selection.
    if (e.target === e.target.getStage() || e.target.name() === 'venue-floor') {
      onSelect(null)
    }
  }

  function handleDragEnd(obj, node) {
    onChange(obj.uid, { x: snap(node.x() / ppm, snapOn), y: snap(node.y() / ppm, snapOn) })
  }

  function handleTransformEnd(obj, node) {
    const scaleX = node.scaleX()
    const scaleY = node.scaleY()
    node.scaleX(1)
    node.scaleY(1)
    onChange(obj.uid, {
      x: snap(node.x() / ppm, snapOn),
      y: snap(node.y() / ppm, snapOn),
      width: Math.max(0.2, snap((obj.width * scaleX), snapOn)),
      height: Math.max(0.2, snap((obj.height * scaleY), snapOn)),
      rotation: Math.round(node.rotation()),
    })
  }

  const W = layout.width * ppm
  const H = layout.height * ppm

  // Grid lines every metre inside the venue.
  const gridLines = []
  if (view.gridOn && layout.width > 0 && layout.height > 0) {
    for (let x = 0; x <= layout.width; x += 1) gridLines.push(<Line key={`v${x}`} points={[x * ppm, 0, x * ppm, H]} stroke="#e2e8f0" strokeWidth={1} listening={false} />)
    for (let y = 0; y <= layout.height; y += 1) gridLines.push(<Line key={`h${y}`} points={[0, y * ppm, W, y * ppm]} stroke="#e2e8f0" strokeWidth={1} listening={false} />)
  }

  return (
    <div ref={wrapRef} className="h-[600px] w-full overflow-hidden rounded-card border border-line bg-[#f8fafc]">
      <Stage
        ref={stageRef}
        width={size.width}
        height={size.height}
        draggable
        onMouseDown={handleStageClick}
        onTouchStart={handleStageClick}
      >
        <Layer>
          {/* Venue boundary */}
          <Rect name="venue-floor" x={0} y={0} width={W} height={H} fill="#ffffff" stroke="#94a3b8" strokeWidth={2} cornerRadius={4} />
          {gridLines}

          {objects.map((obj) => {
            const ls = layerState[obj.layer]
            if (ls?.hidden) return null
            const locked = ls?.locked
            const w = obj.width * ppm
            const h = obj.height * ppm
            const isCircle = shapeForType(obj.object_type) === 'circle'
            const warn = warningsByUid[obj.uid]
            const stroke = warn ? '#ef4444' : selectedUid === obj.uid ? '#2947c8' : '#475569'

            return (
              <Group
                key={obj.uid}
                name={obj.uid}
                ref={(n) => { if (n) nodeRefs.current[obj.uid] = n; else delete nodeRefs.current[obj.uid] }}
                x={obj.x * ppm}
                y={obj.y * ppm}
                rotation={obj.rotation ?? 0}
                draggable={!locked}
                onClick={() => onSelect(obj.uid)}
                onTap={() => onSelect(obj.uid)}
                onDragEnd={(e) => handleDragEnd(obj, e.target)}
                onTransformEnd={(e) => handleTransformEnd(obj, e.target)}
              >
                {isCircle ? (
                  <Ellipse x={w / 2} y={h / 2} radiusX={w / 2} radiusY={h / 2} fill={obj.color ?? '#ffffff'} stroke={stroke} strokeWidth={warn ? 2 : 1.5} />
                ) : (
                  <Rect width={w} height={h} fill={obj.color ?? '#ffffff'} stroke={stroke} strokeWidth={warn ? 2 : 1.5} cornerRadius={3} />
                )}
                {view.zoom >= 0.6 && (
                  <Text
                    text={obj.object_name || ''}
                    width={Math.max(w, 60)}
                    x={isCircle ? -(Math.max(w, 60) - w) / 2 : 0}
                    y={h + 2}
                    align="center"
                    fontSize={11}
                    fill="#334155"
                    listening={false}
                  />
                )}
              </Group>
            )
          })}

          <Transformer
            ref={trRef}
            rotateEnabled
            keepRatio={false}
            boundBoxFunc={(oldBox, newBox) => (newBox.width < 8 || newBox.height < 8 ? oldBox : newBox)}
          />
        </Layer>
      </Stage>
    </div>
  )
})

export default DesignerCanvas
