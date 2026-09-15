// frontend/src/components/ui/ImageUploader.tsx
import { useRef, useState, type ChangeEvent, type DragEvent } from 'react';
import { ImagePlus, X } from 'lucide-react';

import ErrorNote from '@/components/ui/ErrorNote';
import { uploadListingImage } from '@/lib/storage';

interface ImageUploaderProps {
  kind: 'products' | 'equipment';
  listingId?: string;
  value: string[];
  onChange: (urls: string[]) => void;
  max?: number;
}

/**
 * Drop zone plus thumbnail strip. Uploads go straight from the browser to
 * Firebase Storage — the API never handles the bytes, so a slow upload can't
 * tie up a request slot.
 */
export default function ImageUploader({
  kind,
  listingId,
  value,
  onChange,
  max = 4,
}: ImageUploaderProps) {
  const inputRef = useRef<HTMLInputElement | null>(null);
  const [progress, setProgress] = useState<number | null>(null);
  const [error, setError] = useState('');
  const [dragging, setDragging] = useState(false);

  const handleFiles = async (files: FileList | null) => {
    if (!files?.length) return;
    setError('');

    const room = max - value.length;
    if (room <= 0) {
      setError(`You can attach up to ${max} images.`);
      return;
    }

    const batch = Array.from(files).slice(0, room);
    const uploaded: string[] = [];

    try {
      for (const file of batch) {
        setProgress(0);
        uploaded.push(
          await uploadListingImage(file, { kind, listingId, onProgress: setProgress })
        );
      }
      onChange([...value, ...uploaded]);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Could not upload that image');
    } finally {
      setProgress(null);
      if (inputRef.current) inputRef.current.value = '';
    }
  };

  const onDrop = (event: DragEvent<HTMLDivElement>) => {
    event.preventDefault();
    setDragging(false);
    void handleFiles(event.dataTransfer.files);
  };

  return (
    <div className="space-y-3">
      <div
        onDragOver={(e) => {
          e.preventDefault();
          setDragging(true);
        }}
        onDragLeave={() => setDragging(false)}
        onDrop={onDrop}
        className={`rounded-2xl border border-dashed p-5 text-center transition duration-300 ease-grow ${
          dragging ? 'border-canopy bg-canopy/5' : 'border-soil/25 bg-husk2/50'
        }`}
      >
        <ImagePlus size={22} className="mx-auto text-canopy/60" />
        <p className="mt-2 text-sm text-soil/70">
          Drop photos here, or{' '}
          <button
            type="button"
            onClick={() => inputRef.current?.click()}
            className="link-underline font-medium"
          >
            choose files
          </button>
        </p>
        <p className="mt-1 text-xs text-soil/50">JPG, PNG, or WebP up to 5 MB. {max} max.</p>

        <input
          ref={inputRef}
          type="file"
          accept="image/jpeg,image/png,image/webp"
          multiple
          className="sr-only"
          onChange={(e: ChangeEvent<HTMLInputElement>) => handleFiles(e.target.files)}
        />
      </div>

      {progress !== null && (
        <div className="h-1.5 overflow-hidden rounded-full bg-soil/10">
          <div
            className="h-full rounded-full bg-canopy transition-all duration-200"
            style={{ width: `${progress}%` }}
          />
        </div>
      )}

      <ErrorNote message={error} />

      {value.length > 0 && (
        <ul className="grid grid-cols-4 gap-2">
          {value.map((url, index) => (
            <li key={url} className="relative aspect-square overflow-hidden rounded-xl bg-husk2">
              <img src={url} alt={`Upload ${index + 1}`} className="h-full w-full object-cover" />
              <button
                type="button"
                onClick={() => onChange(value.filter((item) => item !== url))}
                aria-label={`Remove image ${index + 1}`}
                className="absolute right-1 top-1 grid h-6 w-6 place-items-center rounded-full bg-soil/75 text-husk transition hover:bg-soil"
              >
                <X size={13} />
              </button>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
