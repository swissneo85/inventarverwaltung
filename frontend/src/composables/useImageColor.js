// Module-level cache: url -> rgb color string. Survives component re-mounts.
const colorCache = new Map()

/**
 * Extracts the average color of the border region of an image via Canvas.
 * Renders to 100×100 and samples only the outermost 1px strip at each edge,
 * so even a hairline original border dominates the average without nearby
 * darker pixels bleeding in. Falls back to '#f3f4f6'.
 */
export function extractImageColor(url) {
  if (colorCache.has(url)) return Promise.resolve(colorCache.get(url))

  return new Promise((resolve) => {
    const img = new Image()
    img.crossOrigin = 'anonymous'

    img.onload = () => {
      try {
        const W = 100, H = 100, B = 1
        const canvas = document.createElement('canvas')
        canvas.width = W
        canvas.height = H
        const ctx = canvas.getContext('2d')
        ctx.drawImage(img, 0, 0, W, H)
        const { data } = ctx.getImageData(0, 0, W, H)
        let r = 0, g = 0, b = 0, n = 0
        for (let y = 0; y < H; y++) {
          for (let x = 0; x < W; x++) {
            if (y < B || y >= H - B || x < B || x >= W - B) {
              const i = (y * W + x) * 4
              if (data[i + 3] > 64) { r += data[i]; g += data[i + 1]; b += data[i + 2]; n++ }
            }
          }
        }
        const color = n > 0
          ? `rgb(${Math.round(r / n)}, ${Math.round(g / n)}, ${Math.round(b / n)})`
          : '#f3f4f6'
        colorCache.set(url, color)
        resolve(color)
      } catch {
        colorCache.set(url, '#f3f4f6')
        resolve('#f3f4f6')
      }
    }

    img.onerror = () => {
      colorCache.set(url, '#f3f4f6')
      resolve('#f3f4f6')
    }

    img.src = url
  })
}
