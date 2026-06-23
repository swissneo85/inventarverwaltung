// Module-level cache: url -> rgb color string. Survives component re-mounts.
const colorCache = new Map()

/**
 * Extracts the average color of an image via a 20×20 Canvas sample.
 * Uses a dedicated img element with crossOrigin='anonymous' so it doesn't
 * taint any visible img element. Falls back to '#f3f4f6' on CORS/load errors.
 */
export function extractImageColor(url) {
  if (colorCache.has(url)) return Promise.resolve(colorCache.get(url))

  return new Promise((resolve) => {
    const img = new Image()
    img.crossOrigin = 'anonymous'

    img.onload = () => {
      try {
        const canvas = document.createElement('canvas')
        canvas.width = 20
        canvas.height = 20
        const ctx = canvas.getContext('2d')
        ctx.drawImage(img, 0, 0, 20, 20)
        const { data } = ctx.getImageData(0, 0, 20, 20)
        let r = 0, g = 0, b = 0, n = 0
        for (let i = 0; i < data.length; i += 4) {
          if (data[i + 3] > 64) { r += data[i]; g += data[i + 1]; b += data[i + 2]; n++ }
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
