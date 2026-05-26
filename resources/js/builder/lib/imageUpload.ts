export const MAX_IMAGE_BYTES = 200 * 1024;
export const IMAGE_ACCEPT = "image/*,.svg";

interface ImageFileLike {
  size: number;
  type: string;
  name: string;
}

function isSvgFile(file: ImageFileLike): boolean {
  return file.name.toLowerCase().endsWith(".svg");
}

export function imageFileError(file: ImageFileLike): string | null {
  if (!file.type.startsWith("image/") && !isSvgFile(file)) {
    return "Choose an image file.";
  }

  if (file.size > MAX_IMAGE_BYTES) {
    return "Images must be 200 KB or smaller.";
  }

  return null;
}

function bytesToBase64(bytes: Uint8Array): string {
  const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
  let out = "";

  for (let i = 0; i < bytes.length; i += 3) {
    const a = bytes[i];
    const b = bytes[i + 1];
    const c = bytes[i + 2];
    out += chars[a >> 2];
    out += chars[((a & 3) << 4) | ((b ?? 0) >> 4)];
    out += i + 1 < bytes.length ? chars[((b & 15) << 2) | ((c ?? 0) >> 6)] : "=";
    out += i + 2 < bytes.length ? chars[(c ?? 0) & 63] : "=";
  }

  return out;
}

export async function imageFileToDataUrl(file: File): Promise<string> {
  const bytes = new Uint8Array(await file.arrayBuffer());
  const type = file.type || (isSvgFile(file) ? "image/svg+xml" : "application/octet-stream");

  return `data:${type};base64,${bytesToBase64(bytes)}`;
}
