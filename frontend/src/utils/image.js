/**
 * Draws an image source (video frame or decoded file) onto a canvas, scaled
 * down to maxDim on the longer side, and returns a compressed JPEG data URI.
 * Keeps camera/photo uploads small before they go over the wire to the
 * Vision API.
 */
export function drawToJpegDataUri(source, sourceWidth, sourceHeight, maxDim = 1024, quality = 0.82) {
  const scale = Math.min(1, maxDim / Math.max(sourceWidth, sourceHeight))
  const width = Math.round(sourceWidth * scale)
  const height = Math.round(sourceHeight * scale)

  const canvas = document.createElement('canvas')
  canvas.width = width
  canvas.height = height

  const ctx = canvas.getContext('2d')
  ctx.drawImage(source, 0, 0, width, height)

  return canvas.toDataURL('image/jpeg', quality)
}

export async function fileToJpegDataUri(file, maxDim = 1024, quality = 0.82) {
  const bitmap = await createImageBitmap(file)
  try {
    return drawToJpegDataUri(bitmap, bitmap.width, bitmap.height, maxDim, quality)
  } finally {
    bitmap.close?.()
  }
}
