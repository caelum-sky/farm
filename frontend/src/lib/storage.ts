// frontend/src/lib/storage.ts
import { getDownloadURL, ref, uploadBytesResumable } from 'firebase/storage';

import { auth, storage } from '@/firebase/config';

const MAX_BYTES = 5 * 1024 * 1024; // matches the limit in storage.rules
const ALLOWED = ['image/jpeg', 'image/png', 'image/webp'];

export interface UploadOptions {
  kind: 'products' | 'equipment';
  listingId?: string;
  onProgress?: (percent: number) => void;
}

/**
 * Sends one image to Storage under listings/{uid}/{kind}/{listingId}/ and
 * resolves to its download URL. The path is keyed on the signed-in uid because
 * storage.rules only permits writes inside a member's own folder.
 *
 * Validation is duplicated here and in the rules on purpose: this copy gives an
 * instant, readable message, the rules copy is the one that can't be bypassed.
 */
export async function uploadListingImage(file: File, options: UploadOptions): Promise<string> {
  if (!storage || !auth?.currentUser) {
    throw new Error('Image upload needs a signed-in account and Firebase storage.');
  }
  if (!ALLOWED.includes(file.type)) {
    throw new Error('Use a JPG, PNG, or WebP image.');
  }
  if (file.size > MAX_BYTES) {
    throw new Error('That image is over 5 MB. Compress it and try again.');
  }

  const uid = auth.currentUser.uid;
  const listingId = options.listingId || 'drafts';
  const safeName = file.name.replace(/[^a-zA-Z0-9.\-_]/g, '-');
  const path = `listings/${uid}/${options.kind}/${listingId}/${Date.now()}-${safeName}`;

  const task = uploadBytesResumable(ref(storage, path), file, { contentType: file.type });

  return new Promise((resolve, reject) => {
    task.on(
      'state_changed',
      (snapshot) => {
        if (options.onProgress && snapshot.totalBytes) {
          options.onProgress(Math.round((snapshot.bytesTransferred / snapshot.totalBytes) * 100));
        }
      },
      (error) => reject(new Error(error.message || 'Upload failed')),
      async () => resolve(await getDownloadURL(task.snapshot.ref))
    );
  });
}
